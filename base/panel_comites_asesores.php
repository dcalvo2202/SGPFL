<?php
/**
 * HU-041: Gestion de comites asesores por flujo de solicitudes.
 * En este panel solo se permite aprobar o rechazar comites propuestos
 * desde solicitudes aprobadas (sin creacion/edicion/eliminacion manual).
 */

include("mod/login/check.php");
include('includes.php');
include('lang/lang.es');
include_once __DIR__ . '/inc/db/bdcommon.inc';
require_once __DIR__ . '/inc/hu041_committee_audit.php';

$current_user_id = $mySessionController->getVar("usuario");
$current_user_name = $mySessionController->getVar("nombre");
$current_user_rol = (int)$mySessionController->getVar("rol");
$base_url = $mySessionController->getVar("cds_domain") . $mySessionController->getVar("cds_locate");

if ($current_user_rol !== 1 && $current_user_rol !== 2 && $current_user_rol !== 3) {
    header('Location: dashboard.php');
    exit;
}

$conn = new mysqli($db_host, $usuario, $clave, $db);
if ($conn->connect_error) {
    die('Error de conexion.');
}
$conn->set_charset('utf8');

function hu041_fetch_pending_committee_requests(mysqli $conn): array
{
        $sql = "SELECT ear.id, ear.applicant_id, ear.full_name, ear.committee_role, ear.postulation_type,
                                     ear.linked_student_id, ear.created_at, su.nombre AS linked_student_name
                        FROM external_advisor_profile_requests ear
                        LEFT JOIN sis_user su ON su.id = ear.linked_student_id
                        WHERE ear.status = 'Aprobado'
                            AND ear.linked_comite_id IS NULL
                            AND ear.linked_student_id IS NOT NULL
                            AND ear.linked_student_id <> ''
                        ORDER BY ear.linked_student_id ASC, ear.created_at ASC";

    $result = $conn->query($sql);
    $groups = [];

    while ($result && ($row = $result->fetch_assoc())) {
        $studentId = trim((string)$row['linked_student_id']);
        if ($studentId === '') {
            continue;
        }

        if (!isset($groups[$studentId])) {
            $groups[$studentId] = [
                'student_id' => $studentId,
                'student_name' => (string)($row['linked_student_name'] ?? ''),
                'roles' => [
                    'Tutor' => [],
                    'Asesor 1' => [],
                    'Asesor 2' => [],
                ],
                'all' => [],
            ];
        }

        $role = (string)($row['committee_role'] ?? '');
        if (!isset($groups[$studentId]['roles'][$role])) {
            $groups[$studentId]['roles'][$role] = [];
        }

        $groups[$studentId]['roles'][$role][] = $row;
        $groups[$studentId]['all'][] = $row;
    }

    $pending = [];
    foreach ($groups as $group) {
        $tutores = $group['roles']['Tutor'] ?? [];
        $asesor1 = $group['roles']['Asesor 1'] ?? [];
        $asesor2 = $group['roles']['Asesor 2'] ?? [];

        $isComplete = count($tutores) === 1 && count($asesor1) === 1 && count($asesor2) === 1;
        $ids = [];
        if ($isComplete) {
            $ids = [
                (string)$tutores[0]['applicant_id'],
                (string)$asesor1[0]['applicant_id'],
                (string)$asesor2[0]['applicant_id'],
            ];
        }
        $isUniquePeople = $isComplete && count(array_unique($ids)) === 3;

        $group['can_approve'] = $isComplete && $isUniquePeople;
        $group['validation_message'] = '';

        if (!$isComplete) {
            $group['validation_message'] = 'El estudiante no tiene exactamente 1 Tutor, 1 Asesor 1 y 1 Asesor 2.';
        } elseif (!$isUniquePeople) {
            $group['validation_message'] = 'La misma persona no puede ocupar dos roles en el mismo comite.';
        }

        $pending[] = $group;
    }

    return $pending;
}

