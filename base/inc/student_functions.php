<?php
/**
 * Funciones para manejo de estudiantes
 * Basado en la lógica existente de search_students.php
 */

/**
 * Obtiene todos los estudiantes de la base de datos
 * @return array Lista de estudiantes con id, nombre y email
 */
function getAllStudents() {
    include_once(__DIR__ . "/db/db.php");
    
    $sql = "SELECT u.id, u.nombre, u.email 
            FROM sis_user u 
            INNER JOIN sis_login l ON u.id = l.id 
            WHERE l.id_roll = 4 
            ORDER BY u.nombre";
    
    return seleccion($sql);
}

/**
 * Obtiene un estudiante por su ID
 * @param string $student_id ID del estudiante
 * @return array|null Datos del estudiante o null si no existe
 */
function getStudentById($student_id) {
    include_once(__DIR__ . "/db/db.php");
    
    $sql = "SELECT u.id, u.nombre, u.email 
            FROM sis_user u 
            INNER JOIN sis_login l ON u.id = l.id 
            WHERE l.id_roll = 4 AND u.id = '" . mysqli_real_escape_string($GLOBALS['id_con'], $student_id) . "'";
    
    $resultado = seleccion($sql);
    return ($resultado && count($resultado) > 0) ? $resultado[0] : null;
}

/**
 * Obtiene estudiantes por una lista de IDs
 * @param array $student_ids Lista de IDs de estudiantes
 * @return array Lista de estudiantes encontrados
 */
function getStudentsByIds($student_ids) {
    if (empty($student_ids)) {
        return [];
    }
    
    include_once(__DIR__ . "/db/db.php");
    
    $ids_escaped = array_map(function($id) {
        return "'" . mysqli_real_escape_string($GLOBALS['id_con'], $id) . "'";
    }, $student_ids);
    
    $ids_string = implode(',', $ids_escaped);
    
    $sql = "SELECT u.id, u.nombre, u.email 
            FROM sis_user u 
            INNER JOIN sis_login l ON u.id = l.id 
            WHERE l.id_roll = 4 AND u.id IN ($ids_string)
            ORDER BY u.nombre";
    
    return seleccion($sql);
}

/**
 * Cuenta el total de estudiantes registrados
 * @return int Número total de estudiantes
 */
function countStudents() {
    include_once(__DIR__ . "/db/db.php");
    
    $sql = "SELECT COUNT(u.id) as total
            FROM sis_user u 
            INNER JOIN sis_login l ON u.id = l.id 
            WHERE l.id_roll = 4";
    
    $resultado = seleccion($sql);
    return ($resultado && count($resultado) > 0) ? (int)$resultado[0]['total'] : 0;
}

/**
 * Genera una tabla HTML con la lista de estudiantes
 * @param array $students Lista de estudiantes
 * @param bool $show_checkboxes Si mostrar checkboxes para selección
 * @param string $checkbox_name Nombre del campo checkbox
 * @return string HTML de la tabla
 */
function renderStudentsTable($students, $show_checkboxes = false, $checkbox_name = 'student_ids[]') {
    if (empty($students)) {
        return '<p class="text-warning">⚠️ No hay estudiantes registrados en la base de datos.</p>';
    }
    
    $html = '<div class="table-responsive">';
    $html .= '<table class="table table-striped table-bordered">';
    $html .= '<thead class="table-light">';
    $html .= '<tr>';
    
    if ($show_checkboxes) {
        $html .= '<th width="5%"><input type="checkbox" id="select_all" title="Seleccionar todos"></th>';
    }
    
    $html .= '<th width="15%">ID</th>';
    $html .= '<th width="50%">Nombre</th>';
    $html .= '<th width="30%">Email</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    
    foreach ($students as $student) {
        $html .= '<tr>';
        
        if ($show_checkboxes) {
            $html .= '<td class="text-center">';
            $html .= '<input type="checkbox" name="' . $checkbox_name . '" value="' . htmlspecialchars($student['id']) . '" class="student-checkbox">';
            $html .= '</td>';
        }
        
        $html .= '<td>' . htmlspecialchars($student['id']) . '</td>';
        $html .= '<td>' . htmlspecialchars($student['nombre']) . '</td>';
        $html .= '<td>' . htmlspecialchars($student['email']) . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</div>';
    
    if ($show_checkboxes) {
        $html .= '
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Seleccionar/deseleccionar todos
            document.getElementById("select_all").addEventListener("change", function() {
                const checkboxes = document.querySelectorAll(".student-checkbox");
                checkboxes.forEach(cb => cb.checked = this.checked);
            });
            
            // Actualizar estado del checkbox "seleccionar todos"
            document.querySelectorAll(".student-checkbox").forEach(cb => {
                cb.addEventListener("change", function() {
                    const allCheckboxes = document.querySelectorAll(".student-checkbox");
                    const checkedCheckboxes = document.querySelectorAll(".student-checkbox:checked");
                    document.getElementById("select_all").checked = allCheckboxes.length === checkedCheckboxes.length;
                });
            });
        });
        </script>';
    }
    
    return $html;
}

