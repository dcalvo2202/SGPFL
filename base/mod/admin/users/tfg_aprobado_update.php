<?php
session_start();
require_once '../../../inc/db/db.php';

// Add: helpers to validate/generate unique identificador
function pa_ident_exists(mysqli $db, string $ident): bool {
    $stmt = mysqli_prepare($db, "SELECT 1 FROM proyecto_aprobado WHERE identificador = ? LIMIT 1");
    if (!$stmt) return false;
    mysqli_stmt_bind_param($stmt, "s", $ident);
    mysqli_stmt_execute($stmt);
    $rs = mysqli_stmt_get_result($stmt);
    $exists = $rs && mysqli_fetch_row($rs);
    mysqli_stmt_close($stmt);
    return (bool)$exists;
}

function pa_generar_ident_unico(mysqli $db): string {
    for ($i = 0; $i < 30; $i++) {
        $ident = 'UNA-TFG-' . str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT) . '-' . date('Y');
        if (!pa_ident_exists($db, $ident)) return $ident;
    }
    // Fallback (extremely unlikely to be needed)
    return 'UNA-TFG-' . strtoupper(bin2hex(random_bytes(2))) . '-' . date('Y');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../../proyecto_aprobado.php?err=1'); exit;
}

$nombre = trim($_POST['nombre'] ?? '');                 // puede venir como título
$proposal_id = (int)($_POST['proposal_id'] ?? 0);       // id de tfg_proposals

// Obtiene/valida el título real según lo recibido
$nombre_title = '';
if ($proposal_id > 0) {
    $q = mysqli_prepare($id_con, "SELECT title FROM tfg_proposals WHERE id = ?");
    mysqli_stmt_bind_param($q, "i", $proposal_id);
    mysqli_stmt_execute($q);
    $rs = mysqli_stmt_get_result($q);
    if ($rs && ($row = mysqli_fetch_assoc($rs))) { $nombre_title = $row['title']; }
    mysqli_stmt_close($q);
} elseif ($nombre !== '') {
    $q = mysqli_prepare($id_con, "SELECT title FROM tfg_proposals WHERE title = ?");
    mysqli_stmt_bind_param($q, "s", $nombre);
    mysqli_stmt_execute($q);
    $rs = mysqli_stmt_get_result($q);
    if ($rs && ($row = mysqli_fetch_assoc($rs))) { $nombre_title = $row['title']; }
    mysqli_stmt_close($q);
}

// Si no hay título válido, error
if ($nombre_title === '') {
    header('Location: ../../../proyecto_aprobado.php?err=1'); exit;
}

// Opcional: evita duplicados por nombre
$dup = mysqli_prepare($id_con, "SELECT 1 FROM proyecto_aprobado WHERE nombre = ? LIMIT 1");
mysqli_stmt_bind_param($dup, "s", $nombre_title);
mysqli_stmt_execute($dup);
$dupRs = mysqli_stmt_get_result($dup);
if ($dupRs && mysqli_num_rows($dupRs) > 0) {
    mysqli_stmt_close($dup);
    header('Location: ../../../proyecto_aprobado.php?err=1'); exit;
}
mysqli_stmt_close($dup);

$comite_id     = (int)($_POST['comite'] ?? 0);
$fecha_raw     = $_POST['fecha_aprobacion'] ?? '';
$identificador = $_SESSION['identificador_preview'] ?? ($_POST['identificador'] ?? '');
// Ensure a fresh, unique identificador (prevents reuse from old tabs/back button)
if ($identificador === '' || pa_ident_exists($id_con, $identificador)) {
    $identificador = pa_generar_ident_unico($id_con);
}
// Keep session in sync (optional)
$_SESSION['identificador_preview'] = $identificador;

