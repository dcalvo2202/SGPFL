<?php

namespace Service;

require_once __DIR__ . '/../vendor/autoload.php';

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Calendar\EventReminders;
use Google\Service\Calendar\EventReminder;
use DateTime;
use DateInterval;
use Exception;

class GoogleCalendarService
{
    private $client;
    private $calendarService;
    private $dbConnection;
    private $googleCalendarConfig;

    public function __construct($dbConnection)
    {
        $this->dbConnection = $dbConnection;
        $this->initializeClient();
    }

    /**
     * Inicializar cliente de Google
     */
    private function initializeClient()
    {
        global $google_calendar_config;

        // config.inc can be required_once'd at global scope before this runs;
        // using 'global' ensures we access that already-loaded variable.
        // If not loaded yet (e.g. standalone use), load it now.
        if (!isset($google_calendar_config) || !is_array($google_calendar_config)) {
            require_once __DIR__ . '/../config.inc';
        }

        if (!isset($google_calendar_config) || !is_array($google_calendar_config)) {
            throw new Exception('No se encontró la configuración de Google Calendar en config.inc');
        }

        $this->googleCalendarConfig = $google_calendar_config;
        
        $this->client = new Client();
        $this->client->setClientId($this->googleCalendarConfig['client_id']);
        $this->client->setClientSecret($this->googleCalendarConfig['client_secret']);
        $this->client->setRedirectUri($this->googleCalendarConfig['redirect_uri']);
        $this->client->addScope($this->googleCalendarConfig['scopes']);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
        
        $this->calendarService = new Calendar($this->client);
    }

    /**
     * Obtener clave de cifrado normalizada a 32 bytes
     */
    private function getEncryptionKey()
    {
        return hash('sha256', $this->googleCalendarConfig['token_cipher_key'], true);
    }

