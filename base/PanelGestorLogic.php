<?php
require_once __DIR__ . "/inc/db/db.php";
class PanelGestor {
    private $conn;

    public function __construct() {
        global $db_host, $usuario, $clave, $db;
        $this->conn = new mysqli($db_host, $usuario, $clave, $db);
        if ($this->conn->connect_error) {
            throw new Exception("Error de conexión: " . $this->conn->connect_error);
        }
        $this->conn->set_charset("utf8");
    }
    
    public function getRevisionesPendientes() {
        $sql = "SELECT title 
                FROM tfg_proposals 
                WHERE status IN ('Pendiente', 'En revisión')
                ORDER BY created_at DESC";
        $result = $this->conn->query($sql);

        $revisiones = [];
        while ($row = $result->fetch_assoc()) {
            $revisiones[] = "Revisión pendiente: " . htmlspecialchars($row["title"]);
        }
        return $revisiones ?: ["No hay revisiones pendientes."];
    }
    public function getAsignacionesPendientes() {
        $sql = "SELECT title 
                FROM tfg_proposals 
                WHERE gestor_asignado IS NULL 
                AND status = 'Pendiente'";
        $result = $this->conn->query($sql);

        $asignaciones = [];
        while ($row = $result->fetch_assoc()) {
            $asignaciones[] = "Falta asignar evaluador a: " . htmlspecialchars($row["title"]);
        }
        return $asignaciones ?: ["No hay asignaciones pendientes."];
    }
    public function getProximaReunion() {
        $sql = "SELECT fecha, tema 
                FROM tfg_commission_meetings 
                WHERE fecha >= CURDATE() 
                ORDER BY fecha ASC LIMIT 1";
        $result = $this->conn->query($sql);

        if ($row = $result->fetch_assoc()) {
            return date("d/m/Y", strtotime($row["fecha"])) . " – " . $row["tema"];
        }
        return "No hay reuniones programadas.";
    }
    public function getAvisos() {
        $sql = "SELECT mensaje, fecha 
                FROM tfg_announcements 
                ORDER BY fecha DESC LIMIT 1";
        $result = $this->conn->query($sql);

        if ($row = $result->fetch_assoc()) {
            return "[" . date("d/m/Y", strtotime($row["fecha"])) . "] " . $row["mensaje"];
        }
        return "No hay avisos recientes.";
    }
    public function getCalificaciones() {
        $sql = "SELECT e.id, u.nombre AS estudiante, p.title AS proyecto, e.nota
                FROM tfg_evaluations e
                JOIN usuarios u ON e.estudiante_id = u.id
                JOIN tfg_proposals p ON e.proyecto_id = p.id
                ORDER BY e.nota DESC";
        $result = $this->conn->query($sql);

        $calificaciones = [];
        while ($row = $result->fetch_assoc()) {
            $calificaciones[] = [
                "id" => $row["id"],
                "estudiante" => $row["estudiante"],
                "proyecto" => $row["proyecto"],
                "nota" => $row["nota"]
            ];
        }
        return $calificaciones ?: [];
    }

    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>