// Nuevo: leer y validar estado aprobado (1..4)
$aprobado      = isset($_POST['aprobado']) ? (int)$_POST['aprobado'] : 0;

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_raw)) {
    header('Location: ../../../proyecto_aprobado.php?err=1'); exit;
}
if ($nombre === '' || !$comite_id) {
    header('Location: ../../../proyecto_aprobado.php?err=1'); exit;
}
if (!in_array($aprobado, [1,2,3,4], true)) {
    header('Location: ../../../proyecto_aprobado.php?err=1'); exit;
}
if (!isset($_FILES['documento']) || $_FILES['documento']['error'] !== UPLOAD_ERR_OK) {
    header('Location: ../../../proyecto_aprobado.php?err=1'); exit;
}

// Leer archivo
$documento_blob = file_get_contents($_FILES['documento']['tmp_name']);

// Validaciones opcionales
if ($_FILES['documento']['size'] > 8*1024*1024) { // 8MB
    header('Location: ../../../proyecto_aprobado.php?err=1'); exit;
}

$fecha_creacion = $fecha_raw . ' 00:00:00';
$dt = DateTime::createFromFormat('Y-m-d H:i:s', $fecha_creacion);
$dt->modify('+1 year');
$fecha_finalizacion = $dt->format('Y-m-d H:i:s');

$estudiantes = isset($_POST['estudiantes']) ? array_filter((array)$_POST['estudiantes']) : [];
$registered_id = (int)($_POST['registered_id'] ?? 0);

if (count($estudiantes) === 0 && $registered_id > 0) {
    $qpm = mysqli_prepare(
        $id_con,
        "SELECT pm.user_id
         FROM project_members pm
         JOIN sis_login l ON l.id = pm.user_id
         WHERE pm.project_id = ?
           AND pm.status = 'Activo'
           AND l.id_roll = 4"
    );
    if ($qpm) {
        mysqli_stmt_bind_param($qpm, "i", $registered_id);
        mysqli_stmt_execute($qpm);
        $rs = mysqli_stmt_get_result($qpm);
        while ($rs && ($r = mysqli_fetch_assoc($rs))) $estudiantes[] = $r['user_id'];
        mysqli_stmt_close($qpm);
    }
}
$unique = array_unique($estudiantes);
if (count($unique) < 1 || count($unique) > 8) {
    header('Location: ../../../proyecto_aprobado.php?err=1'); exit;
}

mysqli_begin_transaction($id_con);

try {
    $sql = "INSERT INTO proyecto_aprobado
            (nombre, proposal_id, comite_id, documento, aprobado, identificador, fecha_creacion, fecha_finalizacion)
            VALUES (?,?,?,?,?,?,?,?)";
    $stmt = mysqli_prepare($id_con, $sql);
    if (!$stmt) { throw new Exception(mysqli_error($id_con)); }
    mysqli_stmt_bind_param(
        $stmt,
        "siisisss", // nombre(s), proposal_id(i), comite_id(i), documento(s), aprobado(i), identificador(s), fechas(s,s)
        $nombre_title,
        $proposal_id,
        $comite_id,
        $documento_blob,
        $aprobado,
        $identificador,
        $fecha_creacion,
        $fecha_finalizacion
    );
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(mysqli_stmt_error($stmt));
    }
    $projectId = mysqli_insert_id($id_con);
    mysqli_stmt_close($stmt);

    // Insertar estudiantes
    $stmt2 = mysqli_prepare(
        $id_con,
        "INSERT INTO proyecto_aprobado_estudiantes (id_aprobado, estudiante_id) VALUES (?,?)"
    );
    if (!$stmt2) { throw new Exception(mysqli_error($id_con)); }

    foreach ($unique as $eid) {
        mysqli_stmt_bind_param($stmt2, "is", $projectId, $eid);
        if (!mysqli_stmt_execute($stmt2)) {
            throw new Exception(mysqli_stmt_error($stmt2));
        }
    }
    mysqli_stmt_close($stmt2);

    mysqli_commit($id_con);
    unset($_SESSION['identificador_preview']);
    header('Location: ../../../proyecto_aprobado.php?ok=1'); exit;
} catch (Exception $ex) {
    mysqli_rollback($id_con);
    $_SESSION['last_sql_error'] = $ex->getMessage();
    header('Location: ../../../proyecto_aprobado.php?err=1'); exit;
}