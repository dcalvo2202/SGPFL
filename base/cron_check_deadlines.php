<?php
// Script para enviar alertas y correos por vencimiento de plazo de entrega de documento final
// Ejecutar desde cron/programador de tareas
//noreply@una.cr 


require_once __DIR__ . '/config.inc';
require_once __DIR__ . '/inc/deadline_functions.php';
require_once __DIR__ . '/inc/alert_functions.php';

// Diagnostico opcional: php cron_check_deadlines.php --debug-load
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
        echo "[debug] sendDeadlineEmail NO esta definida" . PHP_EOL;
    }

    echo "[debug] opcache.enable_cli: " . ini_get('opcache.enable_cli') . PHP_EOL;
    echo str_repeat('-', 70) . PHP_EOL;
}

// Configuracion de conexion (PDO)
try {
    $conn = new PDO('mysql:host=localhost;dbname=base_db;charset=utf8', 'root', '');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    error_log('[cron_check_deadlines] Error de conexion: ' . $e->getMessage());
    exit(1);
}

// Conexion mysqli separada para GoogleCalendarService
$gc_conn = null;
try {
    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/inc/db/bdcommon.inc';
    $_gc_host = isset($db_host) ? $db_host : 'localhost';
    $_gc_user = isset($usuario) ? $usuario : 'root';
    $_gc_pass = isset($clave)   ? $clave   : '';
    $_gc_db   = isset($db)      ? $db      : 'base_db';
    $gc_mysqli = new mysqli($_gc_host, $_gc_user, $_gc_pass, $_gc_db);
    if (!$gc_mysqli->connect_error) {
        $gc_mysqli->set_charset('utf8');
        $gc_conn = $gc_mysqli;
    }
} catch (\Throwable $gc_init_e) {
    error_log('[cron_check_deadlines] Google Calendar init: ' . $gc_init_e->getMessage());
}

$log = [];
$proyectos = getProjectsNearDeadline($conn);
$log[] = 'Proyectos proximos a vencer: ' . count($proyectos);

