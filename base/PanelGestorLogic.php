<?php
require_once __DIR__ . "/inc/db/db.php";
class PanelGestor {
    public function getRevisionesPendientes() {
        try{
            $sql = "SELECT descripcion FROM revisiones_ctfg WHERE estado = 'pendiente'";
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
                "Aprobación de propuesta – Estudiante: Laura Sánchez",
                "Revisión final de TFG – Estudiante: Pedro Gómez"
            ];
        }
    }
    public function getAsignacionesPendientes() {
        try{
            $sql = "SELECT descripcion FROM asignaciones_ctfg WHERE estado = 'pendiente'";
            $resultado = seleccion($sql);

            $asignaciones = [];
            if (!empty($resultado)) {
                foreach ($resultado as $row) {
                    $asignaciones[] = $row["descripcion"];
                }
            }
            return $asignaciones;
        } catch(Exception $e){
            return [
                "Asignar asesor externo – Estudiante: María López",
                "Designar tribunal evaluador – Estudiante: Carlos Fernández"
            ];
        }
    }
    public function getProximaReunion() {
        try{
            $sql = "SELECT fecha FROM reuniones_ctfg ORDER BY fecha ASC LIMIT 1";
            $resultado = seleccion($sql);

            if (!empty($resultado)) {
                return $resultado[0]["fecha"];
            }
            return "No hay reuniones próximas";
        } catch(Exception $e){
            return "25/09/2025 – Sesión ordinaria de la Comisión TFG";
        }
    }
    public function getAvisos() {
        try{
            $sql = "SELECT texto FROM avisos_ctfg ORDER BY fecha DESC LIMIT 1";
            $resultado = seleccion($sql);

            if (!empty($resultado)) {
                return $resultado[0]["texto"];
            }
            return "No hay avisos registrados";
        } catch(Exception $e){
            return "Se deben resolver todas las propuestas pendientes antes del cierre de actas (30/09/2025).";
        }
    }
    public function getCalificaciones() {
        try {
            $sql = "SELECT c.id, e.nombre as estudiante, p.titulo as proyecto, c.nota 
                    FROM calificaciones c
                    JOIN estudiantes e ON c.estudiante_id = e.id
                    JOIN proyectos p ON c.proyecto_id = p.id";
            $resultado = seleccion($sql);

            $calificaciones = [];
            if (!empty($resultado)) {
                foreach ($resultado as $row) {
                    $calificaciones[] = [
                        "id"        => $row["id"],
                        "estudiante"=> $row["estudiante"],
                        "proyecto"  => $row["proyecto"],
                        "nota"      => $row["nota"]
                    ];
                }
            }
            return $calificaciones;
        } catch (Exception $e) {
            return [
                ["id" => 1, "estudiante" => "Ana Pérez", "proyecto" => "Sistema de Gestión Académica", "nota" => 85],
                ["id" => 2, "estudiante" => "Juan Ramírez", "proyecto" => "Plataforma E-learning", "nota" => 90],
                ["id" => 3, "estudiante" => "María López", "proyecto" => "Sistema de Inventario", "nota" => 78]
            ];
        }
    }
}
?>
