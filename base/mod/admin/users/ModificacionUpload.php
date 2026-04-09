<?php
/**
 * ModificacionUpload.php
 * Clase para realizar modificaciones en fechas de proyectos y prórrogas
 * Los triggers capturan automáticamente los cambios en auditoria_cambios_fecha
 */

require_once __DIR__ . "/../../../inc/db/db.php";

class ModificacionUpload {
    private $conn;
    private $current_user_id;

    public function __construct($user_id = null) {
        global $db_host, $usuario, $clave, $db;
        $this->conn = new mysqli($db_host, $usuario, $clave, $db);
        if ($this->conn->connect_error) {
            throw new Exception("Error de conexión: " . $this->conn->connect_error);
        }
        $this->conn->set_charset("utf8");
        
        // Establecer usuario por defecto
        $this->current_user_id = $user_id ?? 'SYSTEM';
    }

    /**
     * Establecer el usuario actual en la sesión MySQL
     * IMPORTANTE: Llamar SIEMPRE antes de hacer cualquier UPDATE
     * @param string $user_id ID del usuario que realiza la acción
     * @return bool
     */
    public function setUsuarioSesion($user_id) {
        $this->current_user_id = $user_id;
        $escaped_user = $this->conn->real_escape_string($user_id);
        return $this->conn->query("SET @current_user_id = '$escaped_user'");
    }