function hu041_get_group_by_student(array $pendingGroups, string $studentId): ?array
{
    foreach ($pendingGroups as $group) {
        if ((string)$group['student_id'] === $studentId) {
            return $group;
        }
    }
    return null;
}

$message = '';
$messageType = 'success';
$inTransaction = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));
    $requestStudentId = trim((string)($_POST['request_student_id'] ?? ''));
    $rejectionReason = trim((string)($_POST['rejection_reason'] ?? ''));

    try {
        if ($requestStudentId === '') {
            throw new Exception('Debe indicar la solicitud de comite a procesar.');
        }

        $pendingGroups = hu041_fetch_pending_committee_requests($conn);
        $target = hu041_get_group_by_student($pendingGroups, $requestStudentId);
        if (!$target) {
            throw new Exception('La solicitud de comite ya no esta disponible o ya fue procesada.');
        }

        if ($action === 'approve_committee_request') {
            if (empty($target['can_approve'])) {
                throw new Exception('No se puede aprobar: ' . ($target['validation_message'] ?: 'configuracion invalida.'));
            }

            $tutor = $target['roles']['Tutor'][0];
            $a1 = $target['roles']['Asesor 1'][0];
            $a2 = $target['roles']['Asesor 2'][0];

            $conn->begin_transaction();
            $inTransaction = true;

            $stmtInsert = $conn->prepare("INSERT INTO comite (tutor, asesor_1, asesor_2) VALUES (?, ?, ?)");
            if (!$stmtInsert) {
                throw new Exception('No se pudo crear el comite.');
            }
            $stmtInsert->bind_param('sss', $tutor['applicant_id'], $a1['applicant_id'], $a2['applicant_id']);
            if (!$stmtInsert->execute()) {
                throw new Exception('No se pudo crear el comite: ' . $stmtInsert->error);
            }
            $comiteId = (int)$conn->insert_id;
            $stmtInsert->close();

            $requestIds = [(int)$tutor['id'], (int)$a1['id'], (int)$a2['id']];
            $stmtLink = $conn->prepare("UPDATE external_advisor_profile_requests SET linked_comite_id = ?, linked_at = NOW() WHERE id = ? AND status = 'Aprobado'");
            if (!$stmtLink) {
                throw new Exception('No se pudieron vincular solicitudes al comite.');
            }
            foreach ($requestIds as $rid) {
                $stmtLink->bind_param('ii', $comiteId, $rid);
                if (!$stmtLink->execute()) {
                    throw new Exception('No se pudo vincular la solicitud ' . $rid . ' al comite.');
                }
            }
            $stmtLink->close();

            require_once __DIR__ . '/inc/student_functions.php';
            foreach ([$tutor, $a1, $a2] as $memberRequest) {
                $memberRequestId = (int)$memberRequest['id'];
                $memberStudentId = trim((string)($memberRequest['linked_student_id'] ?? ''));
                if ($memberStudentId === '') {
                    throw new Exception('Todas las solicitudes del comite deben estar asociadas a un estudiante.');
                }

                $linkResult = linkAdvisorToGroupMembers($conn, $memberRequestId, $memberStudentId);
                if (empty($linkResult['success'])) {
                    throw new Exception('No se pudo asociar al estudiante en la solicitud ' . $memberRequestId . '.');
                }
            }

            hu041_register_audit($conn, (string)$current_user_id, 'COMMITTEE_APPROVED_FROM_REQUESTS', 'comite', $comiteId, [
                'student_id' => $requestStudentId,
                'request_ids' => $requestIds,
            ]);

            $conn->commit();
            $inTransaction = false;
            $message = 'Comite aprobado y creado desde solicitudes.';
        } elseif ($action === 'reject_committee_request') {
            if ($rejectionReason === '' || strlen($rejectionReason) < 10) {
                throw new Exception('Debe indicar un motivo de rechazo de al menos 10 caracteres.');
            }

            $requestIds = [];
            foreach ($target['all'] as $item) {
                $requestIds[] = (int)$item['id'];
            }
            if (empty($requestIds)) {
                throw new Exception('No hay solicitudes para rechazar para este estudiante.');
            }

            $conn->begin_transaction();
            $inTransaction = true;

            $stmtReject = $conn->prepare("UPDATE external_advisor_profile_requests
                                          SET status = 'Rechazado',
                                              admin_comments = ?,
                                              reviewed_by = ?,
                                              reviewed_at = NOW(),
                                              linked_comite_id = NULL,
                                              linked_at = NULL
                                          WHERE id = ?");
            if (!$stmtReject) {
                throw new Exception('No se pudo preparar el rechazo de solicitudes.');
            }

            foreach ($requestIds as $rid) {
                $stmtReject->bind_param('ssi', $rejectionReason, $current_user_id, $rid);
                if (!$stmtReject->execute()) {
                    throw new Exception('No se pudo rechazar la solicitud ' . $rid . '.');
                }
            }
            $stmtReject->close();

            hu041_register_audit($conn, (string)$current_user_id, 'COMMITTEE_REQUEST_REJECTED', 'solicitud_comite', null, [
                'student_id' => $requestStudentId,
                'request_ids' => $requestIds,
                'reason' => $rejectionReason,
            ]);

            $conn->commit();
            $inTransaction = false;
            $message = 'Solicitud de comite rechazada correctamente.';
        }
    } catch (Exception $ex) {
        if ($inTransaction) {
            $conn->rollback();
            $inTransaction = false;
        }
        $messageType = 'danger';
        $message = $ex->getMessage();
    }
}

