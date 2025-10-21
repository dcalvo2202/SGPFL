<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once '../../../inc/db/db.php';

$registered_id = isset($_POST['registered_id']) ? (int)$_POST['registered_id'] : 0;
$proposal_id   = isset($_POST['proposal_id'])   ? (int)$_POST['proposal_id']   : 0;

if ($registered_id <= 0 && $proposal_id <= 0) {
  http_response_code(400);
  echo json_encode(['error' => 'Parámetros inválidos', 'members' => []], JSON_UNESCAPED_UNICODE);
  exit;
}

/* Si solo llega proposal_id, resolver el registered_id */
if ($registered_id <= 0 && $proposal_id > 0) {
  $q = mysqli_prepare($id_con, "SELECT id FROM registered_projects WHERE tfg_proposal_id = ? LIMIT 1");
  if (!$q) { http_response_code(500); echo json_encode(['error'=>'DB prepare pid: '.mysqli_error($id_con),'members'=>[]]); exit; }
  mysqli_stmt_bind_param($q, 'i', $proposal_id);
  if (!mysqli_stmt_execute($q)) { http_response_code(500); echo json_encode(['error'=>'DB execute pid: '.mysqli_stmt_error($q),'members'=>[]]); mysqli_stmt_close($q); exit; }
  $rs = mysqli_stmt_get_result($q);
  $row = $rs ? mysqli_fetch_row($rs) : null;
  mysqli_stmt_close($q);
  if (!$row) { http_response_code(404); echo json_encode(['error'=>'No se encontró registered_id para la propuesta.','members'=>[]]); exit; }
  $registered_id = (int)$row[0];
}

/* Consulta por project_members.project_id (solo Activo y rol Estudiante) */
$sql = "
  SELECT u.id, u.nombre
  FROM project_members pm
  JOIN sis_user  u ON u.id = pm.user_id
  JOIN sis_login l ON l.id = u.id
  WHERE pm.project_id = ?
    AND pm.status = 'Activo'
    AND l.id_roll = 4
  ORDER BY u.nombre";
$stmt = mysqli_prepare($id_con, $sql);
if (!$stmt) { http_response_code(500); echo json_encode(['error'=>'DB prepare: '.mysqli_error($id_con),'members'=>[]]); exit; }
mysqli_stmt_bind_param($stmt, 'i', $registered_id);
if (!mysqli_stmt_execute($stmt)) { http_response_code(500); echo json_encode(['error'=>'DB execute: '.mysqli_stmt_error($stmt),'members'=>[]]); mysqli_stmt_close($stmt); exit; }

$res = mysqli_stmt_get_result($stmt);
$members = [];
while ($res && ($r = mysqli_fetch_assoc($res))) $members[] = $r;
mysqli_stmt_close($stmt);

echo json_encode(['members' => $members], JSON_UNESCAPED_UNICODE);