/**
 * Genera opciones de select HTML para estudiantes
 * @param array $students Lista de estudiantes
 * @param string $selected_id ID del estudiante seleccionado (opcional)
 * @return string HTML con las opciones
 */
function renderStudentsOptions($students, $selected_id = '') {
    $html = '<option value="">-- Seleccionar estudiante --</option>';
    
    foreach ($students as $student) {
        $selected = ($student['id'] == $selected_id) ? 'selected' : '';
        $html .= '<option value="' . htmlspecialchars($student['id']) . '" ' . $selected . '>';
        $html .= htmlspecialchars($student['nombre']) . ' (' . htmlspecialchars($student['id']) . ')';
        $html .= '</option>';
    }
    
    return $html;
}

/**
 * Obtiene todos los compañeros de grupo de un estudiante
 * Busca en la tabla project_members para encontrar otros estudiantes
 * que pertenezcan al mismo proyecto/grupo TFG
 * 
 * @param mysqli $conn Conexión a la base de datos
 * @param string $student_id ID del estudiante principal
 * @return array Lista de estudiantes del grupo (incluyendo al principal)
 *               Cada elemento tiene: id, nombre, email, role, project_id, is_primary
 */
function getGroupMembersByStudentId($conn, $student_id) {
    $student_id = $conn->real_escape_string($student_id);
    
    // Buscar el proyecto donde participa el estudiante
    $sql_project = "SELECT DISTINCT pm.project_id 
                    FROM project_members pm 
                    WHERE pm.user_id = '$student_id' 
                    AND pm.status = 'Activo'";
    
    $result_project = $conn->query($sql_project);
    
    if (!$result_project || $result_project->num_rows === 0) {
        // El estudiante no tiene proyecto, retornar solo al estudiante principal
        $sql_single = "SELECT u.id, u.nombre, u.email, 'N/A' as role, NULL as project_id, 1 as is_primary
                       FROM sis_user u 
                       WHERE u.id = '$student_id'";
        $result_single = $conn->query($sql_single);
        
        if ($result_single && $result_single->num_rows > 0) {
            return [$result_single->fetch_assoc()];
        }
        return [];
    }
    
    // Obtener todos los project_ids donde participa
    $project_ids = [];
    while ($row = $result_project->fetch_assoc()) {
        $project_ids[] = (int)$row['project_id'];
    }
    
    $project_ids_str = implode(',', $project_ids);
    
    // Buscar todos los miembros de esos proyectos
    $sql_members = "SELECT u.id, u.nombre, u.email, pm.role, pm.project_id,
                           CASE WHEN u.id = '$student_id' THEN 1 ELSE 0 END as is_primary
                    FROM project_members pm
                    INNER JOIN sis_user u ON pm.user_id = u.id
                    WHERE pm.project_id IN ($project_ids_str)
                    AND pm.status = 'Activo'
                    ORDER BY is_primary DESC, pm.role DESC, u.nombre ASC";
    
    $result_members = $conn->query($sql_members);
    
    $members = [];
    if ($result_members) {
        while ($row = $result_members->fetch_assoc()) {
            $members[] = $row;
        }
    }
    
    // Si no se encontraron miembros, retornar al menos al estudiante principal
    if (empty($members)) {
        $sql_single = "SELECT u.id, u.nombre, u.email, 'N/A' as role, NULL as project_id, 1 as is_primary
                       FROM sis_user u 
                       WHERE u.id = '$student_id'";
        $result_single = $conn->query($sql_single);
        
        if ($result_single && $result_single->num_rows > 0) {
            return [$result_single->fetch_assoc()];
        }
    }
    
    return $members;
}

/**
 * Vincula un asesor externo con todos los estudiantes de un grupo
 * Inserta registros en la tabla external_advisor_linked_students
 * 
 * @param mysqli $conn Conexión a la base de datos
 * @param int $advisor_request_id ID de la solicitud del asesor externo
 * @param string $primary_student_id ID del estudiante principal (seleccionado en registro)
 * @return array Resultado con success, message, linked_count
 */
