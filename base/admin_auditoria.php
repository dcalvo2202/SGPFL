<?php
include("mod/login/check.php");
include('lang/lang.es');
include_once(__DIR__ . "/inc/db/bdcommon.inc");
include_once(__DIR__ . "/inc/db/db.php");
include_once(__DIR__ . "/functions.php");

$current_user_rol = (int)$mySessionController->getVar("rol");
$base_url = $mySessionController->getVar("cds_domain") . $mySessionController->getVar("cds_locate");

if ($current_user_rol !== 1) {
    header('Location: dashboard.php');
    exit;
}

if (!check_permiso($mod3, $act2, $current_user_rol)) {
    header('Location: dashboard.php');
    exit;
}

$resultado = isset($_GET['resultado']) ? strtoupper(trim((string)$_GET['resultado'])) : 'TODOS';
$filtros_validos = ['TODOS', 'SUCCESS', 'FAIL', 'ALERT'];
if (!in_array($resultado, $filtros_validos, true)) {
    $resultado = 'TODOS';
}

$fecha_desde = isset($_GET['fecha_desde']) ? trim((string)$_GET['fecha_desde']) : '';
$fecha_hasta = isset($_GET['fecha_hasta']) ? trim((string)$_GET['fecha_hasta']) : '';

if ($fecha_desde !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_desde)) {
    $fecha_desde = '';
}
if ($fecha_hasta !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_hasta)) {
    $fecha_hasta = '';
}

$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina < 1) {
    $pagina = 1;
}

$registros_por_pagina = 10;

$where = " WHERE 1=1";
$whereParams = [];
if ($resultado !== 'TODOS') {
    $where .= " AND UPPER(l.action_result) = ?";
    $whereParams[] = $resultado;
}
if ($fecha_desde !== '') {
    $where .= " AND DATE(l.date_bi) >= ?";
    $whereParams[] = $fecha_desde;
}
if ($fecha_hasta !== '') {
    $where .= " AND DATE(l.date_bi) <= ?";
    $whereParams[] = $fecha_hasta;
}

$sql_count = "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN UPPER(l.action_result) = 'SUCCESS' THEN 1 ELSE 0 END) AS success_count,
                SUM(CASE WHEN UPPER(l.action_result) = 'FAIL' THEN 1 ELSE 0 END) AS fail_count,
                SUM(CASE WHEN UPPER(l.action_result) = 'ALERT' THEN 1 ELSE 0 END) AS alert_count
              FROM sis_log l" . $where;

$resumen = seleccion_segura($sql_count, $whereParams);
$total_logs = ($resumen && isset($resumen[0]['total'])) ? (int)$resumen[0]['total'] : 0;
$success_count = ($resumen && isset($resumen[0]['success_count'])) ? (int)$resumen[0]['success_count'] : 0;
$fail_count = ($resumen && isset($resumen[0]['fail_count'])) ? (int)$resumen[0]['fail_count'] : 0;
$alert_count = ($resumen && isset($resumen[0]['alert_count'])) ? (int)$resumen[0]['alert_count'] : 0;

$total_paginas = max(1, (int)ceil($total_logs / $registros_por_pagina));
if ($pagina > $total_paginas) {
    $pagina = $total_paginas;
}

$offset = ($pagina - 1) * $registros_por_pagina;

$sql = "SELECT
            l.id_bi,
            l.date_bi,
            l.id_user,
            u.nombre,
            l.action_type,
            l.action_result,
            l.ip_address,
            l.device_info,
            l.detail
        FROM sis_log l
        LEFT JOIN sis_user u ON u.id = l.id_user" .
        $where .
        " ORDER BY l.date_bi DESC
          LIMIT ?, ?";

$logsParams = $whereParams;
$logsParams[] = $offset;
$logsParams[] = $registros_por_pagina;

