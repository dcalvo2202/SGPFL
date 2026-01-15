<?php
include_once(__DIR__ . "/../../../inc/db/db.php");
include_once(__DIR__ . "/../../../inc/constants.php");
include_once(__DIR__ . "/../../../inc/archive_functions.php");

/**
 * Clase para gestión de proyectos grupales
 * Siguiendo estándares UNA/ESCINF - Proyecto 2025-07
 */
class ProjectGroup {
    
    /**
     * Obtener tipos de proyecto disponibles
     * @return array Lista de tipos de proyecto activos
     */
    public static function get_project_types() {
        $sql = "SELECT * FROM project_types WHERE active = 1 ORDER BY max_members ASC";
        return seleccion($sql);
    }
    
    /**
     * Validar tamaño del grupo según tipo de proyecto
     * @param int $project_type_id ID del tipo de proyecto
     * @param int $member_count Cantidad de miembros
     * @return array Resultado de validación
     */
    public static function validate_group_size($project_type_id, $member_count) {
        $project_type_id = (int)$project_type_id;
        $sql = "SELECT max_members FROM project_types WHERE id = $project_type_id AND active = 1";
        $result = seleccion($sql);
        
        if (empty($result)) {
            return ['valid' => false, 'message' => 'Tipo de proyecto no válido'];
        }
        
        $max_allowed = (int)$result[0]['max_members'];
        
        if ($member_count > $max_allowed) {
            return [
                'valid' => false, 
                'message' => "Máximo {$max_allowed} miembros permitidos para este tipo de proyecto"
            ];
        }
        
        return ['valid' => true, 'message' => 'Validación exitosa'];
    }
    
    /**
     * Crear nuevo proyecto grupal
     * @param array $data Datos del proyecto
     * @return array Resultado de la operación
     */
    public static function create_project($data) {
        // Validar tamaño del grupo
        $validation = self::validate_group_size($data['project_type_id'], count($data['members']));
        if (!$validation['valid']) {
            return ['success' => false, 'message' => $validation['message']];
        }
        
        // Limpiar datos de entrada
        $project_title = addslashes($data['project_title']);
        $description = addslashes($data['description']);
        $project_type_id = (int)$data['project_type_id'];
        
        // Insertar proyecto principal
        $sql_project = "INSERT INTO registered_projects (project_title, description, project_type_id, status, created_at) 
                        VALUES ('$project_title', '$description', $project_type_id, 'Borrador', NOW())";
        
        $result = transaccion($sql_project);
        
        if ($result && !empty($result)) {
            // Obtener el ID del proyecto insertado
            $get_id_sql = "SELECT MAX(id) as project_id FROM registered_projects WHERE project_title = '$project_title'";
            $id_result = seleccion($get_id_sql);
            
            if (!empty($id_result) && isset($id_result[0]['project_id'])) {
                $project_id = $id_result[0]['project_id'];
                
                // Insertar miembros del proyecto
                $members_success = true;
                foreach ($data['members'] as $index => $member) {
                    $user_id = addslashes($member['user_id']);
                    $role = ($index === 0) ? 'Líder' : 'Miembro';
                    $role = addslashes($role);
                    
                    $sql_member = "INSERT INTO project_members (project_id, user_id, role, joined_at) 
                                  VALUES ($project_id, '$user_id', '$role', NOW())";
                    
                    $member_result = transaccion($sql_member);
                    if (!$member_result || empty($member_result)) {
                        $members_success = false;
                        break;
                    }
                }
                
                if ($members_success) {
                    return ['success' => true, 'message' => 'Proyecto registrado exitosamente'];
                } else {
                    // Eliminar proyecto si falló la inserción de miembros
                    $delete_sql = "DELETE FROM registered_projects WHERE id = $project_id";
                    transaccion($delete_sql);
                    return ['success' => false, 'message' => 'Error al registrar los miembros del proyecto'];
                }
            } else {
                return ['success' => false, 'message' => 'Error al obtener ID del proyecto'];
            }
        } else {
            return ['success' => false, 'message' => 'Error al registrar el proyecto'];
        }
    }
    
    /**
     * Obtener proyectos de un usuario específico
     * @param string $user_id ID del usuario
     * @return array Lista de proyectos del usuario
     */
    public static function get_projects_by_user($user_id) {
        $user_id = addslashes($user_id);
        
        $sql = "SELECT p.*, pt.type_name, pt.max_members,
                       GROUP_CONCAT(pm2.role) as user_roles
                FROM registered_projects p
                INNER JOIN project_members pm ON p.id = pm.project_id
                INNER JOIN project_types pt ON p.project_type_id = pt.id
                LEFT JOIN project_members pm2 ON p.id = pm2.project_id AND pm2.user_id = '$user_id'
                WHERE pm.user_id = '$user_id'
                GROUP BY p.id
                ORDER BY p.created_at DESC";
        
        return seleccion($sql);
    }
    
