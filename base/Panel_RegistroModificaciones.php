<?php
// VERIFICAR AUTENTICACIÓN Y PERMISOS
include("mod/login/check.php");

// 1. INCLUIR ARCHIVOS NECESARIOS
include('lang/lang.es');
include_once(__DIR__ . "/inc/db/bdcommon.inc");
include_once(__DIR__ . "/inc/db/db.php");
require_once __DIR__ . "/mod/admin/users/ModificacionUpdate.php";

// 2. OBTENER VARIABLES DE SESIÓN
$current_user_id = $mySessionController->getVar("usuario");
$current_user_name = $mySessionController->getVar("nombre");
$current_user_rol = (int)$mySessionController->getVar("rol");
$base_url = $mySessionController->getVar("cds_domain") . $mySessionController->getVar("cds_locate");

// 3. CONTROL DE ACCESO POR ROL (Solo Administrador - rol 1 y Gestor - rol 2)
if ($current_user_rol !== 1 && $current_user_rol !== 2) {
    header('Location: dashboard.php');
    exit;
}

// 4. INSTANCIAR CLASE DE CONSULTAS
$modificacionUpdate = new ModificacionUpdate();

// 5. OBTENER FILTROS DE LA URL
$tabla_origen = isset($_GET['tabla']) ? trim($_GET['tabla']) : 'todas';
$campo_modificado = isset($_GET['campo']) ? trim($_GET['campo']) : 'todos';
$modificado_por = isset($_GET['usuario']) ? trim($_GET['usuario']) : '';
$fecha_desde = isset($_GET['fecha_desde']) ? trim($_GET['fecha_desde']) : '';
$fecha_hasta = isset($_GET['fecha_hasta']) ? trim($_GET['fecha_hasta']) : '';
$id_registro = isset($_GET['id_registro']) ? trim($_GET['id_registro']) : '';
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina < 1) $pagina = 1;

// Validar fechas
if ($fecha_desde !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_desde)) {
    $fecha_desde = '';
}
if ($fecha_hasta !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_hasta)) {
    $fecha_hasta = '';
}

// 6. OBTENER ESTADÍSTICAS
$estadisticas = $modificacionUpdate->obtenerEstadisticas();

// 7. OBTENER LISTA DE USUARIOS MODIFICADORES (para filtro)
$usuarios_modificadores = $modificacionUpdate->obtenerUsuariosModificadores();

// 8. OBTENER REGISTROS CON FILTROS
$filtros = [
    'tabla_origen' => $tabla_origen,
    'campo_modificado' => $campo_modificado,
    'modificado_por' => $modificado_por,
    'fecha_desde' => $fecha_desde,
    'fecha_hasta' => $fecha_hasta,
    'id_registro' => $id_registro
];

$resultado = $modificacionUpdate->obtenerRegistros($filtros, $pagina, 15);
$registros = $resultado['data'];
$total_registros = $resultado['total'];
$total_paginas = $resultado['paginas'];
$pagina_actual = $resultado['pagina_actual'];

// Helper para construir URL con filtros
function buildFilterUrl($params = []) {
    $base = [
        'tabla' => $_GET['tabla'] ?? 'todas',
        'campo' => $_GET['campo'] ?? 'todos',
        'usuario' => $_GET['usuario'] ?? '',
        'fecha_desde' => $_GET['fecha_desde'] ?? '',
        'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
        'id_registro' => $_GET['id_registro'] ?? '',
        'pagina' => $_GET['pagina'] ?? 1
    ];
    return 'Panel_RegistroModificaciones.php?' . http_build_query(array_merge($base, $params));
}

// Helper para formatear fecha
function formatearFecha($fecha) {
    if (empty($fecha)) return '<span class="text-muted">—</span>';
    $dt = new DateTime($fecha);
    return $dt->format('d/m/Y H:i:s');
}

