<?php
// =============================== INICIALIZACIÓN Y CONFIGURACIÓN ===============================
ob_start();

date_default_timezone_set('America/Costa_Rica');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');

require_once __DIR__ . '/config.inc';
require_once __DIR__ . '/inc/hu041_committee_audit.php';

$base_url = rtrim($cds_domain, '/') . '/' . trim($cds_locate, '/') . '/';
$redirect_ok = $base_url . 'login.php';
$redirect_back = $base_url . 'registro.php';

try {
    require_once __DIR__ . '/lib/mysession/mySession.conf.php';
    require_once __DIR__ . '/lib/mysession/mySession.class.php';
    $mySessionController = mySession::getIstance($_MYSESSION_CONF);
    $current_user_rol = (int)($mySessionController->getVar('rol') ?? 0);
    $current_user_id = (string)($mySessionController->getVar('usuario') ?? '');
    
    if ($current_user_id !== '') {
        if ($current_user_rol === 4) {
            $redirect_ok = $base_url . 'Panel_SubirTFG.php';
        } else {
            $redirect_ok = $base_url . 'dashboard.php';
        }
    }
} catch (Throwable $e) {
    // Ignorar si no hay sesión activa
}

function render_swal_and_exit(string $icon, string $title, string $message, string $redirect_url): void {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $safe_title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safe_message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $safe_redirect = htmlspecialchars($redirect_url, ENT_QUOTES, 'UTF-8');

    header('Content-Type: text/html; charset=UTF-8');
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $safe_title ?></title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .loader {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid #fff;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .swal2-border-radius {
            border-radius: 20px !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2) !important;
        }
        .swal2-confirm-btn {
            padding: 12px 30px !important;
            font-size: 1rem !important;
            border-radius: 10px !important;
            font-weight: 600 !important;
        }
    </style>
</head>
<body>
<div class="loader"></div>
<script>
    (function () {
        const icon = <?= json_encode($icon) ?>;
        const title = <?= json_encode($safe_title) ?>;
        const message = <?= json_encode($safe_message) ?>;
        const redirectUrl = <?= json_encode($safe_redirect) ?>;

        Swal.fire({
            icon: icon,
            title: `<strong style="color: #034991;">${title}</strong>`,
            html: `
                <div style="text-align: center; padding: 20px;">
                    <p style="font-size: 1.05rem; margin-top: 10px; color: #333;">${message}</p>
                    <p style="font-size: 0.9rem; color: #999; margin-top: 15px;">
                        Será redirigido automáticamente...
                    </p>
                </div>
            `,
            confirmButtonText: '<i class="bi bi-check2-circle"></i> Aceptar',
            confirmButtonColor: '#034991',
            allowOutsideClick: false,
            allowEscapeKey: false,
            timer: 7000,
            timerProgressBar: true,
            showClass: {
                popup: 'animate__animated animate__fadeInDown'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutUp'
            },
            customClass: {
                popup: 'swal2-border-radius',
                confirmButton: 'swal2-confirm-btn'
            }
        }).then(function () {
            window.location.href = redirectUrl;
        });
    })();
</script>
</body>
</html>
<?php
    exit;
}

function detect_mime(string $tmp_name): string {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    return $finfo->file($tmp_name) ?: '';
}

function is_valid_docx_file(string $tmp_name): bool {
    if (!class_exists('ZipArchive')) {
        return false;
    }

    $zip = new ZipArchive();

    if ($zip->open($tmp_name) !== true) {
        return false;
    }

    $has_content_types = $zip->locateName('[Content_Types].xml') !== false;
    $has_document_xml = $zip->locateName('word/document.xml') !== false;

    $zip->close();

    return $has_content_types && $has_document_xml;
}

function ini_size_to_bytes(string $value): int {
    $value = trim($value);
    if ($value === '') return 0;
    $last = strtolower(substr($value, -1));
    $num = (float)$value;
    switch ($last) {
        case 'g':
            return (int)($num * 1024 * 1024 * 1024);
        case 'm':
            return (int)($num * 1024 * 1024);
        case 'k':
            return (int)($num * 1024);
        default:
            return (int)$num;
    }
}

