<?php

declare(strict_types=1);

include("../../login/check.php");

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$current_user_id = (string)$mySessionController->getVar("usuario");
$current_user_rol = (int)$mySessionController->getVar("rol");
$base_url = (string)$mySessionController->getVar("cds_domain") . (string)$mySessionController->getVar("cds_locate");

function redirect_with_download_error(string $base_url, string $message): never
{
    $_SESSION['agreement_flash'] = [
        'type' => 'danger',
        'message' => $message,
    ];

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    header('Location: ' . $base_url . 'PanelRegistroAcuerdo.php');
    exit;
}

function load_db_config(string $path): array
{
    $db_host = null;
    $usuario = null;
    $clave = null;
    $db = null;

    require $path;

    return [
        'host' => (string)$db_host,
        'user' => (string)$usuario,
        'pass' => (string)$clave,
        'name' => (string)$db,
    ];
}

function normalize_download_file_name(string $file_name): string
{
    $file_name = basename($file_name);
    $file_name = preg_replace('/[^\w\-. ]/u', '_', $file_name) ?? 'acuerdo.pdf';
    $file_name = trim($file_name);

    if ($file_name === '') {
        return 'acuerdo.pdf';
    }

    return mb_substr($file_name, 0, 255);
}

function build_readable_file_name(string $project_name, string $identifier, string $type = 'aprobacion'): string
{
    $clean_name = preg_replace('/[^\w\s\-áéíóúñÁÉÍÓÚÑ]/u', '', $project_name);
    $clean_name = preg_replace('/\s+/', '_', trim($clean_name));
    $clean_name = mb_substr($clean_name, 0, 50);
    $type_label = 'Acta_Aprobacion';
    return $type_label . '_' . $clean_name . '.pdf';
}

$approval_id = isset($_GET['approval_id']) ? (int)$_GET['approval_id'] : 0;

if ($approval_id <= 0) {
    redirect_with_download_error($base_url, 'Debe indicar un acuerdo de aprobación válido para descargar.');
}

try {
    $dbcfg = load_db_config(__DIR__ . '/../../../inc/db/bdcommon.inc');
    $connection = new mysqli($dbcfg['host'], $dbcfg['user'], $dbcfg['pass'], $dbcfg['name']);
    $connection->set_charset('utf8');

    $sql = "
        SELECT
            pa.id_aprobado,
            pa.nombre,
            pa.identificador,
            pa.documento,
            pa.comite_id,
            p.id_aprobado AS project_id
        FROM proyecto_aprobado pa
        INNER JOIN proyecto_aprobado p
            ON p.id_aprobado = pa.id_aprobado
        WHERE pa.id_aprobado = ? AND pa.aprobado = 1
        LIMIT 1
    ";

    $stmt = $connection->prepare($sql);
    if ($stmt === false) {
        throw new RuntimeException('No se pudo preparar la consulta de descarga del acuerdo.');
    }

    $stmt->bind_param('i', $approval_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $agreement = $result->fetch_assoc();
    $stmt->close();

    if (!$agreement) {
        $connection->close();
        redirect_with_download_error($base_url, 'El acuerdo de aprobación solicitado no existe.');
    }

    $project_id = (int)$agreement['project_id'];

    // Verificar permisos de acceso
    if ($current_user_rol === 2) {
        // El Gestor (rol 2) puede descargar acuerdos de cualquier proyecto aprobado
        $stmt_check = $connection->prepare(
            "SELECT id_aprobado FROM proyecto_aprobado WHERE id_aprobado = ? AND aprobado = 1 LIMIT 1"
        );
        if ($stmt_check === false) {
            throw new RuntimeException('No se pudo verificar el acceso al proyecto.');
        }
        $stmt_check->bind_param('i', $project_id);
        $stmt_check->execute();
        $project_allowed = $stmt_check->get_result()->fetch_assoc() !== null;
        $stmt_check->close();

        if (!$project_allowed) {
            $connection->close();
            redirect_with_download_error($base_url, 'No tiene permiso para descargar acuerdos de ese proyecto.');
        }
    } else {
        // Rol 3 (comité): solo proyectos donde es tutor o asesor
        $sql_allowed = "
            SELECT p.id_aprobado
            FROM proyecto_aprobado p
            INNER JOIN comite c ON c.Id = p.comite_id
            WHERE p.aprobado = 1
              AND p.id_aprobado = ?
              AND (c.tutor = ? OR c.asesor_1 = ? OR c.asesor_2 = ?)
            LIMIT 1
        ";

        $stmt_allowed = $connection->prepare($sql_allowed);
        if ($stmt_allowed === false) {
            throw new RuntimeException('No se pudo verificar el acceso al proyecto.');
        }

        $stmt_allowed->bind_param('isss', $project_id, $current_user_id, $current_user_id, $current_user_id);
        $stmt_allowed->execute();
        $project_allowed = $stmt_allowed->get_result()->fetch_assoc() !== null;
        $stmt_allowed->close();

        if (!$project_allowed) {
            $connection->close();
            redirect_with_download_error($base_url, 'No tiene permiso para descargar acuerdos de ese proyecto.');
        }
    }

    // Obtener documento BLOB y generar nombre de archivo
    $documento_blob = $agreement['documento'];

    if ($documento_blob === null || strlen((string)$documento_blob) === 0) {
        $connection->close();
        redirect_with_download_error($base_url, 'El documento del acuerdo de aprobación está vacío.');
    }

    $file_size = strlen((string)$documento_blob);
    $mime_type = 'application/pdf';
    $file_name = build_readable_file_name(
        (string)$agreement['nombre'],
        (string)$agreement['identificador'],
        'aprobacion'
    );

    // Registrar la descarga
    try {
        $stmt_log = $connection->prepare(
            "INSERT INTO audit_log (action_type, user_id, resource_type, resource_id, created_at)
             VALUES (?, ?, ?, ?, NOW())"
        );
        if ($stmt_log !== false) {
            $action = 'APPROVAL_AGREEMENT_DOWNLOAD';
            $resource_type = 'APPROVAL_AGREEMENT';
            $stmt_log->bind_param('ssis', $action, $current_user_id, $resource_type, $approval_id);
            $stmt_log->execute();
            $stmt_log->close();
        }
    } catch (Throwable $e) {
        // Log error pero permite la descarga
        error_log('Error al registrar descarga de acuerdo de aprobación: ' . $e->getMessage());
    }

    $connection->close();

    // Descargar el documento BLOB
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: attachment; filename="' . $file_name . '"');
    header('Content-Length: ' . $file_size);
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo $documento_blob;
    exit;
} catch (Throwable $e) {
    error_log('Error en descarga de acuerdo de aprobación: ' . $e->getMessage());
    redirect_with_download_error($base_url, 'Ocurrió un error al descargar el archivo: ' . $e->getMessage());
}
