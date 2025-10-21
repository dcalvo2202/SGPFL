<?php
require_once __DIR__ . "/inc/db/db.php";

class PanelAsesor {
    public function getRevisionesPendientes() {
        try{
            $sql = "SELECT descripcion FROM revisiones WHERE estado = 'pendiente'";
            $resultado = seleccion($sql);

            $revisiones = [];
            if (!empty($resultado)) {
                foreach ($resultado as $row) {
                    $revisiones[] = $row["descripcion"];
                }
            }
            return $revisiones;
        } catch(Exception $e){
            return [
                "Revisión del Capítulo 1 – Estudiante: Ana Pérez",
                "Evaluación de Propuesta – Estudiante: Juan Ramírez"
            ];
        }
    }
    public function getProximaReunion() {
        try{
            $sql = "SELECT fecha FROM reuniones ORDER BY fecha ASC LIMIT 1";
            $resultado = seleccion($sql);

            if (!empty($resultado)) {
                return $resultado[0]["fecha"];
            }
            return "No hay reuniones próximas";
        } catch(Exception $e){
            return "20/09/2025 – Sesión de Comité Asesor";
        }
    }
    public function getObservaciones() {
        try{
            $sql = "SELECT texto FROM observaciones ORDER BY fecha DESC LIMIT 1";
            $resultado = seleccion($sql);

            if (!empty($resultado)) {
                return $resultado[0]["texto"];
            }
            return "No hay observaciones registradas";
        } catch(Exception $e){
            return "Recordatorio: entregar retroalimentación en un máximo de 7 días.";
        }
    }
}
?>
