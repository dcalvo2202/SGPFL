<?php
require_once __DIR__ . "/inc/db/db.php";
class PanelCTFG {
    private $conn;
    private $db;

    public function __construct() {
        global $db_host, $usuario, $clave, $db;
        $this->conn = new mysqli($db_host, $usuario, $clave, $db);
        if ($this->conn->connect_error) {
            throw new Exception("Error de conexión: " . $this->conn->connect_error);
        }
        $this->conn->set_charset("utf8");
        global $id_con;
        $this->db = $id_con;
    }
    
    // Helper seguro para consultas directas (sin parámetros)
    private function fetchAll(string $sql): array {
        $res = mysqli_query($this->db, $sql);
        if ($res === false) {
            error_log('[MySQL] ' . mysqli_error($this->db) . ' | SQL: ' . $sql);
            return [];
        }
        $rows = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $rows[] = $row;
        }
        mysqli_free_result($res);
        return $rows;
    }

    public function getAsignacionesPendientes(): array {
        $sql = "SELECT title 
                FROM tfg_proposals 
                WHERE commission_assigned IS NULL AND status = 'Pendiente'";
        return $this->fetchAll($sql);
    }

    public function getRevisionesPendientes(): array {
        $sql = "SELECT title, user_id 
                FROM tfg_proposals 
                WHERE status = 'Pendiente' OR status = 'En revisión'
                ORDER BY created_at DESC";
        return $this->fetchAll($sql);
    }

    public function getProximaReunion(): array {
        $sql = "SELECT fecha, tema 
                FROM tfg_commission_meetings 
                WHERE fecha >= CURDATE() 
                ORDER BY fecha ASC LIMIT 1";
        return $this->fetchAll($sql);
    }

    public function getAvisos(): array {
         $sql = "SELECT mensaje, fecha 
                FROM tfg_announcements 
                ORDER BY fecha DESC LIMIT 1";
        return $this->fetchAll($sql);
    }

    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>
