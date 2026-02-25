<?php
require_once __DIR__ . "/../../../inc/db/db.php";

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
     * @return array ['success' => bool, 'message' => string, 'id' => int|null]
     */
    public function crearSolicitud($proposal_id, $user_id, $extension_number, $reason) {
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

        // Insertar solicitud
        $sql = "INSERT INTO tfg_extension_requests 
                (proposal_id, user_id, extension_number, reason, status, request_date) 
                VALUES (?, ?, ?, ?, 'pendiente', NOW())";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [
                'success' => false,
                'message' => 'Error al preparar la consulta: ' . $this->conn->error,
                'id' => null
            ];
        }

        $stmt->bind_param("isis", $proposal_id, $user_id, $extension_number, $reason);
        
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
            
            // Si se aprobó, actualizar tfg_project_timeline
            if ($status === 'aprobada') {
                $this->actualizarTimeline($request_id);
            }
            
            return [
                'success' => true,
                'message' => 'Solicitud ' . $status . ' correctamente.'
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

    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