foreach ($proyectos as $p) {
    $user_id = $p['user_id'];
    $project_id = $p['project_id'];
    $dias_restantes = $p['dias_restantes'];
    $fecha_base = $p['fecha_base'];
    $prorrogas = $p['prorrogas'];
    $fecha_limite = calculateRealDeadline($fecha_base, $prorrogas);

    // Verificar duplicado
    if (hasAlertBeenSent($conn, $user_id, $project_id, $dias_restantes)) {
        $log[] = "Alerta ya enviada: usuario $user_id, proyecto $project_id, dias $dias_restantes";
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
    $log[] = "Alerta interna registrada para usuario $user_id, proyecto $project_id, dias $dias_restantes";

    // Enviar correo
    $stmt3 = $conn->prepare('SELECT email FROM sis_user WHERE id = ? LIMIT 1');
    $stmt3->execute([$user_id]);
    $row3 = $stmt3->fetch(PDO::FETCH_ASSOC);
    $email = $row3 ? $row3['email'] : null;
    if ($email) {
        $data = [
            'nombre'        => $nombre,
            'proyecto'      => $titulo,
            'fecha_limite'  => $fecha_limite,
            'dias_restantes'=> $dias_restantes
        ];

        if (sendDeadlineEmail($email, $data)) {
            $log[] = "Correo enviado a $email";

            // Solo marcar como enviada cuando el correo se entrego correctamente.
            markAlertAsSent($conn, $user_id, $project_id, $dias_restantes);

            // Sincronizar con Google Calendar para TODOS los usuarios del proyecto
            if ($gc_conn !== null && class_exists('Service\GoogleCalendarService')) {
                try {
                    $gcService = new Service\GoogleCalendarService($gc_conn);

                    // 1. Obtener propuesta_id del proyecto
                    $stmtProposal = $gc_conn->prepare("SELECT proposal_id FROM proyecto_aprobado WHERE id_aprobado = ? LIMIT 1");
                    if ($stmtProposal) {
                        $stmtProposal->bind_param('i', $project_id);
                        $stmtProposal->execute();
                        $rsProposal  = $stmtProposal->get_result();
                        $proposalData = $rsProposal->fetch_assoc();
                        $proposal_id  = (int) ($proposalData['proposal_id'] ?? 0);
                        $stmtProposal->close();

                        if ($proposal_id > 0) {
                            // 2. Recopilar todos los usuarios asociados al proyecto
                            $allProjectUsers = [];

                            // Dueno de la propuesta
                            $stmtOwner = $gc_conn->prepare("SELECT user_id FROM tfg_proposals WHERE id = ? LIMIT 1");
                            if ($stmtOwner) {
                                $stmtOwner->bind_param('i', $proposal_id);
                                $stmtOwner->execute();
                                $rsOwner = $stmtOwner->get_result();
                                if ($rowOwner = $rsOwner->fetch_assoc()) {
                                    $uid = (string) ($rowOwner['user_id'] ?? '');
                                    if ($uid !== '') $allProjectUsers[$uid] = true;
                                }
                                $stmtOwner->close();
                            }

                            // Miembros activos del proyecto registrado
                            $stmtMembers = $gc_conn->prepare("
                                SELECT DISTINCT pm.user_id
                                FROM registered_projects rp
                                INNER JOIN project_members pm ON pm.project_id = rp.id AND pm.status = 'Activo'
                                WHERE rp.tfg_proposal_id = ?
                            ");
                            if ($stmtMembers) {
                                $stmtMembers->bind_param('i', $proposal_id);
                                $stmtMembers->execute();
                                $rsMembers = $stmtMembers->get_result();
                                while ($rowMember = $rsMembers->fetch_assoc()) {
                                    $uid = (string) ($rowMember['user_id'] ?? '');
                                    if ($uid !== '') $allProjectUsers[$uid] = true;
                                }
                                $stmtMembers->close();
                            }

                            // Miembros del comite
                            $stmtComite = $gc_conn->prepare("
                                SELECT DISTINCT c.tutor, c.asesor_1, c.asesor_2
                                FROM proyecto_aprobado pa
                                INNER JOIN comite c ON c.Id = pa.comite_id
                                WHERE pa.id_aprobado = ?
                            ");
                            if ($stmtComite) {
                                $stmtComite->bind_param('i', $project_id);
                                $stmtComite->execute();
                                $rsComite = $stmtComite->get_result();
                                if ($rowComite = $rsComite->fetch_assoc()) {
                                    foreach (['tutor', 'asesor_1', 'asesor_2'] as $role) {
                                        $uid = (string) ($rowComite[$role] ?? '');
                                        if ($uid !== '') $allProjectUsers[$uid] = true;
                                    }
                                }
                                $stmtComite->close();
                            }

                            // 3. Sincronizar para cada usuario
                            $deadlineData = [
                                'project_name'  => $titulo,
                                'deadline_date' => $fecha_limite,
                                'description'   => 'Quedan ' . $dias_restantes . ' dia(s) para la fecha limite de entrega del TFG.',
                            ];

                            foreach (array_keys($allProjectUsers) as $gc_user_id) {
                                $gc_user_id = (string) $gc_user_id;
                                if ($gcService->isSyncEnabled($gc_user_id)) {
                                    try {
                                        $gcService->syncDeadline($gc_user_id, $project_id . '_' . $dias_restantes, $deadlineData);
                                        $log[] = "Google Calendar sync (deadline) para usuario $gc_user_id";
                                    } catch (\Throwable $gc_e) {
                                        error_log('[cron_check_deadlines] GC sync usuario ' . $gc_user_id . ': ' . $gc_e->getMessage());
                                        $log[] = "Error Google Calendar sync para usuario $gc_user_id: " . $gc_e->getMessage();
                                    }
                                }
                            }
                        }
                    }
                } catch (\Throwable $gc_e) {
                    error_log('[cron_check_deadlines] Google Calendar sync general: ' . $gc_e->getMessage());
                    $log[] = "Error Google Calendar sync general: " . $gc_e->getMessage();
                }
            }
        } else {
            $log[] = "Error al enviar correo a $email";
        }
    } else {
        $log[] = "No se encontro email para usuario $user_id";
    }
}

// Registrar log de ejecucion
$log_file = __DIR__ . '/cron_check_deadlines.log';
file_put_contents($log_file, date('Y-m-d H:i:s') . "\n" . implode("\n", $log) . "\n\n", FILE_APPEND);