    /**
     * Obtener miembros de un proyecto específico
     * @param int $project_id ID del proyecto
     * @return array Lista de miembros del proyecto
     */
    public static function get_project_members($project_id) {
        $project_id = (int)$project_id;
        
        $sql = "SELECT pm.*, u.nombre, u.email 
                FROM project_members pm
                INNER JOIN sis_user u ON pm.user_id = u.id
                WHERE pm.project_id = $project_id
                ORDER BY pm.role DESC, pm.joined_at ASC";
        
        return seleccion($sql);
    }
    
    /**
     * Buscar usuarios en el sistema
     * @param string $search_term Término de búsqueda
     * @return array Lista de usuarios encontrados
     */
    public static function search_users($search_term) {
        $search_term = addslashes($search_term);
        $term = "%{$search_term}%";
        
        $sql = "SELECT id, nombre, email FROM sis_user 
                WHERE (nombre LIKE '$term' OR email LIKE '$term' OR id LIKE '$term') 
                AND active = 1 
                LIMIT 10";
        
        return seleccion($sql);
    }
    
    /**
     * Verificar si existe un título de proyecto
     * @param string $title Título a verificar
     * @param int $exclude_id ID a excluir de la búsqueda
     * @return bool True si existe, false si no
     */
    public static function title_exists($title, $exclude_id = 0) {
        $title = addslashes($title);
        $exclude_id = (int)$exclude_id;
        
        $sql = "SELECT id FROM registered_projects WHERE project_title = '$title' AND id != $exclude_id";
        $result = seleccion($sql);
        return !empty($result);
    }
    
    /**
     * Obtener proyecto por ID
     * @param int $project_id ID del proyecto
     * @return array|null Datos del proyecto o null si no existe
     */
    public static function get_project_by_id($project_id) {
        $project_id = (int)$project_id;
        
        $sql = "SELECT p.*, pt.type_name, pt.max_members 
                FROM registered_projects p
                INNER JOIN project_types pt ON p.project_type_id = pt.id
                WHERE p.id = $project_id";
        
        $result = seleccion($sql);
        return !empty($result) ? $result[0] : null;
    }
    
    /**
     * Actualizar estado de proyecto
     * NOTA: El archivado automático (HU-027) se ejecuta en tfg_update_status.php
     * cuando se aprueba la propuesta en el panel de revisión.
     * 
     * @param int $project_id ID del proyecto
     * @param string $status Nuevo estado
     * @return array Resultado de la operación
     */
    public static function update_project_status($project_id, $status) {
        // Usar constantes si están definidas, sino usar array hardcoded para compatibilidad
        $valid_statuses = defined('PROJECT_VALID_STATUSES') ? PROJECT_VALID_STATUSES : 
            ['Borrador', 'Registrado', 'Aprobado', 'Rechazado', 'Vigente', 'Prórroga Activa', 'Concluido', 'Cancelado'];
        
        if (!in_array($status, $valid_statuses)) {
            return ['success' => false, 'message' => 'Estado no válido'];
        }
        
        $status_escaped = addslashes($status);
        $project_id = (int)$project_id;
        
        // Actualización de estado
        $sql = "UPDATE registered_projects SET status = '$status_escaped', updated_at = NOW() WHERE id = $project_id";
        $result = transaccion($sql);
        
        if ($result && !empty($result)) {
            return ['success' => true, 'message' => 'Estado actualizado correctamente'];
        } else {
            return ['success' => false, 'message' => 'Error al actualizar el estado'];
        }
    }
    
    // Mantener métodos con nombres originales para compatibilidad (deprecated)
    public static function getProjectTypes() {
        return self::get_project_types();
    }
    
    public static function validateGroupSize($project_type_id, $member_count) {
        return self::validate_group_size($project_type_id, $member_count);
    }
    
    public static function createProject($data) {
        return self::create_project($data);
    }
    
    public static function getProjectsByUser($user_id) {
        return self::get_projects_by_user($user_id);
    }
    
    public static function getProjectMembers($project_id) {
        return self::get_project_members($project_id);
    }
    
    public static function searchUsers($search_term) {
        return self::search_users($search_term);
    }
    
    public static function titleExists($title, $exclude_id = 0) {
        return self::title_exists($title, $exclude_id);
    }
    
    public static function getProjectById($project_id) {
        return self::get_project_by_id($project_id);
    }
    
    public static function updateProjectStatus($project_id, $status) {
        return self::update_project_status($project_id, $status);
    }
}
?>