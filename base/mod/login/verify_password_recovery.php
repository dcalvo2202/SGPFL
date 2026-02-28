<?php
/**
 * Script de verificación del sistema de recuperación de contraseña
 * Accesible solo en modo desarrollo/testing
 */

// Restricción: solo localhost
$allowed_ips = ['127.0.0.1', 'localhost', '::1'];
$client_ip = $_SERVER['REMOTE_ADDR'] ?? '';

if (!in_array($client_ip, $allowed_ips) && php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('Acceso denegado. Solo disponible en localhost.');
}

include('../../includes.php');
require_once __DIR__ . '/../../config.inc';
require_once __DIR__ . '/../../inc/db/db.php';
require_once __DIR__ . '/../../inc/db/init_password_recovery.php';

// Acción a realizar
$action = $_GET['action'] ?? 'status';

// Inicializar tabla si no existe
init_password_recovery_table();

// Funciones de diagnóstico
function check_table_exists() {
    $sql = "SELECT 1 FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'password_recovery_tokens'";
    $result = seleccion($sql);
    return !empty($result);
}

function check_external_advisors() {
    $sql = "SELECT 
                ear.applicant_id,
                ear.full_name, 
                ear.email, 
                ear.status,
                CASE WHEN sl.id IS NOT NULL THEN 'Sí' ELSE 'No' END AS en_sis_login,
                CASE WHEN su.id IS NOT NULL THEN 'Sí' ELSE 'No' END AS en_sis_user
            FROM external_advisor_profile_requests ear
            LEFT JOIN sis_login sl ON ear.applicant_id = sl.id
            LEFT JOIN sis_user su ON ear.applicant_id = su.id
            WHERE ear.status = 'Aprobado'
            ORDER BY ear.created_at DESC
            LIMIT 10";
    return seleccion($sql);
}

function get_recent_tokens() {
    $sql = "SELECT id, user_id, email, token, type, used, created_at, expires_at 
            FROM password_recovery_tokens 
            ORDER BY created_at DESC LIMIT 10";
    return seleccion($sql);
}

function get_password_changes() {
    $sql = "SELECT id_user, date_bi, detail 
            FROM sis_log 
            WHERE detail LIKE '%Cambio de contraseña%' 
            ORDER BY date_bi DESC LIMIT 10";
    return seleccion($sql);
}

