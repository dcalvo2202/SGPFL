<?php
// Script para enviar alertas y correos por vencimiento de plazo de entrega de documento final
// Ejecutar desde cron/programador de tareas

require_once __DIR__ . '/config.inc';
require_once __DIR__ . '/inc/deadline_functions.php';
require_once __DIR__ . '/inc/alert_functions.php';

// Configuración de conexión (PDO)
try {
    $conn = new PDO('mysql:host=localhost;dbname=base;charset=utf8', 'root', '');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    error_log('[cron_check_deadlines] Error de conexión: ' . $e->getMessage());
    exit(1);
}

$log = [];
$proyectos = getProjectsNearDeadline($conn);
$log[] = 'Proyectos próximos a vencer: ' . count($proyectos);

foreach ($proyectos as $p) {
    $user_id = $p['user_id'];
    $project_id = $p['project_id'];
    $dias_restantes = $p['dias_restantes'];
    $fecha_base = $p['fecha_base'];
    $prorrogas = $p['prorrogas'];
    $fecha_limite = calculateRealDeadline($fecha_base, $prorrogas);

    // Verificar duplicado
    if (hasAlertBeenSent($conn, $user_id, $project_id, $dias_restantes)) {
        $log[] = "Alerta ya enviada: usuario $user_id, proyecto $project_id, días $dias_restantes";
        continue;
    }

    // Obtener datos del usuario y proyecto
    $stmt = $conn->prepare('SELECT nombre FROM sis_user WHERE id = ? LIMIT 1');
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $nombre = $row ? $row['nombre'] : 'Estudiante';

    $stmt2 = $conn->prepare('SELECT titulo FROM proyecto_aprobado WHERE id = ? LIMIT 1');
    $stmt2->execute([$project_id]);
    $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
    $titulo = $row2 ? $row2['titulo'] : 'Proyecto TFG';

    // Registrar alerta interna
    registerDeadlineAlert($conn, $user_id, $titulo, $fecha_limite, $dias_restantes, $project_id);
    $log[] = "Alerta interna registrada para usuario $user_id, proyecto $project_id, días $dias_restantes";

    // Enviar correo
    $stmt3 = $conn->prepare('SELECT email FROM sis_login WHERE id = ? LIMIT 1');
    $stmt3->execute([$user_id]);
    $row3 = $stmt3->fetch(PDO::FETCH_ASSOC);
    $email = $row3 ? $row3['email'] : null;
    if ($email) {
        $data = [
            'nombre' => $nombre,
            'proyecto' => $titulo,
            'fecha_limite' => $fecha_limite,
            'dias_restantes' => $dias_restantes
        ];
        if (sendDeadlineEmail($email, $data)) {
            $log[] = "Correo enviado a $email";
        } else {
            $log[] = "Error al enviar correo a $email";
        }
    } else {
        $log[] = "No se encontró email para usuario $user_id";
    }

    // Marcar alerta como enviada
    markAlertAsSent($conn, $user_id, $project_id, $dias_restantes);
}

// Registrar log de ejecución
$log_file = __DIR__ . '/cron_check_deadlines.log';
file_put_contents($log_file, date('Y-m-d H:i:s') . "\n" . implode("\n", $log) . "\n\n", FILE_APPEND);
