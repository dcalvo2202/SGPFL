<?php
/**
 * TEST DE SISTEMA DE NOTIFICACIONES INTERNAS
 * Verifica que las alertas se guarden correctamente
 */

require_once __DIR__ . '/lib/mysession/mySession.class.php';
require_once __DIR__ . '/lib/mysession/mySession.conf.php';
require_once __DIR__ . '/inc/db/bdcommon.inc';
require_once __DIR__ . '/inc/alert_functions.php';

$mySessionController = mySession::getIstance($_MYSESSION_CONF);

// Verificar que sea administrador
$current_user_id = $mySessionController->getVar("usuario");
$current_user_rol = $mySessionController->getVar("rol");

if (!isset($current_user_id)) {
    die('Sin sesión. Por favor autenticate primero.');
}

if ((int)$current_user_rol !== 1) {
    die('Solo administradores pueden ejecutar este test.');
}

// Crear conexión
$conn = new mysqli($db_host, $usuario, $clave, $db);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// ========== TEST 1: Verificar tabla existe ==========
echo "<h2>TEST 1: Verificar tabla user_alerts</h2>";
$check_table = $conn->query("SHOW TABLES LIKE 'user_alerts'");
if ($check_table && $check_table->num_rows > 0) {
    echo "<p style='color:green;'>✓ Tabla user_alerts existe</p>";
} else {
    echo "<p style='color:red;'>✗ Tabla user_alerts NO existe</p>";
}

// ========== TEST 2: Crear alerta de prueba ==========
echo "<h2>TEST 2: Registrar una alerta de prueba</h2>";
$test_user = $current_user_id;
$test_result = registerAlert(
    $conn, 
    $test_user,
    'TEST: Notificación de Prueba',
    'Esta es una notificación de prueba del sistema de alertas. Si ves esto, las notificaciones internas están funcionando correctamente.',
    'Sistema',
    'Media'
);

if ($test_result) {
    echo "<p style='color:green;'>✓ Alerta registrada correctamente</p>";
} else {
    echo "<p style='color:red;'>✗ Error al registrar alerta</p>";
}

// ========== TEST 3: Verificar que la alerta se guardó ==========
echo "<h2>TEST 3: Verificar que la alerta se guardó</h2>";
$sql_check = "SELECT COUNT(*) as count, MAX(id) as last_id FROM user_alerts WHERE user_id = ? AND subject LIKE 'TEST:%'";
$stmt = $conn->prepare($sql_check);
$stmt->bind_param("s", $test_user);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

$count = (int)$row['count'];
if ($count > 0) {
    echo "<p style='color:green;'>✓ Se encontraron " . $count . " alerta(s) de prueba</p>";
    echo "<p>ID de última alerta registrada: " . $row['last_id'] . "</p>";
} else {
    echo "<p style='color:red;'>✗ No se encontró ninguna alerta de prueba</p>";
}

// ========== TEST 4: Verificar notificaciones sin leer ==========
echo "<h2>TEST 4: Contar notificaciones sin leer</h2>";
$unread_count = "SELECT COUNT(*) as count FROM user_alerts WHERE user_id = ? AND read_at IS NULL";
$stmt = $conn->prepare($unread_count);
$stmt->bind_param("s", $test_user);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

echo "<p>Total de notificaciones sin leer: " . $row['count'] . "</p>";

// ========== TEST 5: Listar últimas 5 alertas ==========
echo "<h2>TEST 5: Últimas 5 alertas del usuario</h2>";
$sql_list = "SELECT id, subject, message, alert_type, priority, sent_at, read_at 
            FROM user_alerts 
            WHERE user_id = ? 
            ORDER BY sent_at DESC 
            LIMIT 5";
$stmt = $conn->prepare($sql_list);
$stmt->bind_param("s", $test_user);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5' style='width:100%; margin-top:10px;'>";
    echo "<tr>
            <th>ID</th>
            <th>Asunto</th>
            <th>Tipo</th>
            <th>Prioridad</th>
            <th>Enviada</th>
            <th>Leída</th>
          </tr>";
    
    while ($row = $result->fetch_assoc()) {
        $read_status = $row['read_at'] ? '✓ ' . $row['read_at'] : 'No leída';
        echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['subject']}</td>
                <td>{$row['alert_type']}</td>
                <td>{$row['priority']}</td>
                <td>{$row['sent_at']}</td>
                <td>{$read_status}</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:orange;'>No hay alertas para este usuario</p>";
}

$stmt->close();

// ========== TEST 6: Probar registerAlertToRole ==========
echo "<h2>TEST 6: Enviar alerta a un rol específico (Estudiantes)</h2>";
$rol_estudiante = 4;
$alert_count = registerAlertToRole(
    $conn,
    $rol_estudiante,
    'TEST: Alerta a Estudiantes',
    'Esta alerta fue enviada a todos los estudiantes del sistema.',
    'Sistema',
    'Media'
);
echo "<p>Alertas enviadas a rol Estudiante: " . $alert_count . "</p>";

// ========== TEST 7: Función getUserAlerts ==========
echo "<h2>TEST 7: Recuperar alertas del usuario actual</h2>";
$user_alerts = getUserAlerts($conn, $test_user, 5);
echo "<p>Se recuperaron " . count($user_alerts) . " alertas</p>";
if (!empty($user_alerts)) {
    echo "<ul>";
    foreach ($user_alerts as $alert) {
        echo "<li><strong>" . htmlspecialchars($alert['subject']) . "</strong> - " . htmlspecialchars($alert['alert_type']) . "</li>";
    }
    echo "</ul>";
}

// ========== TEST 8: Limpiar alertas de prueba ==========
echo "<h2>TEST 8: Limpiar alertas de prueba</h2>";
$delete_sql = "DELETE FROM user_alerts WHERE user_id = ? AND subject LIKE 'TEST:%'";
$stmt = $conn->prepare($delete_sql);
$stmt->bind_param("s", $test_user);
$result_delete = $stmt->execute();
$deleted_count = $stmt->affected_rows;
$stmt->close();

if ($result_delete) {
    echo "<p style='color:green;'>✓ Se eliminaron " . $deleted_count . " alerta(s) de prueba</p>";
} else {
    echo "<p style='color:red;'>✗ Error al eliminar alertas de prueba</p>";
}

$conn->close();

echo "<hr>";
echo "<h2 style='color:green;'>✓ TEST COMPLETADO</h2>";
echo "<p><a href='dashboard.php'>Volver al Dashboard</a></p>";
?>
