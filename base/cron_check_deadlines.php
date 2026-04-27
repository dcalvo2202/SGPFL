<?php
// Script para enviar alertas y correos por vencimiento de plazo de entrega de documento final
// Ejecutar desde cron/programador de tareas
//noreply@una.cr 


require_once __DIR__ . '/config.inc';
require_once __DIR__ . '/inc/deadline_functions.php';
require_once __DIR__ . '/inc/alert_functions.php';

// Diagnóstico opcional: php cron_check_deadlines.php --debug-load
$debug_load = isset($argv) && in_array('--debug-load', $argv, true);
if ($debug_load) {
    $deadlineFile = __DIR__ . '/inc/deadline_functions.php';
    echo "[debug] PHP: " . PHP_VERSION . PHP_EOL;
    echo "[debug] include deadline_functions: " . realpath($deadlineFile) . PHP_EOL;
    echo "[debug] md5(deadline_functions): " . md5_file($deadlineFile) . PHP_EOL;

    if (function_exists('sendDeadlineEmail')) {
        $rf = new ReflectionFunction('sendDeadlineEmail');
        echo "[debug] sendDeadlineEmail definida en: " . $rf->getFileName() . ':' . $rf->getStartLine() . PHP_EOL;
    } else {
        echo "[debug] sendDeadlineEmail NO está definida" . PHP_EOL;
    }

    echo "[debug] opcache.enable_cli: " . ini_get('opcache.enable_cli') . PHP_EOL;
    echo str_repeat('-', 70) . PHP_EOL;
}

// Configuración de conexión (PDO)
try {
    $conn = new PDO('mysql:host=localhost;dbname=base_db;charset=utf8', 'root', '');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    error_log('[cron_check_deadlines] Error de conexión: ' . $e->getMessage());
    exit(1);
}

// Conexión mysqli separada para GoogleCalendarService
$gc_conn = null;
try {
    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/inc/db/bdcommon.inc';
    $gc_mysqli = new mysqli($db_host, $usuario, $clave, $db);
    if (!$gc_mysqli->connect_error) {
        $gc_mysqli->set_charset('utf8');
        $gc_conn = $gc_mysqli;
    }
} catch (\Throwable $gc_init_e) {
    error_log('[cron_check_deadlines] Google Calendar init: ' . $gc_init_e->getMessage());
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

    $stmt2 = $conn->prepare('SELECT nombre FROM proyecto_aprobado WHERE id_aprobado = ? LIMIT 1');
    $stmt2->execute([$project_id]);
    $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
    $titulo = $row2 ? $row2['nombre'] : 'Proyecto TFG';

    // Registrar alerta interna
    registerDeadlineAlert($conn, $user_id, $titulo, $fecha_limite, $dias_restantes, $project_id);
    $log[] = "Alerta interna registrada para usuario $user_id, proyecto $project_id, días $dias_restantes";

    // Enviar correo
    $stmt3 = $conn->prepare('SELECT email FROM sis_user WHERE id = ? LIMIT 1');
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

            // Solo marcar como enviada cuando el correo se entregó correctamente.
            markAlertAsSent($conn, $user_id, $project_id, $dias_restantes);

            // Sincronizar con Google Calendar si el usuario tiene sync activo
            if ($gc_conn !== null && class_exists('Service\GoogleCalendarService')) {
                try {
                    $gcService = new Service\GoogleCalendarService($gc_conn);
                    if ($gcService->isSyncEnabled($user_id)) {
                        $deadlineData = [
                            'project_name'  => $titulo,
                            'deadline_date' => $fecha_limite,
                            'description'   => 'Quedan ' . $dias_restantes . ' día(s) para la fecha límite de entrega del TFG.',
                        ];
                        $gcService->syncDeadline($user_id, $project_id . '_' . $dias_restantes, $deadlineData);
                        $log[] = "Google Calendar sync (deadline) para usuario $user_id";
                    }
                } catch (\Throwable $gc_e) {
                    error_log('[cron_check_deadlines] Google Calendar sync: ' . $gc_e->getMessage());
                    $log[] = "Error Google Calendar sync para usuario $user_id: " . $gc_e->getMessage();
                }
            }
        } else {
            $log[] = "Error al enviar correo a $email";
        }
    } else {
        $log[] = "No se encontró email para usuario $user_id";
    }
}

// Registrar log de ejecución
$log_file = __DIR__ . '/cron_check_deadlines.log';
file_put_contents($log_file, date('Y-m-d H:i:s') . "\n" . implode("\n", $log) . "\n\n", FILE_APPEND);