    /**
     * Actualizar fecha de inicio de un proyecto
     * @param int $project_id ID del proyecto
     * @param string $nueva_fecha Nueva fecha (formato Y-m-d)
     * @param string $user_id Usuario que realiza el cambio
     * @return array
     */
    public function actualizarFechaInicioProyecto($project_id, $nueva_fecha, $user_id) {
        // Establecer usuario para el trigger
        $this->setUsuarioSesion($user_id);

        $sql = "UPDATE registered_projects SET start_date = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [
                'success' => false,
                'message' => 'Error al preparar consulta: ' . $this->conn->error
            ];
        }

        $stmt->bind_param("si", $nueva_fecha, $project_id);
        
        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            $stmt->close();
            return [
                'success' => true,
                'message' => 'Fecha de inicio actualizada correctamente.',
                'affected_rows' => $affected
            ];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return [
                'success' => false,
                'message' => 'Error al actualizar: ' . $error
            ];
        }
    }

    /**
     * Actualizar fecha de finalización de un proyecto
     * @param int $project_id ID del proyecto
     * @param string $nueva_fecha Nueva fecha (formato Y-m-d)
     * @param string $user_id Usuario que realiza el cambio
     * @return array
     */
    public function actualizarFechaFinProyecto($project_id, $nueva_fecha, $user_id) {
        // Establecer usuario para el trigger
        $this->setUsuarioSesion($user_id);

        $sql = "UPDATE registered_projects SET end_date = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [
                'success' => false,
                'message' => 'Error al preparar consulta: ' . $this->conn->error
            ];
        }

        $stmt->bind_param("si", $nueva_fecha, $project_id);
        
        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            $stmt->close();
            return [
                'success' => true,
                'message' => 'Fecha de finalización actualizada correctamente.',
                'affected_rows' => $affected
            ];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return [
                'success' => false,
                'message' => 'Error al actualizar: ' . $error
            ];
        }
    }

    /**
     * Actualizar ambas fechas de un proyecto
     * @param int $project_id ID del proyecto
     * @param string|null $fecha_inicio Nueva fecha de inicio (formato Y-m-d)
     * @param string|null $fecha_fin Nueva fecha de finalización (formato Y-m-d)
     * @param string $user_id Usuario que realiza el cambio
     * @return array
     */
    public function actualizarFechasProyecto($project_id, $fecha_inicio, $fecha_fin, $user_id) {
        // Establecer usuario para el trigger
        $this->setUsuarioSesion($user_id);

        $campos = [];
        $params = [];
        $types = "";

        if ($fecha_inicio !== null) {
            $campos[] = "start_date = ?";
            $params[] = $fecha_inicio;
            $types .= "s";
        }

        if ($fecha_fin !== null) {
            $campos[] = "end_date = ?";
            $params[] = $fecha_fin;
            $types .= "s";
        }

        if (empty($campos)) {
            return [
                'success' => false,
                'message' => 'No se proporcionaron fechas para actualizar.'
            ];
        }

        $params[] = $project_id;
        $types .= "i";

        $sql = "UPDATE registered_projects SET " . implode(", ", $campos) . " WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [
                'success' => false,
                'message' => 'Error al preparar consulta: ' . $this->conn->error
            ];
        }

        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            $stmt->close();
            return [
                'success' => true,
                'message' => 'Fechas del proyecto actualizadas correctamente.',
                'affected_rows' => $affected
            ];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return [
                'success' => false,
                'message' => 'Error al actualizar: ' . $error
            ];
        }
    }

    /**
     * Actualizar fecha de solicitud de prórroga
     * @param int $prorroga_id ID de la prórroga
     * @param string $nueva_fecha Nueva fecha (formato Y-m-d H:i:s)
     * @param string $user_id Usuario que realiza el cambio
     * @return array
     */
    public function actualizarFechaSolicitudProrroga($prorroga_id, $nueva_fecha, $user_id) {
        // Establecer usuario para el trigger
        $this->setUsuarioSesion($user_id);

        $sql = "UPDATE tfg_extension_requests SET request_date = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [
                'success' => false,
                'message' => 'Error al preparar consulta: ' . $this->conn->error
            ];
        }

        $stmt->bind_param("si", $nueva_fecha, $prorroga_id);
        
        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            $stmt->close();
            return [
                'success' => true,
                'message' => 'Fecha de solicitud actualizada correctamente.',
                'affected_rows' => $affected
            ];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return [
                'success' => false,
                'message' => 'Error al actualizar: ' . $error
            ];
        }
    }

    /**
     * Actualizar fecha de respuesta de prórroga
     * @param int $prorroga_id ID de la prórroga
     * @param string $nueva_fecha Nueva fecha (formato Y-m-d H:i:s)
     * @param string $user_id Usuario que realiza el cambio
     * @return array
     */
    public function actualizarFechaRespuestaProrroga($prorroga_id, $nueva_fecha, $user_id) {
        // Establecer usuario para el trigger
        $this->setUsuarioSesion($user_id);

        $sql = "UPDATE tfg_extension_requests SET response_date = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [
                'success' => false,
                'message' => 'Error al preparar consulta: ' . $this->conn->error
            ];
        }

        $stmt->bind_param("si", $nueva_fecha, $prorroga_id);
        
        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            $stmt->close();
            return [
                'success' => true,
                'message' => 'Fecha de respuesta actualizada correctamente.',
                'affected_rows' => $affected
            ];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return [
                'success' => false,
                'message' => 'Error al actualizar: ' . $error
            ];
        }
    }

    /**
     * Registrar un cambio de auditoría manualmente
     * Útil cuando se necesita registrar un cambio sin usar triggers
     * @param string $tabla_origen Nombre de la tabla
     * @param int $id_registro ID del registro
     * @param string $campo_modificado Campo que cambió
     * @param string|null $valor_anterior Valor anterior
     * @param string|null $valor_nuevo Nuevo valor
     * @param string $user_id Usuario que hizo el cambio
     * @param string|null $descripcion Descripción adicional
     * @return array
     */
    public function registrarCambioManual($tabla_origen, $id_registro, $campo_modificado, $valor_anterior, $valor_nuevo, $user_id, $descripcion = null) {
        $sql = "INSERT INTO auditoria_cambios_fecha 
                (tabla_origen, id_registro, campo_modificado, valor_anterior, valor_nuevo, modificado_por, descripcion)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [
                'success' => false,
                'message' => 'Error al preparar consulta: ' . $this->conn->error
            ];
        }

        $stmt->bind_param("sisssss", 
            $tabla_origen, 
            $id_registro, 
            $campo_modificado, 
            $valor_anterior, 
            $valor_nuevo, 
            $user_id, 
            $descripcion
        );
        
        if ($stmt->execute()) {
            $insert_id = $this->conn->insert_id;
            $stmt->close();
            return [
                'success' => true,
                'message' => 'Registro de auditoría creado correctamente.',
                'id' => $insert_id
            ];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return [
                'success' => false,
                'message' => 'Error al insertar: ' . $error
            ];
        }
    }

    /**
     * Obtener las fechas actuales de un proyecto
     * @param int $project_id ID del proyecto
     * @return array|null
     */
    public function obtenerFechasProyecto($project_id) {
        $sql = "SELECT id, start_date, end_date, status FROM registered_projects WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();

        return $data;
    }

    /**
     * Obtener las fechas actuales de una prórroga
     * @param int $prorroga_id ID de la prórroga
     * @return array|null
     */
    public function obtenerFechasProrroga($prorroga_id) {
        $sql = "SELECT id, proposal_id, request_date, response_date, status FROM tfg_extension_requests WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("i", $prorroga_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();

        return $data;
    }

    /**
     * Listar todos los proyectos (para selector)
     * @return array
     */
    public function listarProyectos() {
        $sql = "SELECT 
                    rp.id,
                    rp.start_date,
                    rp.end_date,
                    rp.status,
                    tp.title as titulo
                FROM registered_projects rp
                LEFT JOIN tfg_proposals tp ON tp.id = rp.tfg_proposal_id
                ORDER BY rp.id DESC";

        $result = $this->conn->query($sql);
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }

        return $data;
    }

    /**
     * Listar todas las prórrogas (para selector)
     * @return array
     */
    public function listarProrrogas() {
        $sql = "SELECT 
                    ter.id,
                    ter.proposal_id,
                    ter.extension_number,
                    ter.status,
                    ter.request_date,
                    ter.response_date,
                    tp.title as titulo_propuesta,
                    COALESCE(u.nombre, ter.user_id) as solicitante
                FROM tfg_extension_requests ter
                LEFT JOIN tfg_proposals tp ON tp.id = ter.proposal_id
                LEFT JOIN sis_user u ON u.id = ter.user_id
                ORDER BY ter.id DESC";

        $result = $this->conn->query($sql);
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }

        return $data;
    }

    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
