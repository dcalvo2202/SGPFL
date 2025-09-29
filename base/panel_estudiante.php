<<<<<<< HEAD
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <title><?= $page_title ?></title>
        <style>
            body {
                background-image: url('img/fondo_global.png'); /* Ruta a tu imagen */
                background-size: cover;                 /* La imagen cubre toda la pantalla */
                background-position: center;            /* Centrada */
                background-repeat: no-repeat;           /* No repetir */
                font-family: Arial, sans-serif;
                color: white; /* Opcional, para que el texto se vea mejor */
            }
        </style>
        <?php
            include('includes.php');
            include('lang/lang.es'); /*Añadir conexion a base de datos*/ 
            $footer_title = "Sistema Gestor de Proyectos Finales de Licenciatura\nEscuela de Informatica\nUniversidad Nacional de Costa Rica";
            $proxima_fecha   = "14/09/2025 – Entrega del capítulo 2";
            $tarea_pendiente = "Subir versión corregida del capítulo 2";
            $documento_enviado = "Avance 1 – Revisado con observaciones";
            $notificacion    = "[10/09/2025] Nueva fecha de entrega asignada";
        ?>
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-..." crossorigin="anonymous">

        <!-- Bootstrap JS Bundle (incluye Popper) -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-..." crossorigin="anonymous"></script>
    </head>
    <body class="d-flex flex-column min-vh-100">
        <div class="page-container flex-grow-1">
            <h3 class="mb-4 text-center">Bienvenido, Estudiante de Licenciatura</h3>
            <header class="d-flex justify-content-end gap-3 p-3">
                <a href="logout.php" class="btn btn-outline-dark">Logout</a>
                <a href="/" class="btn btn-outline-dark">Fechas importantes</a>
                <a href="/" class="btn btn-outline-dark">Enviar documentos</a>
                <a href="index.php" class="btn btn-outline-dark">Inicio</a>
            </header>
            <main class="flex-grow-1 container py-4">
                <div class="mb-3">
                    <label class="form-label fw-bold">Próximas fechas:</label>
                    <div class="alert alert-info"><?= $proxima_fecha ?></div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Tareas pendientes:</label>
                    <div class="alert alert-warning"><?= $tarea_pendiente ?></div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Documentos enviados:</label>
                    <div class="alert alert-success"><?= $documento_enviado ?></div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Notificaciones:</label>
                    <div class="alert alert-primary"><?= $notificacion ?></div>
                </div>
            </main>
        </div>
        <footer class="bg-dark text-white py-4 text-center">
            <p class="text-center small"><?= nl2br($footer_title) ?></p>
        </footer>
    </body>
=======
<?php
// SIMULAR SESIÓN SIN LOGIN PARA PRUEBAS
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}


// DATOS FIJOS PARA USUARIO DE PRUEBA (NO USAR SISTEMA DE SESIONES REAL)
$current_user_id = '112170040';
$current_user_name = 'AARON CASTILLO ALPIZAR';
$current_user_email = 'acastil@una.cr';
$current_user_role = 'Estudiante';

// INCLUIR SOLO LOS ARCHIVOS NECESARIOS PARA BD
include_once(__DIR__ . "/config.inc");
include_once(__DIR__ . "/inc/db/bdcommon.inc");

// SIMULAR OBJETO mySession PARA EVITAR ERRORES (PERO NO USARLO)
class FakeMySession {
    public function getVar($key) {
        global $current_user_id, $current_user_name, $current_user_email;
        switch($key) {
            case 'usuario': return $current_user_id;
            case 'nombre': return $current_user_name;
            case 'email': return $current_user_email;
            default: return null;
        }
    }
}
$mySessionController = new FakeMySession();

// CONFIGURACIÓN DE RUTAS
$base_url = isset($cds_locate) ? $cds_locate : '/SGPFL/Sistema-Gestor-de-Proyectos-Finales-de-Licenciatura/base/';
$relative_base = './';

// DEBUG: Confirmar usuario simulado
error_log("Panel estudiante - Usuario FIJO para pruebas: $current_user_id ($current_user_name)");

// --- Obtener estadísticas del usuario FIJO ---
$total_proposals = 0;
$approved_proposals = 0;

try {
    // USAR VARIABLES DE BD DEL ARCHIVO bdcommon.inc
    $conn = new mysqli($db_host, $usuario, $clave, $db);
    if ($conn->connect_error) {
        error_log("Error BD estadísticas: " . $conn->connect_error);
        // Usar datos simulados si hay error
        $total_proposals = 1;
        $approved_proposals = 0;
    } else {
        $conn->set_charset("utf8");
        
        $sql_stats = "SELECT status, COUNT(*) as count FROM tfg_proposals WHERE user_id = ? GROUP BY status";
        $stmt_stats = $conn->prepare($sql_stats);
        $stmt_stats->bind_param("s", $current_user_id);
        $stmt_stats->execute();
        $result_stats = $stmt_stats->get_result();

        while($row_stat = $result_stats->fetch_assoc()) {
            if ($row_stat['status'] === 'Aprobado') {
                $approved_proposals = $row_stat['count'];
            }
            $total_proposals += $row_stat['count'];
        }
        
        $stmt_stats->close();
        $conn->close();
    }
    
} catch (Exception $e) {
    error_log("Error obteniendo estadísticas: " . $e->getMessage());
    // Usar datos simulados si hay error
    $total_proposals = 1;
    $approved_proposals = 0;
}

