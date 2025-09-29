<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Ruta relativa a bdcommon.inc
$base_path = realpath(__DIR__ . '/../../../');
include $base_path . '/inc/db/bdcommon.inc';

$search_term = $_GET['term'] ?? '';

if (strlen($search_term) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $conn = new mysqli($db_host, $usuario, $clave, $db);
    if ($conn->connect_error) {
        throw new Exception("Conexión fallida: " . $conn->connect_error);
    }
    $conn->set_charset("utf8");
    
    // Buscar usuarios en la base de datos
    $sql = "SELECT u.id, u.nombre, u.email 
            FROM sis_user u 
            INNER JOIN sis_login l ON u.id = l.id 
            WHERE (u.nombre LIKE ? OR u.email LIKE ? OR u.id LIKE ?)
            AND u.id != ?
            ORDER BY u.nombre 
            LIMIT 15";
    
    $search_pattern = "%$search_term%";
    $current_user = $_SESSION['id'] ?? '112170040';
    
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
            'nombre' => $row['nombre'],
            'email' => $row['email'] ?? 'sin-email@una.ac.cr'
        ];
    }
    
    $stmt->close();
    $conn->close();
    
    echo json_encode($users);
    
} catch (Exception $e) {
    error_log("Error en búsqueda de usuarios: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
?>