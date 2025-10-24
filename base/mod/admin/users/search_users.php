<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Ruta relativa a bdcommon.inc
$base_path = realpath(__DIR__ . '/../../../');
include $base_path . '/inc/db/bdcommon.inc';

$search_term = $_GET['term'] ?? '';

if (strlen($search_term) < 2) {
    echo json_encode([], JSON_UNESCAPED_UNICODE);
    exit;
}

// Función para formatear nombres a Title Case
function formatearNombre($nombre) {
    // Convertir a minúsculas primero
    $nombre = mb_strtolower($nombre, 'UTF-8');
    
    // Convertir primera letra de cada palabra a mayúscula
    $palabras = explode(' ', $nombre);
    $palabrasFormateadas = array_map(function($palabra) {
        return mb_convert_case($palabra, MB_CASE_TITLE, 'UTF-8');
    }, $palabras);
    
    return implode(' ', $palabrasFormateadas);
}

try {
    $conn = new mysqli($db_host, $usuario, $clave, $db);
    if ($conn->connect_error) {
        throw new Exception("Conexión fallida: " . $conn->connect_error);
    }
    $conn->set_charset("utf8");
    
    // Obtener el usuario actual del sistema de sesiones
    $current_user = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : '';
    
    // Buscar SOLO ESTUDIANTES (id_roll = 4) en la base de datos
    // Búsqueda que ignora acentos y mayúsculas
    $sql = "SELECT u.id, u.nombre, u.email 
            FROM sis_user u 
            INNER JOIN sis_login l ON u.id = l.id 
            WHERE (u.nombre LIKE ? 
                   OR u.email LIKE ? 
                   OR u.id LIKE ?)
            AND l.id_roll = 4
            AND u.id != ?
            ORDER BY u.nombre 
            LIMIT 15";
    
    $search_pattern = "%$search_term%";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error al preparar consulta: " . $conn->error);
    }
    
    $stmt->bind_param("ssss", $search_pattern, $search_pattern, $search_pattern, $current_user);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $users = [];
    
    while ($row = $result->fetch_assoc()) {
        $users[] = [
            'id' => $row['id'],
            'nombre' => formatearNombre($row['nombre']),
            'email' => $row['email'] ?? 'sin-email@una.ac.cr'
        ];
    }
    
    $stmt->close();
    $conn->close();
    
    echo json_encode($users, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    error_log("Error en búsqueda de usuarios: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>