$logs = seleccion_segura($sql, $logsParams);
if (!is_array($logs)) {
    $logs = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<?php include('head.php'); ?>
<link rel="stylesheet" href="inc/css/admin_panels_responsive.css">
<style>
    /* Estilos específicos de admin_auditoria */
    @media (max-width: 576px) {
        .auditoria-table-wrapper .table td:nth-child(1)::before { content: "Fecha/Hora"; }
        .auditoria-table-wrapper .table td:nth-child(2)::before { content: "Usuario"; }
        .auditoria-table-wrapper .table td:nth-child(3)::before { content: "Nombre"; }
        .auditoria-table-wrapper .table td:nth-child(4)::before { content: "Acción"; }
        .auditoria-table-wrapper .table td:nth-child(5)::before { content: "Resultado"; }
        .auditoria-table-wrapper .table td:nth-child(6)::before { content: "IP"; }
        .auditoria-table-wrapper .table td:nth-child(7)::before { content: "Dispositivo"; }
        .auditoria-table-wrapper .table td:nth-child(8)::before { content: "Detalle"; }
    }
</style>
<body class="fondo-una d-flex flex-column min-vh-100">

    <?php include 'header.php'; ?>

    <main class="flex-fill">
        <div class="container my-4">
            <div class="dashboard-header text-center mb-4">
                <h1>Auditoría de Accesos</h1>
                <p class="lead">Monitoreo de eventos de acceso y seguridad del sistema</p>
            </div>

            <div class="alert alert-info" role="alert">
                Se muestran registros de acceso, intentos fallidos y alertas de seguridad. Usa filtros y paginación para navegar.
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card shadow-sm h-100 border-start border-4" style="border-color:#0d6efd !important;">
                        <div class="card-body">
                            <h6 class="text-muted mb-1">Total registros</h6>
                            <h4 class="mb-0"><?= (int)$total_logs ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm h-100 border-start border-4" style="border-color:#198754 !important;">
                        <div class="card-body">
                            <h6 class="text-muted mb-1">Éxitos</h6>
                            <h4 class="mb-0"><?= (int)$success_count ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm h-100 border-start border-4" style="border-color:#dc3545 !important;">
                        <div class="card-body">
                            <h6 class="text-muted mb-1">Fallidos</h6>
                            <h4 class="mb-0"><?= (int)$fail_count ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm h-100 border-start border-4" style="border-color:#ffc107 !important;">
                        <div class="card-body">
                            <h6 class="text-muted mb-1">Alertas</h6>
                            <h4 class="mb-0"><?= (int)$alert_count ?></h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <?php
                            $query_base = [];
                            if ($fecha_desde !== '') {
                                $query_base['fecha_desde'] = $fecha_desde;
                            }
                            if ($fecha_hasta !== '') {
                                $query_base['fecha_hasta'] = $fecha_hasta;
                            }
                        ?>
                        <div>
                        <strong>Filtro por resultado:</strong>
                        <a href="admin_auditoria.php?<?= http_build_query(array_merge($query_base, ['resultado' => 'TODOS', 'pagina' => 1])) ?>" class="btn btn-sm <?= $resultado === 'TODOS' ? 'btn-primary' : 'btn-outline-primary' ?> ms-2">Todos</a>
                        <a href="admin_auditoria.php?<?= http_build_query(array_merge($query_base, ['resultado' => 'SUCCESS', 'pagina' => 1])) ?>" class="btn btn-sm <?= $resultado === 'SUCCESS' ? 'btn-success' : 'btn-outline-success' ?>">Éxitos</a>
                        <a href="admin_auditoria.php?<?= http_build_query(array_merge($query_base, ['resultado' => 'FAIL', 'pagina' => 1])) ?>" class="btn btn-sm <?= $resultado === 'FAIL' ? 'btn-danger' : 'btn-outline-danger' ?>">Fallidos</a>
                        <a href="admin_auditoria.php?<?= http_build_query(array_merge($query_base, ['resultado' => 'ALERT', 'pagina' => 1])) ?>" class="btn btn-sm <?= $resultado === 'ALERT' ? 'btn-warning text-dark' : 'btn-outline-warning' ?>">Alertas</a>
                    </div>
                    <div>
                        <small class="text-muted">Mostrando <?= (int)$registros_por_pagina ?> por página · Página <?= (int)$pagina ?> de <?= (int)$total_paginas ?></small>
                    </div>
                </div>

                    <form method="get" class="row g-3 align-items-end">
                        <input type="hidden" name="resultado" value="<?= htmlspecialchars($resultado) ?>">
                        <input type="hidden" name="pagina" value="1">
                        <div class="col-md-4">
                            <label class="form-label">Fecha desde</label>
                            <input type="date" name="fecha_desde" class="form-control" value="<?= htmlspecialchars($fecha_desde) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fecha hasta</label>
                            <input type="date" name="fecha_hasta" class="form-control" value="<?= htmlspecialchars($fecha_hasta) ?>">
                        </div>
                        <div class="col-md-4 text-end">
                            <button type="submit" class="btn btn-primary" style="margin-right: 10px;">
                                <i class="fa fa-filter"></i> Aplicar fechas
                            </button>
                            <a href="admin_auditoria.php" class="btn btn-secondary">Limpiar filtros</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="d-flex justify-content-end align-items-center mb-3">
                <?php
                    $pagina_anterior = max(1, $pagina - 1);
                    $pagina_siguiente = min($total_paginas, $pagina + 1);
                ?>
                <a class="btn btn-outline-secondary btn-sm <?= $pagina <= 1 ? 'disabled' : '' ?>" href="admin_auditoria.php?<?= http_build_query(array_merge($query_base, ['resultado' => $resultado, 'pagina' => $pagina_anterior])) ?>">Anterior</a>
                <span class="mx-2">Página <?= (int)$pagina ?> de <?= (int)$total_paginas ?></span>
                <a class="btn btn-outline-secondary btn-sm <?= $pagina >= $total_paginas ? 'disabled' : '' ?>" href="admin_auditoria.php?<?= http_build_query(array_merge($query_base, ['resultado' => $resultado, 'pagina' => $pagina_siguiente])) ?>">Siguiente</a>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3">Listado de eventos</h5>
                    <div class="auditoria-table-wrapper">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha/Hora</th>
                                    <th>Usuario</th>
                                    <th>Nombre</th>
                                    <th>Acción</th>
                                    <th>Resultado</th>
                                    <th>IP</th>
                                    <th>Dispositivo</th>
                                    <th>Detalle</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($total_logs === 0): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">No hay eventos de auditoría para mostrar.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $row): ?>
                                        <?php
                                            $result = strtoupper((string)$row['action_result']);
                                            $action_type = strtoupper((string)$row['action_type']);
                                            if ($action_type === 'LOGIN') {
                                                $action_label = 'INICIO DE SESIÓN';
                                            } elseif ($action_type === 'SECURITY_ALERT') {
                                                $action_label = 'ALERTA DE SEGURIDAD';
                                            } else {
                                                $action_label = $action_type;
                                            }
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string)$row['date_bi']) ?></td>
                                            <td><?= htmlspecialchars((string)$row['id_user']) ?></td>
                                            <td><?= htmlspecialchars((string)$row['nombre']) ?></td>
                                            <td><?= htmlspecialchars((string)$action_label) ?></td>
                                            <td>
                                                <?php if ($result === 'SUCCESS'): ?>
                                                    <span class="badge bg-success">ÉXITO</span>
                                                <?php elseif ($result === 'FAIL'): ?>
                                                    <span class="badge bg-danger">FALLIDO</span>
                                                <?php elseif ($result === 'ALERT'): ?>
                                                    <span class="badge bg-warning text-dark">ALERTA</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?= htmlspecialchars($result) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars((string)$row['ip_address']) ?></td>
                                            <td><?= htmlspecialchars((string)$row['device_info']) ?></td>
                                            <td><?= htmlspecialchars((string)$row['detail']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end align-items-center mt-3 mb-4">
                <?php
                    $pagina_anterior = max(1, $pagina - 1);
                    $pagina_siguiente = min($total_paginas, $pagina + 1);
                ?>
                <a class="btn btn-outline-secondary btn-sm <?= $pagina <= 1 ? 'disabled' : '' ?>" href="admin_auditoria.php?<?= http_build_query(array_merge($query_base, ['resultado' => $resultado, 'pagina' => $pagina_anterior])) ?>">Anterior</a>
                <span class="mx-2">Página <?= (int)$pagina ?> de <?= (int)$total_paginas ?></span>
                <a class="btn btn-outline-secondary btn-sm <?= $pagina >= $total_paginas ? 'disabled' : '' ?>" href="admin_auditoria.php?<?= http_build_query(array_merge($query_base, ['resultado' => $resultado, 'pagina' => $pagina_siguiente])) ?>">Siguiente</a>
            </div>

            <div class="mt-4 text-center">
                <a href="<?= htmlspecialchars($base_url) ?>dashboard.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left-circle"></i> Volver al Panel Principal
                </a>
            </div>
        </div>
    </main>

    <footer class="footer-una mt-auto">
        <div class="container">
            <p class="mb-1">&copy; <?= date('Y') ?> Universidad Nacional de Costa Rica</p>
            <small>Escuela de Informática - Proyecto SGPFL v3.0</small>
        </div>
    </footer>
</body>
</html>
