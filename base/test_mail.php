<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

$defaultTo = 'rodri100ro@gmail.com'; // Cambia esto para tus pruebas
$result = null;
$detail = null;

function enviar_con_mail(string $to, string $subject, string $html, string $from): array {
    $headers  = "From: {$from}\r\n";
    $headers .= "Reply-To: {$from}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";

    $ok = @mail($to, $subject, $html, $headers);
    $err = error_get_last();
    return [$ok, $ok ? 'OK' : ('mail() falló' . ($err ? ' | ' . ($err['message'] ?? '') : ''))];
}

function enviar_con_phpmailer(array $cfg): array {
    if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
        return [false, 'PHPMailer no está instalado. Ejecuta: composer require phpmailer/phpmailer'];
    }
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $cfg['host'];
        $mail->Port       = (int)$cfg['port'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $cfg['user'];
        $mail->Password   = $cfg['pass'];
        $mail->SMTPSecure = $cfg['secure']; // 'tls' o 'ssl'
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($cfg['from'], 'Prueba SGPFL');
        $mail->addAddress($cfg['to']);
        $mail->Subject = $cfg['subject'];
        $mail->isHTML(true);
        $mail->Body    = $cfg['body'];
        $mail->AltBody = strip_tags($cfg['body']);

        $mail->send();
        return [true, 'OK'];
    } catch (Throwable $e) {
        return [false, 'Error PHPMailer: ' . $e->getMessage()];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to      = trim($_POST['to'] ?? '');
    $from    = trim($_POST['from'] ?? 'no-reply@localhost');
    $subject = trim($_POST['subject'] ?? 'Prueba de correo');
    $body    = $_POST['body'] ?? '<b>Correo de prueba</b>';
    $method  = $_POST['method'] ?? 'mail';

    if ($method === 'mail') {
        [$result, $detail] = enviar_con_mail($to, $subject, $body, $from);
    } else {
        // Config SMTP por defecto para Gmail (requiere “Contraseña de aplicación”)
        $host   = trim($_POST['smtp_host'] ?? 'smtp.gmail.com');
        $port   = (int)($_POST['smtp_port'] ?? 587);
        $secure = $_POST['smtp_secure'] ?? 'tls'; // 'tls' o 'ssl'
        $user   = trim($_POST['smtp_user'] ?? '');
        $pass   = $_POST['smtp_pass'] ?? '';

        require_once __DIR__ . '/vendor/autoload.php'; // Composer autoload

        [$result, $detail] = enviar_con_phpmailer([
            'host' => $host,
            'port' => $port,
            'secure' => $secure,
            'user' => $user,
            'pass' => $pass,
            'from' => $from ?: $user,
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
        ]);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Prueba de envío de correos</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body { font-family: Arial, sans-serif; margin: 24px; }
fieldset { margin-top: 16px; }
.result { padding: 12px; border-radius: 6px; margin-bottom: 12px; }
.ok { background: #e6ffed; border: 1px solid #34c759; }
.fail { background: #ffecec; border: 1px solid #ff3b30; }
label { display:block; margin-top:8px; }
input[type=text], input[type=email], input[type=password], textarea { width:100%; padding:8px; }
small { color:#666; }
</style>
</head>
<body>
<h1>Prueba de envío de correos</h1>

<?php if ($result !== null): ?>
<div class="result <?= $result ? 'ok' : 'fail' ?>">
    <strong><?= $result ? 'Envío exitoso' : 'Envío fallido' ?></strong><br>
    <small><?= htmlspecialchars($detail) ?></small>
</div>
<?php endif; ?>

<form method="post" autocomplete="off">
    <fieldset>
        <legend>Datos del correo</legend>
        <label>Para (To)
            <input type="email" name="to" required value="<?= htmlspecialchars($_POST['to'] ?? $defaultTo) ?>">
        </label>
        <label>De (From)
            <input type="text" name="from" value="<?= htmlspecialchars($_POST['from'] ?? 'no-reply@localhost') ?>">
            <small>Para Gmail SMTP, suele usarse el mismo usuario.</small>
        </label>
        <label>Asunto
            <input type="text" name="subject" value="<?= htmlspecialchars($_POST['subject'] ?? 'Prueba de correo') ?>">
        </label>
        <label>Mensaje (HTML)
            <textarea name="body" rows="6"><?= htmlspecialchars($_POST['body'] ?? '<b>Correo de prueba</b> desde SGPFL') ?></textarea>
        </label>
        <label>Método:
            <select name="method" onchange="document.getElementById('smtp').style.display = this.value==='smtp' ? 'block' : 'none'">
                <option value="mail" <?= (($_POST['method'] ?? '') === 'mail') ? 'selected' : '' ?>>PHP mail()</option>
                <option value="smtp" <?= (($_POST['method'] ?? '') === 'smtp') ? 'selected' : '' ?>>SMTP (PHPMailer)</option>
            </select>
        </label>
    </fieldset>

    <fieldset id="smtp" style="display: <?= (($_POST['method'] ?? '') === 'smtp') ? 'block' : 'none' ?>;">
        <legend>Configuración SMTP</legend>
        <label>Host
            <input type="text" name="smtp_host" value="<?= htmlspecialchars($_POST['smtp_host'] ?? 'smtp.gmail.com') ?>">
        </label>
        <label>Puerto
            <input type="text" name="smtp_port" value="<?= htmlspecialchars($_POST['smtp_port'] ?? '587') ?>">
        </label>
        <label>Seguridad
            <input type="text" name="smtp_secure" value="<?= htmlspecialchars($_POST['smtp_secure'] ?? 'tls') ?>">
        </label>
        <label>Usuario
            <input type="text" name="smtp_user" value="<?= htmlspecialchars($_POST['smtp_user'] ?? '') ?>">
        </label>
        <label>Contraseña
            <input type="password" name="smtp_pass" value="">
        </label>
        <small>Para Gmail: usa una “Contraseña de aplicación”.</small>
    </fieldset>

    <p>
        <button type="submit">Enviar</button>
        <a href="?phpinfo=1">phpinfo()</a>
    </p>
</form>

<?php if (isset($_GET['phpinfo'])) phpinfo(); ?>
</body>
</html>