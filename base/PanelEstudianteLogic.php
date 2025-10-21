<?php
require_once __DIR__ . "/inc/db/db.php";
    class PanelEstudiante {
        private $conn;

        public function __construct() {
            global $db_host, $usuario, $clave, $db;
            $this->conn = new mysqli($db_host, $usuario, $clave, $db);
            if ($this->conn->connect_error) {
                throw new Exception("Error de conexión: " . $this->conn->connect_error);
            }
            $this->conn->set_charset("utf8");
        }
        
        public function getProximaFecha($idEstudiante) {
            $sql = "SELECT fecha, evento 
                FROM tfg_project_timeline 
                WHERE user_id = ? AND fecha >= CURDATE()
                ORDER BY fecha ASC LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $idEstudiante);
            $stmt->execute();
            $result = $stmt->get_result();
    
            if ($row = $result->fetch_assoc()) {
                return $row["fecha"] . " – " . $row["evento"];
            }
            return "No hay fechas próximas registradas.";
        }
        public function getTareaPendiente($idEstudiante) {
            $sql = "SELECT title, status 
                FROM tfg_proposals 
                WHERE user_id = ? AND status NOT IN ('Aprobado', 'Rechazado')
                ORDER BY created_at DESC LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $idEstudiante);
            $stmt->execute();
            $result = $stmt->get_result();
    
            if ($row = $result->fetch_assoc()) {
                return "Revisar propuesta: " . $row["title"] . " (" . $row["status"] . ")";
            }
            return "No hay tareas pendientes.";
        }
        public function getDocumentoEnviado($idEstudiante) {
           $sql = "SELECT title, status 
                FROM tfg_final_documents 
                WHERE submitted_by = ? 
                ORDER BY uploaded_at DESC LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $idEstudiante);
            $stmt->execute();
            $result = $stmt->get_result();
    
            if ($row = $result->fetch_assoc()) {
                return $row["title"] . " – " . $row["status"];
            }
            return "No se han enviado documentos.";
        }
        public function getNotificacion($idEstudiante) {
            $sql = "SELECT message, created_at 
                FROM tfg_notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $idEstudiante);
            $stmt->execute();
            $result = $stmt->get_result();
    
            if ($row = $result->fetch_assoc()) {
                $fecha = date("d/m/Y", strtotime($row["created_at"]));
                return "[$fecha] " . $row["message"];
            }
            return "No hay notificaciones recientes.";
        }

        public function __destruct() {
            if ($this->conn) {
                $this->conn->close();
            }
        }
    }
?>
