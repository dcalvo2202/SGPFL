<?php

declare(strict_types=1);

include("../../login/check.php");

require_once __DIR__ . '/CommitteeMinuteProjectRepository.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$current_user_id = (string)$mySessionController->getVar("usuario");
$current_user_rol = (int)$mySessionController->getVar("rol");
$base_url = (string)$mySessionController->getVar("cds_domain") . (string)$mySessionController->getVar("cds_locate");

function redirect_with_download_error(string $base_url, string $message, ?int $project_id = null): never
{
    $_SESSION['minute_flash'] = [
        'type' => 'danger',
        'message' => $message,
    ];

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    $location = $base_url . 'panel_committee_minutes.php';

    if ($project_id !== null && $project_id > 0) {
        $location .= '?project_id=' . urlencode((string)$project_id);
    }

    header('Location: ' . $location);
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
    $file_name = preg_replace('/[^\w\-. ]/u', '_', $file_name) ?? 'minuta.pdf';
    $file_name = trim($file_name);

    if ($file_name === '') {
        return 'minuta.pdf';
    }

    return mb_substr($file_name, 0, 255);
}

function drain_remaining_results(mysqli $connection): void
{
    while ($connection->more_results()) {
        $connection->next_result();
        $result = $connection->store_result();
        if ($result instanceof mysqli_result) {
            $result->free();
        }
    }
}

$minute_id = isset($_GET['minute_id']) ? (int)$_GET['minute_id'] : 0;

if ($minute_id <= 0) {
    redirect_with_download_error($base_url, 'Debe indicar una minuta válida para descargar.');
}

try {
    $dbcfg = load_db_config(__DIR__ . '/../../../inc/db/bdcommon.inc');
    $connection = new mysqli($dbcfg['host'], $dbcfg['user'], $dbcfg['pass'], $dbcfg['name']);
    $connection->set_charset('utf8');

    $sql = "
        SELECT
            pm.id,
            pm.project_id,
            pm.session_date,
            pm.file_name,
            pm.mime_type,
            pm.file_size,
            pm.file_data
        FROM project_minutes pm
        WHERE pm.id = ?
        LIMIT 1
    ";

    $stmt = $connection->prepare($sql);
    if ($stmt === false) {
        throw new RuntimeException('No se pudo preparar la consulta de descarga de la minuta.');
    }

    $stmt->bind_param('i', $minute_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $minute = $result->fetch_assoc();
    $stmt->close();

    if (!$minute) {
        $connection->close();
        redirect_with_download_error($base_url, 'La minuta solicitada no existe.');
    }

    $project_id = (int)$minute['project_id'];

    $project_repository = new CommitteeMinuteProjectRepository($connection);

    if ($current_user_rol === 2) {
        // El Gestor (rol 2) puede descargar minutas de cualquier proyecto aprobado
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
            redirect_with_download_error($base_url, 'No tiene permiso para descargar minutas de ese proyecto.', $project_id);
        }
    } else {
        // Rol 3 (comité): solo proyectos donde es tutor o asesor
        $allowed_projects = $project_repository->findProjectsByCommitteeMember($current_user_id);

        $allowed_project_ids = array_map(
            static fn(array $project): int => (int)$project['id_aprobado'],
            $allowed_projects
        );

        if (!in_array($project_id, $allowed_project_ids, true)) {
            $connection->close();
            redirect_with_download_error(
                $base_url,
                'No tiene permiso para descargar minutas de ese proyecto.',
                $project_id
            );
        }
    }

    $file_name = normalize_download_file_name((string)$minute['file_name']);
    $mime_type = trim((string)$minute['mime_type']);
    $file_size = (int)$minute['file_size'];
    $file_data = $minute['file_data'];

    if (!is_string($file_data) || $file_data === '') {
        $connection->close();
        redirect_with_download_error(
            $base_url,
            'La minuta no tiene contenido disponible para descarga.',
            $project_id
        );
    }

    if ($mime_type === '') {
        $mime_type = 'application/pdf';
    }

    // Bitácora de descarga (no bloquea la descarga si falla)
    try {
        $ip_address = mb_substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
        $device_info = mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
        $detail = sprintf(
            'HU-035 minuta descargada | minute_id=%d | project_id=%d | file_name=%s',
            $minute_id,
            $project_id,
            $file_name
        );

        $log_stmt = $connection->prepare("CALL insert_access_log(?, ?, ?, ?, ?, ?, @log_res)");
        if ($log_stmt !== false) {
            $action_type = 'MINUTE_DOWNLOAD';
            $action_result = 'SUCCESS';

            $log_stmt->bind_param(
                'ssssss',
                $current_user_id,
                $action_type,
                $action_result,
                $ip_address,
                $device_info,
                $detail
            );
            $log_stmt->execute();
            $log_stmt->close();
            drain_remaining_results($connection);
        }
    } catch (Throwable $log_error) {
        // No bloquear la descarga por un fallo en bitácora
    }

    $connection->close();

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: attachment; filename="' . $file_name . '"; filename*=UTF-8\'\'' . rawurlencode($file_name));
    header('Content-Length: ' . (string)$file_size);
    header('Cache-Control: private, must-revalidate');
    header('Pragma: public');
    header('Expires: 0');

    echo $file_data;
    exit;
} catch (Throwable $e) {
    redirect_with_download_error(
        $base_url,
        'No se pudo descargar la minuta: ' . $e->getMessage()
    );
}