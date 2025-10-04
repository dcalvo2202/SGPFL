<?php
session_start();
require_once '../../../inc/db/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../../proyecto_aprobado.php?err=1'); exit;
}

$nombre = trim($_POST['nombre'] ?? '');
// Opcional: validar que exista realmente en tfg_proposals
$valida = mysqli_prepare($id_con, "SELECT 1 FROM tfg_proposals WHERE title = ?");
mysqli_stmt_bind_param($valida, "s", $nombre);
mysqli_stmt_execute($valida);
$existe = mysqli_stmt_get_result($valida);
if (!$existe || mysqli_num_rows($existe) === 0) {
    mysqli_stmt_close($valida);
    header('Location: ../../../proyecto_aprobado.php?err=1'); exit;
}
mysqli_stmt_close($valida);

$comite_id     = (int)($_POST['comite'] ?? 0);
$fecha_raw     = $_POST['fecha_aprobacion'] ?? '';
$identificador = $_SESSION['identificador_preview'] ?? ($_POST['identificador'] ?? '');
$aprobado      = 1;

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_raw)) {
    header('Location: ../../../proyecto_aprobado.php?err=1'); exit;
}
if ($nombre === '' || !$comite_id) {
    header('Location: ../../../proyecto_aprobado.php?err=1'); exit;
}
if (!isset($_FILES['documento']) || $_FILES['documento']['error'] !== UPLOAD_ERR_OK) {
    header('Location: ../../../proyecto_aprobado.php?err=1'); exit;
}

// Leer archivo
$documento_blob = file_get_contents($_FILES['documento']['tmp_name']);

// Validaciones opcionales
if ($_FILES['documento']['size'] > 10*1024*1024) { // 10MB
    header('Location: ../../../proyecto_aprobado.php?err=1'); exit;
}

$fecha_creacion = $fecha_raw . ' 00:00:00';
$dt = DateTime::createFromFormat('Y-m-d H:i:s', $fecha_creacion);
$dt->modify('+1 year');
$fecha_finalizacion = $dt->format('Y-m-d H:i:s');

$estudiantes = isset($_POST['estudiantes']) ? array_filter((array)$_POST['estudiantes']) : [];
$unique = array_unique($estudiantes);
if (count($unique) < 1 || count($unique) > 8) {
    header('Location: ../../../proyecto_aprobado.php?err=1'); exit;
}

mysqli_begin_transaction($id_con);

try {
    $sql = "INSERT INTO proyecto_aprobado
            (nombre, comite_id, documento, aprobado, identificador, fecha_creacion, fecha_finalizacion)
            VALUES (?,?,?,?,?,?,?)";
    $stmt = mysqli_prepare($id_con, $sql);
    if (!$stmt) { throw new Exception(mysqli_error($id_con)); }
    mysqli_stmt_bind_param(
        $stmt,
        "sisbsss",
        $nombre,
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