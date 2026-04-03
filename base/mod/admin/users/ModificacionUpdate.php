<?php
/**
 * ModificacionUpdate.php
 * Clase para consultar y obtener registros de auditoría de cambios en fechas
 * Tablas auditadas: registered_projects, tfg_extension_requests
 */

require_once __DIR__ . "/../../../inc/db/db.php";

class ModificacionUpdate {
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
     * Obtener todos los registros de auditoría con filtros opcionales
     * @param array $filtros ['tabla_origen', 'campo_modificado', 'modificado_por', 'fecha_desde', 'fecha_hasta', 'id_registro']
     * @param int $pagina Número de página (1-based)
     * @param int $por_pagina Registros por página
     * @return array ['success' => bool, 'data' => array, 'total' => int, 'paginas' => int]
     */
    public function obtenerRegistros($filtros = [], $pagina = 1, $por_pagina = 15) {
        $where = "WHERE 1=1";
        $params = [];
        $types = "";

        // Filtro por tabla origen
        if (!empty($filtros['tabla_origen']) && $filtros['tabla_origen'] !== 'todas') {
            $where .= " AND a.tabla_origen = ?";
            $params[] = $filtros['tabla_origen'];
            $types .= "s";
        }

        // Filtro por campo modificado
        if (!empty($filtros['campo_modificado']) && $filtros['campo_modificado'] !== 'todos') {
            $where .= " AND a.campo_modificado = ?";
            $params[] = $filtros['campo_modificado'];
            $types .= "s";
        }

        // Filtro por usuario que modificó
        if (!empty($filtros['modificado_por'])) {
            $where .= " AND a.modificado_por = ?";
            $params[] = $filtros['modificado_por'];
            $types .= "s";
        }

        // Filtro por fecha desde
        if (!empty($filtros['fecha_desde'])) {
            $where .= " AND DATE(a.fecha_modificacion) >= ?";
            $params[] = $filtros['fecha_desde'];
            $types .= "s";
        }

        // Filtro por fecha hasta
        if (!empty($filtros['fecha_hasta'])) {
            $where .= " AND DATE(a.fecha_modificacion) <= ?";
            $params[] = $filtros['fecha_hasta'];
            $types .= "s";
        }

        // Filtro por ID de registro
        if (!empty($filtros['id_registro'])) {
            $where .= " AND a.id_registro = ?";
            $params[] = (int)$filtros['id_registro'];
            $types .= "i";
        }

        // Contar total de registros
        $sql_count = "SELECT COUNT(*) as total FROM auditoria_cambios_fecha a $where";
        $total = 0;

        if (empty($params)) {
            $result = $this->conn->query($sql_count);
            if ($result) {
                $row = $result->fetch_assoc();
                $total = (int)$row['total'];
            }
        } else {
            $stmt = $this->conn->prepare($sql_count);
            if ($stmt) {
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result) {
                    $row = $result->fetch_assoc();
                    $total = (int)$row['total'];
                }
                $stmt->close();
            }
        }

        $total_paginas = max(1, (int)ceil($total / $por_pagina));
        $pagina = max(1, min($pagina, $total_paginas));
        $offset = ($pagina - 1) * $por_pagina;

        // Obtener registros con JOIN a sis_user para nombre del usuario
        $sql = "SELECT 
                    a.id,
                    a.tabla_origen,
                    a.id_registro,
                    a.campo_modificado,
                    a.valor_anterior,
                    a.valor_nuevo,
                    a.modificado_por,
                    a.fecha_modificacion,
                    a.descripcion,
                    COALESCE(u.nombre, a.modificado_por) as nombre_usuario
                FROM auditoria_cambios_fecha a
                LEFT JOIN sis_user u ON u.id = a.modificado_por
                $where
                ORDER BY a.fecha_modificacion DESC
                LIMIT ?, ?";

        $params[] = $offset;
        $params[] = $por_pagina;
        $types .= "ii";