function test_send_email($email = null) {
    if (!$email) {
        return ['success' => false, 'message' => 'Email requerido'];
    }

    // Validar que sea asesor aprobado
    $advisor = seleccion_segura(
        "SELECT applicant_id, full_name FROM external_advisor_profile_requests 
         WHERE email = ? AND status = 'Aprobado'",
        [$email]
    );

    if (empty($advisor)) {
        return [
            'success' => false, 
            'message' => 'Asesor no encontrado o no aprobado'
        ];
    }

    $advisor_id = $advisor[0]['applicant_id'];
    
    // Sincronizar si falta sis_login
    $check_login = seleccion_segura("SELECT id FROM sis_login WHERE id = ?", [$advisor_id]);
    if (empty($check_login)) {
        $temp_password = substr($advisor_id, 0, 4) . date('Y');
        $hashed_password = md5($temp_password);
        $rol_asesor = 5;
        
        $result = ejecutar_query(
            "INSERT INTO sis_login (id, pass, id_roll) VALUES (?, ?, ?)",
            [$advisor_id, $hashed_password, $rol_asesor]
        );
        
        if (!$result['success']) {
            return [
                'success' => false,
                'message' => 'Error sincronizando sis_login: ' . $result['error']
            ];
        }
    }
    
    // Sincronizar si falta sis_user
    $check_user = seleccion_segura("SELECT id FROM sis_user WHERE id = ?", [$advisor_id]);
    if (empty($check_user)) {
        @ejecutar_query(
            "INSERT INTO sis_user (id, nombre, email) VALUES (?, ?, ?)",
            [$advisor_id, $advisor[0]['full_name'], $email]
        );
    }

    // Generar token de prueba
    $token = 'TEST_' . bin2hex(random_bytes(16)) . '_' . time();
    
    // Intentar guardar en BD
    $expires_at = date('Y-m-d H:i:s', time() + 3600);
    $result = ejecutar_query(
        "INSERT INTO password_recovery_tokens (user_id, email, token, type, expires_at) 
         VALUES (?, ?, ?, 'external_advisor', ?)",
        [$advisor_id, $email, $token, $expires_at]
    );

    if ($result['success'] === false) {
        return [
            'success' => false,
            'message' => 'Error al guardar token en BD: ' . $result['error']
        ];
    }

    return [
        'success' => true,
        'message' => 'Token de prueba generado exitosamente',
        'token' => $token,
        'advisor' => $advisor[0],
        'synced' => true
    ];
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verificación - Sistema de Recuperación de Contraseña</title>
    <link rel="stylesheet" href="../../lib/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../lib/font-awesome/css/all.min.css">
    <style>
        body { padding: 20px; background-color: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .status-ok { color: #28a745; }
        .status-error { color: #dc3545; }
        .code-block { background: #f8f9fa; padding: 15px; border-radius: 4px; border-left: 4px solid #007bff; margin: 15px 0; }
        table { font-size: 12px; }
        .badge { margin: 2px; }
        .nav-buttons { margin-bottom: 20px; }
        .nav-buttons a { margin-right: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fa fa-shield-alt"></i> Verificación del Sistema de Recuperación de Contraseña</h1>
        <hr>

        <div class="nav-buttons">
            <a href="?action=status" class="btn btn-primary btn-sm">Estado General</a>
            <a href="?action=advisors" class="btn btn-info btn-sm">Asesores Aprobados</a>
            <a href="?action=sync" class="btn btn-success btn-sm">Sincronizar Asesores</a>
            <a href="?action=tokens" class="btn btn-warning btn-sm">Tokens Recientes</a>
            <a href="?action=changes" class="btn btn-secondary btn-sm">Cambios de Contraseña</a>
            <a href="?action=test" class="btn btn-danger btn-sm">Test Email</a>
        </div>

        <?php if ($action === 'status'): ?>
            <h2>Estado del Sistema</h2>
            
            <div class="card mb-3">
                <div class="card-header">
                    <h5>Tablas de Base de Datos</h5>
                </div>
                <div class="card-body">
                    <p>
                        <strong>Tabla password_recovery_tokens:</strong> 
                        <?php if (check_table_exists()): ?>
                            <span class="badge badge-success status-ok">✓ Existe</span>
                        <?php else: ?>
                            <span class="badge badge-danger status-error">✗ No encontrada</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h5>Configuración de Correo</h5>
                </div>
                <div class="card-body">
                    <p><strong>sendmail_from (php.ini):</strong></p>
                    <div class="code-block">
                        <?= ini_get('sendmail_from') ?: 'No configurado' ?>
                    </div>
                    <p><strong>SMTP Server:</strong></p>
                    <div class="code-block">
                        <?= ini_get('SMTP') ?: 'No configurado' ?>:<?= ini_get('smtp_port') ?: '25' ?>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5>Archivos del Sistema</h5>
                </div>
                <div class="card-body">
                    <?php
                    $files = [
                        'cambiar_contrasena_asesor.php' => 'Página principal',
                        'send_password_recovery_email.php' => 'Envío de email',
                        'reset_password_advisor.php' => 'Reset de contraseña',
                    ];
                    foreach ($files as $file => $desc):
                        $path = __DIR__ . '/' . $file;
                        $exists = file_exists($path);
                    ?>
                        <p>
                            <strong><?= $file ?>:</strong> <?= $desc ?>
                            <?php if ($exists): ?>
                                <span class="badge badge-success status-ok">✓ OK</span>
                            <?php else: ?>
                                <span class="badge badge-danger status-error">✗ Falta</span>
                            <?php endif; ?>
                        </p>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php elseif ($action === 'advisors'): ?>
            <h2>Asesores Externos Aprobados</h2>
            <?php $advisors = check_external_advisors(); ?>
            <?php if (!empty($advisors)): ?>
                <div class="alert alert-info mb-3">
                    <strong>ℹ️ Nota:</strong> Los asesores deben estar en <code>sis_login</code> y <code>sis_user</code> para poder recuperar contraseña.
                    Si faltan, el sistema los creará automáticamente.
                </div>
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>ID (Cédula)</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>En sis_login</th>
                            <th>En sis_user</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($advisors as $advisor): ?>
                            <tr class="<?= ($advisor['en_sis_login'] === 'No' || $advisor['en_sis_user'] === 'No') ? 'table-warning' : '' ?>">
                                <td><?= htmlspecialchars($advisor['applicant_id']) ?></td>
                                <td><?= htmlspecialchars($advisor['full_name']) ?></td>
                                <td><?= htmlspecialchars($advisor['email']) ?></td>
                                <td>
                                    <?php if ($advisor['en_sis_login'] === 'Sí'): ?>
                                        <span class="badge badge-success">✓</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">✗ Falta</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($advisor['en_sis_user'] === 'Sí'): ?>
                                        <span class="badge badge-success">✓</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">✗ Falta</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge badge-success"><?= htmlspecialchars($advisor['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="alert alert-warning mt-3">
                    <strong>⚠️ Importante:</strong> Si algún asesor muestra "✗ Falta", 
                    no podrá recuperar su contraseña hasta que sus registros se sincronicen. 
                    El sistema lo hará automáticamente en el primer intento de recuperación.
                </div>
            <?php else: ?>
                <div class="alert alert-warning">No hay asesores externos aprobados</div>
            <?php endif; ?>

        <?php elseif ($action === 'tokens'): ?>
            <h2>Tokens Recientes</h2>
            <?php $tokens = get_recent_tokens(); ?>
            <?php if (!empty($tokens)): ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Token</th>
                            <th>Usado</th>
                            <th>Expira</th>
                            <th>Creado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tokens as $token): ?>
                            <tr>
                                <td><?= $token['id'] ?></td>
                                <td><?= htmlspecialchars($token['user_id']) ?></td>
                                <td><?= htmlspecialchars($token['email']) ?></td>
                                <td><small><?= substr($token['token'], 0, 20) ?>...</small></td>
                                <td>
                                    <?php if ($token['used']): ?>
                                        <span class="badge badge-success">Sí</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">No</span>
                                    <?php endif; ?>
                                </td>
                                <td><small><?= $token['expires_at'] ?></small></td>
                                <td><small><?= $token['created_at'] ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">No hay tokens registrados</div>
            <?php endif; ?>

        <?php elseif ($action === 'changes'): ?>
            <h2>Cambios de Contraseña Registrados</h2>
            <?php $changes = get_password_changes(); ?>
            <?php if (!empty($changes)): ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID de Bitácora</th>
                            <th>Usuario</th>
                            <th>Fecha/Hora</th>
                            <th>Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($changes as $change): ?>
                            <tr>
                                <td><?= $change['id_bi'] ?></td>
                                <td><?= htmlspecialchars($change['id_user']) ?></td>
                                <td><?= $change['date_bi'] ?></td>
                                <td><?= htmlspecialchars($change['detail']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">No hay cambios de contraseña registrados</div>
            <?php endif; ?>

        <?php elseif ($action === 'test'): ?>
            <h2>Test de Envío de Email</h2>
            <?php 
            $test_result = null;
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])):
                $test_result = test_send_email($_POST['email']);
            endif;
            ?>

            <form method="POST" class="card" style="max-width: 500px;">
                <div class="card-body">
                    <div class="form-group">
                        <label>Email del Asesor Externo (Aprobado):</label>
                        <input type="email" name="email" class="form-control" required>
                        <small class="form-text text-muted">Ingresa el email de un asesor externo aprobado</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Generar Token de Prueba</button>
                </div>
            </form>

            <?php if ($test_result): ?>
                <div class="card mt-3">
                    <div class="card-body">
                        <?php if ($test_result['success']): ?>
                            <div class="alert alert-success">
                                <h5>✓ Éxito</h5>
                                <p><?= $test_result['message'] ?></p>
                                <div class="code-block">
                                    <strong>Token:</strong><br>
                                    <?= $test_result['token'] ?>
                                </div>
                                <div class="code-block">
                                    <strong>Asesor:</strong><br>
                                    <?= htmlspecialchars($test_result['advisor']['full_name']) ?><br>
                                    <?= htmlspecialchars($test_result['advisor']['applicant_id']) ?>
                                </div>
                                <p><strong>URL de Prueba:</strong></p>
                                <div class="code-block" style="word-break: break-all;">
                                    <?php
                                    $url = rtrim($cds_domain, '/') . '/' . trim($cds_locate, '/') . 
                                           '/mod/login/cambiar_contrasena_asesor.php?token=' . urlencode($test_result['token']);
                                    echo htmlspecialchars($url);
                                    ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <h5>✗ Error</h5>
                                <p><?= $test_result['message'] ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

        <?php endif; ?>

        <hr>
        <p class="text-muted">
            <small>
                Sistema de Verificación - Disponible solo en localhost<br>
                Fecha: <?= date('Y-m-d H:i:s') ?>
            </small>
        </p>
    </div>

    <script src="../../lib/jquery-3.1.0.min.js"></script>
    <script src="../../lib/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>
