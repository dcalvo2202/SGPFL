<?php
session_start();
require_once __DIR__ . '/../../../inc/db/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../../proyecto_aprobado.php?err=1');
    exit;
}

$nombre        = trim($_POST['nombre'] ?? '');
$estudiante_id = trim($_POST['estudiante'] ?? '');
$comite_id     = (int)($_POST['comite'] ?? 0);
$categoria_id  = (int)($_POST['categoria'] ?? 0);
$identificador = $_SESSION['identificador_preview'] ?? ($_POST['identificador'] ?? '');
$fecha_raw     = $_POST['fecha_aprobacion'] ?? '';
$aprobado      = 1; // o 0 si quieres marcar luego

// Validar fecha (formato YYYY-MM-DD)
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_raw)) {
    $fecha_aprobacion = $fecha_raw . ' 00:00:00';
} else {
    header('Location: ../../../proyecto_aprobado.php?err=1'); exit;
}

// Validar archivo
if (!isset($_FILES['documento']) || $_FILES['documento']['error'] !== UPLOAD_ERR_OK) {
    header('Location: ../../../proyecto_aprobado.php?err=1'); exit;
}

$doc_tmp  = $_FILES['documento']['tmp_name'];
$documento_blob = file_get_contents($doc_tmp);

// Validaciones mínimas
if ($nombre === '' || $estudiante_id === '' || !$comite_id || !$categoria_id) {
    header('Location: ../../../proyecto_aprobado.php?err=1'); exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=base_db;charset=utf8mb4","root","");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("
      INSERT INTO proyecto_aprobados
      (nombre, estudiante_id, comite_id, categoria_id, documento, aprobado, identificador, fecha_creacion)
      VALUES (:n,:e,:c,:cat,:doc,:ap,:id,:f)
    ");
    $stmt->bindValue(':n',  $nombre);
    $stmt->bindValue(':e',  $estudiante_id);
    $stmt->bindValue(':c',  $comite_id, PDO::PARAM_INT);
    $stmt->bindValue(':cat', $categoria_id, PDO::PARAM_INT);
    $stmt->bindValue(':doc', $documento_blob, PDO::PARAM_LOB);
    $stmt->bindValue(':ap',  $aprobado, PDO::PARAM_INT);
    $stmt->bindValue(':id',  $identificador);
    $stmt->bindValue(':f',   $fecha_aprobacion);

    $stmt->execute();

    unset($_SESSION['identificador_preview']);
    header('Location: ../../../proyecto_aprobado.php?ok=1');
    exit;

} catch (PDOException $e) {
    error_log($e->getMessage());
    header('Location: ../../../proyecto_aprobado.php?err=1');
    exit;
}