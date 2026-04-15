<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

include("../../login/check.php");
include_once(__DIR__ . '/../../../inc/db/bdcommon.inc');
include_once(__DIR__ . '/../../../inc/alert_functions.php');

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

$proyecto_id = isset($_POST['proyecto_id']) ? (int)$_POST['proyecto_id'] : 0;
$proposal_id = isset($_POST['proposal_id']) ? (int)$_POST['proposal_id'] : 0;
$fecha_aprobacion = trim($_POST['fecha_aprobacion_documento_final'] ?? '');
$fecha_defensa = trim($_POST['fecha_defensa'] ?? '');
$codigo_acuerdo = trim($_POST['codigo_acuerdo'] ?? '');
$correo_destino = trim($_POST['correo_destino'] ?? 'malcolm.chaves.obando@est.una.ac.cr');

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
    echo json_encode(['success' => false, 'message' => 'Solo se permite PDF.']);
    exit;
}

$conn = new mysqli($db_host, $usuario, $clave, $db);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']);
    exit;
}
$conn->set_charset("utf8mb4");

try {
    $conn->begin_transaction();

    // Verificar proyecto + estudiantes
    $sqlProyecto = "SELECT p.id_aprobado, p.nombre, p.proposal_id, p.fecha_finalizacion
                    FROM proyecto_aprobado p
                    WHERE p.id_aprobado = ? AND p.proposal_id = ?
                    LIMIT 1";
    $stmtProyecto = $conn->prepare($sqlProyecto);
    $stmtProyecto->bind_param("ii", $proyecto_id, $proposal_id);
    $stmtProyecto->execute();
    $rsProyecto = $stmtProyecto->get_result();
    $proyecto = $rsProyecto->fetch_assoc();
    $stmtProyecto->close();

    if (!$proyecto) {
        throw new Exception("No se encontró el proyecto aprobado.");
    }

    // Regla: no permitir reemplazo si faltan 15 días o menos para la defensa
    $dias_restantes = (new DateTime($fecha_defensa))->diff(new DateTime(date('Y-m-d')))->days;
    $fecha_hoy = new DateTime(date('Y-m-d'));
    $fecha_def = new DateTime($fecha_defensa);

    $stmtExiste = $conn->prepare("SELECT id, archivo_ruta FROM acuerdo_defensa_publica WHERE proyecto_id = ? LIMIT 1");
    $stmtExiste->bind_param("i", $proyecto_id);
    $stmtExiste->execute();
    $rsExiste = $stmtExiste->get_result();
    $acuerdoExistente = $rsExiste->fetch_assoc();
    $stmtExiste->close();

    if ($acuerdoExistente) {
        $limite = (clone $fecha_def)->modify('-15 days');
        if ($fecha_hoy >= $limite) {
            throw new Exception("Ya no se permite reemplazar el acuerdo porque faltan 15 días o menos para la defensa.");
        }
    }

    // Crear carpeta
    $uploadDir = __DIR__ . '/../../../uploads/acuerdos_defensa/';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
        throw new Exception("No se pudo crear la carpeta de acuerdos.");
    }

    $safeCode = preg_replace('/[^A-Za-z0-9\-_]/', '_', $codigo_acuerdo);
    $fileName = 'acuerdo_defensa_' . $proyecto_id . '_' . $safeCode . '_' . time() . '.pdf';
    $targetPath = $uploadDir . $fileName;
    $relativePath = 'uploads/acuerdos_defensa/' . $fileName;

    if (!move_uploaded_file($_FILES['documento']['tmp_name'], $targetPath)) {
        throw new Exception("No se pudo guardar el archivo en el servidor.");
    }

    if ($acuerdoExistente) {
        $stmtUpdate = $conn->prepare("
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
        $fileSize = (int)$_FILES['documento']['size'];
        $stmtUpdate->bind_param(
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
        if (!$stmtUpdate->execute()) {
            throw new Exception("No se pudo actualizar el acuerdo.");
        }
        $stmtUpdate->close();

        if (!empty($acuerdoExistente['archivo_ruta'])) {
            $oldPath = __DIR__ . '/../../../' . $acuerdoExistente['archivo_ruta'];
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }
    } else {
        $stmtInsert = $conn->prepare("
            INSERT INTO acuerdo_defensa_publica
            (proyecto_id, proposal_id, codigo_acuerdo, fecha_aprobacion_documento_final, fecha_defensa,
             archivo_nombre, archivo_ruta, mime_type, file_size, correo_destino, subido_por, enviado_correo)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,0)
        ");
        $fileSize = (int)$_FILES['documento']['size'];
        $stmtInsert->bind_param(
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
        if (!$stmtInsert->execute()) {
            throw new Exception("No se pudo registrar el acuerdo.");
        }
        $stmtInsert->close();
    }

    // Buscar estudiantes del proyecto
    $studentIds = [];
    $stmtStudents = $conn->prepare("
        SELECT estudiante_id
        FROM proyecto_aprobado_estudiantes
        WHERE id_aprobado = ?
    ");
    $stmtStudents->bind_param("i", $proyecto_id);
    $stmtStudents->execute();
    $rsStudents = $stmtStudents->get_result();
    while ($row = $rsStudents->fetch_assoc()) {
        $studentIds[] = $row['estudiante_id'];
    }
    $stmtStudents->close();

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

    // Correo simple con adjunto
    $boundary = md5((string)microtime(true));
    $subject = "Acuerdo de defensa pública - {$codigo_acuerdo}";
    $htmlBody = "
        <html><body>
        <p>Se remite el acuerdo de defensa pública del proyecto:</p>
        <p><strong>" . htmlspecialchars($proyecto['nombre'], ENT_QUOTES, 'UTF-8') . "</strong></p>
        <p><strong>Código:</strong> " . htmlspecialchars($codigo_acuerdo, ENT_QUOTES, 'UTF-8') . "</p>
        <p><strong>Fecha de defensa:</strong> " . htmlspecialchars($fecha_defensa, ENT_QUOTES, 'UTF-8') . "</p>
        </body></html>
    ";

    $fileData = chunk_split(base64_encode(file_get_contents($targetPath)));

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "From: malcolm.chaves.obando@est.una.ac.cr\r\n";
    $headers .= "Reply-To: malcolm.chaves.obando@est.una.ac.cr\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

    $message  = "--{$boundary}\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $message .= $htmlBody . "\r\n";
    $message .= "--{$boundary}\r\n";
    $message .= "Content-Type: application/pdf; name=\"{$fileName}\"\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n";
    $message .= "Content-Disposition: attachment; filename=\"{$fileName}\"\r\n\r\n";
    $message .= $fileData . "\r\n";
    $message .= "--{$boundary}--";

    $mailSent = mail($correo_destino, $subject, $message, $headers);

    $stmtMail = $conn->prepare("UPDATE acuerdo_defensa_publica SET enviado_correo = ? WHERE proyecto_id = ?");
    $mailFlag = $mailSent ? 1 : 0;
    $stmtMail->bind_param("ii", $mailFlag, $proyecto_id);
    $stmtMail->execute();
    $stmtMail->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => $mailSent
            ? 'Acuerdo registrado, notificación enviada y correo procesado correctamente.'
            : 'Acuerdo registrado y notificación enviada, pero el correo no se pudo enviar.'
    ]);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    $conn->close();
}
?>