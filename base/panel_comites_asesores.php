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
require_once __DIR__ . '/inc/hu041_committee_rules.php'; // Dejar este include para evitar dependencias circulares con funciones de base de datos

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

$message = '';
$messageType = 'success';
$inTransaction = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));
    $requestStudentId = trim((string)($_POST['request_student_id'] ?? ''));
    $rejectionReason = trim((string)($_POST['rejection_reason'] ?? ''));

    try {
        hu041_validate_committee_panel_action($action, $requestStudentId, $rejectionReason);

        $pendingGroups = hu041_fetch_pending_committee_requests($conn);
        $target = hu041_get_group_by_student($pendingGroups, $requestStudentId);

        if (!$target) {
            throw new Exception('La solicitud de comite ya no esta disponible o ya fue procesada.');
        }

        if ($action === 'approve_committee_request') {
            hu041_validate_target_committee_group($target);

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
            
            hu041_validate_committee_panel_action($action, $requestStudentId, $rejectionReason);

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

$comites_search = trim((string)($_GET['comite_buscar'] ?? ''));
$comites_page = max(1, (int)($_GET['comite_pagina'] ?? 1));
$comites_per_page = 10;
$comites_total = 0;

$comites = [];

if ($comites_search !== '') {
    $search_like = '%' . $comites_search . '%';
    $where_clause = " WHERE (c.tutor LIKE ? OR t.nombre LIKE ? OR c.asesor_1 LIKE ? OR a1.nombre LIKE ? OR c.asesor_2 LIKE ? OR a2.nombre LIKE ?)";

    $sqlCount = "SELECT COUNT(*) AS total
                 FROM comite c
                 LEFT JOIN sis_user t ON t.id = c.tutor
                 LEFT JOIN sis_user a1 ON a1.id = c.asesor_1
                 LEFT JOIN sis_user a2 ON a2.id = c.asesor_2" . $where_clause;
    $stmtCount = $conn->prepare($sqlCount);
    if ($stmtCount) {
        $stmtCount->bind_param('ssssss', $search_like, $search_like, $search_like, $search_like, $search_like, $search_like);
        $stmtCount->execute();
        $countResult = $stmtCount->get_result();
        if ($countResult && ($countRow = $countResult->fetch_assoc())) {
            $comites_total = (int)$countRow['total'];
        }
        $stmtCount->close();
    }
} else {
    $countResult = $conn->query("SELECT COUNT(*) AS total FROM comite");
    if ($countResult && ($countRow = $countResult->fetch_assoc())) {
        $comites_total = (int)$countRow['total'];
    }
}

$comites_total_pages = max(1, (int)ceil($comites_total / $comites_per_page));
if ($comites_page > $comites_total_pages) {
    $comites_page = $comites_total_pages;
}
$comites_offset = ($comites_page - 1) * $comites_per_page;

if ($comites_search !== '') {
    $search_like = '%' . $comites_search . '%';
    $sqlComites = "SELECT c.Id,
                          c.tutor, t.nombre AS tutor_nombre,
                          c.asesor_1, a1.nombre AS asesor1_nombre,
                          c.asesor_2, a2.nombre AS asesor2_nombre,
                          (SELECT COUNT(*) FROM proyecto_aprobado pa WHERE pa.comite_id = c.Id) AS proyectos_asociados
                   FROM comite c
                   LEFT JOIN sis_user t ON t.id = c.tutor
                   LEFT JOIN sis_user a1 ON a1.id = c.asesor_1
                   LEFT JOIN sis_user a2 ON a2.id = c.asesor_2
                   WHERE (c.tutor LIKE ? OR t.nombre LIKE ? OR c.asesor_1 LIKE ? OR a1.nombre LIKE ? OR c.asesor_2 LIKE ? OR a2.nombre LIKE ?)
                   ORDER BY c.Id DESC
                   LIMIT ? OFFSET ?";
    $stmtComites = $conn->prepare($sqlComites);
    if ($stmtComites) {
        $stmtComites->bind_param('ssssssii', $search_like, $search_like, $search_like, $search_like, $search_like, $search_like, $comites_per_page, $comites_offset);
        $stmtComites->execute();
        $resComites = $stmtComites->get_result();
        while ($resComites && ($row = $resComites->fetch_assoc())) {
            $comites[] = $row;
        }
        $stmtComites->close();
    }
} else {
    $sqlComites = "SELECT c.Id,
                          c.tutor, t.nombre AS tutor_nombre,
                          c.asesor_1, a1.nombre AS asesor1_nombre,
                          c.asesor_2, a2.nombre AS asesor2_nombre,
                          (SELECT COUNT(*) FROM proyecto_aprobado pa WHERE pa.comite_id = c.Id) AS proyectos_asociados
                   FROM comite c
                   LEFT JOIN sis_user t ON t.id = c.tutor
                   LEFT JOIN sis_user a1 ON a1.id = c.asesor_1
                   LEFT JOIN sis_user a2 ON a2.id = c.asesor_2
                   ORDER BY c.Id DESC
                   LIMIT ? OFFSET ?";
    $stmtComites = $conn->prepare($sqlComites);
    if ($stmtComites) {
        $stmtComites->bind_param('ii', $comites_per_page, $comites_offset);
        $stmtComites->execute();
        $resComites = $stmtComites->get_result();
        while ($resComites && ($row = $resComites->fetch_assoc())) {
            $comites[] = $row;
        }
        $stmtComites->close();
    }
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
    <style>
        .custom-modal-overlay {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1060;
            padding: 1rem;
        }

        .custom-modal-content {
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 0.75rem;
            width: min(100%, 540px);
            box-shadow: 0 0.75rem 1.5rem rgba(0,0,0,.18);
            animation: fadeIn 0.25s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .custom-modal-content h4 {
            margin-top: 0;
            color: #034991;
            font-weight: 700;
        }

        .custom-modal-content textarea {
            width: 100%;
            min-height: 100px;
            margin: 15px 0;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-family: inherit;
        }

        .custom-modal-content .btn-group-modal {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .dashboard-header h1 {
            font-weight: 700;
        }

.dashboard-header .lead {
        }

        .card-header {
            display: block;
            padding: 0.75rem 1rem;
            background-color: #f8f9fa;
            border-bottom: 1px solid rgba(0,0,0,.125);
        }

        .card-header strong {
            font-size: 1.1rem;
            color: #212529;
        }

        .student-name {
            font-weight: 600;
            line-height: 1.15;
        }

        .student-meta {
            color: #6c757d;
        }

        .table thead th {
            font-weight: 700;
            padding-top: 1.15rem;
            padding-bottom: 1.15rem;
        }

        .table tbody td {
            padding-top: 1.25rem;
            padding-bottom: 1.25rem;
            vertical-align: middle;
        }

        .student-name {
            font-size: 1.45rem;
            font-weight: 600;
            line-height: 1.15;
        }

        .student-meta {
            font-size: 1.2rem;
            color: #6c757d;
        }

        .validation-cell {
            display: flex;
            flex-direction: column;
            gap: 0.55rem;
            align-items: flex-start;
            max-width: 560px;
        }

        .validation-badge {
            font-size: 1.15rem;
            font-weight: 700;
            line-height: 1;
            padding: 0.4rem 0.65rem;
            border-radius: 0.45rem;
        }

        .validation-message {
            line-height: 1.35;
            margin-top: 0.1rem;
        }

        .btn-rechazar-comite,
        .btn-aprobar {
            font-weight: 600;
            padding: 0.7rem 1.3rem;
            min-width: 150px;
        }

        .btn-aprobar:disabled {
            opacity: 0.45;
            cursor: not-allowed;
            box-shadow: none;
        }

        .btn-aprobar.btn-success,
        .btn-rechazar-comite.btn-danger {
            color: #fff;
        }

        .btn-aprobar.btn-success {
            background-color: #0f5132;
            border-color: #0f5132;
        }

        .btn-aprobar.btn-success:hover,
        .btn-aprobar.btn-success:focus {
            background-color: #0b3d26;
            border-color: #0b3d26;
        }

        .btn-rechazar-comite {
            border-width: 2px;
        }

        .badge {
            padding: 0.35rem 0.5rem;
        }

        .modal-error {
            color: #dc3545;
            font-size: 0.875rem;
            display: none;
            margin-bottom: 10px;
        }

        .comites-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .comites-toolbar .input-group {
            max-width: 520px;
        }

        .comites-summary {
            font-size: 1rem;
            color: #6c757d;
        }

        /* Tabla de comites responsive */
        .comites-table-wrapper {
            width: 100%;
        }

        .comites-table-wrapper .table td,
        .comites-table-wrapper .table th {
            word-break: break-word;
            text-align: left;
        }

        .pending-requests-table-wrapper {
            width: 100%;
        }

        .pending-requests-table-wrapper .table td,
        .pending-requests-table-wrapper .table th {
            word-break: break-word;
            text-align: left;
        }

        /* Estilos responsive para tablas a nivel de cards en móvil */
        @media (max-width: 754px) {
            .comites-toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .comites-toolbar .input-group {
                max-width: 100%;
                width: 100%;
            }

            .comites-toolbar .input-group input {
                font-size: 0.95rem;
                padding: 0.5rem 0.75rem;
            }

            .comites-toolbar .input-group button {
                padding: 0.5rem 0.75rem;
                font-size: 0.95rem;
            }

            .comites-summary {
                text-align: center;
                width: 100%;
                font-size: 0.9rem;
            }

            .table-responsive {
                border-radius: 0.25rem;
            }
        }

        @media (max-width: 576px) {
            .card-header strong {
                font-size: 1rem;
            }

            /* Responsive table mobile */
            .comites-table-wrapper .table thead,
            .pending-requests-table-wrapper .table thead { display: none; }

            .comites-table-wrapper .table tbody tr,
            .pending-requests-table-wrapper .table tbody tr {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
                border: 1px solid #dee2e6;
                border-radius: 0.25rem;
                padding: 0.75rem;
                margin-bottom: 0.75rem;
                background-color: #fff;
            }

            .comites-table-wrapper .table td,
            .pending-requests-table-wrapper .table td {
                display: flex;
                flex-direction: column;
                padding: 0.25rem 0 !important;
                border: none !important;
                text-align: left;
                width: 100%;
            }

            .comites-table-wrapper .table td::before,
            .pending-requests-table-wrapper .table td::before {
                font-weight: 600;
                color: #034991;
                font-size: 0.8rem;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                margin-bottom: 0.25rem;
                display: block;
            }

            /* Labels - Tabla comites */
            .comites-table-wrapper .table td:nth-child(1)::before { content: "ID"; }
            .comites-table-wrapper .table td:nth-child(2)::before { content: "Tutor"; }
            .comites-table-wrapper .table td:nth-child(3)::before { content: "Asesor 1"; }
            .comites-table-wrapper .table td:nth-child(4)::before { content: "Asesor 2"; }
            .comites-table-wrapper .table td:nth-child(5)::before { content: "Proyectos"; }

            /* Labels - Tabla solicitudes */
            .pending-requests-table-wrapper .table td:nth-child(1)::before { content: "Estudiante"; }
            .pending-requests-table-wrapper .table td:nth-child(2)::before { content: "Tutor"; }
            .pending-requests-table-wrapper .table td:nth-child(3)::before { content: "Asesor 1"; }
            .pending-requests-table-wrapper .table td:nth-child(4)::before { content: "Asesor 2"; }
            .pending-requests-table-wrapper .table td:nth-child(5)::before { content: "Estado"; }
            .pending-requests-table-wrapper .table td:nth-child(6)::before { content: "Rechazo"; }
            .pending-requests-table-wrapper .table td:nth-child(7)::before { content: "Acciones"; }
        }
</style>
</head>
<body class="fondo-una d-flex flex-column min-vh-100">
<?php include 'header.php'; ?>

<main class="flex-fill">
    <div class="container my-5">
        <div class="dashboard-header text-center mb-4">
            <h1>Aprobación de Comites Asesores</h1>
            <p class="lead">Bienvenido, <?= htmlspecialchars((string)$current_user_name) ?>. Aqui solo se aprueban o rechazan comites propuestos por solicitudes.</p>
        </div>

        <?php if ($message !== ''): ?>
            <div class="alert alert-<?= $messageType === 'danger' ? 'danger' : 'success' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-light"><strong>Solicitudes de comite pendientes</strong></div>
            <div class="card-body">
                <?php if (empty($pendingCommitteeRequests)): ?>
                    <div class="alert alert-info mb-0">No hay solicitudes de comite pendientes por aprobar o rechazar.</div>
                <?php else: ?>
                    <div class="pending-requests-table-wrapper">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
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
                                    : ('Sin asignar');
                                $a1Text = count($a1Rows) === 1
                                    ? ($a1Rows[0]['full_name'] . ' (' . $a1Rows[0]['applicant_id'] . ')')
                                    : ('Sin asignar');
                                $a2Text = count($a2Rows) === 1
                                    ? ($a2Rows[0]['full_name'] . ' (' . $a2Rows[0]['applicant_id'] . ')')
                                    : ('Sin asignar');
                                ?>
                                <tr>
                                    <td>
                                        <div class="student-name"><?= htmlspecialchars((string)$group['student_name']) ?></div>
                                        <div class="student-meta"><?= htmlspecialchars((string)$group['student_id']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($tutorText) ?></td>
                                    <td><?= htmlspecialchars($a1Text) ?></td>
                                    <td><?= htmlspecialchars($a2Text) ?></td>
                                    <td>
                                        <div class="validation-cell">
                                            <?php if (!empty($group['can_approve'])): ?>
                                                <span class="badge bg-success validation-badge">Listo para aprobar</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger validation-badge">Invalido</span>
                                                <div class="student-meta validation-message"><?= htmlspecialchars((string)$group['validation_message']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <button type="button"
                                                class="btn btn-sm btn-danger btn-rechazar-comite"
                                                data-student-id="<?= htmlspecialchars((string)$group['student_id'], ENT_QUOTES) ?>"
                                                data-student-name="<?= htmlspecialchars((string)$group['student_name'], ENT_QUOTES) ?>">
                                            Rechazar
                                        </button>
                                    </td>
                                    <td>
                                        <form method="post" onsubmit="return confirm('Aprobar comite para el estudiante <?= htmlspecialchars((string)$group['student_id'], ENT_QUOTES) ?>?');">
                                            <input type="hidden" name="action" value="approve_committee_request">
                                            <input type="hidden" name="request_student_id" value="<?= htmlspecialchars((string)$group['student_id']) ?>">
                                            <button type="submit"
                                                    class="btn btn-sm btn-success btn-aprobar"
                                                    <?= !empty($group['can_approve']) ? '' : 'disabled aria-disabled="true" title="La solicitud es inválida y no puede aprobarse"' ?>>
                                                Aprobar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-light"><strong>Comites actuales</strong></div>
            <div class="card-body">
                <div class="comites-toolbar">
                    <form method="get" class="input-group">
                        <input type="text"
                               name="comite_buscar"
                               class="form-control"
                               placeholder="Buscar por nombre o cédula de Tutor / Asesor"
                               value="<?= htmlspecialchars($comites_search) ?>">
                        <input type="hidden" name="comite_pagina" value="1">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Buscar</button>
                        <?php if ($comites_search !== ''): ?>
                            <a class="btn btn-outline-secondary" href="panel_comites_asesores.php"><i class="bi bi-x-circle"></i> Limpiar</a>
                        <?php endif; ?>
                    </form>
                    <div class="comites-summary">
                        Mostrando <?= count($comites) ?> de <?= (int)$comites_total ?> comité(s)
                    </div>
                </div>

                <?php if (empty($comites)): ?>
                    <div class="alert alert-info mb-0">No hay comites registrados.</div>
                <?php else: ?>
                    <div class="comites-table-wrapper">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tutor</th>
                                    <th>Asesor 1</th>
                                    <th>Asesor 2</th>
                                    <th>Proyectos Asignados</th>
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
                    </div>

                    <?php if ($comites_total_pages > 1): ?>
                        <nav class="mt-3" aria-label="Paginación de comités actuales">
                            <ul class="pagination justify-content-center mb-0">
                                <?php
                                $prevPage = max(1, $comites_page - 1);
                                $nextPage = min($comites_total_pages, $comites_page + 1);
                                $queryBase = 'panel_comites_asesores.php?comite_buscar=' . urlencode($comites_search) . '&comite_pagina=';
                                ?>
                                <li class="page-item <?= $comites_page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= $queryBase . $prevPage ?>" aria-label="Anterior">&laquo;</a>
                                </li>

                                <?php for ($p = 1; $p <= $comites_total_pages; $p++): ?>
                                    <li class="page-item <?= $p === $comites_page ? 'active' : '' ?>">
                                        <a class="page-link" href="<?= $queryBase . $p ?>"><?= $p ?></a>
                                    </li>
                                <?php endfor; ?>

                                <li class="page-item <?= $comites_page >= $comites_total_pages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= $queryBase . $nextPage ?>" aria-label="Siguiente">&raquo;</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="dashboard.php" class="btn btn-secondary btn-lg px-4">
                <i class="bi bi-arrow-left-circle"></i> Volver al Panel Principal
            </a>
        </div>
    </div>
</main>

<div id="modalRechazoComite" class="custom-modal-overlay">
    <div class="custom-modal-content">
        <h4><i class="bi bi-x-circle text-danger"></i> Rechazar Comité</h4>
        <p id="modalRechazoComiteTexto">¿Por qué rechaza este comité?</p>
        <form method="post" id="formRechazoComite">
            <input type="hidden" name="action" value="reject_committee_request">
            <input type="hidden" name="request_student_id" id="rejectionStudentId" value="">
            <textarea name="rejection_reason" id="rejectionReason" placeholder="Ej: Falta documentación, integrantes incorrectos, datos inconsistentes..." maxlength="500" required></textarea>
            <div id="errorRechazoComite" class="modal-error">Debe especificar un motivo detallado (mínimo 10 caracteres).</div>
            <div class="btn-group-modal">
                <button type="button" class="btn btn-secondary" id="btnCancelarRechazoComite">Cancelar</button>
                <button type="submit" class="btn btn-danger" id="btnConfirmarRechazoComite">
                    <i class="bi bi-x-lg"></i> Rechazar
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    var modalRechazo = document.getElementById('modalRechazoComite');
    var modalRechazoTexto = document.getElementById('modalRechazoComiteTexto');
    var rejectionStudentId = document.getElementById('rejectionStudentId');
    var rejectionReason = document.getElementById('rejectionReason');
    var errorRechazo = document.getElementById('errorRechazoComite');
    var btnCancelar = document.getElementById('btnCancelarRechazoComite');
    var formRechazo = document.getElementById('formRechazoComite');

    function showModal(modal) {
        modal.style.display = 'flex';
    }

    function hideModal(modal) {
        modal.style.display = 'none';
    }

    document.querySelectorAll('.btn-rechazar-comite').forEach(function (button) {
        button.addEventListener('click', function () {
            var studentId = this.getAttribute('data-student-id') || '';
            var studentName = this.getAttribute('data-student-name') || '';

            rejectionStudentId.value = studentId;
            rejectionReason.value = '';
            errorRechazo.style.display = 'none';
            modalRechazoTexto.innerHTML = '¿Por qué rechaza el comité de <strong>' + studentName + '</strong>?';
            showModal(modalRechazo);
            rejectionReason.focus();
        });
    });

    btnCancelar.addEventListener('click', function () {
        hideModal(modalRechazo);
    });

    formRechazo.addEventListener('submit', function (event) {
        if (rejectionReason.value.trim().length < 10) {
            event.preventDefault();
            errorRechazo.style.display = 'block';
            rejectionReason.focus();
        }
    });

    window.addEventListener('click', function (event) {
        if (event.target === modalRechazo) {
            hideModal(modalRechazo);
        }
    });
})();
</script>
</body>
</html>
