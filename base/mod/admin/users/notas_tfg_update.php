<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../inc/db/db.php';
require_once __DIR__ . '/notas_tfg_upload.php';

$creado_por = $_SESSION['usuario'] ?? ($_SESSION['id'] ?? null);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok'=>false,'msg'=>'Método no permitido']); exit;
}

$id   = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$tit  = trim($_POST['titulo'] ?? '');
$nota = trim($_POST['notas'] ?? '');

if ($id <= 0 || $tit === '' || $nota === '') {
  http_response_code(422);
  echo json_encode(['ok'=>false,'msg'=>'Datos inválidos']); exit;
}

// Opcional: límites
if (mb_strlen($tit) > 200) {
  http_response_code(422);
  echo json_encode(['ok'=>false,'msg'=>'Título demasiado largo']); exit;
}

$newId = guardarNotaProyecto($id_con, $id, $tit, $nota, $creado_por);

if ($newId > 0) {
  echo json_encode(['ok'=>true,'id'=>$newId]); exit;
}

http_response_code(500);
echo json_encode(['ok'=>false,'msg'=>'Error al guardar']);

/**
 * Inserta una nota y devuelve el ID insertado o 0 si falla
 */
function guardarNotaProyecto(mysqli $conn, int $proyectoId, string $titulo, string $notas, ?string $usuarioCreador): int {
    $sql = "INSERT INTO proyecto_notas (proyecto_id, titulo, notas, creado_por) VALUES (?,?,?,?)";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return 0;

    mysqli_stmt_bind_param($stmt, "isss", $proyectoId, $titulo, $notas, $usuarioCreador);
    $ok = mysqli_stmt_execute($stmt);
    $newId = $ok ? (int)mysqli_insert_id($conn) : 0;

    mysqli_stmt_close($stmt);
    return $newId;
}