        $data = [];
        $stmt = $this->conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
            }
            $stmt->close();
        }

        return [
            'success' => true,
            'data' => $data,
            'total' => $total,
            'paginas' => $total_paginas,
            'pagina_actual' => $pagina
        ];
    }

    /**
     * Obtener un registro específico por ID
     * @param int $id ID del registro de auditoría
     * @return array|null
     */
    public function obtenerRegistroPorId($id) {
        $sql = "SELECT 
                    a.*,
                    COALESCE(u.nombre, a.modificado_por) as nombre_usuario
                FROM auditoria_cambios_fecha a
                LEFT JOIN sis_user u ON u.id = a.modificado_por
                WHERE a.id = ?";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();

        return $data;
    }

    /**
     * Obtener historial de cambios de un proyecto específico
     * @param int $id_proyecto ID del proyecto en registered_projects
     * @return array
     */
    public function obtenerHistorialProyecto($id_proyecto) {
        $sql = "SELECT 
                    a.*,
                    COALESCE(u.nombre, a.modificado_por) as nombre_usuario
                FROM auditoria_cambios_fecha a
                LEFT JOIN sis_user u ON u.id = a.modificado_por
                WHERE a.tabla_origen = 'registered_projects' AND a.id_registro = ?
                ORDER BY a.fecha_modificacion DESC";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param("i", $id_proyecto);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();

        return $data;
    }

    /**
     * Obtener historial de cambios de una prórroga específica
     * @param int $id_prorroga ID de la prórroga en tfg_extension_requests
     * @return array
     */
    public function obtenerHistorialProrroga($id_prorroga) {
        $sql = "SELECT 
                    a.*,
                    COALESCE(u.nombre, a.modificado_por) as nombre_usuario
                FROM auditoria_cambios_fecha a
                LEFT JOIN sis_user u ON u.id = a.modificado_por
                WHERE a.tabla_origen = 'tfg_extension_requests' AND a.id_registro = ?
                ORDER BY a.fecha_modificacion DESC";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param("i", $id_prorroga);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();

        return $data;
    }

    /**
     * Obtener lista de usuarios que han realizado modificaciones (para filtro)
     * @return array
     */
    public function obtenerUsuariosModificadores() {
        $sql = "SELECT DISTINCT 
                    a.modificado_por,
                    COALESCE(u.nombre, a.modificado_por) as nombre_usuario
                FROM auditoria_cambios_fecha a
                LEFT JOIN sis_user u ON u.id = a.modificado_por
                ORDER BY nombre_usuario ASC";

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
     * Obtener estadísticas generales de auditoría
     * @return array
     */
    public function obtenerEstadisticas() {
        $stats = [
            'total_cambios' => 0,
            'cambios_proyectos' => 0,
            'cambios_prorrogas' => 0,
            'cambios_hoy' => 0,
            'cambios_semana' => 0
        ];

        // Total de cambios
        $result = $this->conn->query("SELECT COUNT(*) as total FROM auditoria_cambios_fecha");
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_cambios'] = (int)$row['total'];
        }

        // Cambios en proyectos
        $result = $this->conn->query("SELECT COUNT(*) as total FROM auditoria_cambios_fecha WHERE tabla_origen = 'registered_projects'");
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['cambios_proyectos'] = (int)$row['total'];
        }

        // Cambios en prórrogas
        $result = $this->conn->query("SELECT COUNT(*) as total FROM auditoria_cambios_fecha WHERE tabla_origen = 'tfg_extension_requests'");
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['cambios_prorrogas'] = (int)$row['total'];
        }

        // Cambios hoy
        $result = $this->conn->query("SELECT COUNT(*) as total FROM auditoria_cambios_fecha WHERE DATE(fecha_modificacion) = CURDATE()");
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['cambios_hoy'] = (int)$row['total'];
        }

        // Cambios última semana
        $result = $this->conn->query("SELECT COUNT(*) as total FROM auditoria_cambios_fecha WHERE fecha_modificacion >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['cambios_semana'] = (int)$row['total'];
        }

        return $stats;
    }

    /**
     * Obtener información del proyecto relacionado
     * @param int $id_registro ID del registro en registered_projects
     * @return array|null
     */
    public function obtenerInfoProyecto($id_registro) {
        $sql = "SELECT 
                    rp.id,
                    rp.start_date,
                    rp.end_date,
                    rp.status,
                    tp.title as titulo_propuesta,
                    pt.name as tipo_proyecto
                FROM registered_projects rp
                LEFT JOIN tfg_proposals tp ON tp.id = rp.tfg_proposal_id
                LEFT JOIN project_types pt ON pt.id = rp.project_type_id
                WHERE rp.id = ?";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("i", $id_registro);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();

        return $data;
    }

    /**
     * Obtener información de la prórroga relacionada
     * @param int $id_registro ID del registro en tfg_extension_requests
     * @return array|null
     */
    public function obtenerInfoProrroga($id_registro) {
        $sql = "SELECT 
                    ter.id,
                    ter.proposal_id,
                    ter.user_id,
                    ter.extension_number,
                    ter.status,
                    ter.request_date,
                    ter.response_date,
                    tp.title as titulo_propuesta,
                    COALESCE(u.nombre, ter.user_id) as nombre_solicitante
                FROM tfg_extension_requests ter
                LEFT JOIN tfg_proposals tp ON tp.id = ter.proposal_id
                LEFT JOIN sis_user u ON u.id = ter.user_id
                WHERE ter.id = ?";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("i", $id_registro);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();

        return $data;
    }

    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