    /**
     * Cifrar un valor usando OpenSSL y AES-256-GCM
     */
    private function encryptValue($plainText)
    {
        if ($plainText === null || $plainText === '') {
            return null;
        }

        $cipher = $this->googleCalendarConfig['token_cipher'] ?? 'aes-256-gcm';
        $ivLength = openssl_cipher_iv_length($cipher);
        $iv = random_bytes($ivLength);
        $tag = '';

        $cipherText = openssl_encrypt(
            (string) $plainText,
            $cipher,
            $this->getEncryptionKey(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($cipherText === false) {
            throw new Exception('No se pudo cifrar el valor del token');
        }

        return json_encode([
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'data' => base64_encode($cipherText),
        ]);
    }

    /**
     * Descifrar un valor previamente protegido con OpenSSL
     */
    private function decryptValue($encryptedPayload)
    {
        if ($encryptedPayload === null || $encryptedPayload === '') {
            return null;
        }

        $payload = json_decode($encryptedPayload, true);

        if (!is_array($payload) || !isset($payload['iv'], $payload['tag'], $payload['data'])) {
            throw new Exception('El payload cifrado es inválido');
        }

        $plainText = openssl_decrypt(
            base64_decode($payload['data']),
            $this->googleCalendarConfig['token_cipher'] ?? 'aes-256-gcm',
            $this->getEncryptionKey(),
            OPENSSL_RAW_DATA,
            base64_decode($payload['iv']),
            base64_decode($payload['tag'])
        );

        if ($plainText === false) {
            throw new Exception('No se pudo descifrar el valor del token');
        }

        return $plainText;
    }

    /**
     * Obtener URL de autenticación
     */
    public function getAuthUrl()
    {
        return $this->client->createAuthUrl();
    }

    /**
     * Manejar callback de autenticación
     */
    public function handleAuthCallback($authCode, $userId)
    {
        try {
            $accessToken = $this->client->fetchAccessTokenWithAuthCode($authCode);
            
            if (isset($accessToken['error'])) {
                throw new Exception('Error al obtener token: ' . $accessToken['error']);
            }

            // Guardar token en BD
            $this->saveTokens($userId, $accessToken);
            
            return true;
        } catch (\Throwable $e) {
            error_log('GoogleCalendarService::handleAuthCallback Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Guardar tokens en base de datos
     */
    private function saveTokens($userId, $accessToken)
    {
        $refreshToken = $accessToken['refresh_token'] ?? null;
        $expiresAt = date('Y-m-d H:i:s', time() + $accessToken['expires_in']);
        $encryptedAccessToken = $this->encryptValue(json_encode($accessToken));
        $encryptedRefreshToken = $this->encryptValue($refreshToken);

        $query = "
            INSERT INTO google_calendar_tokens 
            (id_user, access_token, refresh_token, token_expires_at, sync_enabled)
            VALUES (?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE
            access_token = VALUES(access_token),
            refresh_token = COALESCE(VALUES(refresh_token), refresh_token),
            token_expires_at = VALUES(token_expires_at),
            sync_enabled = 1,
            updated_at = NOW()
        ";

        $stmt = $this->dbConnection->prepare($query);
        $stmt->bind_param('ssss', $userId, $encryptedAccessToken, $encryptedRefreshToken, $expiresAt);
        
        if (!$stmt->execute()) {
            throw new Exception('Error al guardar tokens: ' . $stmt->error);
        }
    }

    /**
     * Obtener y refrescar token si es necesario
     */
    private function getValidAccessToken($userId)
    {
        $query = "SELECT access_token, refresh_token, token_expires_at FROM google_calendar_tokens WHERE id_user = ?";
        $stmt = $this->dbConnection->prepare($query);
        $stmt->bind_param('s', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('No hay token de Google para este usuario');
        }

        $row = $result->fetch_assoc();
        $accessToken = json_decode($this->decryptValue($row['access_token']), true);
        $refreshToken = $this->decryptValue($row['refresh_token']);
        $tokenExpiresAt = $row['token_expires_at']; // datetime en BD, sin cifrar
        
        // Verificar si el token está expirado
        if (strtotime($tokenExpiresAt) < time()) {
            if ($refreshToken) {
                // Refrescar token
                $this->client->setAccessToken($accessToken);
                $refreshedToken = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                
                if (!isset($refreshedToken['error'])) {
                    $this->saveTokens($userId, $refreshedToken);
                    $accessToken = $refreshedToken;
                }
            }
        }

        $this->client->setAccessToken($accessToken);
        return $accessToken;
    }

    /**
     * Sincronizar evento de PRORROGA
     */
    public function syncExtensionRequest($userId, $eventId, $projectData)
    {
        try {
            $this->getValidAccessToken($userId);

            $googleEvent = new Event();
            $googleEvent->setSummary("Evento Proyecto : " . $projectData['project_name']);
            
            $startDate = new DateTime($projectData['request_date']);
            $endDate = (new DateTime($projectData['request_date']))->add(new DateInterval('PT1H'));
            
            $startDt = new EventDateTime();
            $startDt->setDateTime($startDate->format(DateTime::RFC3339));
            $startDt->setTimeZone('America/Costa_Rica');
            $googleEvent->setStart($startDt);

            $endDt = new EventDateTime();
            $endDt->setDateTime($endDate->format(DateTime::RFC3339));
            $endDt->setTimeZone('America/Costa_Rica');
            $googleEvent->setEnd($endDt);

            $description = sprintf(
                "Proyecto: %s\nEstudiantes: %s\nEstado: %s\nFecha de solicitud: %s",
                $projectData['project_name'],
                $projectData['student_names'],
                $projectData['status'],
                $projectData['request_date']
            );
            
            $googleEvent->setDescription($description);
            $googleEvent->setColorId('3'); // Color naranja para prorrogas

            // Verificar si el evento ya existe
            $existingEventId = $this->getExistingEventId($userId, 'prorroga', $eventId);
            
            if ($existingEventId) {
                // Actualizar evento
                $this->calendarService->events->update('primary', $existingEventId, $googleEvent);
                $syncedEventId = $existingEventId;
            } else {
                // Crear evento nuevo
                $createdEvent = $this->calendarService->events->insert('primary', $googleEvent);
                $syncedEventId = $createdEvent->getId();
            }

            // Registrar en log de sincronización
            $this->logSync($userId, 'prorroga', $eventId, $syncedEventId, 'synced');
            
            return true;
        } catch (\Throwable $e) {
            error_log('GoogleCalendarService::syncExtensionRequest Error: ' . $e->getMessage());
            $this->logSync($userId, 'prorroga', $eventId, null, 'failed', $e->getMessage());
            return false;
        }
    }

    /**
     * Sincronizar evento de ACTA
     */
    public function syncProjectMinutes($userId, $eventId, $minutesData)
    {
        try {
            $this->getValidAccessToken($userId);

            $googleEvent = new Event();
            $googleEvent->setSummary("Acta: " . $minutesData['project_name']);
            
            $startDate = new DateTime($minutesData['meeting_date']);
            $endDate = (new DateTime($minutesData['meeting_date']))->add(new DateInterval('PT2H'));
            
            $startDt = new EventDateTime();
            $startDt->setDateTime($startDate->format(DateTime::RFC3339));
            $startDt->setTimeZone('America/Costa_Rica');
            $googleEvent->setStart($startDt);

            $endDt = new EventDateTime();
            $endDt->setDateTime($endDate->format(DateTime::RFC3339));
            $endDt->setTimeZone('America/Costa_Rica');
            $googleEvent->setEnd($endDt);

            $description = sprintf(
                "Proyecto: %s\nAsistentes: %s\nNotas: %s",
                $minutesData['project_name'],
                $minutesData['attendees'],
                $minutesData['notes'] ?? 'No hay notas'
            );
            
            $googleEvent->setDescription($description);
            $googleEvent->setColorId('2'); // Color azul para actas

            $existingEventId = $this->getExistingEventId($userId, 'acta', $eventId);
            
            if ($existingEventId) {
                $this->calendarService->events->update('primary', $existingEventId, $googleEvent);
                $syncedEventId = $existingEventId;
            } else {
                $createdEvent = $this->calendarService->events->insert('primary', $googleEvent);
                $syncedEventId = $createdEvent->getId();
            }

            $this->logSync($userId, 'acta', $eventId, $syncedEventId, 'synced');
            return true;
        } catch (\Throwable $e) {
            error_log('GoogleCalendarService::syncProjectMinutes Error: ' . $e->getMessage());
            $this->logSync($userId, 'acta', $eventId, null, 'failed', $e->getMessage());
            return false;
        }
    }

    /**
     * Sincronizar DEADLINE
     */
    public function syncDeadline($userId, $eventId, $deadlineData)
    {
        try {
            $this->getValidAccessToken($userId);

            $googleEvent = new Event();
            $googleEvent->setSummary("Deadline: " . $deadlineData['description']);
            
            $deadline = new DateTime($deadlineData['deadline_date']);

            $startDt = new EventDateTime();
            $startDt->setDate($deadline->format('Y-m-d'));
            $googleEvent->setStart($startDt);

            $endDt = new EventDateTime();
            $endDt->setDate($deadline->format('Y-m-d'));
            $googleEvent->setEnd($endDt);

            $description = sprintf(
                "Actividad: %s\nProyecto: %s\nFecha límite: %s",
                $deadlineData['description'],
                $deadlineData['project_name'] ?? 'General',
                $deadlineData['deadline_date']
            );
            
            $googleEvent->setDescription($description);
            $googleEvent->setColorId('9'); // Color rojo para deadlines

            // Agregar notificaciones
            $reminder1 = new EventReminder();
            $reminder1->setMethod('notification');
            $reminder1->setMinutes(1440); // 24 horas antes

            $reminder2 = new EventReminder();
            $reminder2->setMethod('popup');
            $reminder2->setMinutes(60); // 1 hora antes

            $reminders = new EventReminders();
            $reminders->setUseDefault(false);
            $reminders->setOverrides([$reminder1, $reminder2]);
            $googleEvent->setReminders($reminders);

            $existingEventId = $this->getExistingEventId($userId, 'deadline', $eventId);
            
            if ($existingEventId) {
                $this->calendarService->events->update('primary', $existingEventId, $googleEvent);
                $syncedEventId = $existingEventId;
            } else {
                $createdEvent = $this->calendarService->events->insert('primary', $googleEvent);
                $syncedEventId = $createdEvent->getId();
            }

            $this->logSync($userId, 'deadline', $eventId, $syncedEventId, 'synced');
            return true;
        } catch (\Throwable $e) {
            error_log('GoogleCalendarService::syncDeadline Error: ' . $e->getMessage());
            $this->logSync($userId, 'deadline', $eventId, null, 'failed', $e->getMessage());
            return false;
        }
    }

    /**
     * Sincronizar evento de TIMELINE DE PROYECTO
     */
    public function syncProjectTimeline($userId, $eventId, $timelineData)
    {
        try {
            $this->getValidAccessToken($userId);

            $googleEvent = new Event();
            $googleEvent->setSummary("Hito: " . $timelineData['milestone_name']);
            
            $eventDate = new DateTime($timelineData['target_date']);

            $startDt = new EventDateTime();
            $startDt->setDate($eventDate->format('Y-m-d'));
            $googleEvent->setStart($startDt);

            $endDt = new EventDateTime();
            $endDt->setDate($eventDate->format('Y-m-d'));
            $googleEvent->setEnd($endDt);

            $description = sprintf(
                "Proyecto: %s\nDescripción: %s\nEstado: %s",
                $timelineData['project_name'],
                $timelineData['description'] ?? '',
                $timelineData['status'] ?? 'Pendiente'
            );
            
            $googleEvent->setDescription($description);
            $googleEvent->setColorId('5'); // Color verde para timeline

            $existingEventId = $this->getExistingEventId($userId, 'timeline', $eventId);
            
            if ($existingEventId) {
                $this->calendarService->events->update('primary', $existingEventId, $googleEvent);
                $syncedEventId = $existingEventId;
            } else {
                $createdEvent = $this->calendarService->events->insert('primary', $googleEvent);
                $syncedEventId = $createdEvent->getId();
            }

            $this->logSync($userId, 'timeline', $eventId, $syncedEventId, 'synced');
            return true;
        } catch (\Throwable $e) {
            error_log('GoogleCalendarService::syncProjectTimeline Error: ' . $e->getMessage());
            $this->logSync($userId, 'timeline', $eventId, null, 'failed', $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar evento de Google Calendar
     */
    public function deleteEvent($userId, $eventType, $eventId)
    {
        try {
            $this->getValidAccessToken($userId);

            $existingEventId = $this->getExistingEventId($userId, $eventType, $eventId);
            
            if ($existingEventId) {
                $this->calendarService->events->delete('primary', $existingEventId);
                
                // Actualizar log
                $query = "DELETE FROM google_calendar_sync_log WHERE id_user = ? AND event_type = ? AND event_id = ?";
                $stmt = $this->dbConnection->prepare($query);
                $stmt->bind_param('ssi', $userId, $eventType, $eventId);
                $stmt->execute();
            }

            return true;
        } catch (\Throwable $e) {
            error_log('GoogleCalendarService::deleteEvent Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener ID del evento en Google si ya existe
     */
    private function getExistingEventId($userId, $eventType, $eventId)
    {
        $query = "SELECT google_event_id FROM google_calendar_sync_log 
                  WHERE id_user = ? AND event_type = ? AND event_id = ? 
                  AND google_event_id IS NOT NULL";
        $stmt = $this->dbConnection->prepare($query);
        $stmt->bind_param('ssi', $userId, $eventType, $eventId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['google_event_id'];
        }

        return null;
    }

    /**
     * Registrar sincronización en log
     */
    private function logSync($userId, $eventType, $eventId, $googleEventId, $status, $errorMessage = null)
    {
        $query = "
            INSERT INTO google_calendar_sync_log 
            (id_user, event_type, event_id, google_event_id, sync_status, last_sync_at, error_message)
            VALUES (?, ?, ?, ?, ?, NOW(), ?)
            ON DUPLICATE KEY UPDATE
            google_event_id = VALUES(google_event_id),
            sync_status = VALUES(sync_status),
            last_sync_at = NOW(),
            error_message = VALUES(error_message)
        ";

        $stmt = $this->dbConnection->prepare($query);
        $stmt->bind_param('ssisss', $userId, $eventType, $eventId, $googleEventId, $status, $errorMessage);
        $stmt->execute();
    }

    /**
     * Verificar si el usuario tiene sincronización habilitada
     */
    public function isSyncEnabled($userId)
    {
        $query = "SELECT sync_enabled FROM google_calendar_tokens WHERE id_user = ?";
        $stmt = $this->dbConnection->prepare($query);
        $stmt->bind_param('s', $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return (bool) $row['sync_enabled'];
        }

        return false;
    }

    /**
     * Desconectar Google Calendar
     */
    public function disconnectUser($userId)
    {
        $query = "UPDATE google_calendar_tokens SET sync_enabled = 0 WHERE id_user = ?";
        $stmt = $this->dbConnection->prepare($query);
        $stmt->bind_param('s', $userId);
        return $stmt->execute();
    }
}
