<?php
/**
 * HU-027: Panel de Archivo Histórico
 * 
 * Este panel permite a Admin, CTFG y Subdirección consultar proyectos
 * que han sido archivados (concluidos o cancelados).
 * 
 * Cumple con el Artículo 68 RGPEA para conservación de registros.
 */

// Verificar autenticación
include("mod/login/check.php");
require_once('inc/constants.php');
require_once('inc/archive_functions.php');

// Variables de sesión
$current_user_id = $mySessionController->getVar("usuario");
$current_user_name = $mySessionController->getVar("nombre");
$rol = $mySessionController->getVar("rol");

// URL base
$cds_domain = $mySessionController->getVar("cds_domain");
$cds_locate = $mySessionController->getVar("cds_locate");
$base_url = $cds_domain . $cds_locate;

// Verificar permisos: solo Admin, CTFG y Gestor Académico (Subdirección)
$allowed_roles = [ROL_ADMIN, ROL_CTFG, ROL_GESTOR];

if (!in_array($rol, $allowed_roles)) {
    header("location: ./index.php");
    exit();
}

// Conexión a base de datos (incluir después de variables de sesión)
$conn = new mysqli('localhost', 'root', '', 'base_db');
$conn->set_charset("utf8mb4");

// Registrar acceso al archivo
logArchiveAccess($conn, $current_user_id, 'VIEW_LIST');

// Procesar filtros
$filters = [];
if (!empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}
if (!empty($_GET['archive_reason'])) {
    $filters['archive_reason'] = $_GET['archive_reason'];
}
if (!empty($_GET['date_from'])) {
    $filters['date_from'] = $_GET['date_from'];
}
if (!empty($_GET['date_to'])) {
    $filters['date_to'] = $_GET['date_to'];
}

// Obtener propuestas archivadas
$archived_proposals = getArchivedProposals($conn, $filters);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archivo Histórico de Proyectos</title>
    <?php include('head.php'); ?>
    <style>
        .archive-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        .badge-concluido {
            background-color: #198754;
        }
        .badge-cancelado {
            background-color: #dc3545;
        }
        .compression-info {
            font-size: 0.8rem;
            color: #6c757d;
        }
        .filter-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        .table-archive th {
            background-color: #495057;
            color: white;
        }
        .archive-icon {
            font-size: 3rem;
            color: #6c757d;
        }
    </style>