function linkAdvisorToGroupMembers($conn, $advisor_request_id, $primary_student_id) {
    $advisor_request_id = (int)$advisor_request_id;
    $primary_student_id = $conn->real_escape_string($primary_student_id);
    
    // Obtener todos los miembros del grupo
    $group_members = getGroupMembersByStudentId($conn, $primary_student_id);
    
    if (empty($group_members)) {
        return [
            'success' => false,
            'message' => 'No se encontraron estudiantes para vincular',
            'linked_count' => 0
        ];
    }
    
    $linked_count = 0;
    $errors = [];
    
    // Verificar si la tabla existe
    $table_check = $conn->query("SHOW TABLES LIKE 'external_advisor_linked_students'");
    if (!$table_check || $table_check->num_rows === 0) {
        // La tabla no existe, crearla
        $create_table_sql = "
        CREATE TABLE IF NOT EXISTS `external_advisor_linked_students` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `advisor_request_id` int(11) DEFAULT NULL,
            `internal_advisor_id` varchar(50) DEFAULT NULL,
            `student_id` varchar(50) NOT NULL,
            `is_primary` tinyint(1) DEFAULT 0,
            `project_id` int(11) DEFAULT NULL,
            `linked_at` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_advisor_student` (`advisor_request_id`, `student_id`),
            UNIQUE KEY `unique_internal_advisor_student` (`internal_advisor_id`, `student_id`),
            KEY `idx_advisor_request` (`advisor_request_id`),
            KEY `idx_internal_advisor` (`internal_advisor_id`),
            KEY `idx_student` (`student_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci";
        
        if (!$conn->query($create_table_sql)) {
            return [
                'success' => false,
                'message' => 'Error al crear la tabla de vinculación: ' . $conn->error,
                'linked_count' => 0
            ];
        }
    }
    
    // Preparar la consulta de inserción
    $stmt = $conn->prepare("INSERT INTO external_advisor_linked_students 
                            (advisor_request_id, student_id, is_primary, project_id, linked_at)
                            VALUES (?, ?, ?, ?, NOW())
                            ON DUPLICATE KEY UPDATE linked_at = NOW()");
    
    if (!$stmt) {
        return [
            'success' => false,
            'message' => 'Error al preparar consulta de vinculación: ' . $conn->error,
            'linked_count' => 0
        ];
    }
    
    foreach ($group_members as $member) {
        $is_primary = ($member['id'] === $primary_student_id) ? 1 : 0;
        $project_id = !empty($member['project_id']) ? (int)$member['project_id'] : null;
        
        $stmt->bind_param('isii', $advisor_request_id, $member['id'], $is_primary, $project_id);
        
        if ($stmt->execute()) {
            $linked_count++;
            error_log("VINCULACIÓN: Asesor $advisor_request_id vinculado a estudiante {$member['id']} (primary: $is_primary)");
        } else {
            $errors[] = "Error vinculando estudiante {$member['id']}: " . $stmt->error;
        }
    }
    
    $stmt->close();
    
    return [
        'success' => $linked_count > 0,
        'message' => $linked_count > 0 
            ? "Se vinculó al asesor con $linked_count estudiante(s) del grupo" 
            : "No se pudo vincular al asesor con ningún estudiante",
        'linked_count' => $linked_count,
        'group_members' => $group_members,
        'errors' => $errors
    ];
}

/**
 * Obtiene todos los estudiantes vinculados a un asesor externo
 * 
 * @param mysqli $conn Conexión a la base de datos
 * @param int $advisor_request_id ID de la solicitud del asesor externo
 * @return array Lista de estudiantes vinculados
 */
function getLinkedStudentsByAdvisor($conn, $advisor_request_id) {
    $advisor_request_id = (int)$advisor_request_id;
    
    // Verificar si la tabla existe
    $table_check = $conn->query("SHOW TABLES LIKE 'external_advisor_linked_students'");
    if (!$table_check || $table_check->num_rows === 0) {
        // La tabla no existe, usar el campo linked_student_id de la tabla original
        $sql = "SELECT u.id, u.nombre, u.email, 1 as is_primary, NULL as project_id
                FROM external_advisor_profile_requests ear
                INNER JOIN sis_user u ON ear.linked_student_id = u.id
                WHERE ear.id = $advisor_request_id";
        
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            return [$result->fetch_assoc()];
        }
        return [];
    }
    
    $sql = "SELECT u.id, u.nombre, u.email, eals.is_primary, eals.project_id
            FROM external_advisor_linked_students eals
            INNER JOIN sis_user u ON eals.student_id = u.id
            WHERE eals.advisor_request_id = $advisor_request_id
            ORDER BY eals.is_primary DESC, u.nombre ASC";
    
    $result = $conn->query($sql);
    
    $students = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
    }
    
    return $students;
}

/**
 * Obtiene todos los estudiantes vinculados a un asesor externo por su ID de usuario
 * (Útil cuando el asesor ya tiene cuenta en el sistema)
 * 
 * @param mysqli $conn Conexión a la base de datos
 * @param string $advisor_user_id ID de usuario del asesor (cédula)
 * @return array Lista de estudiantes vinculados
 */
function getLinkedStudentsByAdvisorUserId($conn, $advisor_user_id) {
    $advisor_user_id = $conn->real_escape_string($advisor_user_id);
    
    // Primero obtener el ID de la solicitud del asesor
    $sql_request = "SELECT id FROM external_advisor_profile_requests 
                    WHERE applicant_id = '$advisor_user_id' AND status = 'Aprobado'";
    
    $result_request = $conn->query($sql_request);
    
    if (!$result_request || $result_request->num_rows === 0) {
        return [];
    }
    
    $advisor_request_id = (int)$result_request->fetch_assoc()['id'];
    
    return getLinkedStudentsByAdvisor($conn, $advisor_request_id);
}
?>
