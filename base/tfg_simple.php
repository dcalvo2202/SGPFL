<?php
// Versión simplificada para debug
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

function respond_json($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Verificar autenticación básica con sesiones PHP estándar
if (!isset($_SESSION['user_id']) || $_SESSION['id_roll'] != 4) {
    respond_json(false, 'Acceso no autorizado. Solo estudiantes pueden crear propuestas TFG');
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond_json(false, 'Método no permitido');
}

// DEBUG: Mostrar todos los datos POST que están llegando
error_log("=== DEBUG POST DATA SIMPLE ===");
error_log("Raw POST: " . print_r($_POST, true));
error_log("Method: " . $_SERVER['REQUEST_METHOD']);

// Validar datos requeridos
$required_fields = ['title', 'description', 'project_type_id'];
foreach ($required_fields as $field) {
    $value = $_POST[$field] ?? '';
    $trimmed = trim($value);
    error_log("Campo '$field': existe=" . (isset($_POST[$field]) ? 'SI' : 'NO') . ", valor='" . $value . "', trimmed='" . $trimmed . "'");
    
    if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
        respond_json(false, "El campo '$field' es requerido. Valor recibido: '" . ($value ?? 'NULL') . "'");
    }
}

// Si llegamos aquí, todos los campos requeridos están presentes
$user_id = $_SESSION['user_id'];
$title = trim($_POST['title']);
$description = trim($_POST['description']);

respond_json(true, "Formulario válido - Todos los campos requeridos están presentes", [
    'user_id' => $user_id,
    'title' => $title,
    'description_length' => strlen($description),
    'fields_received' => array_keys($_POST)
]);
?>