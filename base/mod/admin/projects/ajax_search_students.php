<?php
/**
 * Endpoint AJAX para búsqueda de estudiantes
 * Para uso en register_group.php
 */
include("../../login/check.php");
include("../../../inc/student_functions.php");

header('Content-Type: application/json');

// Verificar que sea petición AJAX
if (!isset($_POST['search_term'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Término de búsqueda requerido']);
    exit;
}

$search_term = trim($_POST['search_term']);

if (strlen($search_term) < 2) {
    echo json_encode([]);
    exit;
}

try {
    // Obtener todos los estudiantes
    $all_students = getAllStudents();
    
    // Filtrar resultados basado en el término de búsqueda
    $filtered_results = array_filter($all_students, function($student) use ($search_term) {
        $search_term_lower = strtolower($search_term);
        return (
            strpos(strtolower($student['nombre']), $search_term_lower) !== false ||
            strpos(strtolower($student['email']), $search_term_lower) !== false ||
            strpos(strtolower($student['id']), $search_term_lower) !== false
        );
    });
    
    // Limitar a 10 resultados máximo
    $filtered_results = array_slice($filtered_results, 0, 10);
    
    echo json_encode(array_values($filtered_results));
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en la búsqueda: ' . $e->getMessage()]);
}
?>