// Helper para formatear fecha corta (solo fecha sin hora)
function formatearFechaCorta($fecha, $esValorAnterior = false) {
    if ($fecha === null || $fecha === '') {
        return $esValorAnterior
            ? '<span class="text-muted">Sin valor previo</span>'
            : '<span class="text-muted">NULL</span>';
    }

    try {
        $dt = new DateTime($fecha);
        return $dt->format('d/m/Y');
    } catch (Exception $e) {
        return '<span class="text-muted">Fecha inválida</span>';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<!-- =============================== HEAD =============================== -->
<?php include 'head.php'; ?>
<body class="fondo-una d-flex flex-column min-vh-100">

    <!-- =============================== HEADER =============================== -->
    <?php include 'header.php'; ?>

    <!-- =============================== MAIN CONTENT =============================== -->
    <main class="flex-fill">
        <div class="container my-4">
            
            <!-- TÍTULO -->
            <div class="dashboard-header text-center mb-4">
                <h1><i class="fa fa-history"></i> Registro de Modificaciones de Fechas</h1>
                <p class="lead">Auditoría de cambios en fechas de proyectos y prórrogas</p>
            </div>

            <!-- ALERTA INFORMATIVA -->
            <div class="alert alert-info text-center" role="alert">
                <i class="fa fa-info-circle"></i>
                Este panel muestra el historial de modificaciones en fechas
                
            </div>

            <!-- ==================== DASHBOARD ESTADÍSTICAS ==================== -->
            <div class="row g-3 mb-4">
                <div class="col-md-3 col-6">
                    <div class="card shadow-sm h-100 border-start border-4" style="border-color:#0d6efd !important;">
                        <div class="card-body text-center">
                            <h6 class="text-muted mb-1"><i class="fa fa-database"></i> Total Cambios</h6>
                            <h3 class="mb-0 text-primary"><?= (int)$estadisticas['total_cambios'] ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card shadow-sm h-100 border-start border-4" style="border-color:#198754 !important;">
                        <div class="card-body text-center">
                            <h6 class="text-muted mb-1"><i class="fa fa-project-diagram"></i> Proyectos</h6>
                            <h3 class="mb-0 text-success"><?= (int)$estadisticas['cambios_proyectos'] ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card shadow-sm h-100 border-start border-4" style="border-color:#6f42c1 !important;">
                        <div class="card-body text-center">
                            <h6 class="text-muted mb-1"><i class="fa fa-clock"></i> Prórrogas</h6>
                            <h3 class="mb-0" style="color:#6f42c1;"><?= (int)$estadisticas['cambios_prorrogas'] ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card shadow-sm h-100 border-start border-4" style="border-color:#fd7e14 !important;">
                        <div class="card-body text-center">
                            <h6 class="text-muted mb-1"><i class="fa fa-calendar-day"></i> Hoy</h6>
                            <h3 class="mb-0" style="color:#fd7e14;"><?= (int)$estadisticas['cambios_hoy'] ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================== FILTROS ==================== -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fa fa-filter"></i> Filtros de búsqueda</h5>
                </div>
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <input type="hidden" name="campo" value="todos">

                        <!-- Tabla origen -->
                        <div class="col-md-3">
                            <label class="form-label">Tabla origen</label>
                            <select name="tabla" class="form-select">
                                <option value="todas" <?= $tabla_origen === 'todas' ? 'selected' : '' ?>>Todas</option>
                                <option value="registered_projects" <?= $tabla_origen === 'registered_projects' ? 'selected' : '' ?>>Proyectos</option>
                                <option value="tfg_extension_requests" <?= $tabla_origen === 'tfg_extension_requests' ? 'selected' : '' ?>>Prórrogas</option>
                            </select>
                        </div>
                        
                        <!-- Usuario -->
                        <div class="col-md-3">
                            <label class="form-label">Modificado por</label>
                            <select name="usuario" class="form-select">
                                <option value="">Todos los usuarios</option>
                                <?php foreach ($usuarios_modificadores as $usr): ?>
                                    <option value="<?= htmlspecialchars($usr['modificado_por']) ?>" 
                                            <?= $modificado_por === $usr['modificado_por'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($usr['nombre_usuario']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- ID Registro -->
                        <div class="col-md-3">
                            <label class="form-label">ID Registro</label>
                            <input type="number" name="id_registro" class="form-control" 
                                   placeholder="Ej: 123" value="<?= htmlspecialchars($id_registro) ?>">
                        </div>
                        
                        <!-- Fecha desde -->
                        <div class="col-md-3">
                            <label class="form-label">Fecha desde</label>
                            <input type="date" name="fecha_desde" class="form-control" 
                                   value="<?= htmlspecialchars($fecha_desde) ?>">
                        </div>
                        
                        <!-- Fecha hasta -->
                        <div class="col-md-3">
                            <label class="form-label">Fecha hasta</label>
                            <input type="date" name="fecha_hasta" class="form-control" 
                                   value="<?= htmlspecialchars($fecha_hasta) ?>">
                        </div>
                        
                        <!-- Botones -->
                        <div class="col-md-6 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-search"></i> Buscar
                            </button>
                            <a href="Panel_RegistroModificaciones.php" class="btn btn-secondary">
                                <i class="fa fa-eraser"></i> Limpiar
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ==================== TABLA DE REGISTROS ==================== -->
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fa fa-list"></i> Modificaciones recientes</h5>
                    <span class="badge bg-secondary"><?= $total_registros ?> registros encontrados</span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($registros)): ?>
                        <div class="alert alert-warning m-3">
                            <i class="fa fa-exclamation-triangle"></i> No se encontraron registros con los filtros seleccionados.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width:140px;">Fecha cambio</th>
                                        <th>Usuario</th>
                                        <th>Tabla</th>
                                        <th>ID Reg.</th>
                                        <th>Valor anterior</th>
                                        <th>Valor nuevo</th>
                                        <th style="width:80px;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($registros as $reg): ?>
                                        <tr>
                                            <td>
                                                <small><?= formatearFecha($reg['fecha_modificacion']) ?></small>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($reg['nombre_usuario']) ?></strong>
                                                <br><small class="text-muted"><?= htmlspecialchars($reg['modificado_por']) ?></small>
                                            </td>
                                            <td>
                                                <?php if ($reg['tabla_origen'] === 'registered_projects'): ?>
                                                    <span class="badge bg-success">Proyectos</span>
                                                <?php else: ?>
                                                    <span class="badge bg-purple" style="background-color:#6f42c1;">Prórrogas</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">#<?= (int)$reg['id_registro'] ?></span>
                                            </td>
                                            <td>
                                                <span class="text-danger">
                                                    <?= formatearFechaCorta($reg['valor_anterior'], true) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="text-success">
                                                    <?= formatearFechaCorta($reg['valor_nuevo']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-info btn-ver-detalle" 
                                                        data-id="<?= $reg['id'] ?>"
                                                        data-fecha="<?= htmlspecialchars(formatearFecha($reg['fecha_modificacion'])) ?>"
                                                        data-usuario="<?= htmlspecialchars($reg['nombre_usuario']) ?>"
                                                        data-usuario-id="<?= htmlspecialchars($reg['modificado_por']) ?>"
                                                        data-registro="<?= (int)$reg['id_registro'] ?>"
                                                        data-anterior="<?= htmlspecialchars(formatearFechaCorta($reg['valor_anterior'], true)) ?>"
                                                        data-nuevo="<?= htmlspecialchars(formatearFechaCorta($reg['valor_nuevo'])) ?>"
                                                        data-descripcion="<?= htmlspecialchars($reg['descripcion'] ?? '') ?>"
                                                        title="Ver detalle">
                                                    <i class="fa fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- PAGINACIÓN -->
                <?php if ($total_paginas > 1): ?>
                <div class="card-footer">
                    <nav aria-label="Paginación">
                        <ul class="pagination justify-content-center mb-0">
                            <!-- Primera página -->
                            <li class="page-item <?= $pagina_actual <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= buildFilterUrl(['pagina' => 1]) ?>">
                                    <i class="fa fa-angle-double-left"></i>
                                </a>
                            </li>
                            
                            <!-- Anterior -->
                            <li class="page-item <?= $pagina_actual <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= buildFilterUrl(['pagina' => $pagina_actual - 1]) ?>">
                                    <i class="fa fa-angle-left"></i>
                                </a>
                            </li>
                            
                            <!-- Páginas -->
                            <?php
                            $rango = 2;
                            $inicio = max(1, $pagina_actual - $rango);
                            $fin = min($total_paginas, $pagina_actual + $rango);
                            
                            if ($inicio > 1): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif;
                            
                            for ($i = $inicio; $i <= $fin; $i++): ?>
                                <li class="page-item <?= $i === $pagina_actual ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= buildFilterUrl(['pagina' => $i]) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor;
                            
                            if ($fin < $total_paginas): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                            
                            <!-- Siguiente -->
                            <li class="page-item <?= $pagina_actual >= $total_paginas ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= buildFilterUrl(['pagina' => $pagina_actual + 1]) ?>">
                                    <i class="fa fa-angle-right"></i>
                                </a>
                            </li>
                            
                            <!-- Última página -->
                            <li class="page-item <?= $pagina_actual >= $total_paginas ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= buildFilterUrl(['pagina' => $total_paginas]) ?>">
                                    <i class="fa fa-angle-double-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <p class="text-center text-muted mt-2 mb-0">
                        Página <?= $pagina_actual ?> de <?= $total_paginas ?> | 
                        Mostrando <?= count($registros) ?> de <?= $total_registros ?> registros
                    </p>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <!-- =============================== FOOTER =============================== -->
    <?php include 'footer.php'; ?>
    
    <!-- Script para modal con SweetAlert2 -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.btn-ver-detalle').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var id = this.getAttribute('data-id');
                var fecha = this.getAttribute('data-fecha');
                var usuario = this.getAttribute('data-usuario');
                var usuarioId = this.getAttribute('data-usuario-id');
                var registro = this.getAttribute('data-registro');
                var anterior = this.getAttribute('data-anterior');
                var nuevo = this.getAttribute('data-nuevo');
                var descripcion = this.getAttribute('data-descripcion');
                
                var descripcionHtml = '';
                if (descripcion && descripcion.trim() !== '') {
                    descripcionHtml = '<tr><th>Descripción:</th><td>' + descripcion + '</td></tr>';
                }
                
                var htmlContent = `
                    <table class="table table-sm text-start">
                        <tr>
                            <th style="width:45%;">Fecha modificación:</th>
                            <td>${fecha}</td>
                        </tr>
                        <tr>
                            <th>Usuario:</th>
                            <td>${usuario} <small class="text-muted">(${usuarioId})</small></td>
                        </tr>
                        <tr>
                            <th>ID registro:</th>
                            <td><span class="badge bg-secondary">#${registro}</span></td>
                        </tr>
                        <tr>
                            <th>Valor anterior:</th>
                            <td><span class="text-danger">${anterior}</span></td>
                        </tr>
                        <tr>
                            <th>Valor nuevo:</th>
                            <td><span class="text-success">${nuevo}</span></td>
                        </tr>
                        ${descripcionHtml}
                    </table>
                `;
                
                Swal.fire({
                    title: '<i class="fa fa-info-circle"></i> Detalle del cambio #' + id,
                    html: htmlContent,
                    width: 600,
                    showCloseButton: true,
                    showConfirmButton: true,
                    confirmButtonText: 'Cerrar',
                    confirmButtonColor: '#6c757d'
                });
            });
        });
    });
    </script>
</body>
</html>

