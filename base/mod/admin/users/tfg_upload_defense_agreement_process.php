<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

include_once __DIR__ . '/../../login/check.php';

$base_path = realpath(__DIR__ . '/../../../');

include_once $base_path . '/inc/db/db.php';
include_once $base_path . '/inc/alert_functions.php';

require_once $base_path . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once $base_path . '/vendor/phpmailer/phpmailer/src/SMTP.php';
require_once $base_path . '/vendor/phpmailer/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

header('Content-Type: application/json; charset=utf-8');

$current_user_id  = $mySessionController->getVar("usuario");
$current_user_rol = (int)$mySessionController->getVar("rol");

if (!$current_user_id || $current_user_rol !== 3) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$proyecto_id      = isset($_POST['proyecto_id']) ? (int)$_POST['proyecto_id'] : 0;
$proposal_id      = isset($_POST['proposal_id']) ? (int)$_POST['proposal_id'] : 0;
$fecha_aprobacion = trim($_POST['fecha_aprobacion_documento_final'] ?? '');
$fecha_defensa    = trim($_POST['fecha_defensa'] ?? '');
$codigo_acuerdo   = trim($_POST['codigo_acuerdo'] ?? '');
$correo_destino   = trim($_POST['correo_destino'] ?? 'malcolm.chaves.obando@est.una.ac.cr');

if ($proyecto_id <= 0 || $proposal_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Proyecto o propuesta inválidos.']);
    exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_aprobacion)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'La fecha de aprobación es inválida.']);
    exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_defensa)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'La fecha de defensa es inválida.']);
    exit;
}

if ($codigo_acuerdo === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El código del acuerdo es obligatorio.']);
    exit;
}

if (!isset($_FILES['documento']) || $_FILES['documento']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Debe adjuntar el PDF del acuerdo.']);
    exit;
}

$max_size = 10 * 1024 * 1024;
if ($_FILES['documento']['size'] > $max_size) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El archivo excede el tamaño máximo de 10 MB.']);
    exit;
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $_FILES['documento']['tmp_name']);
finfo_close($finfo);

if ($mime_type !== 'application/pdf') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Solo se permiten archivos PDF.']);
    exit;
}

$conn = $id_con;
mysqli_set_charset($conn, "utf8mb4");