</head>
<body class="fondo-una d-flex flex-column min-vh-100">
    <!-- =============================== HEADER =============================== -->
    <?php include('header.php'); ?>
    
    <!-- =============================== CONTENIDO PRINCIPAL =============================== -->
    <main class="flex-fill">
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-archive-fill me-2 icon-md"></i>
                            <h4 class="mb-0" style="color: white !important;">Archivo Histórico de Proyectos</h4>
                        </div>
                        <a href="<?= htmlspecialchars($base_url) ?>dashboard.php" class="btn btn-light btn-sm">
                            <i class="bi bi-arrow-left"></i> Volver al Panel Principal
                        </a>
                    </div>
                    
                    <div class="card-body">
                        <!-- Información de cumplimiento -->
                        <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            <div>
                                <strong>Art. 68 RGPEA:</strong> Este archivo conserva registros de proyectos concluidos 
                                o cancelados para fines de auditoría. Todos los accesos son registrados.
                            </div>
                        </div>
                        
                        <!-- Filtros -->
                        <div class="filter-card">
                            <form method="GET" action="" class="row g-3">
                                <div class="col-md-4">
                                    <label for="search" class="form-label">Buscar</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                                        <input type="text" class="form-control" id="search" name="search" 
                                               placeholder="Título o ID de usuario"
                                               value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="archive_reason" class="form-label">Estado</label>
                                    <select class="form-select" id="archive_reason" name="archive_reason">
                                        <option value="">Todos</option>
                                        <option value="Concluido" <?php echo ($filters['archive_reason'] ?? '') === 'Concluido' ? 'selected' : ''; ?>>
                                            Concluidos
                                        </option>
                                        <option value="Cancelado" <?php echo ($filters['archive_reason'] ?? '') === 'Cancelado' ? 'selected' : ''; ?>>
                                            Cancelados
                                        </option>
                                    </select>
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="date_from" class="form-label">Desde</label>
                                    <input type="date" class="form-control" id="date_from" name="date_from"
                                           value="<?php echo htmlspecialchars($filters['date_from'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="date_to" class="form-label">Hasta</label>
                                    <input type="date" class="form-control" id="date_to" name="date_to"
                                           value="<?php echo htmlspecialchars($filters['date_to'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="bi bi-filter"></i> Filtrar
                                    </button>
                                    <a href="panel_archivo_historico.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-x-circle"></i>
                                    </a>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Tabla de resultados -->
                        <?php if (empty($archived_proposals)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-archive archive-icon"></i>
                                <h5 class="mt-3 text-muted">No se encontraron proyectos archivados</h5>
                                <p class="text-muted">Los proyectos concluidos o cancelados aparecerán aquí.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-archive">
                                    <thead>
                                        <tr>
                                            <th>ID Original</th>
                                            <th>Título</th>
                                            <th>Estudiante</th>
                                            <th>Estado</th>
                                            <th>Fecha Archivo</th>
                                            <th>Compresión</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($archived_proposals as $proposal): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($proposal['original_proposal_id']); ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($proposal['title']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($proposal['disciplines']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($proposal['user_name'] ?? $proposal['user_id']); ?></td>
                                                <td>
                                                    <span class="badge archive-badge <?php echo $proposal['archive_reason'] === 'Concluido' ? 'badge-concluido' : 'badge-cancelado'; ?>">
                                                        <?php echo htmlspecialchars($proposal['archive_reason']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($proposal['archived_at'])); ?></td>
                                                <td class="compression-info">
                                                    <?php if ($proposal['is_compressed'] && $proposal['file_size'] > 0): ?>
                                                        <?php 
                                                            $ratio = round((1 - ($proposal['compressed_size'] / $proposal['file_size'])) * 100);
                                                        ?>
                                                        <i class="bi bi-file-zip text-success"></i>
                                                        <?php echo formatFileSize($proposal['file_size']); ?> → 
                                                        <?php echo formatFileSize($proposal['compressed_size']); ?>
                                                        <br><small>(<?php echo $ratio; ?>% reducción)</small>
                                                    <?php else: ?>
                                                        <span class="text-muted">Sin documento</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="ver_archivo_detalle.php?id=<?php echo $proposal['id']; ?>" 
                                                           class="btn btn-outline-primary" title="Ver detalles">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <?php if ($proposal['file_size'] > 0): ?>
                                                            <a href="descargar_archivo.php?type=proposal&id=<?php echo $proposal['id']; ?>" 
                                                               class="btn btn-outline-success" title="Descargar documento">
                                                                <i class="bi bi-download"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mt-3">
                                <small class="text-muted">
                                    <i class="bi bi-clock-history"></i> 
                                    Mostrando <?php echo count($archived_proposals); ?> registro(s)
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </main>
    
    <!-- =============================== FOOTER =============================== -->
    <?php include('footer.php'); ?>
    
    <script>
        // DataTables para mejor navegación si hay muchos registros
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof $.fn.DataTable !== 'undefined' && document.querySelector('.table-archive tbody tr')) {
                $('.table-archive').DataTable({
                    language: {
                        url: 'lib/DataTables/es-ES.json'
                    },
                    pageLength: 25,
                    order: [[5, 'desc']] // Ordenar por fecha de archivo descendente
                });
            }
        });
    </script>
</body>
</html>

<?php
/**
 * Función auxiliar para formatear tamaño de archivo
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

$conn->close();
?>
