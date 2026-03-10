<?php
include("mod/login/check.php");
include('lang/lang.es');

require_once __DIR__ . '/inc/db/db.php';
require_once __DIR__ . '/lib/mysession/mySession.conf.php';
require_once __DIR__ . '/lib/mysession/mySession.class.php';

$mySessionController = mySession::getIstance($_MYSESSION_CONF);

// Acceso
$current_user_id  = $mySessionController->getVar("usuario");
$current_user_rol = $mySessionController->getVar("rol");

if ($current_user_rol != 2 && $current_user_rol != 1 && $current_user_rol != 3) {
  header('Location: dashboard.php');
  exit;
}

function backErr(string $msg): void {
  header("Location: ProyectosRegistrados.php?err_cancel=" . rawurlencode($msg));
  exit;
}
function backOk(): void {
  header("Location: ProyectosRegistrados.php?ok_cancel=1");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') backErr('Método no permitido.');

$proyecto_id   = (int)($_POST['proyecto_id'] ?? 0);
$motivo        = trim($_POST['motivo'] ?? '');
$observaciones = trim($_POST['observaciones'] ?? '');

if ($proyecto_id <= 0) backErr('ID de proyecto no válido.');
if ($motivo === '' || mb_strlen($motivo) > 500) backErr('Motivo es requerido (máx. 500 caracteres).');
if (!$current_user_id) backErr('Sesión inválida.');

function getProyecto(mysqli $db, int $pid): array {
  $sql = "SELECT id_aprobado, proposal_id, estado, fecha_creacion, fecha_ultimo_avance
          FROM proyecto_aprobado
          WHERE id_aprobado = ?
          LIMIT 1";
  $stmt = mysqli_prepare($db, $sql);
  mysqli_stmt_bind_param($stmt, 'i', $pid);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $row = $res ? mysqli_fetch_assoc($res) : null;
  mysqli_stmt_close($stmt);
  if (!$row) backErr('Proyecto no encontrado.');
  return $row;
}

function getUltimoAvance(mysqli $db, int $pid, array $proyecto): ?string {
  // Prioridad 1: notas
  $stmt = mysqli_prepare($db, "SELECT MAX(creado_en) AS ult FROM proyecto_notas WHERE proyecto_id = ?");
  if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $pid);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    if ($row && !empty($row['ult'])) return $row['ult'];
  }

  // Prioridad 2: campo nuevo
  if (!empty($proyecto['fecha_ultimo_avance'])) return $proyecto['fecha_ultimo_avance'];

  // Prioridad 3: fecha creación
  if (!empty($proyecto['fecha_creacion'])) return $proyecto['fecha_creacion'];

  return null;
}

function validarSeisMeses(string $fechaUltimoAvance): void {
  $hoy = new DateTime('now');
  $base = new DateTime($fechaUltimoAvance);
  $limite = (clone $base)->modify('+6 months');
  if ($hoy < $limite) backErr('No se puede cancelar: el proyecto registra avances en los últimos 6 meses (Art. 73 RGPEA).');
}

function existeCancelacion(mysqli $db, int $pid): bool {
  $stmt = mysqli_prepare($db, "SELECT 1 FROM acuerdo_cancelacion WHERE proyecto_id = ? LIMIT 1");
  mysqli_stmt_bind_param($stmt, 'i', $pid);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $ok = $res && mysqli_fetch_row($res);
  mysqli_stmt_close($stmt);
  return (bool)$ok;
}

// --- Lógica ---
$proyecto = getProyecto($id_con, $proyecto_id);
if (strtoupper($proyecto['estado'] ?? '') === 'CANCELADO') backErr('El proyecto ya está CANCELADO.');
if (existeCancelacion($id_con, $proyecto_id)) backErr('Ya existe un acuerdo de cancelación para este proyecto.');

$fechaUltimoAvance = getUltimoAvance($id_con, $proyecto_id, $proyecto);
if ($fechaUltimoAvance === null) backErr('No hay fechas para validar 6 meses sin avances.');
validarSeisMeses($fechaUltimoAvance);

mysqli_begin_transaction($id_con);
try {
  // Insert acuerdo
  $sqlIns = "INSERT INTO acuerdo_cancelacion
              (proyecto_id, usuario_id, motivo, observaciones, fecha_cancelacion, fecha_ultimo_avance_usada)
            VALUES (?, ?, ?, NULLIF(?, ''), NOW(), ?)";
  $stmt = mysqli_prepare($id_con, $sqlIns);
  if (!$stmt) throw new Exception(mysqli_error($id_con));
  mysqli_stmt_bind_param($stmt, 'issss', $proyecto_id, $current_user_id, $motivo, $observaciones, $fechaUltimoAvance);
  if (!mysqli_stmt_execute($stmt)) throw new Exception(mysqli_stmt_error($stmt));
  mysqli_stmt_close($stmt);

  // Update estado
  $stmt = mysqli_prepare($id_con, "UPDATE proyecto_aprobado SET estado='CANCELADO', aprobado=4 WHERE id_aprobado=?");
  mysqli_stmt_bind_param($stmt, 'i', $proyecto_id);
  if (!mysqli_stmt_execute($stmt)) throw new Exception(mysqli_stmt_error($stmt));
  mysqli_stmt_close($stmt);

  if (!empty($proyecto['proposal_id'])) {
      $stmt = mysqli_prepare($id_con, "UPDATE tfg_proposals SET status='Cancelado' WHERE id=?");
      mysqli_stmt_bind_param($stmt, 'i', $proyecto['proposal_id']);
      if (!mysqli_stmt_execute($stmt)) throw new Exception(mysqli_stmt_error($stmt));
      mysqli_stmt_close($stmt);
  }

  mysqli_commit($id_con);
  backOk();

} catch (Throwable $e) {
  mysqli_rollback($id_con);
  backErr('Error al cancelar: ' . $e->getMessage());
}
?>