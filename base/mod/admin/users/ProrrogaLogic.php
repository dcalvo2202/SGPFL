<?php
require_once __DIR__ . "/../../../inc/db/db.php";
require_once __DIR__ . '/../../../vendor/autoload.php';

class ProrrogaLogic {
    private $conn;

    public function __construct() {
        global $db_host, $usuario, $clave, $db;
        $this->conn = new mysqli($db_host, $usuario, $clave, $db);
        if ($this->conn->connect_error) {
            throw new Exception("Error de conexión: " . $this->conn->connect_error);
        }
        $this->conn->set_charset("utf8");
    }

    /**
     * Crear una nueva solicitud de prórroga
     * @param int $proposal_id ID del proyecto
     * @param string $user_id ID del usuario
     * @param int $extension_number Número de prórroga (1 o 2)
     * @param string $reason Motivo de la solicitud
     * @param array $archivos Archivos subidos ($_FILES['documento_prorroga'])
     * @return array ['success' => bool, 'message' => string, 'id' => int|null]
     */
    public function crearSolicitud($proposal_id, $user_id, $extension_number, $reason, $archivos = []) {
        // Validar que no tenga más de 2 prórrogas aprobadas
        $aprobadas = $this->contarProrrogasAprobadas($proposal_id);
        if ($aprobadas >= 2) {
            return [
                'success' => false,
                'message' => 'Ya ha utilizado sus 2 prórrogas permitidas.',
                'id' => null
            ];
        }

        // Validar que no tenga solicitud pendiente
        if ($this->tieneSolicitudPendiente($proposal_id)) {
            return [
                'success' => false,
                'message' => 'Ya tiene una solicitud de prórroga pendiente.',
                'id' => null
            ];
        }

        // Validar número de prórroga
        if ($extension_number < 1 || $extension_number > 2) {
            return [
                'success' => false,
                'message' => 'Número de prórroga inválido.',
                'id' => null
            ];
        }

        // Procesar archivos si existen
        $documento_path = null;
        if (!empty($archivos) && isset($archivos['name']) && !empty($archivos['name'][0])) {
            $resultado_archivos = $this->guardarArchivos($archivos, $proposal_id, $user_id);
            if (!$resultado_archivos['success']) {
                return $resultado_archivos;
            }
            $documento_path = json_encode($resultado_archivos['paths']);
        }

        // Insertar solicitud
        $sql = "INSERT INTO tfg_extension_requests 
                (proposal_id, user_id, extension_number, reason, status, request_date, documento_path) 
                VALUES (?, ?, ?, ?, 'pendiente', NOW(), ?)";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [
                'success' => false,
                'message' => 'Error al preparar la consulta: ' . $this->conn->error,
                'id' => null
            ];
        }

        $stmt->bind_param("isiss", $proposal_id, $user_id, $extension_number, $reason, $documento_path);
        
        if ($stmt->execute()) {
            $insert_id = $this->conn->insert_id;
            $stmt->close();

            return [
                'success' => true,
                'message' => 'Solicitud de prórroga enviada correctamente.',
                'id' => $insert_id
            ];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return [
                'success' => false,
                'message' => 'Error al guardar la solicitud: ' . $error,
                'id' => null
            ];
        }
    }

    private function detectarMimeReal($tmp_name) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        return $finfo->file($tmp_name) ?: '';
    }

    private function mensajeErrorSubida($error_code) {
        switch ((int)$error_code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'El archivo excede el tamaño máximo permitido por el servidor.';
            case UPLOAD_ERR_PARTIAL:
                return 'El archivo se subió parcialmente. Intente nuevamente.';
            case UPLOAD_ERR_NO_FILE:
                return 'No se seleccionó ningún archivo.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'No existe carpeta temporal para procesar la subida.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'No se pudo escribir el archivo en el servidor.';
            case UPLOAD_ERR_EXTENSION:
                return 'Una extensión de PHP bloqueó la subida del archivo.';
            default:
                return 'Error desconocido al subir el archivo.';
        }
    }

    /**
     * Guardar archivos de soporte de prórroga
     * @param array $archivos $_FILES array
     * @param int $proposal_id
     * @param string $user_id
     * @return array ['success' => bool, 'message' => string, 'paths' => array]
     */
    
    private function guardarArchivos($archivos, $proposal_id, $user_id) {
        $base_path = realpath(__DIR__ . '/../../../');
        $upload_dir = $base_path . '/uploads/prorrogas/' . $proposal_id . '/';
        
        // Crear directorio si no existe
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                return [
                    'success' => false,
                    'message' => 'Error al crear el directorio de uploads.',
                    'paths' => []
                ];
            }
        }

        $paths = [];
        $total_files = count($archivos['name']);
        
        for ($i = 0; $i < $total_files; $i++) {
            $nombre_original = $archivos['name'][$i] ?? '';

            if ($nombre_original === '') {
                continue;
            }

            $error_code = (int)($archivos['error'][$i] ?? UPLOAD_ERR_NO_FILE);

            if ($error_code !== UPLOAD_ERR_OK) {
                return [
                    'success' => false,
                    'message' => $this->mensajeErrorSubida($error_code),
                    'paths' => []
                ];
            }

            $tmp_name = $archivos['tmp_name'][$i] ?? '';
            $size = (int)($archivos['size'][$i] ?? 0);

            if ($tmp_name === '' || !is_uploaded_file($tmp_name)) {
                return [
                    'success' => false,
                    'message' => 'El archivo "' . $nombre_original . '" no fue recibido correctamente.',
                    'paths' => []
                ];
            }

            if (!is_readable($tmp_name)) {
                return [
                    'success' => false,
                    'message' => 'No se pudo leer el archivo "' . $nombre_original . '".',
                    'paths' => []
                ];
            }

            if ($size <= 0) {
                return [
                    'success' => false,
                    'message' => 'El archivo "' . $nombre_original . '" está vacío o no es válido.',
                    'paths' => []
                ];
            }

            if ($size > 20 * 1024 * 1024) {
                return [
                    'success' => false,
                    'message' => 'El archivo "' . $nombre_original . '" excede el tamaño máximo de 20 MB.',
                    'paths' => []
                ];
            }

            $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));

            if ($extension !== 'pdf') {
                return [
                    'success' => false,
                    'message' => 'El archivo "' . $nombre_original . '" debe tener extensión PDF.',
                    'paths' => []
                ];
            }

            $mime_type = $this->detectarMimeReal($tmp_name);

            if ($mime_type !== 'application/pdf') {
                return [
                    'success' => false,
                    'message' => 'El archivo "' . $nombre_original . '" no es un PDF válido. Tipo detectado: ' . $mime_type,
                    'paths' => []
                ];
            }

            // Generar nombre único
            $extension = 'pdf';
            $nombre_seguro = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($nombre_original, PATHINFO_FILENAME));
            $nombre_final = $nombre_seguro . '_' . date('Ymd_His') . '_' . uniqid() . '.' . $extension;
            $ruta_destino = $upload_dir . $nombre_final;

            // Mover archivo
            if (move_uploaded_file($tmp_name, $ruta_destino)) {
                // Guardar ruta relativa
                $paths[] = 'uploads/prorrogas/' . $proposal_id . '/' . $nombre_final;
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al guardar el archivo "' . $nombre_original . '".',
                    'paths' => []
                ];
            }
        }

        return [
            'success' => true,
            'message' => 'Archivos guardados correctamente.',
            'paths' => $paths
        ];
    }

    /**
     * Contar prórrogas aprobadas de un proyecto
     * @param int $proposal_id
     * @return int
     */
    public function contarProrrogasAprobadas($proposal_id) {
        $sql = "SELECT COUNT(*) as total FROM tfg_extension_requests 
                WHERE proposal_id = ? AND status = 'aprobada'";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return 0;
        
        $stmt->bind_param("i", $proposal_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return (int)($row['total'] ?? 0);
    }

    /**
     * Verificar si tiene solicitud pendiente
     * @param int $proposal_id
     * @return bool
     */
    public function tieneSolicitudPendiente($proposal_id) {
        $sql = "SELECT id FROM tfg_extension_requests 
                WHERE proposal_id = ? AND status = 'pendiente' LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;
        
        $stmt->bind_param("i", $proposal_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $tiene = ($result->num_rows > 0);
        $stmt->close();
        
        return $tiene;
    }

    /**
     * Obtener solicitudes pendientes (para panel de gestión)
     * @return array
     */
    public function obtenerSolicitudesPendientes() {
        $sql = "SELECT er.*, tp.title as proyecto_titulo, tp.user_id as estudiante_id
                FROM tfg_extension_requests er
                JOIN tfg_proposals tp ON er.proposal_id = tp.id
                WHERE er.status = 'pendiente'
                ORDER BY er.request_date ASC";
        
        $result = $this->conn->query($sql);
        $solicitudes = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $solicitudes[] = $row;
            }
        }
        
        return $solicitudes;
    }

    /**
     * Aprobar o rechazar una solicitud
     * @param int $request_id ID de la solicitud
     * @param string $status 'aprobada' o 'rechazada'
     * @param string $responded_by ID del usuario que responde
     * @param string $comment Comentario de respuesta
     * @return array ['success' => bool, 'message' => string]
     */
    public function responderSolicitud($request_id, $status, $responded_by, $comment = '') {
        if (!in_array($status, ['aprobada', 'rechazada'])) {
            return ['success' => false, 'message' => 'Estado inválido.'];
        }

        $requestData = $this->getPendingRequestData($request_id);
        if (!$requestData) {
            return ['success' => false, 'message' => 'No se encontró una solicitud pendiente para procesar.'];
        }

        // Establecer usuario para trigger de auditoría
        $this->conn->query("SET @current_user_id = '" . $this->conn->real_escape_string($responded_by) . "'");

        $sql = "UPDATE tfg_extension_requests 
                SET status = ?, response_date = NOW(), responded_by = ?, response_comment = ?
                WHERE id = ? AND status = 'pendiente'";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Error al preparar consulta.'];
        }

        $stmt->bind_param("sssi", $status, $responded_by, $comment, $request_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $stmt->close();

            $message = 'Solicitud ' . $status . ' correctamente.';

            // Si se aprobó, actualizar tfg_project_timeline y disparar sincronización.
            if ($status === 'aprobada') {
                $this->actualizarTimeline($request_id);
                $syncResult = $this->syncApprovedExtensionRequest($request_id, $requestData);

                if (($syncResult['status'] ?? '') === 'synced') {
                    $message .= ' Evento de Google Calendar sincronizado.';
                } elseif (($syncResult['status'] ?? '') === 'failed') {
                    $message .= ' Google Calendar: ' . ($syncResult['message'] ?? 'No se pudo sincronizar.');
                }
            }
            
            return [
                'success' => true,
                'message' => $message
            ];
        }
        
        $stmt->close();
        return ['success' => false, 'message' => 'No se pudo actualizar la solicitud.'];
    }

    /**
     * Actualizar timeline del proyecto cuando se aprueba prórroga
     * @param int $request_id
     */
    private function actualizarTimeline($request_id) {
        // Obtener datos de la solicitud
        $sql = "SELECT proposal_id, extension_number FROM tfg_extension_requests WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $solicitud = $result->fetch_assoc();
        $stmt->close();

        if (!$solicitud) return;

        // Calcular días a agregar: 1ra prórroga = 365 días, 2da = 180 días
        $dias_agregar = ($solicitud['extension_number'] == 1) ? 365 : 180;

        // Actualizar status en tfg_project_timeline
        $sql_update = "UPDATE tfg_project_timeline 
                       SET status = 'Prorroga Activa', 
                           days_remaining = IFNULL(days_remaining, 0) + ?
                       WHERE proposal_id = ?";
        $stmt = $this->conn->prepare($sql_update);
        $stmt->bind_param("ii", $dias_agregar, $solicitud['proposal_id']);
        $stmt->execute();
        $stmt->close();

    // Actualizar estado del proyecto aprobado
        $sql_estado = "UPDATE proyecto_aprobado
                    SET estado = 'Prorrogado'
                    WHERE proposal_id = ?";
        $stmt = $this->conn->prepare($sql_estado);
        $stmt->bind_param("i", $solicitud['proposal_id']);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Obtener datos base de una solicitud pendiente.
     * @param int $request_id
     * @return array|null
     */
    private function getPendingRequestData($request_id) {
        $sql = "SELECT id, proposal_id, user_id, request_date, status
                FROM tfg_extension_requests
                WHERE id = ? AND status = 'pendiente'
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('i', $request_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $row ?: null;
    }

    /**
     * Obtener todos los usuarios asociados al proyecto (estudiantes + comité)
     * @param int $proposal_id
     * @return array Array de user_ids
     */
    private function getAllProjectUsers($proposal_id) {
        $users = [];

        // 1. Obtener dueño de la propuesta
        $sqlOwner = "SELECT user_id FROM tfg_proposals WHERE id = ? LIMIT 1";
        $stmtOwner = $this->conn->prepare($sqlOwner);
        if ($stmtOwner) {
            $stmtOwner->bind_param('i', $proposal_id);
            $stmtOwner->execute();
            $resultOwner = $stmtOwner->get_result();
            if ($row = $resultOwner->fetch_assoc()) {
                $userId = (string) ($row['user_id'] ?? '');
                if ($userId !== '') {
                    $users[$userId] = true;
                }
            }
            $stmtOwner->close();
        }

        // 2. Obtener miembros activos del proyecto registrado
        $sqlMembers = "SELECT DISTINCT pm.user_id
                       FROM registered_projects rp
                       INNER JOIN project_members pm ON pm.project_id = rp.id AND pm.status = 'Activo'
                       WHERE rp.tfg_proposal_id = ?";
        $stmtMembers = $this->conn->prepare($sqlMembers);
        if ($stmtMembers) {
            $stmtMembers->bind_param('i', $proposal_id);
            $stmtMembers->execute();
            $resultMembers = $stmtMembers->get_result();
            while ($row = $resultMembers->fetch_assoc()) {
                $userId = (string) ($row['user_id'] ?? '');
                if ($userId !== '') {
                    $users[$userId] = true;
                }
            }
            $stmtMembers->close();
        }

        // 3. Obtener miembros del comité (tutor, asesor_1, asesor_2)
        $sqlCommittee = "SELECT DISTINCT c.tutor, c.asesor_1, c.asesor_2
                         FROM proyecto_aprobado pa
                         INNER JOIN comite c ON c.Id = pa.comite_id
                         WHERE pa.proposal_id = ?";
        $stmtCommittee = $this->conn->prepare($sqlCommittee);
        if ($stmtCommittee) {
            $stmtCommittee->bind_param('i', $proposal_id);
            $stmtCommittee->execute();
            $resultCommittee = $stmtCommittee->get_result();
            if ($row = $resultCommittee->fetch_assoc()) {
                foreach (['tutor', 'asesor_1', 'asesor_2'] as $role) {
                    $userId = (string) ($row[$role] ?? '');
                    if ($userId !== '') {
                        $users[$userId] = true;
                    }
                }
            }
            $stmtCommittee->close();
        }

        return array_keys($users);
    }

    /**
     * Sincronizar la prórroga aprobada con Google Calendar.
     * Sincroniza para TODOS los estudiantes y miembros del comité.
     * No bloquea el flujo de aprobación si falla.
     * @param int $request_id
     * @param array $requestData
     */
    private function syncApprovedExtensionRequest($request_id, array $requestData) {
        try {
            $proposalId = (int) ($requestData['proposal_id'] ?? 0);
            if ($proposalId <= 0) {
                return ['status' => 'failed', 'message' => 'Datos de la solicitud incompletos para sincronizar.'];
            }

            if (!class_exists('Service\\GoogleCalendarService')) {
                return ['status' => 'failed', 'message' => 'Servicio de Google Calendar no disponible.'];
            }

            $googleCalendarService = new Service\GoogleCalendarService($this->conn);
            
            // Obtener todos los usuarios asociados al proyecto
            $allUsers = $this->getAllProjectUsers($proposalId);
            
            if (empty($allUsers)) {
                return ['status' => 'failed', 'message' => 'No se encontraron usuarios asociados al proyecto.'];
            }

            $projectData = $this->buildExtensionProjectData($proposalId);
            $projectData['status'] = 'aprobada';
            $projectData['request_date'] = $requestData['request_date'] ?? date('Y-m-d H:i:s');

            // Sincronizar para cada usuario que tenga Google Calendar habilitado
            $syncedCount = 0;
            $failedCount = 0;
            
            foreach ($allUsers as $userId) {
                $userId = (string) $userId;
                
                // Registrar intento
                $this->logExtensionSyncAttempt($userId, (int) $request_id, 'pending', null);

                try {
                    if (!$googleCalendarService->isSyncEnabled($userId)) {
                        $this->logExtensionSyncAttempt(
                            $userId,
                            (int) $request_id,
                            'skipped',
                            'Usuario sin Google Calendar conectado.'
                        );
                        continue;
                    }

                    $syncOk = $googleCalendarService->syncExtensionRequest(
                        $userId,
                        (int) $request_id,
                        $projectData
                    );

                    if ($syncOk) {
                        $syncedCount++;
                    } else {
                        $failedCount++;
                        $this->logExtensionSyncAttempt(
                            $userId,
                            (int) $request_id,
                            'failed',
                            'Error al sincronizar con Google Calendar'
                        );
                    }
                } catch (Throwable $e) {
                    $failedCount++;
                    $this->logExtensionSyncAttempt(
                        $userId,
                        (int) $request_id,
                        'failed',
                        $e->getMessage()
                    );
                    error_log('ProrrogaLogic::syncApprovedExtensionRequest Error para usuario ' . $userId . ': ' . $e->getMessage());
                }
            }

            $message = "Sincronización completada. Sincronizados: $syncedCount, Fallos: $failedCount";
            $status = ($syncedCount > 0) ? 'synced' : 'failed';
            
            return ['status' => $status, 'message' => $message];
        } catch (Throwable $e) {
            error_log('ProrrogaLogic::syncApprovedExtensionRequest Error general: ' . $e->getMessage());
            return ['status' => 'failed', 'message' => $e->getMessage()];
        }
    }

    /**
     * Registrar intento de sincronización de prórroga para trazabilidad.
     * @param string $userId
     * @param int $requestId
     * @param string $status pending|synced|failed
     * @param string|null $errorMessage
     */
    private function logExtensionSyncAttempt($userId, $requestId, $status, $errorMessage = null) {
        $query = "
            INSERT INTO google_calendar_sync_log
            (id_user, event_type, event_id, google_event_id, sync_status, last_sync_at, error_message)
            VALUES (?, 'prorroga', ?, NULL, ?, NOW(), ?)
            ON DUPLICATE KEY UPDATE
            sync_status = VALUES(sync_status),
            last_sync_at = NOW(),
            error_message = VALUES(error_message)
        ";

        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            return;
        }

        $stmt->bind_param('siss', $userId, $requestId, $status, $errorMessage);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Obtener historial de prórrogas de un proyecto
     * @param int $proposal_id
     * @return array
     */
    public function obtenerHistorialProrrogas($proposal_id) {
        $sql = "SELECT * FROM tfg_extension_requests 
                WHERE proposal_id = ? 
                ORDER BY extension_number ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $proposal_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $historial = [];
        while ($row = $result->fetch_assoc()) {
            $historial[] = $row;
        }
        $stmt->close();
        
        return $historial;
    }

    /**
     * Construir datos de proyecto para la sincronización de prórroga.
     * @param int $proposal_id
     * @return array
     */
    private function buildExtensionProjectData($proposal_id) {
        $projectName = '';
        $studentNames = '';

        $sqlProject = "SELECT tp.title, tp.user_id, su.nombre AS owner_name
                       FROM tfg_proposals tp
                       LEFT JOIN sis_user su ON su.id = tp.user_id
                       WHERE tp.id = ?
                       LIMIT 1";
        $stmtProject = $this->conn->prepare($sqlProject);
        if ($stmtProject) {
            $stmtProject->bind_param('i', $proposal_id);
            $stmtProject->execute();
            $resultProject = $stmtProject->get_result();
            if ($row = $resultProject->fetch_assoc()) {
                $projectName = $row['title'] ?? '';
                $studentNames = $row['owner_name'] ?? '';
            }
            $stmtProject->close();
        }

        $sqlMembers = "SELECT GROUP_CONCAT(DISTINCT su.nombre ORDER BY su.nombre SEPARATOR ', ') AS student_names
                       FROM registered_projects rp
                       INNER JOIN project_members pm ON pm.project_id = rp.id AND pm.status = 'Activo'
                       INNER JOIN sis_user su ON su.id = pm.user_id
                       WHERE rp.tfg_proposal_id = ?";
        $stmtMembers = $this->conn->prepare($sqlMembers);
        if ($stmtMembers) {
            $stmtMembers->bind_param('i', $proposal_id);
            $stmtMembers->execute();
            $resultMembers = $stmtMembers->get_result();
            if ($rowMembers = $resultMembers->fetch_assoc()) {
                if (!empty($rowMembers['student_names'])) {
                    $studentNames = $rowMembers['student_names'];
                }
            }
            $stmtMembers->close();
        }

        return [
            'project_name' => $projectName,
            'student_names' => $studentNames,
        ];
    }

    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
