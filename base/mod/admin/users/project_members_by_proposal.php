<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../inc/db/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['members' => []], JSON_UNESCAPED_UNICODE);
  exit;
}

$registeredId = isset($_POST['registered_id']) ? (int)$_POST['registered_id'] : 0;
if ($registeredId <= 0) {
  http_response_code(400);
  echo json_encode(['members' => []], JSON_UNESCAPED_UNICODE);
  exit;
}

$sql = "SELECT pm.user_id AS id, COALESCE(u.nombre,'') AS nombre
        FROM project_members pm
        JOIN sis_user u ON u.id = pm.user_id
        WHERE pm.project_id = ?";

$stmt = mysqli_prepare($id_con, $sql);
if (!$stmt) {
  http_response_code(500);
  echo json_encode(['members' => []], JSON_UNESCAPED_UNICODE);
  exit;
}

mysqli_stmt_bind_param($stmt, 'i', $registeredId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$members = [];
if ($res) {
  while ($row = mysqli_fetch_assoc($res)) {
    $members[] = ['id' => $row['id'], 'nombre' => $row['nombre']];
  }
  mysqli_free_result($res);
}
mysqli_stmt_close($stmt);

echo json_encode(['members' => $members], JSON_UNESCAPED_UNICODE);