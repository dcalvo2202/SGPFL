<?php
declare(strict_types=1);

include("mod/login/check.php");
include('lang/lang.es');

require_once __DIR__ . '/mod/admin/users/prorroga_report_service.php';

$current_user_id = $mySessionController->getVar("usuario");
$current_user_name = $mySessionController->getVar("nombre");
$current_user_rol = (int)$mySessionController->getVar("rol");
$base_url = $mySessionController->getVar("cds_domain") . $mySessionController->getVar("cds_locate");

if ($current_user_rol !== 2 && $current_user_rol !== 1) {
    header('Location: dashboard.php');
    exit;
}

$anio = null;
$estado = 'Prorrogado';
$sede = null;
$error_message = '';
$report_data = [
    'meta' => [
        'resumen' => [
            'total_proyectos' => 0,
            'proyectos_con_multiples_prorrogas' => 0,
            'proyectos_con_una_prorroga' => 0,
        ],
        'filtros' => [
            'anio' => null,
            'estado' => 'Prorrogado',
            'sede' => null,
            'descripcion' => 'Estado: Prorrogado | Año: Todos | Sede: No disponible',
        ],
    ],
    'filas' => [],
];

try {
    if (isset($_GET['anio']) && trim((string)$_GET['anio']) !== '') {
        $anio_raw = trim((string)$_GET['anio']);

        if (!preg_match('/^\d{4}$/', $anio_raw)) {
            throw new InvalidArgumentException('El año ingresado no es válido.');
        }

        $anio = (int)$anio_raw;
    }

    $builder = new ProrrogaReportPdfData();
    $report_data = $builder->construirDataReporte($anio, $estado, $sede);

} catch (Throwable $e) {
    $error_message = $e->getMessage();
}

$download_url = $base_url . 'mod/admin/users/descargar_reporte_prorrogas.php?estado=Prorrogado';
if ($anio !== null) {
    $download_url .= '&anio=' . urlencode((string)$anio);
}

$available_years = [];
$current_year = (int)date('Y');
for ($year = $current_year + 1; $year >= 2024; $year--) {
    $available_years[] = $year;
}
?>
<!DOCTYPE html>
<html lang="es">
<?php include 'head.php'; ?>
<body class="fondo-una d-flex flex-column min-vh-100">

<?php include 'header.php'; ?>