try {
    mysqli_begin_transaction($conn);

    $sqlProyecto = "SELECT p.id_aprobado, p.nombre, p.proposal_id
                    FROM proyecto_aprobado p
                    WHERE p.id_aprobado = ? AND p.proposal_id = ?
                    LIMIT 1";
    $stmtProyecto = mysqli_prepare($conn, $sqlProyecto);
    mysqli_stmt_bind_param($stmtProyecto, "ii", $proyecto_id, $proposal_id);
    mysqli_stmt_execute($stmtProyecto);
    $rsProyecto = mysqli_stmt_get_result($stmtProyecto);
    $proyecto = mysqli_fetch_assoc($rsProyecto);
    mysqli_stmt_close($stmtProyecto);

    if (!$proyecto) {
        throw new \Exception("No se encontró el proyecto aprobado.");
    }

    $fecha_hoy = new DateTime(date('Y-m-d'));
    $fecha_def = new DateTime($fecha_defensa);

    $stmtExiste = mysqli_prepare($conn, "SELECT id, archivo_ruta FROM acuerdo_defensa_publica WHERE proyecto_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmtExiste, "i", $proyecto_id);
    mysqli_stmt_execute($stmtExiste);
    $rsExiste = mysqli_stmt_get_result($stmtExiste);
    $acuerdoExistente = mysqli_fetch_assoc($rsExiste);
    mysqli_stmt_close($stmtExiste);

    if ($acuerdoExistente) {
        $limite = (clone $fecha_def)->modify('-15 days');
        if ($fecha_hoy >= $limite) {
            throw new \Exception("Ya no se permite reemplazar el acuerdo porque faltan 15 días o menos para la defensa.");
        }
    }

    $uploadDir = $base_path . '/uploads/acuerdos_defensa/';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
        throw new \Exception("No se pudo crear la carpeta de acuerdos.");
    }

    $safeCode = preg_replace('/[^A-Za-z0-9\-_]/', '_', $codigo_acuerdo);
    $fileName = 'acuerdo_defensa_' . $proyecto_id . '_' . $safeCode . '_' . time() . '.pdf';
    $targetPath = $uploadDir . $fileName;
    $relativePath = 'uploads/acuerdos_defensa/' . $fileName;

    if (!move_uploaded_file($_FILES['documento']['tmp_name'], $targetPath)) {
        throw new \Exception("No se pudo guardar el archivo en el servidor.");
    }

    $fileSize = (int)$_FILES['documento']['size'];

    if ($acuerdoExistente) {
        $stmtUpdate = mysqli_prepare($conn, "
            UPDATE acuerdo_defensa_publica
               SET codigo_acuerdo = ?,
                   fecha_aprobacion_documento_final = ?,
                   fecha_defensa = ?,
                   archivo_nombre = ?,
                   archivo_ruta = ?,
                   mime_type = ?,
                   file_size = ?,
                   correo_destino = ?,
                   subido_por = ?,
                   enviado_correo = 0
             WHERE proyecto_id = ?
        ");
        mysqli_stmt_bind_param(
            $stmtUpdate,
            "ssssssissi",
            $codigo_acuerdo,
            $fecha_aprobacion,
            $fecha_defensa,
            $fileName,
            $relativePath,
            $mime_type,
            $fileSize,
            $correo_destino,
            $current_user_id,
            $proyecto_id
        );
        if (!mysqli_stmt_execute($stmtUpdate)) {
            throw new \Exception("No se pudo actualizar el acuerdo.");
        }
        mysqli_stmt_close($stmtUpdate);

        if (!empty($acuerdoExistente['archivo_ruta'])) {
            $oldPath = $base_path . '/' . $acuerdoExistente['archivo_ruta'];
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }
    } else {
        $stmtInsert = mysqli_prepare($conn, "
            INSERT INTO acuerdo_defensa_publica
            (proyecto_id, proposal_id, codigo_acuerdo, fecha_aprobacion_documento_final, fecha_defensa,
             archivo_nombre, archivo_ruta, mime_type, file_size, correo_destino, subido_por, enviado_correo)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,0)
        ");
        mysqli_stmt_bind_param(
            $stmtInsert,
            "iissssssiss",
            $proyecto_id,
            $proposal_id,
            $codigo_acuerdo,
            $fecha_aprobacion,
            $fecha_defensa,
            $fileName,
            $relativePath,
            $mime_type,
            $fileSize,
            $correo_destino,
            $current_user_id
        );
        if (!mysqli_stmt_execute($stmtInsert)) {
            throw new \Exception("No se pudo registrar el acuerdo.");
        }
        mysqli_stmt_close($stmtInsert);
    }

    $studentIds = [];
    $stmtStudents = mysqli_prepare($conn, "
        SELECT estudiante_id
        FROM proyecto_aprobado_estudiantes
        WHERE id_aprobado = ?
    ");
    mysqli_stmt_bind_param($stmtStudents, "i", $proyecto_id);
    mysqli_stmt_execute($stmtStudents);
    $rsStudents = mysqli_stmt_get_result($stmtStudents);
    while ($row = mysqli_fetch_assoc($rsStudents)) {
        $studentIds[] = $row['estudiante_id'];
    }
    mysqli_stmt_close($stmtStudents);

    foreach ($studentIds as $studentId) {
        registerAlert(
            $conn,
            $studentId,
            'Acuerdo de defensa adjuntado',
            'La Comisión de TFG adjuntó el acuerdo para programar la defensa pública de su proyecto.',
            'Informativa',
            'Media',
            'project',
            $proyecto_id
        );
    }

    $mailSent = false;
    $mailError = '';

    try {
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = 'smtp.office365.com';
        $mail->Port       = 587;
        $mail->SMTPAuth   = true;
        $mail->Username   = 'malcolm.chaves.obando@est.una.ac.cr';
        $mail->Password   = 'AQUI_TU_PASSWORD';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('malcolm.chaves.obando@est.una.ac.cr', 'SGPFL');
        $mail->addAddress($correo_destino);

        $mail->Subject = "Acuerdo de defensa pública - {$codigo_acuerdo}";
        $mail->isHTML(true);
        $mail->Body = "
            <p>Se remite el acuerdo de defensa pública del proyecto:</p>
            <p><strong>" . htmlspecialchars($proyecto['nombre'], ENT_QUOTES, 'UTF-8') . "</strong></p>
            <p><strong>Código:</strong> " . htmlspecialchars($codigo_acuerdo, ENT_QUOTES, 'UTF-8') . "</p>
            <p><strong>Fecha de defensa:</strong> " . htmlspecialchars($fecha_defensa, ENT_QUOTES, 'UTF-8') . "</p>
        ";
        $mail->AltBody = "Se remite el acuerdo de defensa pública del proyecto {$proyecto['nombre']}. Código: {$codigo_acuerdo}. Fecha de defensa: {$fecha_defensa}.";

        if (is_file($targetPath)) {
            $mail->addAttachment($targetPath, $fileName);
        }

        $mail->send();
        $mailSent = true;
    } catch (MailException $e) {
        $mailError = $e->getMessage();
        error_log('HU-016 PHPMailer error: ' . $mailError);
    } catch (\Throwable $e) {
        $mailError = $e->getMessage();
        error_log('HU-016 correo error general: ' . $mailError);
    }

    $stmtMail = mysqli_prepare($conn, "UPDATE acuerdo_defensa_publica SET enviado_correo = ? WHERE proyecto_id = ?");
    $mailFlag = $mailSent ? 1 : 0;
    mysqli_stmt_bind_param($stmtMail, "ii", $mailFlag, $proyecto_id);
    mysqli_stmt_execute($stmtMail);
    mysqli_stmt_close($stmtMail);

    mysqli_commit($conn);

    echo json_encode([
        'success' => true,
        'message' => $mailSent
            ? 'Acuerdo registrado, notificación enviada y correo procesado correctamente.'
            : 'Acuerdo registrado y notificación enviada, pero el correo no se pudo enviar.'
    ]);
} catch (\Exception $e) {
    mysqli_rollback($conn);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>