// SIMULAR ProjectGroup::getProjectsByUser() PARA EVITAR ERROR
$user_projects = [];
$total_projects = 0;

try {
    // Si existe la clase ProjectGroup, usarla, sino simular
    if (class_exists('ProjectGroup')) {
        include_once("mod/admin/projects/ProjectGroup.php");
        $user_projects = ProjectGroup::getProjectsByUser($current_user_id) ?? [];
        $total_projects = count($user_projects);
    } else {
        // Simular datos si la clase no existe
        $total_projects = 1;
    }
} catch (Exception $e) {
    error_log("Error ProjectGroup: " . $e->getMessage());
    $total_projects = 1;
}

// Verificar si hay mensaje de éxito
$success_message = '';
if (isset($_GET['success']) && $_GET['success'] === 'tfg_created') {
    $success_message = '¡Propuesta TFG y proyecto grupal creados exitosamente!';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Estudiante - SGPFL</title>
    
    <meta name="description" content="Sistema de Gestión de Proyectos Finales de Licenciatura - UNA ESCINF">
    <meta name="author" content="UNA - Escuela de Informática">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link rel="stylesheet" href="<?= $relative_base ?>inc/css/estilo.css">
    <link rel="stylesheet" href="<?= $relative_base ?>inc/css/panel_estudiante.css">
    
    <link rel="icon" type="image/x-icon" href="<?= $relative_base ?>inc/img/favicon.ico">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark navbar-una">
        <div class="container">
            <div class="logo-una">UNA</div>
            <a class="navbar-brand" href="#">
                <i class="bi bi-mortarboard-fill"></i> SGPFL - ESCINF
            </a>
            <div class="collapse navbar-collapse" id="nav-main-menu">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#"><i class="bi bi-house-fill"></i> Dashboard</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($current_user_name) ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container dashboard-container">
        
        <?php if ($success_message): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: '<?= addslashes($success_message) ?>',
                    confirmButtonColor: '#034991',
                    timer: 5000,
                    timerProgressBar: true
                });
            });
        </script>
        <?php endif; ?>

        <div class="dashboard-header text-center">
            <h1>Sistema Integrado de Gestión de TFG y Proyectos Grupales</h1>
            <p class="lead text-center">       Plataforma unificada para la gestión de trabajos finales de graduación</p>
        </div>

        <div class="quick-actions-section">
            <h2 class="mb-4"><i class="bi bi-lightning-fill"></i> Acciones Rápidas</h2>
            <div class="quick-actions-grid justify-content-center">
                <div class="col-md-6 col-lg-4">
                    <div class="dashboard-card" onclick="location.href='<?= $base_url ?>mod/admin/users/tfg_upload.php'">
                        <div class="card-icon">
                            <i class="bi bi-file-earmark-plus-fill"></i>
                        </div>
                        <h5>Nueva Propuesta TFG</h5>
                        <p>Crear propuesta y formar grupo</p>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="section-title">Actividad Reciente</h2>
        <div class="activity-section">
            <?php
            // USAR USUARIO FIJO PARA PRUEBAS
            $user_id = $current_user_id; // '112170040'
            
            try {
                // CONEXIÓN BD USANDO VARIABLES DE bdcommon.inc
                $conn = new mysqli($db_host, $usuario, $clave, $db);
                
                if ($conn->connect_error) {
                    throw new Exception("Error de conexión BD: " . $conn->connect_error);
                }
                
                $conn->set_charset("utf8");
                
                // DEBUG: Confirmar usuario en consulta
                error_log("Consultando propuestas para usuario FIJO: $user_id");
                
                $sql = "SELECT 
                            tfg.id as tfg_id,
                            tfg.title,
                            tfg.disciplines,
                            tfg.project_description,
                            tfg.file_name,
                            tfg.status as tfg_status,
                            tfg.created_at,
                            pt.type_name,
                            rp.id as project_id,
                            GROUP_CONCAT(
                                CONCAT(su.nombre, ' (', pm.role, ')')
                                ORDER BY pm.role DESC SEPARATOR ', '
                            ) as members_list
                        FROM tfg_proposals tfg
                        LEFT JOIN registered_projects rp ON tfg.id = rp.tfg_proposal_id
                        LEFT JOIN project_types pt ON rp.project_type_id = pt.id
                        LEFT JOIN project_members pm ON rp.id = pm.project_id
                        LEFT JOIN sis_user su ON pm.user_id = su.id
                        WHERE tfg.user_id = ?
                        GROUP BY tfg.id, pt.type_name, rp.id
                        ORDER BY tfg.created_at DESC
                        LIMIT 5";
                
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Error preparando consulta: " . $conn->error);
                }
                
                $stmt->bind_param("s", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                // DEBUG: Confirmar resultados
                error_log("Propuestas encontradas: " . $result->num_rows);
                
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $formatted_date = date('d/m/Y', strtotime($row['created_at']));
                        
                        // Lógica de estado dinámico
                        $status_text = 'Desconocido';
                        $status_class = 'status-default';
                        $status_icon = 'bi-question-circle';

                        switch ($row['tfg_status']) {
                            case 'Pendiente de Revisión':
                                $status_text = 'Pendiente de Revisión';
                                $status_class = 'status-pending';
                                $status_icon = 'bi-clock-history';
                                break;
                            case 'Aprobado':
                                $status_text = 'Aprobado';
                                $status_class = 'status-approved';
                                $status_icon = 'bi-check-circle-fill';
                                break;
                            case 'Rechazado':
                                $status_text = 'Rechazado';
                                $status_class = 'status-rejected';
                                $status_icon = 'bi-x-circle-fill';
                                break;
                        }
                        ?>
                        
                        <div class="activity-card">
                            <div class="card-header">
                                <h3><?= htmlspecialchars($row['type_name'] ?? 'Propuesta TFG') ?></h3>
                                <div class="card-subtitle"><?= htmlspecialchars($row['disciplines']) ?></div>
                            </div>
                            
                            <h4 class="project-title"><?= htmlspecialchars($row['title']) ?></h4>
                            
                            <p class="project-description">
                                <?= htmlspecialchars(substr($row['project_description'], 0, 150)) ?>
                                <?= strlen($row['project_description']) > 150 ? '...' : '' ?>
                            </p>
                            
                            <?php if ($row['members_list']): ?>
                            <div class="members-info">
                                <strong><i class="bi bi-people-fill"></i> Miembros:</strong> <?= htmlspecialchars($row['members_list']) ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="status-date-row">
                                <div>
                                    <span class="status-badge <?= $status_class ?>">
                                        <i class="bi <?= $status_icon ?>"></i> <?= $status_text ?>
                                    </span>
                                    <br><span class="date-info">📅 <?= $formatted_date ?></span>
                                </div>
                                
                                <?php if ($row['tfg_status'] === 'Aprobado' && !empty($row['project_id'])): ?>
                                    <a href="mod/admin/projects/manage_project.php?id=<?= $row['project_id'] ?>" class="btn-action btn-view">
                                        <i class="bi bi-kanban"></i> Ver Proyecto
                                    </a>
                                <?php elseif (!empty($row['file_name'])): ?>
                                    <a href="mod/admin/users/tfg_download.php?id=<?= $row['tfg_id'] ?>" class="btn-action btn-download" target="_blank">
                                        <i class="bi bi-download"></i> Descargar
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php
                    }
                } else {
                    ?>
                    <div class="empty-state">
                        <h3>No tienes propuestas TFG</h3>
                        <p>Aún no has enviado ninguna propuesta de Trabajo Final de Graduación.</p>
                        <p><small><strong>Usuario de prueba:</strong> <?= htmlspecialchars($user_id) ?> (<?= htmlspecialchars($current_user_name) ?>)</small></p>
                        <a href="mod/admin/users/tfg_upload.php" class="btn-primary">
                            <i class="bi bi-plus-circle-fill"></i> Crear Primera Propuesta
                        </a>
                    </div>
                    <?php
                }
                
                $stmt->close();
                $conn->close();
                
            } catch (Exception $e) {
                error_log("Error en panel_estudiante: " . $e->getMessage());
                ?>
                <div class="empty-state">
                    <p style="color: #CD1719;">❌ Error: <?= htmlspecialchars($e->getMessage()) ?></p>
                    <p><small>Usuario: <?= htmlspecialchars($user_id) ?> (<?= htmlspecialchars($current_user_name) ?>)</small></p>
                    <p><small>BD: <?= $db_host ?>/<?= $db ?></small></p>
                    
                    <a href="mod/admin/users/tfg_upload.php" class="btn-primary">
                        ➕ Crear Propuesta (Ignorar Error)
                    </a>
                </div>
                <?php
            }
            ?>
        </div>
        </div>

    <footer class="footer-una">
        <div class="container">
            <p>
                Copyright © 2025. Todos los derechos reservados. 
                USTDS-Escuela de Informática-UNA<br>
                Contacto: escinf@una.ac.cr | Tel: +506 2562-4000 ext. 2200
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
>>>>>>> HU-002
</html>
