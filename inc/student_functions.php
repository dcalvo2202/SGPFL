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
?>
