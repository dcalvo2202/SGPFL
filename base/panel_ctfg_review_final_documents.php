<?php
// VERIFICAR AUTENTICACIÓN Y PERMISOS
include("mod/login/check.php");

// 1. INCLUIR ARCHIVOS NECESARIOS
include('includes.php');
include('lang/lang.es');
include('inc/db/db.php');

// 2. OBTENER VARIABLES DE SESIÓN
$current_user_id = $mySessionController->getVar("usuario");
$current_user_name = $mySessionController->getVar("nombre");
$current_user_rol = $mySessionController->getVar("rol");
$base_url = $mySessionController->getVar("cds_domain") . $mySessionController->getVar("cds_locate");

// 3. CONTROL DE ACCESO POR ROL (Solo CTFG - rol 3)
if ($current_user_rol != 3) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<!-- =============================== HEAD =============================== -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revisión Documentos Finales - CTFG</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/webp" href="<?= htmlspecialchars($favicon_url) ?>">

    <!-- Bootstrap CSS y Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Estilos personalizados -->
    <link href="<?= htmlspecialchars($base_url . 'inc/css/estilo.css') ?>" rel="stylesheet">
    <link href="<?= htmlspecialchars($base_url . 'inc/css/panel_estudiante.css') ?>" rel="stylesheet">
    
    <style>
        /* Tipografía consistente con todo el sistema */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .dashboard-header h1 {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: #034991;
            margin-bottom: 0.5rem;
        }
        
        .dashboard-header .lead {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #6c757d;
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
        
        .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 600;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .status-pendiente {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffc107;
        }
        
        .btn-download {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-weight: 600;
        }
        
        .empty-state {
            padding: 4rem 2rem;
            text-align: center;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 1.5rem;
        }
        
        .empty-state h3 {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #034991;
            font-weight: 700;
        }
        
        .empty-state p {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #6c757d;
        }
    </style>
</head>
<body class="fondo-una d-flex flex-column min-vh-100">

    <!-- =============================== HEADER =============================== -->
    <header class="navbar-una" style="background: linear-gradient(135deg, #CD1719, #A01215) !important;">
        <div class="container-fluid px-4">
            <div class="header-left d-flex align-items-center">
                <img src="<?= htmlspecialchars($base_url) ?>img/logo.webp" alt="Logo UNA" class="logo-una">
                <div class="header-text ms-3">
                    <h5 class="mb-0 text-white fw-bold">Universidad Nacional de Costa Rica</h5>
                    <small class="text-light opacity-85">Escuela de Informática</small>
                </div>
            </div>
            <div class="header-right text-end">
                <div class="user-info text-white mb-2">
                    <i class="bi bi-person-circle fs-5"></i>
                    <span class="ms-2 fw-semibold"><?= htmlspecialchars($current_user_name) ?></span>
                </div>
                <div class="user-details">
                    <small class="text-light opacity-75">ID: <?= htmlspecialchars($current_user_id) ?></small>
                    <a href="<?= htmlspecialchars($base_url) ?>dashboard.php" class="btn btn-outline-light btn-sm ms-2" style="font-size: 1.05rem; padding: 0.55rem 1.1rem;">
                        <i class="bi bi-house-fill"></i> Inicio
                    </a>
                     <a href="<?= htmlspecialchars($base_url) ?>mod/login/logout.php" class="btn btn-outline-light btn-sm ms-2" style="font-size: 1.05rem; padding: 0.55rem 1.1rem;">
                        <i class="bi bi-box-arrow-right"></i> Salir
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- =============================== CONTENIDO PRINCIPAL =============================== -->
    <main class="flex-fill">
        <div class="container my-5">
            
            <div class="dashboard-header text-center mb-5">
                <h1 style="font-size: 2.5rem; font-weight: 700;">Revisión de Documentos Finales de TFG</h1>
                <p class="lead">Documentos finales pendientes de revisión y aprobación.</p>
            </div>

            <div class="card document-table">
                <div class="card-body">
                    <?php
                    // Obtener documentos finales pendientes de revisión
                    try {
                        $conn = new mysqli($db_host, $usuario, $clave, $db);
                        if ($conn->connect_error) {
                            throw new Exception("Error de conexión: " . $conn->connect_error);
                        }
                        
                        $conn->set_charset("utf8");
                        
                        $sql = "SELECT 
                                    fd.id,
                                    fd.proposal_id,
                                    fd.status,
                                    fd.submitted_at,
                                    fd.project_status,
                                    tp.title as proposal_title,
                                    u.nombre as student_name,
                                    u.id as student_id,
                                    f.file_name,
                                    f.file_size
                                FROM tfg_final_documents fd
                                INNER JOIN tfg_proposals tp ON fd.proposal_id = tp.id
                                INNER JOIN sis_user u ON fd.submitted_by = u.id
                                INNER JOIN tfg_files f ON fd.file_id = f.id
                                WHERE fd.status = 'Pendiente de Revision'
                                ORDER BY fd.submitted_at DESC";
                        
                        $result = $conn->query($sql);
                        
                        if ($result && $result->num_rows > 0):
                    ?>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Estudiante</th>
                                    <th>Título de la Propuesta</th>
                                    <th>Fecha de Subida</th>
                                    <th>Estado del Proyecto</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($row['student_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($row['student_id']) ?></small>
                                    </td>
                                    <td>
                                        <div><?= htmlspecialchars($row['proposal_title']) ?></div>
                                        <small class="text-muted">
                                            <i class="bi bi-file-pdf"></i> <?= htmlspecialchars($row['file_name']) ?>
                                            (<?= number_format($row['file_size'] / (1024 * 1024), 2) ?> MB)
                                        </small>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($row['submitted_at'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $row['project_status'] === 'Vigente' ? 'success' : 'warning' ?>">
                                            <?= htmlspecialchars($row['project_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-pendiente">
                                            <?= htmlspecialchars($row['status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php $download_url = $base_url . 'mod/admin/users/tfg_final_download.php?id=' . $row['id']; ?>
                                        <a href="<?= htmlspecialchars($download_url) ?>" 
                                           class="btn btn-sm btn-primary btn-download" 
                                           target="_blank"
                                           title="Descargar y revisar documento PDF">
                                            <i class="bi bi-download me-1"></i> Descargar PDF
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php 
                        else: 
                    ?>
                    
                    <div class="empty-state">
                        <i class="bi bi-check2-circle"></i>
                        <h3>¡Todo al día!</h3>
                        <p class="mb-0">No hay documentos finales pendientes de revisión en este momento.</p>
                    </div>
                    
                    <?php 
                        endif;
                        $conn->close();
                    } catch (Exception $e) {
                        echo '<div class="alert alert-danger m-4">';
                        echo '<i class="bi bi-exclamation-triangle"></i> ';
                        echo '<strong>Error:</strong> ' . htmlspecialchars($e->getMessage());
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <a href="panel_ctfg.php" class="btn btn-lg btn-secondary px-5">
                    <i class="bi bi-arrow-left-circle me-2"></i> Volver al Panel Principal
                </a>
            </div>
        </div>
    </main>
                </a>
            </div>
            
        </div>
    </main>

    <!-- =============================== FOOTER =============================== -->
    <footer class="footer-una mt-auto">
        <div class="container">
            <p class="mb-1">&copy; <?= date('Y') ?> Universidad Nacional de Costa Rica</p>
            <small>Escuela de Informática - Proyecto SGPFL v3.0</small>
        </div>
    </footer>

</body>
</html>
