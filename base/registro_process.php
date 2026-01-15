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

$base_url = rtrim($cds_domain, '/') . '/' . trim($cds_locate, '/') . '/';
$redirect_ok = $base_url . 'login.php';
$redirect_back = $base_url . 'registro.php';

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

function validate_uploaded_file(array $file, array $allowed_mimes, array $allowed_exts, int $max_mb, string $label): array {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $code = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        throw new Exception("Error al subir $label (código $code)");
    }

    $name = (string)($file['name'] ?? '');
    $size = (int)($file['size'] ?? 0);
    $tmp = (string)($file['tmp_name'] ?? '');

    if ($tmp === '' || !is_uploaded_file($tmp)) {
        throw new Exception("Archivo temporal inválido para $label");
    }

    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_exts, true)) {
        throw new Exception("$label debe tener extensión: " . implode(', ', $allowed_exts));
    }

    $max_bytes = $max_mb * 1024 * 1024;
    if ($size <= 0) {
        throw new Exception("$label está vacío");
    }
    if ($size > $max_bytes) {
        throw new Exception("$label excede el tamaño máximo de {$max_mb}MB");
    }

    $mime = detect_mime($tmp);
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

    $applicant_id = trim($_POST['applicant_id'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $id_tipo_tel = trim($_POST['id_tipo_tel'] ?? '');
    $institution = trim($_POST['institution'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');

    if ($applicant_id === '' || $full_name === '' || $email === '' || $institution === '' || $specialization === '') {
        throw new Exception('Debe completar todos los campos requeridos');
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
        ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
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

    if (in_array($existing_status, ['En Revisión', 'Aprobado'], true)) {
        $conn->close();
        throw new Exception('Ya existe una solicitud para esta cédula con estado: ' . $existing_status . '');
    }

    $null_blob_1 = null;
    $null_blob_2 = null;

    if ($existing_status === 'Rechazado') {
        $sql = "UPDATE external_advisor_profile_requests
                SET full_name = ?, email = ?, telefono = ?, id_tipo_tel = ?, institution = ?, specialization = ?,
                    cv_document = ?, cv_file_name = ?, cv_mime_type = ?, cv_file_size = ?,
                    id_copy_document = ?, id_copy_file_name = ?, id_copy_mime_type = ?, id_copy_file_size = ?,
                    status = 'En Revisión', admin_comments = NULL, reviewed_by = NULL, reviewed_at = NULL,
                    updated_at = NOW()
                WHERE applicant_id = ?";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $conn->close();
            throw new Exception('Error interno al preparar la actualización');
        }

        $telefono_param = ($telefono === '') ? null : $telefono;

        $stmt->bind_param(
            'ssssssbssibssi' . 's',
            $full_name,
            $email,
            $telefono_param,
            $id_tipo_tel,
            $institution,
            $specialization,
            $null_blob_1,
            $cv['name'],
            $cv['mime'],
            $cv['size'],
            $null_blob_2,
            $id_copy['name'],
            $id_copy['mime'],
            $id_copy['size'],
            $applicant_id
        );

        $stmt->send_long_data(6, $cv['content']);
        $stmt->send_long_data(10, $id_copy['content']);

        if (!$stmt->execute()) {
            $stmt->close();
            $conn->close();
            throw new Exception('No se pudo actualizar la solicitud');
        }
        $stmt->close();
    } else {
        $sql = "INSERT INTO external_advisor_profile_requests
                (applicant_id, full_name, email, telefono, id_tipo_tel, institution, specialization,
                 cv_document, cv_file_name, cv_mime_type, cv_file_size,
                 id_copy_document, id_copy_file_name, id_copy_mime_type, id_copy_file_size,
                 status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'En Revisión', NOW(), NOW())";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $conn->close();
            throw new Exception('Error interno al preparar la inserción');
        }

        $telefono_param = ($telefono === '') ? null : $telefono;

        $stmt->bind_param(
            'sssssssbssibssi',
            $applicant_id,
            $full_name,
            $email,
            $telefono_param,
            $id_tipo_tel,
            $institution,
            $specialization,
            $null_blob_1,
            $cv['name'],
            $cv['mime'],
            $cv['size'],
            $null_blob_2,
            $id_copy['name'],
            $id_copy['mime'],
            $id_copy['size']
        );

        $stmt->send_long_data(7, $cv['content']);
        $stmt->send_long_data(11, $id_copy['content']);

        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            $conn->close();

            if (stripos($err, 'Duplicate') !== false) {
                throw new Exception('Ya existe una solicitud registrada para esta cédula');
            }

            throw new Exception('No se pudo guardar la solicitud');
        }
        $stmt->close();
    }

    $conn->close();

    // =============================== NOTIFICACIÓN POR CORREO ===============================
    $secretaria_email = 'rodri100ro@gmail.com';
    $from_email = 'rodri100ro@gmail.com';

    $headers = "From: {$from_email}\r\n";
    $headers .= "Reply-To: {$from_email}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";

    $panel_subdireccion_url = $base_url . 'panel_subdireccion.php';

    $subject_secretaria = 'Notificación: Solicitud de Asesor Externo en revisión - SGPFL';
    $message_secretaria = '
    <html><head><meta charset="UTF-8"></head><body>
      <div style="font-family: Arial, sans-serif; color: #333; line-height: 1.6;">
        <p>Estimada/o Secretaría/o de Subdirección,</p>
        <p>Se ha recibido una nueva solicitud de <strong>Registro de Asesor Externo</strong> en el SGPFL.</p>
        <p><strong>Datos del solicitante:</strong></p>
        <ul>
          <li><strong>Fecha:</strong> ' . date('d/m/Y H:i') . '</li>
          <li><strong>ID:</strong> ' . htmlspecialchars($applicant_id) . '</li>
          <li><strong>Nombre:</strong> ' . htmlspecialchars($full_name) . '</li>
          <li><strong>Correo:</strong> ' . htmlspecialchars($email) . '</li>
          <li><strong>Institución:</strong> ' . htmlspecialchars($institution) . '</li>
          <li><strong>Especialización:</strong> ' . htmlspecialchars($specialization) . '</li>
          <li><strong>Estado:</strong> <strong>En Revisión</strong></li>
        </ul>
        <p>Puede ingresar al sistema para visualizar las solicitudes pendientes:</p>
        <p><a href="' . htmlspecialchars($panel_subdireccion_url) . '">' . htmlspecialchars($panel_subdireccion_url) . '</a></p>
        <p style="margin-top:20px; font-size:12px; color:#777;">' . date('d/m/Y') . '</p>
      </div>
    </body></html>';

    // Enviar correo a la Secretaría de Subdirección
    @mail($secretaria_email, $subject_secretaria, $message_secretaria, $headers);

    $subject_applicant = 'Confirmación: Solicitud recibida para revisión - SGPFL';
    $message_applicant = '
    <html><head><meta charset="UTF-8"></head><body>
      <div style="font-family: Arial, sans-serif; color: #333; line-height: 1.6;">
        <p>Estimado/a ' . htmlspecialchars($full_name) . ',</p>
        <p>Le confirmamos que su solicitud de <strong>Registro como Asesor Externo</strong> fue recibida correctamente.</p>
        <ul>
          <li><strong>Fecha:</strong> ' . date('d/m/Y H:i') . '</li>
          <li><strong>Estado actual:</strong> <strong>En Revisión</strong></li>
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
