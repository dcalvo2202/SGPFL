<?php

declare(strict_types=1);

include("../../login/check.php");

require_once __DIR__ . '/CommitteeMinuteProjectRepository.php';
require_once __DIR__ . '/CommitteeMinuteParticipantsRepository.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

const MINUTE_MAX_FILE_SIZE_BYTES = 20971520; // 20 MB

$current_user_id = (string)$mySessionController->getVar("usuario");
$base_url = (string)$mySessionController->getVar("cds_domain") . (string)$mySessionController->getVar("cds_locate");

function redirect_to_minutes_panel(string $base_url, string $message, string $type, ?int $project_id = null): never
{
    $_SESSION['minute_flash'] = [
        'type' => $type,
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

function normalize_uploaded_file_name(string $file_name): string
{
    $base_name = basename($file_name);
    $base_name = preg_replace('/[^\w\-. ]/u', '_', $base_name) ?? 'minuta.pdf';
    $base_name = trim($base_name);

    if ($base_name === '') {
        return 'minuta.pdf';
    }

    return mb_substr($base_name, 0, 255);
}

function validate_session_date(string $raw_date): bool
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw_date)) {
        return false;
    }

    $date = DateTimeImmutable::createFromFormat('Y-m-d', $raw_date);
    if ($date === false) {
        return false;
    }

    return $date->format('Y-m-d') === $raw_date;
}