$pendingCommitteeRequests = hu041_fetch_pending_committee_requests($conn);

$comites = [];
$sqlComites = "SELECT c.Id,
                      c.tutor, t.nombre AS tutor_nombre,
                      c.asesor_1, a1.nombre AS asesor1_nombre,
                      c.asesor_2, a2.nombre AS asesor2_nombre,
                      (SELECT COUNT(*) FROM proyecto_aprobado pa WHERE pa.comite_id = c.Id) AS proyectos_asociados
               FROM comite c
               LEFT JOIN sis_user t ON t.id = c.tutor
               LEFT JOIN sis_user a1 ON a1.id = c.asesor_1
               LEFT JOIN sis_user a2 ON a2.id = c.asesor_2
               ORDER BY c.Id DESC";
$resComites = $conn->query($sqlComites);
while ($resComites && ($row = $resComites->fetch_assoc())) {
    $comites[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aprobacion de Comites Asesores - SGPFL UNA</title>
    <link rel="icon" type="image/webp" href="<?= htmlspecialchars($base_url) ?>img/logo.webp">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= htmlspecialchars($base_url . 'inc/css/estilo.css') ?>" rel="stylesheet">
    <link href="<?= htmlspecialchars($base_url . 'inc/css/panel_estudiante.css') ?>" rel="stylesheet">
</head>
<body class="fondo-una d-flex flex-column min-vh-100">
<?php include 'header.php'; ?>

<main class="flex-fill">
    <div class="container my-5">
        <div class="dashboard-header text-center mb-4">
            <h1 style="font-size: 2.5rem; font-weight: 700;">Aprobacion de Comites Asesores</h1>
            <p class="lead">Bienvenido, <?= htmlspecialchars((string)$current_user_name) ?>. Aqui solo se aprueban o rechazan comites propuestos por solicitudes.</p>
        </div>

        <?php if ($message !== ''): ?>
            <div class="alert alert-<?= $messageType === 'danger' ? 'danger' : 'success' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light"><strong>Solicitudes de comite pendientes</strong></div>
            <div class="card-body">
                <?php if (empty($pendingCommitteeRequests)): ?>
                    <div class="alert alert-info mb-0">No hay solicitudes de comite pendientes por aprobar o rechazar.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                            <tr>
                                <th>Estudiante</th>
                                <th>Tutor</th>
                                <th>Asesor 1</th>
                                <th>Asesor 2</th>
                                <th>Estado validacion</th>
                                <th>Rechazo</th>
                                <th>Acciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($pendingCommitteeRequests as $group): ?>
                                <?php
                                $tutorRows = $group['roles']['Tutor'] ?? [];
                                $a1Rows = $group['roles']['Asesor 1'] ?? [];
                                $a2Rows = $group['roles']['Asesor 2'] ?? [];

                                $tutorText = count($tutorRows) === 1
                                    ? ($tutorRows[0]['full_name'] . ' (' . $tutorRows[0]['applicant_id'] . ')')
                                    : ('Cantidad: ' . count($tutorRows));
                                $a1Text = count($a1Rows) === 1
                                    ? ($a1Rows[0]['full_name'] . ' (' . $a1Rows[0]['applicant_id'] . ')')
                                    : ('Cantidad: ' . count($a1Rows));
                                $a2Text = count($a2Rows) === 1
                                    ? ($a2Rows[0]['full_name'] . ' (' . $a2Rows[0]['applicant_id'] . ')')
                                    : ('Cantidad: ' . count($a2Rows));
                                ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars((string)$group['student_name']) ?>
                                        <div class="small text-muted"><?= htmlspecialchars((string)$group['student_id']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($tutorText) ?></td>
                                    <td><?= htmlspecialchars($a1Text) ?></td>
                                    <td><?= htmlspecialchars($a2Text) ?></td>
                                    <td>
                                        <?php if (!empty($group['can_approve'])): ?>
                                            <span class="badge bg-success">Listo para aprobar</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Invalido</span>
                                            <div class="small text-muted mt-1"><?= htmlspecialchars((string)$group['validation_message']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="min-width: 220px;">
                                        <form method="post" class="d-flex gap-2 align-items-start">
                                            <input type="hidden" name="action" value="reject_committee_request">
                                            <input type="hidden" name="request_student_id" value="<?= htmlspecialchars((string)$group['student_id']) ?>">
                                            <textarea name="rejection_reason" class="form-control form-control-sm" rows="2" minlength="10" required placeholder="Motivo de rechazo"></textarea>
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Rechazar</button>
                                        </form>
                                    </td>
                                    <td>
                                        <form method="post" onsubmit="return confirm('Aprobar comite para el estudiante <?= htmlspecialchars((string)$group['student_id'], ENT_QUOTES) ?>?');">
                                            <input type="hidden" name="action" value="approve_committee_request">
                                            <input type="hidden" name="request_student_id" value="<?= htmlspecialchars((string)$group['student_id']) ?>">
                                            <button type="submit" class="btn btn-sm btn-primary" <?= !empty($group['can_approve']) ? '' : 'disabled' ?>>Aprobar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-light"><strong>Comites actuales</strong></div>
            <div class="card-body">
                <?php if (empty($comites)): ?>
                    <div class="alert alert-info mb-0">No hay comites registrados.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tutor</th>
                                <th>Asesor 1</th>
                                <th>Asesor 2</th>
                                <th>Proyectos</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($comites as $c): ?>
                                <tr>
                                    <td><?= (int)$c['Id'] ?></td>
                                    <td><?= htmlspecialchars((string)$c['tutor_nombre']) ?> (<?= htmlspecialchars((string)$c['tutor']) ?>)</td>
                                    <td><?= htmlspecialchars((string)$c['asesor1_nombre']) ?> (<?= htmlspecialchars((string)$c['asesor_1']) ?>)</td>
                                    <td><?= htmlspecialchars((string)$c['asesor2_nombre']) ?> (<?= htmlspecialchars((string)$c['asesor_2']) ?>)</td>
                                    <td><span class="badge bg-secondary"><?= (int)$c['proyectos_asociados'] ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left-circle"></i> Volver al panel principal
            </a>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