function bytes_to_human(int $bytes): string {
    if ($bytes <= 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = (int)floor(log($bytes, 1024));
    $i = max(0, min($i, count($units) - 1));
    $value = $bytes / (1024 ** $i);
    return ($i === 0 ? (string)(int)$value : number_format($value, 2)) . ' ' . $units[$i];
}

function upload_error_to_message(int $code, string $label): string {
    $uploadMax = ini_get('upload_max_filesize') ?: '';
    $postMax = ini_get('post_max_size') ?: '';
    $uploadMaxBytes = ini_size_to_bytes($uploadMax);
    $postMaxBytes = ini_size_to_bytes($postMax);

    switch ($code) {
        case UPLOAD_ERR_INI_SIZE:
            return "El archivo de {$label} excede el límite del servidor (upload_max_filesize={$uploadMax}).";
        case UPLOAD_ERR_FORM_SIZE:
            return "El archivo de {$label} excede el límite del formulario.";
        case UPLOAD_ERR_PARTIAL:
            return "El archivo de {$label} se subió parcialmente. Intente de nuevo.";
        case UPLOAD_ERR_NO_FILE:
            return "No se adjuntó el archivo de {$label}.";
        case UPLOAD_ERR_NO_TMP_DIR:
            return "Falta la carpeta temporal del servidor para subir {$label}.";
        case UPLOAD_ERR_CANT_WRITE:
            return "El servidor no pudo escribir el archivo de {$label} en disco.";
        case UPLOAD_ERR_EXTENSION:
            return "Una extensión de PHP bloqueó la subida de {$label}.";
        default:
            $hint = '';
            if ($postMaxBytes > 0) {
                $hint = " (post_max_size={$postMax}, upload_max_filesize={$uploadMax})";
            }
            return "Error al subir {$label} (código {$code}){$hint}.";
    }
}

function validate_uploaded_file(
    array $file,
    array $allowed_mimes,
    array $allowed_exts,
    int $max_mb,
    string $label
): array {
    $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($error !== UPLOAD_ERR_OK) {
        throw new Exception(upload_error_to_message($error, $label));
    }

    $name = (string)($file['name'] ?? '');
    $tmp = (string)($file['tmp_name'] ?? '');
    $size = (int)($file['size'] ?? 0);

    if ($name === '') {
        throw new Exception("$label no tiene nombre de archivo");
    }

    if ($tmp === '' || !is_uploaded_file($tmp)) {
        throw new Exception("$label no fue recibido correctamente");
    }

    if (!is_readable($tmp)) {
        throw new Exception("No se pudo leer el archivo de $label");
    }

    if ($size <= 0) {
        throw new Exception("$label está vacío o no es válido");
    }

    $max_bytes = $max_mb * 1024 * 1024;
    if ($size > $max_bytes) {
        throw new Exception("$label excede el tamaño máximo de {$max_mb} MB");
    }

    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed_exts, true)) {
        throw new Exception("$label debe tener extensión: " . implode(', ', $allowed_exts));
    }

    $mime = detect_mime($tmp);

    // Algunos DOCX pueden ser detectados como application/zip u octet-stream.
    // En ese caso se valida su estructura interna para evitar aceptar ZIPs falsos.
    $is_docx = $ext === 'docx' && in_array('docx', $allowed_exts, true);

    if ($is_docx && in_array($mime, ['application/zip', 'application/octet-stream'], true)) {
        if (!is_valid_docx_file($tmp)) {
            throw new Exception("$label no es un DOCX válido");
        }

        $mime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    }

    if (!in_array($mime, $allowed_mimes, true)) {
        throw new Exception("$label no es un archivo válido. Tipo detectado: $mime");
    }

    $content = file_get_contents($tmp);

    if ($content === false || strlen($content) === 0) {
        throw new Exception("No se pudo leer el contenido de $label");
    }

    return [
        'name' => $name,
        'mime' => $mime,
        'size' => $size,
        'content' => $content,
    ];
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Cuando se excede post_max_size, PHP suele dejar $_POST y $_FILES vacíos.
    // Detectamos este caso y mostramos un mensaje útil.
    if (empty($_POST) && empty($_FILES)) {
        $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
        $postMax = ini_get('post_max_size') ?: '';
        $postMaxBytes = ini_size_to_bytes($postMax);
        if ($contentLength > 0 && $postMaxBytes > 0 && $contentLength > $postMaxBytes) {
            $uploadMax = ini_get('upload_max_filesize') ?: '';
            throw new Exception(
                'La solicitud excede el límite del servidor (post_max_size=' . $postMax .
                ', upload_max_filesize=' . $uploadMax .
                '). Tamaño recibido: ' . bytes_to_human($contentLength)
            );
        }
    }

    $applicant_id = trim($_POST['applicant_id'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $id_tipo_tel = trim($_POST['id_tipo_tel'] ?? '');
    $institution = trim($_POST['institution'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $linked_student_id = trim($_POST['linked_student_id'] ?? '');
    $postulation_type = trim($_POST['postulation_type'] ?? '');
    $committee_subrole = trim($_POST['committee_subrole'] ?? '');
    $committee_role = trim($_POST['committee_role'] ?? '');

    if ($applicant_id === '' || $full_name === '' || $email === '' || $institution === '' || $specialization === '') {
        throw new Exception('Debe completar todos los campos requeridos');
    }

    $valid_postulation_types = ['Asesor Externo', 'Asesor Interno', 'Tutor'];
    if (!in_array($postulation_type, $valid_postulation_types, true)) {
        throw new Exception('Debe seleccionar el tipo de postulación.');
    }

    if ($postulation_type === 'Tutor') {
        $committee_subrole = null;
        $committee_role = 'Tutor';
    } else {
        $valid_subroles = ['Asesor 1', 'Asesor 2'];
        if (!in_array($committee_subrole, $valid_subroles, true)) {
            throw new Exception('Debe seleccionar Asesor 1 o Asesor 2 para este tipo de postulación.');
        }
        $committee_role = $committee_subrole;
    }

    // El estudiante a asesorar es requerido
    if ($linked_student_id === '') {
        throw new Exception('Debe seleccionar un estudiante a asesorar');
    }

    if (strlen($applicant_id) > 50) {
        throw new Exception('El identificador excede el máximo de 50 caracteres');
    }

    if (strlen($full_name) > 255 || strlen($institution) > 255 || strlen($specialization) > 255) {
        throw new Exception('Uno de los campos excede el máximo de 255 caracteres');
    }

    if (strlen($email) > 100) {
        throw new Exception('El correo excede el máximo de 100 caracteres');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('El correo electrónico no es válido');
    }

    if ($telefono !== '' && strlen($telefono) > 15) {
        throw new Exception('El teléfono excede el máximo de 15 caracteres');
    }

    if ($id_tipo_tel === '') {
        $id_tipo_tel = null;
    }

    $cv = validate_uploaded_file(
        $_FILES['cv_document'] ?? [],
        [
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ],
        ['pdf', 'docx'],
        5,
        'el Currículum'
    );

    $id_copy = validate_uploaded_file(
        $_FILES['id_copy_document'] ?? [],
        ['application/pdf', 'image/jpeg', 'image/png'],
        ['pdf', 'jpg', 'jpeg', 'png'],
        2,
        'la fotocopia de cédula'
    );

    $cover_letter = validate_uploaded_file(
        $_FILES['cover_letter_document'] ?? [],
        ['application/pdf'],
        ['pdf'],
        5,
        'la carta de solicitud'
    );

    // =============================== BD ===============================
    include_once __DIR__ . '/inc/db/bdcommon.inc';

    $conn = new mysqli($db_host, $usuario, $clave, $db);
    if ($conn->connect_error) {
        throw new Exception('Error de conexión a la base de datos');
    }
    $conn->set_charset('utf8');

    $table_check = $conn->query("SHOW TABLES LIKE 'external_advisor_profile_requests'");
    if (!$table_check || $table_check->num_rows === 0) {
        $conn->close();
        throw new Exception('La tabla external_advisor_profile_requests no existe.');
    }

    $existing_status = null;
    $stmt_check = $conn->prepare('SELECT status FROM external_advisor_profile_requests WHERE applicant_id = ? LIMIT 1');
    $stmt_check->bind_param('s', $applicant_id);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();
    if ($res_check && $res_check->num_rows > 0) {
        $existing_status = ($res_check->fetch_assoc())['status'] ?? null;
    }
    $stmt_check->close();

    if (in_array($existing_status, ['En Revisión', 'En Revision', 'Aprobado'], true)) {
        $conn->close();
        throw new Exception('Ya existe una solicitud para esta cédula con estado: ' . $existing_status . '');
    }

    // Verificar que el email no esté siendo usado por otro usuario diferente
    $stmt_email = $conn->prepare('SELECT applicant_id FROM external_advisor_profile_requests WHERE email = ? AND applicant_id != ? LIMIT 1');
    $stmt_email->bind_param('ss', $email, $applicant_id);
    $stmt_email->execute();
    $res_email = $stmt_email->get_result();
    if ($res_email && $res_email->num_rows > 0) {
        $other_user = $res_email->fetch_assoc();
        $stmt_email->close();
        $conn->close();
        throw new Exception('El correo electrónico ' . htmlspecialchars($email) . ' ya está registrado para otro usuario (ID: ' . htmlspecialchars($other_user['applicant_id']) . '). Por favor use otro correo o contacte a soporte.');
    }
    $stmt_email->close();

    $null_blob_1 = null;
    $null_blob_2 = null;
    $null_blob_3 = null;

    if ($existing_status === 'Rechazado') {
        $sql = "UPDATE external_advisor_profile_requests
                SET full_name = ?, email = ?, telefono = ?, id_tipo_tel = ?, institution = ?, specialization = ?,
                    postulation_type = ?, committee_subrole = ?, committee_role = ?,
                    cv_document = ?, cv_file_name = ?, cv_mime_type = ?, cv_file_size = ?,
                    id_copy_document = ?, id_copy_file_name = ?, id_copy_mime_type = ?, id_copy_file_size = ?,
                    cover_letter_document = ?, cover_letter_file_name = ?, cover_letter_mime_type = ?, cover_letter_file_size = ?,
                    linked_student_id = ?, linked_comite_id = NULL, linked_at = NULL,
                    status = 'En Revision', admin_comments = NULL, reviewed_by = NULL, reviewed_at = NULL,
                    updated_at = NOW()
                WHERE applicant_id = ?";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $conn->close();
            throw new Exception('Error interno al preparar la actualización');
        }

        $telefono_param = ($telefono === '') ? null : $telefono;
        $linked_student_param = ($linked_student_id === '') ? null : $linked_student_id;

        $stmt->bind_param(
            'sssssssssbssibssibssiss',
            $full_name,
            $email,
            $telefono_param,
            $id_tipo_tel,
            $institution,
            $specialization,
            $postulation_type,
            $committee_subrole,
            $committee_role,
            $null_blob_1,
            $cv['name'],
            $cv['mime'],
            $cv['size'],
            $null_blob_2,
            $id_copy['name'],
            $id_copy['mime'],
            $id_copy['size'],
            $null_blob_3,
            $cover_letter['name'],
            $cover_letter['mime'],
            $cover_letter['size'],
            $linked_student_param,
            $applicant_id
        );

        $stmt->send_long_data(9, $cv['content']);
        $stmt->send_long_data(13, $id_copy['content']);
        $stmt->send_long_data(17, $cover_letter['content']);

        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            $conn->close();
            if (
                stripos($err, 'max_allowed_packet') !== false ||
                stripos($err, 'packet') !== false ||
                stripos($err, 'server has gone away') !== false
            ) {
                throw new Exception(
                    'No se pudo guardar los documentos en la base de datos (posible límite de MySQL: max_allowed_packet). ' .
                    'Intente con archivos más livianos o aumente max_allowed_packet en el servidor.'
                );
            }
            throw new Exception('No se pudo actualizar la solicitud');
        }
        $stmt->close();
    } else {
        $sql = "INSERT INTO external_advisor_profile_requests
            (applicant_id, full_name, email, telefono, id_tipo_tel, institution, specialization,
             postulation_type, committee_subrole, committee_role,
             cv_document, cv_file_name, cv_mime_type, cv_file_size,
             id_copy_document, id_copy_file_name, id_copy_mime_type, id_copy_file_size,
             cover_letter_document, cover_letter_file_name, cover_letter_mime_type, cover_letter_file_size,
             linked_student_id,
             status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'En Revision', NOW(), NOW())";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $conn->close();
            throw new Exception('Error interno al preparar la inserción');
        }

        $telefono_param = ($telefono === '') ? null : $telefono;
        $linked_student_param = ($linked_student_id === '') ? null : $linked_student_id;

        $stmt->bind_param(
            'ssssssssssbssibssibssis',
            $applicant_id,
            $full_name,
            $email,
            $telefono_param,
            $id_tipo_tel,
            $institution,
            $specialization,
            $postulation_type,
            $committee_subrole,
            $committee_role,
            $null_blob_1,
            $cv['name'],
            $cv['mime'],
            $cv['size'],
            $null_blob_2,
            $id_copy['name'],
            $id_copy['mime'],
            $id_copy['size'],
            $null_blob_3,
            $cover_letter['name'],
            $cover_letter['mime'],
            $cover_letter['size'],
            $linked_student_param
        );

        $stmt->send_long_data(10, $cv['content']);
        $stmt->send_long_data(14, $id_copy['content']);
        $stmt->send_long_data(18, $cover_letter['content']);

        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            $conn->close();

            if (stripos($err, 'Duplicate') !== false) {
                throw new Exception('Ya existe una solicitud registrada para esta cédula');
            }

            if (
                stripos($err, 'max_allowed_packet') !== false ||
                stripos($err, 'packet') !== false ||
                stripos($err, 'server has gone away') !== false
            ) {
                throw new Exception(
                    'No se pudo guardar los documentos en la base de datos (posible límite de MySQL: max_allowed_packet). ' .
                    'Intente con archivos más livianos o aumente max_allowed_packet en el servidor.'
                );
            }

            throw new Exception('No se pudo guardar la solicitud');
        }
        $stmt->close();
    }

    hu041_register_audit($conn, $applicant_id, 'REQUEST_CREATED', 'solicitud_comite', null, [
        'postulation_type' => $postulation_type,
        'committee_role' => $committee_role,
        'linked_student_id' => $linked_student_id,
    ]);

    $conn->close();

    // =============================== OBTENER NOMBRE DEL ESTUDIANTE VINCULADO ===============================
    $linked_student_name = 'No especificado';
    if (!empty($linked_student_id)) {
        try {
            include_once __DIR__ . '/inc/db/bdcommon.inc';
            $conn2 = new mysqli($db_host, $usuario, $clave, $db);
            $conn2->set_charset('utf8');
            $stmt2 = $conn2->prepare('SELECT nombre FROM sis_user WHERE id = ? LIMIT 1');
            $stmt2->bind_param('s', $linked_student_id);
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            if ($row2 = $res2->fetch_assoc()) {
                $linked_student_name = $row2['nombre'];
            }
            $stmt2->close();
            $conn2->close();
        } catch (Exception $e) {
            error_log('Error obteniendo nombre estudiante: ' . $e->getMessage());
        }
    }

    // =============================== NOTIFICACIÓN POR CORREO ===============================
    $secretaria_email = 'rodri100ro@gmail.com';
    $from_email = 'rodri100ro@gmail.com';

    $headers = "From: {$from_email}\r\n";
    $headers .= "Reply-To: {$from_email}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";

    $panel_subdireccion_url = $base_url . 'panel_subdireccion.php';

    $subject_secretaria = 'Notificación: Solicitud de Comité Asesor en revisión - SGPFL';
    $message_secretaria = '
    <html><head><meta charset="UTF-8"></head><body>
      <div style="font-family: Arial, sans-serif; color: #333; line-height: 1.6;">
        <p>Estimada/o Secretaría/o de Subdirección,</p>
        <p>Se ha recibido una nueva solicitud para <strong>integrar un Comité Asesor</strong> en el SGPFL.</p>
        <p><strong>Datos del solicitante:</strong></p>
        <ul>
          <li><strong>Fecha:</strong> ' . date('d/m/Y H:i') . '</li>
          <li><strong>ID:</strong> ' . htmlspecialchars($applicant_id) . '</li>
          <li><strong>Nombre:</strong> ' . htmlspecialchars($full_name) . '</li>
          <li><strong>Correo:</strong> ' . htmlspecialchars($email) . '</li>
          <li><strong>Tipo de postulación:</strong> ' . htmlspecialchars($postulation_type) . '</li>
          <li><strong>Rol solicitado:</strong> ' . htmlspecialchars($committee_role) . '</li>
          <li><strong>Institución:</strong> ' . htmlspecialchars($institution) . '</li>
          <li><strong>Especialización:</strong> ' . htmlspecialchars($specialization) . '</li>
          <li><strong>Estudiante a asesorar:</strong> ' . htmlspecialchars($linked_student_name) . ' (ID: ' . htmlspecialchars($linked_student_id) . ')</li>
                    <li><strong>Estado:</strong> <strong>En Revision</strong></li>
        </ul>
        <p>Puede ingresar al sistema para visualizar las solicitudes pendientes:</p>
        <p><a href="' . htmlspecialchars($panel_subdireccion_url) . '">' . htmlspecialchars($panel_subdireccion_url) . '</a></p>
        <p style="margin-top:20px; font-size:12px; color:#777;">' . date('d/m/Y') . '</p>
      </div>
    </body></html>';

    // Enviar correo a la Secretaría de Subdirección
    @mail($secretaria_email, $subject_secretaria, $message_secretaria, $headers);

    $subject_applicant = 'Confirmación: Solicitud para Comité Asesor recibida - SGPFL';
    $message_applicant = '
    <html><head><meta charset="UTF-8"></head><body>
      <div style="font-family: Arial, sans-serif; color: #333; line-height: 1.6;">
        <p>Estimado/a ' . htmlspecialchars($full_name) . ',</p>
                <p>Le confirmamos que su solicitud para integrar un <strong>Comité Asesor</strong> fue recibida correctamente.</p>
        <ul>
          <li><strong>Fecha:</strong> ' . date('d/m/Y H:i') . '</li>
                    <li><strong>Tipo de postulación:</strong> ' . htmlspecialchars($postulation_type) . '</li>
                    <li><strong>Rol solicitado:</strong> ' . htmlspecialchars($committee_role) . '</li>
                    <li><strong>Estado actual:</strong> <strong>En Revision</strong></li>
        </ul>
        <p>La Subdirección revisará la información y los documentos aportados. Recibirá una notificación cuando exista un resultado.</p>
        <p style="margin-top:20px; font-size:12px; color:#777;">' . date('d/m/Y') . '</p>
      </div>
    </body></html>';

    // Enviar correo al solicitante
    @mail($email, $subject_applicant, $message_applicant, $headers);

    render_swal_and_exit('success', 'Solicitud enviada', 'Su solicitud fue registrada y quedó en revisión, pronto le notificaremos el resultado.', $redirect_ok);

} catch (Exception $e) {
    error_log('Error registro asesor externo: ' . $e->getMessage());
    render_swal_and_exit('error', 'No se pudo enviar', $e->getMessage(), $redirect_back);
}