function uploaded_file_error_message(int $error_code): string
{
    return match ($error_code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño permitido.',
        UPLOAD_ERR_PARTIAL => 'El archivo se cargó de forma incompleta.',
        UPLOAD_ERR_NO_FILE => 'Debe seleccionar un archivo PDF.',
        UPLOAD_ERR_NO_TMP_DIR => 'No existe un directorio temporal disponible para la carga.',
        UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en disco.',
        UPLOAD_ERR_EXTENSION => 'La carga del archivo fue detenida por una extensión del servidor.',
        default => 'Ocurrió un error desconocido al cargar el archivo.',
    };
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

function minuteAlreadyExists(mysqli $connection, int $project_id, string $session_date): bool
{
    $sql = "
        SELECT 1
        FROM project_minutes
        WHERE project_id = ?
          AND session_date = ?
        LIMIT 1
    ";

    $stmt = $connection->prepare($sql);
    if ($stmt === false) {
        throw new RuntimeException('No se pudo preparar la validación de duplicidad de minuta.');
    }

    $stmt->bind_param('is', $project_id, $session_date);
    $stmt->execute();

    $result = $stmt->get_result();
    $exists = $result->fetch_assoc() !== null;

    $stmt->close();

    return $exists;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to_minutes_panel($base_url, 'Método no permitido para registrar la minuta.', 'danger');
}

$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
$session_date = trim((string)($_POST['session_date'] ?? ''));
$selected_attendees = $_POST['attendees'] ?? [];

if ($project_id <= 0) {
    redirect_to_minutes_panel($base_url, 'Debe seleccionar un proyecto válido.', 'danger');
}

if (!validate_session_date($session_date)) {
    redirect_to_minutes_panel($base_url, 'La fecha de sesión no es válida.', 'danger', $project_id);
}

if (!is_array($selected_attendees)) {
    redirect_to_minutes_panel($base_url, 'La lista de asistentes enviada no es válida.', 'danger', $project_id);
}

$selected_attendees = array_values(array_unique(array_filter(array_map(
    static fn($value): string => trim((string)$value),
    $selected_attendees
), static fn(string $value): bool => $value !== '')));

if ($selected_attendees === []) {
    redirect_to_minutes_panel($base_url, 'Debe marcar al menos un asistente.', 'danger', $project_id);
}

if (!isset($_FILES['minute_pdf']) || !is_array($_FILES['minute_pdf'])) {
    redirect_to_minutes_panel($base_url, 'Debe adjuntar la minuta en PDF.', 'danger', $project_id);
}

$uploaded_file = $_FILES['minute_pdf'];

if ((int)$uploaded_file['error'] !== UPLOAD_ERR_OK) {
    redirect_to_minutes_panel(
        $base_url,
        uploaded_file_error_message((int)$uploaded_file['error']),
        'danger',
        $project_id
    );
}

$file_size = (int)($uploaded_file['size'] ?? 0);
if ($file_size <= 0) {
    redirect_to_minutes_panel($base_url, 'El archivo cargado está vacío.', 'danger', $project_id);
}

if ($file_size > MINUTE_MAX_FILE_SIZE_BYTES) {
    redirect_to_minutes_panel(
        $base_url,
        'La minuta excede el tamaño máximo permitido de 20 MB.',
        'danger',
        $project_id
    );
}

$tmp_name = (string)($uploaded_file['tmp_name'] ?? '');
$original_name = normalize_uploaded_file_name((string)($uploaded_file['name'] ?? 'minuta.pdf'));

if ($tmp_name === '' || !is_uploaded_file($tmp_name)) {
    redirect_to_minutes_panel($base_url, 'No se detectó un archivo subido válido.', 'danger', $project_id);
}

$extension = strtolower((string)pathinfo($original_name, PATHINFO_EXTENSION));
if ($extension !== 'pdf') {
    redirect_to_minutes_panel($base_url, 'La minuta debe estar en formato PDF.', 'danger', $project_id);
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$detected_mime_type = (string)$finfo->file($tmp_name);

if ($detected_mime_type !== 'application/pdf') {
    redirect_to_minutes_panel(
        $base_url,
        'El archivo cargado no corresponde a un PDF válido.',
        'danger',
        $project_id
    );
}

$file_data = file_get_contents($tmp_name);
if ($file_data === false || $file_data === '') {
    redirect_to_minutes_panel($base_url, 'No se pudo leer el contenido del PDF.', 'danger', $project_id);
}

$connection = null;

try {
    $dbcfg = load_db_config(__DIR__ . '/../../../inc/db/bdcommon.inc');
    $connection = new mysqli($dbcfg['host'], $dbcfg['user'], $dbcfg['pass'], $dbcfg['name']);
    $connection->set_charset('utf8');

    $project_repository = new CommitteeMinuteProjectRepository($connection);
    $participant_repository = new CommitteeMinuteParticipantsRepository($connection);

    $projects = $project_repository->findProjectsByCommitteeMember($current_user_id);
    $allowed_project_ids = array_map(
        static fn(array $project): int => (int)$project['id_aprobado'],
        $projects
    );

    if (!in_array($project_id, $allowed_project_ids, true)) {
        redirect_to_minutes_panel(
            $base_url,
            'No tiene permiso para registrar minutas en ese proyecto.',
            'danger'
        );
    }

    $participants = $participant_repository->findParticipantsByProjectId($project_id);
    if ($participants === []) {
        redirect_to_minutes_panel(
            $base_url,
            'El proyecto no tiene participantes válidos para registrar asistencia.',
            'danger',
            $project_id
        );
    }

    $participant_roles = [];
    foreach ($participants as $participant) {
        $participant_roles[(string)$participant['user_id']] = (string)$participant['participant_role'];
    }

    $invalid_attendees = array_diff($selected_attendees, array_keys($participant_roles));
    if ($invalid_attendees !== []) {
        redirect_to_minutes_panel(
            $base_url,
            'Se detectaron asistentes no válidos para el proyecto seleccionado.',
            'danger',
            $project_id
        );
    }

    if (minuteAlreadyExists($connection, $project_id, $session_date)) {
    redirect_to_minutes_panel(
        $base_url,
        'Ya existe una minuta registrada para este proyecto en esa fecha de sesión.',
        'danger',
        $project_id
    );
    }

    $connection->begin_transaction();

    $insert_minute_sql = "
        INSERT INTO project_minutes
            (project_id, session_date, file_name, mime_type, file_size, file_data, uploaded_by)
        VALUES
            (?, ?, ?, ?, ?, ?, ?)
    ";

    $minute_stmt = $connection->prepare($insert_minute_sql);
    $mime_type = 'application/pdf';

    $minute_stmt->bind_param(
        'isssibs',
        $project_id,
        $session_date,
        $original_name,
        $mime_type,
        $file_size,
        $file_data,
        $current_user_id
    );
    $minute_stmt->send_long_data(5, $file_data);
    $minute_stmt->execute();

    $minute_id = (int)$connection->insert_id;
    $minute_stmt->close();

    $insert_attendee_sql = "
        INSERT INTO project_minute_attendees
            (minute_id, user_id, participant_role, attended)
        VALUES
            (?, ?, ?, ?)
    ";

    $attendee_stmt = $connection->prepare($insert_attendee_sql);

    foreach ($participants as $participant) {
        $participant_user_id = (string)$participant['user_id'];
        $participant_role = (string)$participant['participant_role'];
        $attended = in_array($participant_user_id, $selected_attendees, true) ? 1 : 0;

        $attendee_stmt->bind_param(
            'issi',
            $minute_id,
            $participant_user_id,
            $participant_role,
            $attended
        );
        $attendee_stmt->execute();
    }

    $attendee_stmt->close();
    $connection->commit();

    $ip_address = mb_substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
    $device_info = mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    $detail = sprintf(
        'HU-035 minuta registrada | minute_id=%d | project_id=%d | session_date=%s | file_name=%s | attended_count=%d | total_participants=%d',
        $minute_id,
        $project_id,
        $session_date,
        $original_name,
        count($selected_attendees),
        count($participants)
    );

    $log_stmt = $connection->prepare("CALL insert_access_log(?, ?, ?, ?, ?, ?, @log_res)");
    $action_type = 'MINUTE_UPLOAD';
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

    $connection->close();

    redirect_to_minutes_panel(
    $base_url,
    'La minuta se registró correctamente. No se permitirá otra minuta para esa misma fecha en este proyecto.',
    'success',
    null
    );

} catch (Throwable $e) {
    if ($connection instanceof mysqli) {
        try {
            if ($connection->errno !== 0 || $connection->ping()) {
                $connection->rollback();
            }
        } catch (Throwable $rollback_error) {
        }

        try {
            $connection->close();
        } catch (Throwable $close_error) {
        }
    }

    if ($e instanceof mysqli_sql_exception && (int)$e->getCode() === 1062) {
    redirect_to_minutes_panel(
        $base_url,
        'Ya existe una minuta registrada para este proyecto en esa fecha de sesión.',
        'danger',
        $project_id > 0 ? $project_id : null
    );
    }

    redirect_to_minutes_panel(
        $base_url,
        'No se pudo registrar la minuta: ' . $e->getMessage(),
        'danger',
        $project_id > 0 ? $project_id : null
    );
}