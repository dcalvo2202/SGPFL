<?php
session_start();
require_once '../../../inc/db/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../../proyecto_aprobado.php?err=1'); exit;
}

$nombre        = trim($_POST['nombre'] ?? '');
$estudiante_id = trim($_POST['estudiante'] ?? '');
$comite_id     = (int)($_POST['comite'] ?? 0);
$fecha_raw     = $_POST['fecha_aprobacion'] ?? '';
$identificador = $_SESSION['identificador_preview'] ?? ($_POST['identificador'] ?? '');
$aprobado      = 1;

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_raw)) {
    header('Location: ../../../proyecto_aprobado.php?err=1'); exit;
}
if ($nombre === '' || $estudiante_id === '' || !$comite_id) {
    header('Location: ../../../proyecto_aprobado.php?err=1'); exit;
}
if (!isset($_FILES['documento']) || $_FILES['documento']['error'] !== UPLOAD_ERR_OK) {
    header('Location: ../../../proyecto_aprobado.php?err=1'); exit;
}

$fecha_creacion = $fecha_raw . ' 00:00:00';
$documento_blob = file_get_contents($_FILES['documento']['tmp_name']);

$sql = "INSERT INTO proyecto_aprobados
        (nombre, estudiante_id, comite_id, documento, aprobado, identificador, fecha_creacion)
        VALUES (?,?,?,?,?,?,?)";

$stmt = mysqli_prepare($id_con, $sql);
if (!$stmt) {
    header('Location: ../../../proyecto_aprobado.php?err=1'); exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "ssibiss",
    $nombre,
    $estudiante_id,
    $comite_id,
    $documento_blob,
    $aprobado,
    $identificador,
    $fecha_creacion
);

$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

unset($_SESSION['identificador_preview']);

if ($ok) {
    header('Location: ../../../proyecto_aprobado.php?ok=1'); 
} else {
    header('Location: ../../../proyecto_aprobado.php?err=1'); 
}
exit;

$q = "SELECT c.Id,
            t.nombre  AS tutor_nombre,
            a1.nombre AS asesor1_nombre,
            a2.nombre AS asesor2_nombre
      FROM comite c
      JOIN sis_user t  ON t.id  = c.tutor
      JOIN sis_user a1 ON a1.id = c.asesor_1
      JOIN sis_user a2 ON a2.id = c.asesor_2
      ORDER BY c.Id";
$res = mysqli_query($id_con, $q);