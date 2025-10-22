<?php
require_once __DIR__ . "/inc/db/db.php";

class PanelAsesor {
    private $conn;

    public function __construct() {
        global $db_host, $usuario, $clave, $db;
        $this->conn = new mysqli($db_host, $usuario, $clave, $db);
        if ($this->conn->connect_error) {
            throw new Exception("Error de conexión: " . $this->conn->connect_error);
        }
        $this->conn->set_charset("utf8");
    }
    
    public function getRevisionesPendientes($asesor_id = null) {
        $sql = "SELECT dr.id, p.title, dr.review_type, dr.status
                FROM tfg_document_reviews dr
                INNER JOIN tfg_final_documents fd ON dr.document_id = fd.id
                INNER JOIN tfg_proposals p ON fd.proposal_id = p.id
                WHERE dr.status = 'Pendiente'";
        if ($asesor_id) {
            $sql .= " AND dr.asesor_id = " . intval($asesor_id);
        }

        $sql .= " ORDER BY dr.id DESC";
        $result = $this->conn->query($sql);
        $revisiones = [];

        while ($row = $result->fetch_assoc()) {
            $revisiones[] = "Revisión de " . htmlspecialchars($row["review_type"]) . " – Proyecto: " . htmlspecialchars($row["title"]);
        }

        return $revisiones ?: ["No hay revisiones pendientes."];
    }
    public function getProximaReunion($asesor_id = null) {
        $sql = "SELECT fecha, tema 
                FROM tfg_meetings 
                WHERE fecha >= CURDATE()";
        if ($asesor_id) {
            $sql .= " AND asesor_id = " . intval($asesor_id);
        }

        $sql .= " ORDER BY fecha ASC LIMIT 1";
        $result = $this->conn->query($sql);
        
        if ($row = $result->fetch_assoc()) {
            return date("d/m/Y", strtotime($row["fecha"])) . " – " . $row["tema"];
        }
        return "No hay reuniones próximas.";
    }
    public function getObservaciones($asesor_id = null) {
        $sql = "SELECT texto, fecha 
                FROM tfg_observaciones";

        if ($asesor_id) {
            $sql .= " WHERE asesor_id = " . intval($asesor_id);
        }

        $sql .= " ORDER BY fecha DESC LIMIT 1";
        $result = $this->conn->query($sql);
        
        if ($row = $result->fetch_assoc()) {
            return "[" . date("d/m/Y", strtotime($row["fecha"])) . "] " . $row["texto"];
        }
        return "No hay observaciones registradas.";
    }

    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>
