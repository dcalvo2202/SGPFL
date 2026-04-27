# Sincronización con Google Calendar - Guía de Implementación

## Descripción General
Este documento describe los pasos necesarios para sincronizar eventos de la aplicación (prorrogas, actas, deadlines, timeline de proyectos) con Google Calendar de los usuarios.

---

## PASO 1: Configurar Google Cloud Project y OAuth 2.0

### 1.1 Crear proyecto en Google Cloud Console
1. Ir a [Google Cloud Console](https://console.cloud.google.com/)
2. Crear nuevo proyecto: `Nombre: TFG Calendar Sync`
3. Esperar a que se complete la creación

### 1.2 Habilitar Google Calendar API
1. En el menú superior, buscar "APIs y servicios"
2. Hacer clic en "Habilitar APIs y servicios"
3. Buscar "Google Calendar API"
4. Hacer clic en "Habilitar"

### 1.3 Configurar pantalla de consentimiento
1. Ir a "OAuth consent screen"
2. Seleccionar "Usuario externo"
3. Llenar información:
   - **Nombre de la app:** TFG Calendar Sync
   - **Email de soporte:** tu@email.com
   - **Datos de desarrollador:** tu@email.com
4. En "Permisos", agregar:
   - `calendar` (acceso a Google Calendar)
   - `userinfo.profile`
   - `userinfo.email`

### 1.4 Crear credenciales OAuth 2.0
1. Ir a "Credenciales"
2. Hacer clic en "Crear credenciales" → "ID de cliente de OAuth"
3. Tipo: "Aplicación web"
4. Agregar URIs autorizados:
   ```
   http://localhost/base/auth/google_callback.php
   https://tudominio.com/base/auth/google_callback.php
   ```
5. Guardar el **Client ID** y **Client Secret**

---

## PASO 2: Instalar Google Client Library

### 2.1 Agregar dependencia con Composer
```bash
composer require google/apiclient
```

### 2.2 Agregar configuración en `config.inc`

Agregar la configuración de Google Calendar dentro del arreglo global ya centralizado en `config.inc`.

```php
$google_calendar_config = [
    'client_id' => getenv('GOOGLE_CLIENT_ID') ?: 'YOUR_CLIENT_ID.apps.googleusercontent.com',
    'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: 'YOUR_CLIENT_SECRET',
    'redirect_uri' => getenv('GOOGLE_REDIRECT_URI') ?: 'http://localhost/base/auth/google_callback.php',
    'scopes' => ['https://www.googleapis.com/auth/calendar'],
    'token_cipher' => 'aes-256-gcm',
    'token_cipher_key' => getenv('GOOGLE_TOKEN_CIPHER_KEY') ?: 'CAMBIAR_ESTA_CLAVE_EN_PRODUCCION',
];
```

**Importante:** `GOOGLE_TOKEN_CIPHER_KEY` debe resolverse a 32 bytes efectivos para AES-256. En la implementación del servicio se normaliza con `hash('sha256', $clave, true)` para obtener una clave binaria segura y de longitud fija.

---

## PASO 3: Crear Estructura de Base de Datos

### 3.1 Tabla para almacenar tokens de Google
**Archivo:** `sql/google_calendar_tokens.sql`

```sql
CREATE TABLE IF NOT EXISTS `google_calendar_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` varchar(50) NOT NULL,
  `access_token` longtext NOT NULL,
  `refresh_token` longtext,
  `token_expires_at` longtext NOT NULL,
  `calendar_id` varchar(255),
  `sync_enabled` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_token` (`id_user`),
  FOREIGN KEY (`id_user`) REFERENCES `sis_login`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

### 3.2 Tabla para mapeo de eventos
**Archivo:** `sql/google_calendar_sync_log.sql`

```sql
CREATE TABLE IF NOT EXISTS `google_calendar_sync_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` varchar(50) NOT NULL,
  `event_type` varchar(50) NOT NULL COMMENT 'prorroga, acta, deadline, timeline',
  `event_id` int(11) NOT NULL,
  `google_event_id` varchar(255),
  `sync_status` enum('pending', 'synced', 'failed') DEFAULT 'pending',
  `last_sync_at` datetime,
  `error_message` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_event_mapping` (`id_user`, `event_type`, `event_id`),
  KEY `idx_sync_status` (`sync_status`),
  FOREIGN KEY (`id_user`) REFERENCES `sis_login`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

---

## PASO 4: Crear Clase GoogleCalendarService

**Archivo:** `service/GoogleCalendarService.php`

```php
<?php

namespace Service;

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
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
        require_once __DIR__ . '/../config.inc';
        global $google_calendar_config;

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
        } catch (Exception $e) {
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
        $encryptedExpiresAt = $this->encryptValue($expiresAt);

        $query = "
            INSERT INTO google_calendar_tokens 
            (id_user, access_token, refresh_token, token_expires_at, sync_enabled)
            VALUES (?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE
            access_token = VALUES(access_token),
            refresh_token = COALESCE(VALUES(refresh_token), refresh_token),
            token_expires_at = VALUES(token_expires_at),
            updated_at = NOW()
        ";

        $stmt = $this->dbConnection->prepare($query);
        $stmt->bind_param('ssss', $userId, $encryptedAccessToken, $encryptedRefreshToken, $encryptedExpiresAt);
        
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
        $tokenExpiresAt = $this->decryptValue($row['token_expires_at']);
        
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
            $googleEvent->setSummary("Solicitud de Prórroga: " . $projectData['project_name']);
            
            $startDate = new DateTime($projectData['request_date']);
            $endDate = (new DateTime($projectData['request_date']))->add(new DateInterval('PT1H'));
            
            $googleEvent->setStart([
                'dateTime' => $startDate->format('c'),
                'timeZone' => 'America/Bogota'
            ]);
            
            $googleEvent->setEnd([
                'dateTime' => $endDate->format('c'),
                'timeZone' => 'America/Bogota'
            ]);

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
        } catch (Exception $e) {
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
            
            $googleEvent->setStart([
                'dateTime' => $startDate->format('c'),
                'timeZone' => 'America/Bogota'
            ]);
            
            $googleEvent->setEnd([
                'dateTime' => $endDate->format('c'),
                'timeZone' => 'America/Bogota'
            ]);

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
        } catch (Exception $e) {
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
            $reminderDate = (clone $deadline)->sub(new DateInterval('P1D'));
            
            $googleEvent->setStart([
                'date' => $deadline->format('Y-m-d'),
                'timeZone' => 'America/Bogota'
            ]);
            
            $googleEvent->setEnd([
                'date' => $deadline->format('Y-m-d'),
                'timeZone' => 'America/Bogota'
            ]);

            $description = sprintf(
                "Actividad: %s\nProyecto: %s\nFecha límite: %s",
                $deadlineData['description'],
                $deadlineData['project_name'] ?? 'General',
                $deadlineData['deadline_date']
            );
            
            $googleEvent->setDescription($description);
            $googleEvent->setColorId('9'); // Color rojo para deadlines

            // Agregar notificación
            $event_reminders = [
                [
                    'method' => 'notification',
                    'minutes' => 1440 // 24 horas antes
                ],
                [
                    'method' => 'popup',
                    'minutes' => 60 // 1 hora antes
                ]
            ];
            $googleEvent->setReminders(['useDefault' => false, 'overrides' => $event_reminders]);

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
        } catch (Exception $e) {
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
            
            $googleEvent->setStart([
                'date' => $eventDate->format('Y-m-d'),
                'timeZone' => 'America/Bogota'
            ]);
            
            $googleEvent->setEnd([
                'date' => $eventDate->format('Y-m-d'),
                'timeZone' => 'America/Bogota'
            ]);

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
        } catch (Exception $e) {
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
        } catch (Exception $e) {
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
```

---

## PASO 5: Crear Controladores de Autenticación

**Archivo:** `auth/google_auth.php`

```php
<?php
session_start();
require_once __DIR__ . '/../includes.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Service\GoogleCalendarService;

if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.php');
    exit;
}

$googleCalendarService = new GoogleCalendarService($conn);
$authUrl = $googleCalendarService->getAuthUrl();

header('Location: ' . $authUrl);
exit;
?>
```

**Archivo:** `auth/google_callback.php`

```php
<?php
session_start();
require_once __DIR__ . '/../includes.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Service\GoogleCalendarService;

if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.php');
    exit;
}

if (!isset($_GET['code'])) {
    $_SESSION['error'] = 'Error en la autenticación de Google Calendar';
    header('Location: ../perfil.php');
    exit;
}

$googleCalendarService = new GoogleCalendarService($conn);

if ($googleCalendarService->handleAuthCallback($_GET['code'], $_SESSION['id_user'])) {
    $_SESSION['success'] = 'Google Calendar conectado correctamente';
} else {
    $_SESSION['error'] = 'Error al conectar Google Calendar';
}

header('Location: ../perfil.php');
exit;
?>
```

---

## PASO 6: Modificar Archivos Existentes

### 6.1 Agregar en `panel_solicitudProrroga.php` (después de insertar en BD)

```php
// Sincronizar con Google Calendar si está habilitado
if (class_exists('Service\GoogleCalendarService')) {
    $googleCalendarService = new Service\GoogleCalendarService($conn);
    
    if ($googleCalendarService->isSyncEnabled($_SESSION['id_user'])) {
        $projectData = [
            'project_name' => $project_name,
            'student_names' => implode(', ', $student_names),
            'status' => $status,
            'request_date' => $request_date
        ];
        
        $googleCalendarService->syncExtensionRequest(
            $_SESSION['id_user'],
            $last_insert_id,
            $projectData
        );
    }
}
```

### 6.2 Agregar en `procesar_acta.php` (después de crear acta)

```php
// Sincronizar acta con Google Calendar
if (class_exists('Service\GoogleCalendarService')) {
    $googleCalendarService = new Service\GoogleCalendarService($conn);
    
    if ($googleCalendarService->isSyncEnabled($_SESSION['id_user'])) {
        $minutesData = [
            'project_name' => $project_name,
            'meeting_date' => $meeting_date,
            'attendees' => implode(', ', $attendees),
            'notes' => $notes ?? ''
        ];
        
        $googleCalendarService->syncProjectMinutes(
            $_SESSION['id_user'],
            $acta_id,
            $minutesData
        );
    }
}
```

### 6.3 Agregar en `cron_check_deadlines.php` (para sincronizar deadlines)

```php
// Sincronizar deadlines con Google Calendar
$query = "SELECT id_user FROM google_calendar_tokens WHERE sync_enabled = 1";
$result = $conn->query($query);

while ($row = $result->fetch_assoc()) {
    $userId = $row['id_user'];
    $googleCalendarService = new Service\GoogleCalendarService($conn);
    
    // Obtener deadlines pendientes del usuario
    $deadlineQuery = "SELECT * FROM deadline_alerts_sent WHERE id_user = ? AND synced = 0";
    $stmt = $conn->prepare($deadlineQuery);
    $stmt->bind_param('s', $userId);
    $stmt->execute();
    $deadlines = $stmt->get_result();
    
    while ($deadline = $deadlines->fetch_assoc()) {
        $deadlineData = [
            'description' => $deadline['description'],
            'project_name' => $deadline['project_name'],
            'deadline_date' => $deadline['deadline_date']
        ];
        
        if ($googleCalendarService->syncDeadline($userId, $deadline['id'], $deadlineData)) {
            // Marcar como sincronizado
            $updateQuery = "UPDATE deadline_alerts_sent SET synced = 1 WHERE id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param('i', $deadline['id']);
            $updateStmt->execute();
        }
    }
}
```

---

## PASO 7: Crear Interfaz en Perfil de Usuario

**Agregar en `perfil.php`:**

```php
<?php
// Verificar si el usuario tiene Google Calendar conectado
$query = "SELECT * FROM google_calendar_tokens WHERE id_user = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $_SESSION['id_user']);
$stmt->execute();
$googleCalendarConnected = $stmt->get_result()->num_rows > 0;
?>

<!-- Sección Google Calendar en el perfil -->
<div class="card mt-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="bi bi-calendar-check"></i> Sincronización con Google Calendar
        </h5>
    </div>
    <div class="card-body">
        <?php if ($googleCalendarConnected): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i> Google Calendar está conectado
            </div>
            <p class="text-muted">Los eventos de tu proyecto se sincronizan automáticamente con tu calendario de Google.</p>
            
            <form method="POST" action="../auth/disconnect_google.php" style="display: inline;">
                <button type="submit" class="btn btn-danger" onclick="return confirm('¿Desconectar Google Calendar?');">
                    <i class="bi bi-x-circle"></i> Desconectar Google Calendar
                </button>
            </form>
        <?php else: ?>
            <p class="text-muted">Conecta tu Google Calendar para sincronizar automáticamente los eventos de tu proyecto.</p>
            <a href="../auth/google_auth.php" class="btn btn-primary">
                <i class="bi bi-calendar-plus"></i> Conectar Google Calendar
            </a>
        <?php endif; ?>
    </div>
</div>
```

---

## PASO 8: Crear Script de Desconexión

**Archivo:** `auth/disconnect_google.php`

```php
<?php
session_start();
require_once __DIR__ . '/../includes.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Service\GoogleCalendarService;

if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.php');
    exit;
}

$googleCalendarService = new GoogleCalendarService($conn);
$googleCalendarService->disconnectUser($_SESSION['id_user']);

$_SESSION['success'] = 'Google Calendar desconectado correctamente';
header('Location: ../perfil.php');
exit;
?>
```

---

## PASO 9: Pruebas y Validación

### 9.1 Test de autenticación
1. Ir a perfil del usuario
2. Hacer clic en "Conectar Google Calendar"
3. Autorizar la aplicación
4. Verificar que se guarden los tokens en BD
5. Confirmar que `access_token`, `refresh_token` y `token_expires_at` no queden legibles en texto plano en la base de datos

### 9.2 Test de sincronización de prorrogas
1. Crear una nueva solicitud de prórroga
2. Verificar que aparezca en Google Calendar
3. Modificar la prórroga
4. Verificar que se actualice en Google Calendar

### 9.3 Test de sincronización de actas
1. Crear un acta
2. Verificar sincronización
3. Modificar datos del acta
4. Confirmar actualización

### 9.4 Test de deadlines
1. Ejecutar cron de deadlines
2. Verificar que aparezcan en Google Calendar
3. Validar notificaciones

---

## PASO 10: Configuración de Producción

### 10.1 Configurar variables de entorno
Definir estas variables en el entorno del servidor web o cargarlas desde un archivo `.env` usando una librería como `vlucas/phpdotenv` antes de ejecutar el flujo de autenticación:
```
GOOGLE_CLIENT_ID=tu_client_id
GOOGLE_CLIENT_SECRET=tu_client_secret
GOOGLE_REDIRECT_URI=https://tudominio.com/base/auth/google_callback.php
GOOGLE_TOKEN_CIPHER_KEY=una_clave_de_32_bytes_super_segura
```

### 10.2 Leer variables desde `config.inc`
```php
$google_calendar_config = [
    'client_id' => getenv('GOOGLE_CLIENT_ID') ?: 'YOUR_CLIENT_ID.apps.googleusercontent.com',
    'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: 'YOUR_CLIENT_SECRET',
    'redirect_uri' => getenv('GOOGLE_REDIRECT_URI') ?: 'http://localhost/base/auth/google_callback.php',
    'scopes' => ['https://www.googleapis.com/auth/calendar'],
    'token_cipher' => 'aes-256-gcm',
    'token_cipher_key' => getenv('GOOGLE_TOKEN_CIPHER_KEY') ?: 'CAMBIAR_ESTA_CLAVE_EN_PRODUCCION',
];
```

### 10.3 Ejecutar migraciones de BD
```bash
mysql -u root base_db < sql/google_calendar_tokens.sql
mysql -u root base_db < sql/google_calendar_sync_log.sql
```

---

## Flujo de Sincronización

```
┌─────────────────────────────────────────────────────────┐
│  Usuario crea evento (Prorroga, Acta, Deadline, etc)    │
└──────────────────────┬──────────────────────────────────┘
                       │
                       ▼
        ┌──────────────────────────────┐
        │ ¿Usuario tiene Google        │
        │ Calendar habilitado?         │
        └──────────────┬───────────────┘
                   Yes│ No
                       │ └─→ (No se sincroniza)
                       │
                       ▼
        ┌──────────────────────────────┐
        │ Crear/Actualizar evento      │
        │ en Google Calendar           │
        └──────────────┬───────────────┘
                       │
                       ▼
        ┌──────────────────────────────┐
        │ Registrar en log de sync     │
        │ (google_calendar_sync_log)   │
        └──────────────┬───────────────┘
                       │
                       ▼
        ┌──────────────────────────────┐
        │ Notificar al usuario         │
        │ (Sincronizado exitosamente)  │
        └──────────────────────────────┘
```

---

## Resolución de Problemas

### Error: "Token expirado"
- **Causa:** El refresh token es inválido
- **Solución:** Desconectar y reconectar Google Calendar

### Error: "Acceso denegado"
- **Causa:** Cambio de permisos en Google
- **Solución:** Volver a autorizar en Google Cloud Console

### Los eventos no aparecen
- **Verificar:**
  1. Que `sync_enabled = 1` en `google_calendar_tokens`
  2. El estado en `google_calendar_sync_log`
  3. Logs en `error_log` del servidor

### Sincronización lenta
- **Optimizar:**
  1. Hacer batch sync de eventos
  2. Usar cron jobs en lugar de sync en tiempo real
  3. Implementar cola de sincronización

---

## Consideraciones de Seguridad

1. **Nunca** guardar tokens en localStorage o cookies
2. **Siempre** usar HTTPS en producción
3. **Encriptar** `access_token`, `refresh_token` y `token_expires_at` en la capa PHP con `openssl_encrypt` usando `AES-256-GCM`
4. **Auditar** acceso a tokens
5. **Guardar** `GOOGLE_TOKEN_CIPHER_KEY` fuera del repositorio y rotarla de forma controlada

---

## Resumen de Archivos a Crear

```
├── service/
│   └── GoogleCalendarService.php       (Servicio principal)
├── auth/
│   ├── google_auth.php                 (Iniciar autenticación)
│   ├── google_callback.php             (Callback de OAuth)
│   └── disconnect_google.php           (Desconectar)
├── sql/
│   ├── google_calendar_tokens.sql      (Tabla de tokens)
│   └── google_calendar_sync_log.sql    (Log de sincronización)
├── config.inc                          (Configuración centralizada)
└── GOOGLE_CALENDAR_SYNC.md             (Este archivo)
```

---

## Próximos Pasos

1. ✅ Completar Paso 1-5
2. ✅ Crear archivos de configuración
3. ✅ Instalar dependencias
4. ✅ Crear tablas en BD
5. ✅ Integrar en archivos existentes
6. ✅ Crear interfaz de usuario
7. ✅ Realizar pruebas
8. ✅ Desplegar en producción

