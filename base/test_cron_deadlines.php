<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/config.inc';
require_once __DIR__ . '/inc/deadline_functions.php';
require_once __DIR__ . '/inc/alert_functions.php';

// ─── Conexión PDO ────────────────────────────────────────────────────────────
$dbError = null;
$conn = null;
try {
    $conn = new PDO('mysql:host=localhost;dbname=base_db;charset=utf8', 'root', '');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    $dbError = $e->getMessage();
}

// ─── Procesar acciones POST ──────────────────────────────────────────────────
$actionLog  = [];
$actionMode = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $actionMode        = $_POST['action'] ?? 'dryrun';
    $emailOverride     = trim($_POST['email_override'] ?? '');
    $registrarEnBD     = isset($_POST['registrar_bd']);
    $forzarUser        = trim($_POST['forzar_user_id'] ?? '');
    $forzarProject     = trim($_POST['forzar_project_id'] ?? '');
    $forzarDias        = (int)($_POST['forzar_dias'] ?? 7);

    if ($actionMode === 'forzar' && $forzarUser !== '' && $forzarProject !== '' && $emailOverride !== '') {
        // ── Modo forzado: envío directo sin depender de datos reales en BD ──
        $stmt = $conn->prepare('SELECT nombre FROM sis_user WHERE id = ? LIMIT 1');
        $stmt->execute([$forzarUser]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $nombre = $row ? $row['nombre'] : 'Estudiante de prueba';

        $stmt2 = $conn->prepare('SELECT nombre FROM proyecto_aprobado WHERE id_aprobado = ? LIMIT 1');
        $stmt2->execute([$forzarProject]);
        $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
        $titulo = $row2 ? $row2['nombre'] : 'Proyecto TFG de prueba';

        $fechaLimite = date('Y-m-d', strtotime("+{$forzarDias} days"));

        $data = [
            'nombre'        => $nombre,
            'proyecto'      => $titulo,
            'fecha_limite'  => $fechaLimite,
            'dias_restantes' => $forzarDias,
        ];

        $sent = sendDeadlineEmail($emailOverride, $data);
        $actionLog[] = ['tipo' => 'forzado', 'ok' => $sent,
            'msg' => $sent
                ? "Correo enviado a <strong>" . htmlspecialchars($emailOverride) . "</strong> para usuario <strong>{$forzarUser}</strong>, proyecto <strong>{$forzarProject}</strong>"
                : "Error al enviar correo a <strong>" . htmlspecialchars($emailOverride) . "</strong> — revisa PHP error_log y sendmail.log"
        ];

        if ($sent && $registrarEnBD) {
            registerDeadlineAlert($conn, $forzarUser, $titulo, $fechaLimite, $forzarDias, (int)$forzarProject);
            markAlertAsSent($conn, $forzarUser, (int)$forzarProject, $forzarDias);
            $actionLog[] = ['tipo' => 'info', 'ok' => true, 'msg' => 'Alerta registrada en BD (deadline_alerts_sent + user_alerts)'];
        }

    } elseif ($actionMode === 'ejecutar' && $emailOverride !== '') {
        // ── Modo ejecutar: corre la lógica real del cron pero redirige correos ──
        $proyectos = getProjectsNearDeadline($conn);
        $actionLog[] = ['tipo' => 'info', 'ok' => true, 'msg' => 'Proyectos detectados por la BD: <strong>' . count($proyectos) . '</strong>'];

        if (empty($proyectos)) {
            $actionLog[] = ['tipo' => 'warn', 'ok' => false,
                'msg' => 'No se detectaron proyectos dentro de los umbrales [' . implode(', ', DEADLINE_THRESHOLDS) . '] días. Usa el modo "Forzar envío" para probar con datos manuales.'];
        }

        foreach ($proyectos as $p) {
            $user_id    = $p['user_id'];
            $project_id = $p['project_id'];
            $dias       = $p['dias_restantes'];
            $fechaLimite = calculateRealDeadline($p['fecha_base'], $p['prorrogas']);

            $yaEnviada = hasAlertBeenSent($conn, $user_id, $project_id, $dias);
            if ($yaEnviada && !$registrarEnBD) {
                $actionLog[] = ['tipo' => 'skip', 'ok' => null,
                    'msg' => "Omitido — alerta ya enviada: usuario {$user_id}, proyecto {$project_id}, días {$dias}"];
                continue;
            }

            $stmt = $conn->prepare('SELECT nombre FROM sis_user WHERE id = ? LIMIT 1');
            $stmt->execute([$user_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $nombre = $row ? $row['nombre'] : 'Estudiante';

            $stmt2 = $conn->prepare('SELECT nombre FROM proyecto_aprobado WHERE id_aprobado = ? LIMIT 1');
            $stmt2->execute([$project_id]);
            $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
            $titulo = $row2 ? $row2['nombre'] : 'Proyecto TFG';

            $data = [
                'nombre'         => $nombre,
                'proyecto'       => $titulo,
                'fecha_limite'   => $fechaLimite,
                'dias_restantes' => $dias,
            ];

            // Enviar siempre al email override, no al real del estudiante
            $sent = sendDeadlineEmail($emailOverride, $data);
            $actionLog[] = ['tipo' => 'correo', 'ok' => $sent,
                'msg' => $sent
                    ? "Correo enviado a <strong>" . htmlspecialchars($emailOverride) . "</strong> (usuario {$user_id} / proyecto {$project_id} / {$dias} días)"
                    : "Error al enviar correo — usuario {$user_id}, proyecto {$project_id}"
            ];

            if ($sent && $registrarEnBD) {
                registerDeadlineAlert($conn, $user_id, $titulo, $fechaLimite, $dias, (int)$project_id);
                markAlertAsSent($conn, $user_id, (int)$project_id, $dias);
                $actionLog[] = ['tipo' => 'info', 'ok' => true, 'msg' => "Alerta registrada en BD: usuario {$user_id}, proyecto {$project_id}"];
            }
        }

    } else {
        // ── Modo dry run: solo consulta, sin enviar nada ──
        $actionMode = 'dryrun';
    }
}

// ─── Cargar proyectos para la tabla de vista previa ─────────────────────────
$proyectosVista = [];
if ($conn) {
    try {
        $proyectosVista = getProjectsNearDeadline($conn);
        foreach ($proyectosVista as &$p) {
            $p['fecha_limite'] = calculateRealDeadline($p['fecha_base'], $p['prorrogas']);
            $p['alerta_enviada'] = hasAlertBeenSent($conn, $p['user_id'], $p['project_id'], $p['dias_restantes']);

            $s = $conn->prepare('SELECT nombre, email FROM sis_user WHERE id = ? LIMIT 1');
            $s->execute([$p['user_id']]);
            $u = $s->fetch(PDO::FETCH_ASSOC);
            $p['nombre'] = $u['nombre'] ?? '—';
            $p['email']  = $u['email']  ?? '—';

            $s2 = $conn->prepare('SELECT nombre FROM proyecto_aprobado WHERE id_aprobado = ? LIMIT 1');
            $s2->execute([$p['project_id']]);
            $u2 = $s2->fetch(PDO::FETCH_ASSOC);
            $p['titulo'] = $u2['nombre'] ?? '—';
        }
        unset($p);
    } catch (Exception $e) {
        $dbError = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Test: Cron de vencimientos</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
*, *::before, *::after { box-sizing: border-box; }
body { font-family: Arial, sans-serif; margin: 0; padding: 24px; background: #f4f6f9; color: #1f2937; }
h1 { margin-bottom: 4px; }
.subtitle { color: #6b7280; margin-bottom: 24px; font-size: 14px; }
.card { background: #fff; border: 1px solid #d1d5db; border-radius: 8px; padding: 24px; margin-bottom: 20px; }
h2 { font-size: 16px; margin: 0 0 16px 0; border-bottom: 1px solid #e5e7eb; padding-bottom: 8px; }
.alert { padding: 10px 14px; border-radius: 6px; margin-bottom: 12px; font-size: 14px; }
.alert-error   { background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; }
.alert-ok      { background: #f0fdf4; border: 1px solid #86efac; color: #166534; }
.alert-warn    { background: #fffbeb; border: 1px solid #fcd34d; color: #92400e; }
.alert-skip    { background: #f9fafb; border: 1px solid #d1d5db; color: #4b5563; }
.alert-info    { background: #eff6ff; border: 1px solid #93c5fd; color: #1e40af; }
label { display: block; margin-top: 12px; font-size: 14px; font-weight: bold; }
label span { font-weight: normal; color: #6b7280; margin-left: 4px; }
input[type=text], input[type=email], input[type=number] { width: 100%; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 5px; font-size: 14px; margin-top: 4px; }
input[type=checkbox] { margin-right: 6px; }
.check-label { display: flex; align-items: center; margin-top: 14px; font-size: 14px; cursor: pointer; }
.row { display: flex; gap: 16px; flex-wrap: wrap; }
.col { flex: 1; min-width: 180px; }
button { margin-top: 16px; padding: 10px 20px; border: none; border-radius: 6px; font-size: 14px; cursor: pointer; font-weight: bold; }
.btn-dryrun  { background: #e5e7eb; color: #111827; }
.btn-ejecutar { background: #2563eb; color: #fff; }
.btn-forzar  { background: #7c3aed; color: #fff; }
.btn-dryrun:hover   { background: #d1d5db; }
.btn-ejecutar:hover { background: #1d4ed8; }
.btn-forzar:hover   { background: #6d28d9; }
table { width: 100%; border-collapse: collapse; font-size: 13px; }
th { background: #f3f4f6; text-align: left; padding: 10px 12px; border-bottom: 2px solid #e5e7eb; }
td { padding: 10px 12px; border-bottom: 1px solid #e5e7eb; vertical-align: middle; }
tr:last-child td { border-bottom: none; }
.badge { display: inline-block; padding: 2px 9px; border-radius: 12px; font-size: 12px; font-weight: bold; }
.badge-ok   { background: #dcfce7; color: #166534; }
.badge-pend { background: #fef9c3; color: #854d0e; }
.badge-skip { background: #f3f4f6; color: #6b7280; }
.section-title { font-size: 13px; font-weight: bold; color: #374151; margin-bottom: 6px; }
#forzar-section { display: none; }
</style>
</head>
<body>

<h1>Test: Cron de vencimientos de plazos</h1>
<p class="subtitle">
    Simula la ejecución de <code>cron_check_deadlines.php</code> desde el navegador.
    Los correos se redirigen al email de prueba que ingreses — los emails reales de los estudiantes no se usan.
</p>

<?php if ($dbError): ?>
<div class="alert alert-error"><strong>Error de conexión a la BD:</strong> <?= htmlspecialchars($dbError) ?></div>
<?php endif; ?>

<?php if (!empty($actionLog)): ?>
<div class="card">
    <h2>Resultado de la ejecución</h2>
    <?php foreach ($actionLog as $entry): ?>
        <?php
        $cls = match($entry['tipo']) {
            'correo'  => $entry['ok'] ? 'alert-ok' : 'alert-error',
            'forzado' => $entry['ok'] ? 'alert-ok' : 'alert-error',
            'warn'    => 'alert-warn',
            'skip'    => 'alert-skip',
            default   => 'alert-info',
        };
        ?>
        <div class="alert <?= $cls ?>"><?= $entry['msg'] ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ─── Tabla de proyectos detectados ──────────────────────────────────────── -->
<div class="card">
    <h2>Proyectos detectados por la BD <small style="font-weight:normal;color:#6b7280;">(umbrales: <?= implode(', ', DEADLINE_THRESHOLDS) ?> días)</small></h2>

    <?php if (!$conn): ?>
        <p style="color:#991b1b;">Sin conexión a la BD.</p>
    <?php elseif (empty($proyectosVista)): ?>
        <div class="alert alert-warn">
            No hay proyectos con fechas dentro de los umbrales definidos hoy (<?= date('Y-m-d') ?>).
            Usa el modo <strong>Forzar envío</strong> para probar con datos manuales.
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Usuario ID</th>
                    <th>Nombre</th>
                    <th>Email real</th>
                    <th>Proyecto ID</th>
                    <th>Título</th>
                    <th>Fecha límite</th>
                    <th>Días rest.</th>
                    <th>Alerta enviada</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($proyectosVista as $i => $p): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars((string)$p['user_id']) ?></td>
                    <td><?= htmlspecialchars($p['nombre']) ?></td>
                    <td style="color:#6b7280;"><?= htmlspecialchars($p['email']) ?></td>
                    <td><?= htmlspecialchars((string)$p['project_id']) ?></td>
                    <td><?= htmlspecialchars($p['titulo']) ?></td>
                    <td><?= htmlspecialchars($p['fecha_limite']) ?></td>
                    <td style="text-align:center;"><strong><?= (int)$p['dias_restantes'] ?></strong></td>
                    <td style="text-align:center;">
                        <?php if ($p['alerta_enviada']): ?>
                            <span class="badge badge-ok">Sí</span>
                        <?php else: ?>
                            <span class="badge badge-pend">Pendiente</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- ─── Formulario de control ───────────────────────────────────────────────── -->
<div class="card">
    <h2>Control de ejecución</h2>
    <form method="post" autocomplete="off">

        <label>Email destino (override) <span>— todos los correos del test se enviarán a esta dirección</span>
            <input type="email" name="email_override" required
                   value="<?= htmlspecialchars($_POST['email_override'] ?? 'rodri100ro@gmail.com') ?>">
        </label>

        <label class="check-label">
            <input type="checkbox" name="registrar_bd" <?= isset($_POST['registrar_bd']) ? 'checked' : '' ?>>
            Registrar alertas en la BD (deadline_alerts_sent + user_alerts) — desactiva para repetir la prueba
        </label>

        <div style="margin-top:20px;">
            <div class="section-title">Modos de ejecución</div>
            <div class="row">
                <div class="col">
                    <button type="submit" name="action" value="dryrun" class="btn-dryrun" style="width:100%">
                        🔍 Solo Dry Run
                    </button>
                    <small style="color:#6b7280;">Recarga la tabla superior sin enviar correos.</small>
                </div>
                <div class="col">
                    <button type="submit" name="action" value="ejecutar" class="btn-ejecutar" style="width:100%">
                        ▶ Ejecutar cron (datos reales)
                    </button>
                    <small style="color:#6b7280;">Procesa los proyectos detectados y envía correos al override.</small>
                </div>
                <div class="col">
                    <button type="button" class="btn-forzar" style="width:100%"
                            onclick="document.getElementById('forzar-section').style.display = document.getElementById('forzar-section').style.display === 'none' ? 'block' : 'none'">
                        ⚡ Forzar envío
                    </button>
                    <small style="color:#6b7280;">Envía un correo de prueba con datos manuales, sin depender de la BD.</small>
                </div>
            </div>
        </div>

        <!-- Sección forzar envío -->
        <div id="forzar-section" style="display:<?= (isset($_POST['action']) && $_POST['action'] === 'forzar') ? 'block' : 'none' ?>; margin-top:20px; padding:16px; background:#faf5ff; border:1px solid #c4b5fd; border-radius:6px;">
            <div class="section-title" style="color:#6d28d9;">Parámetros de envío forzado</div>
            <div class="row">
                <div class="col">
                    <label>User ID <span>(de sis_user)</span>
                        <input type="text" name="forzar_user_id"
                               value="<?= htmlspecialchars($_POST['forzar_user_id'] ?? '') ?>"
                               placeholder="ej: 116440018">
                    </label>
                </div>
                <div class="col">
                    <label>Project ID <span>(id_aprobado)</span>
                        <input type="text" name="forzar_project_id"
                               value="<?= htmlspecialchars($_POST['forzar_project_id'] ?? '') ?>"
                               placeholder="ej: 13">
                    </label>
                </div>
                <div class="col">
                    <label>Días restantes <span>(para el correo)</span>
                        <input type="number" name="forzar_dias" min="1" max="90"
                               value="<?= (int)($_POST['forzar_dias'] ?? 7) ?>">
                    </label>
                </div>
            </div>
            <button type="submit" name="action" value="forzar" class="btn-forzar">
                ⚡ Enviar ahora
            </button>
        </div>

    </form>
</div>

<p style="font-size:12px; color:#9ca3af; margin-top:8px;">
    Este archivo es solo para pruebas. No debe estar accesible en producción.
</p>

</body>
</html>