<main class="flex-fill">
    <div class="container my-5">

        <div class="dashboard-header text-center mb-5">
            <h1>Reporte de proyectos en prórroga</h1>
            <p class="lead">
                Desde este panel puede consultar los proyectos con estado Prorrogado
                y generar el reporte consolidado en formato PDF para Secretaría.
            </p>
        </div>

        <div class="card document-table mb-4">
            <div class="card-body p-4">
                <h2 class="prorroga-section-title mb-4">
                    <i class="bi bi-funnel"></i> Filtros del reporte
                </h2>

                <form method="get" action="panel_reporte_prorrogas.php" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="anio" class="form-label fw-semibold">Año</label>
                        <select name="anio" id="anio" class="form-select">
                            <option value="">Todos</option>
                            <?php foreach ($available_years as $year): ?>
                                <option value="<?php echo htmlspecialchars((string)$year, ENT_QUOTES, 'UTF-8'); ?>"
                                    <?php echo ($anio === $year) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars((string)$year, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="estado" class="form-label fw-semibold">Estado</label>
                        <input
                            type="text"
                            id="estado"
                            class="form-control"
                            value="Prorrogado"
                            readonly
                        >
                    </div>

                    <div class="col-md-4">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-search me-2"></i> Filtrar
                            </button>
                            <a href="panel_reporte_prorrogas.php" class="btn btn-outline-secondary">
                                Limpiar filtros
                            </a>
                        </div>
                    </div>
                </form>

                <div class="alert alert-info mt-4 mb-0">
                    El reporte PDF se generará con los filtros aplicados. El filtro por sede queda pendiente
                    hasta que exista una fuente de datos clara en la base actual.
                </div>
            </div>
        </div>

        <div class="card document-table mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
                    <div>
                        <h2 class="prorroga-section-title mb-2">
                            <i class="bi bi-table"></i> Proyectos prorrogados
                        </h2>
                        <p class="text-muted mb-0">
                            Se muestran únicamente proyectos con estado <strong>Prorrogado</strong>.
                        </p>
                    </div>

                    <div class="d-grid">
                        <a href="<?php echo htmlspecialchars($download_url, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-danger">
                            <i class="bi bi-download me-2"></i> Descargar reporte PDF
                        </a>
                    </div>
                </div>

                <?php if ($error_message !== ''): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php else: ?>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="prorroga-summary-box">
                                <span class="prorroga-summary-label">Total proyectos</span>
                                <span class="prorroga-summary-value">
                                    <?php echo htmlspecialchars((string)($report_data['meta']['resumen']['total_proyectos'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="prorroga-summary-box">
                                <span class="prorroga-summary-label">Múltiples prórrogas</span>
                                <span class="prorroga-summary-value">
                                    <?php echo htmlspecialchars((string)($report_data['meta']['resumen']['proyectos_con_multiples_prorrogas'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="prorroga-summary-box">
                                <span class="prorroga-summary-label">Una prórroga</span>
                                <span class="prorroga-summary-value">
                                    <?php echo htmlspecialchars((string)($report_data['meta']['resumen']['proyectos_con_una_prorroga'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <?php if (empty($report_data['filas'])): ?>
                        <div class="prorroga-empty-state">
                            <i class="bi bi-folder2-open"></i>
                            <h5>No se encontraron proyectos prorrogados</h5>
                            <p class="mb-0">Ajusta el filtro de año o limpia los filtros para volver a consultar.</p>
                        </div>
                    <?php else: ?>
                        <div class="prorroga-table-wrapper">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Identificador</th>
                                        <th>Proyecto</th>
                                        <th>Estudiantes</th>
                                        <th>Comité Asesor</th>
                                        <th>Prórrogas</th>
                                        <th>Última solicitud</th>
                                        <th>Detalle</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($report_data['filas'] as $fila): ?>
                                        <tr class="<?php echo !empty($fila['resaltar_multiples_prorrogas']) ? 'prorroga-highlight-row' : ''; ?>">
                                            <td>
                                                <strong><?php echo htmlspecialchars((string)$fila['identificador'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                            </td>
                                            <td>
                                                <div class="prorroga-table-title">
                                                    <?php echo htmlspecialchars((string)$fila['nombre_proyecto'], ENT_QUOTES, 'UTF-8'); ?>
                                                </div>
                                                <div class="prorroga-table-meta">
                                                    Creación: <?php echo htmlspecialchars((string)$fila['fecha_creacion'], ENT_QUOTES, 'UTF-8'); ?><br>
                                                    Finalización: <?php echo htmlspecialchars((string)$fila['fecha_finalizacion'], ENT_QUOTES, 'UTF-8'); ?><br>
                                                    Estado: <?php echo htmlspecialchars((string)$fila['estado'], ENT_QUOTES, 'UTF-8'); ?>
                                                </div>
                                            </td>
                                            <td class="prorroga-preline">
                                                <?php echo htmlspecialchars((string)$fila['estudiantes'], ENT_QUOTES, 'UTF-8'); ?>
                                            </td>
                                            <td class="prorroga-table-meta">
                                                <strong>Tutor:</strong> <?php echo htmlspecialchars((string)$fila['comite_asesor']['tutor'], ENT_QUOTES, 'UTF-8'); ?><br>
                                                <strong>Asesor 1:</strong> <?php echo htmlspecialchars((string)$fila['comite_asesor']['asesor_1'], ENT_QUOTES, 'UTF-8'); ?><br>
                                                <strong>Asesor 2:</strong> <?php echo htmlspecialchars((string)$fila['comite_asesor']['asesor_2'], ENT_QUOTES, 'UTF-8'); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <?php echo htmlspecialchars((string)$fila['cantidad_prorrogas_activas'], ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                                <?php if (!empty($fila['resaltar_multiples_prorrogas'])): ?>
                                                    <div class="small text-warning fw-semibold mt-1">Múltiples</div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars((string)$fila['ultima_fecha_solicitud'], ENT_QUOTES, 'UTF-8'); ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars((string)$fila['detalle_fechas_solicitud'], ENT_QUOTES, 'UTF-8'); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="panel_ctfg.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left-circle"></i> Volver al panel principal
            </a>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>

<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .dashboard-header h1 {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        font-weight: 700;
        color: #034991;
        margin-bottom: 0.5rem;
    }

    .dashboard-header .lead {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #6c757d;
        max-width: 900px;
        margin: 0 auto;
    }

    .document-table {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    .table thead th {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        font-weight: 600;
        font-size: 0.95rem;
        color: #034991;
        border-bottom: 2px solid #dee2e6;
    }

    .table tbody td {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        vertical-align: middle;
    }

    .prorroga-section-title {
        color: #c8151a;
        font-size: 1.35rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .prorroga-summary-box {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        padding: 1rem 1.2rem;
        display: flex;
        flex-direction: column;
        min-height: 96px;
        justify-content: center;
    }

    .prorroga-summary-label {
        color: #6c757d;
        font-size: 0.95rem;
        margin-bottom: 0.35rem;
    }

    .prorroga-summary-value {
        color: #034991;
        font-size: 1.7rem;
        font-weight: 700;
        line-height: 1;
    }

    .prorroga-empty-state {
        padding: 2.5rem 1.5rem;
        text-align: center;
        color: #6c757d;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .prorroga-empty-state i {
        font-size: 2.5rem;
        color: #adb5bd;
        margin-bottom: 0.75rem;
        display: block;
    }

    .prorroga-table-title {
        font-weight: 700;
        color: #034991;
        margin-bottom: 0.35rem;
    }

    .prorroga-table-meta {
        font-size: 0.92rem;
        color: #6c757d;
        line-height: 1.45;
    }

    .prorroga-preline {
        white-space: pre-line;
    }

    .prorroga-highlight-row {
        background: #fff8e1;
    }

    @media (max-width: 767px) {
        .prorroga-summary-value {
            font-size: 1.4rem;
        }

        /* Responsive table mobile */
        .prorroga-table-wrapper .table thead { display: none; }
        .prorroga-table-wrapper .table tbody tr {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            padding: 0.75rem;
            margin-bottom: 0.75rem;
            background-color: #fff;
        }
        .prorroga-table-wrapper .table td {
            display: flex;
            flex-direction: column;
            padding: 0.25rem 0 !important;
            border: none !important;
            text-align: left;
            width: 100%;
        }
        .prorroga-table-wrapper .table td::before {
            font-weight: 600;
            color: #034991;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }
        .prorroga-table-wrapper .table td:nth-child(1)::before { content: "Identificador"; }
        .prorroga-table-wrapper .table td:nth-child(2)::before { content: "Proyecto"; }
        .prorroga-table-wrapper .table td:nth-child(3)::before { content: "Estudiantes"; }
        .prorroga-table-wrapper .table td:nth-child(4)::before { content: "Comité"; }
        .prorroga-table-wrapper .table td:nth-child(5)::before { content: "Prórrogas"; }
        .prorroga-table-wrapper .table td:nth-child(6)::before { content: "Última solicitud"; }
        .prorroga-table-wrapper .table td:nth-child(7)::before { content: "Detalle"; }
    }
</style>
</body>
</html>