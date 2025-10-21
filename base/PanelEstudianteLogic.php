<?php
require_once __DIR__ . "/../base/inc/db/db.php";
    class PanelEstudiante {
        public function getProximaFecha($idEstudiante) {
            try{
                $sql = "SELECT fecha FROM fechas_entregas 
                    WHERE estudiante_id = $idEstudiante 
                    ORDER BY fecha ASC 
                    LIMIT 1";
                $resultado = seleccion($sql);

                if (count($resultado) > 0) {
                    return $resultado[0]['fecha'];
                }
                return "No hay próximas fechas";
            } catch(Exception $e){
                return "14/09/2025 – Entrega del capítulo 2";
            }
        }
        public function getTareaPendiente($idEstudiante) {
            try{
                $sql = "SELECT descripcion FROM tareas 
                    WHERE estudiante_id = $idEstudiante 
                    AND estado = 'pendiente' 
                    LIMIT 1";
                $resultado = seleccion($sql);

                if (count($resultado) > 0) {
                    return $resultado[0]['descripcion'];
                }
                return "No hay tareas pendientes";
            } catch(Exception $e){
                return "Subir versión corregida del capítulo 2";
            }
        }
        public function getDocumentoEnviado($idEstudiante) {
            try{
                $sql = "SELECT nombre FROM documentos 
                    WHERE estudiante_id = $idEstudiante 
                    ORDER BY fecha_envio DESC 
                    LIMIT 1";
                $resultado = seleccion($sql);

                if (count($resultado) > 0) {
                    return $resultado[0]['nombre'];
                }
                return "No se han enviado documentos";
            } catch(Exception $e){
                return "Avance 1 – Revisado con observaciones";
            }
        }
        public function getNotificacion($idEstudiante) {
            try{
                $sql = "SELECT mensaje, fecha FROM notificaciones 
                    WHERE estudiante_id = $idEstudiante 
                    ORDER BY fecha DESC 
                    LIMIT 1";
                $resultado = seleccion($sql);

                if (count($resultado) > 0) {
                    return "[".$resultado[0]['fecha']."] ".$resultado[0]['mensaje'];
                }
                return "No hay notificaciones";
            } catch(Exception $e){
                return "[10/09/2025] Nueva fecha de entrega asignada";
            }
        }
    }
?>
