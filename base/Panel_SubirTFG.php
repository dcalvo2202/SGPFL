<?php
// VERIFICAR AUTENTICACIÓN USANDO EL SISTEMA ESTÁNDAR
include("mod/login/check.php");
include('lang/lang.es');

// Obtener variables de sesión
$current_user_id = $mySessionController->getVar("usuario");
$current_user_name = $mySessionController->getVar("nombre");
$current_user_rol = $mySessionController->getVar("rol");

// Obtener base_url de la sesión (configurado durante el login)
$cds_domain = $mySessionController->getVar("cds_domain");
$cds_locate = $mySessionController->getVar("cds_locate");
$base_url = $cds_domain . $cds_locate;

// Verificar que sea estudiante (rol 4 según la base de datos)
if ($current_user_rol != 4) {
    header('Location: dashboard.php');
    exit;
}

// INCLUIR ARCHIVOS NECESARIOS
include_once(__DIR__ . "/inc/db/bdcommon.inc");
include_once(__DIR__ . "/inc/db/db.php");

// Verificar si hay mensaje de éxito
$success_message = '';
if (isset($_GET['success']) && $_GET['success'] === 'tfg_created') {
    $success_message = '¡Propuesta TFG y proyecto grupal creados exitosamente!';
}

// --- Obtener estadísticas del usuario autenticado ---
$total_proposals = 0;
$approved_proposals = 0;

try {
    $conn = new mysqli($db_host, $usuario, $clave, $db);
    if ($conn->connect_error) {
        throw new Exception("Error BD estadísticas: " . $conn->connect_error);
    }
    
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
    
} catch (Exception $e) {
    error_log("Error obteniendo estadísticas: " . $e->getMessage());
    // Usar datos por defecto si hay error
    $total_proposals = 0;
    $approved_proposals = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<?php include('head.php'); ?>
<body class="fondo-una d-flex flex-column min-vh-100">

    <!-- =============================== HEADER =============================== -->
    <?php include 'header.php'; ?> 

    <!-- =============================== CONTENIDO PRINCIPAL =============================== -->
    <main class="flex-fill">
        <div class="container my-5">
            
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
            
            <div class="dashboard-header text-center mb-5">
                <h1 style="font-size: 2.5rem; font-weight: 700;">Sistema Integrado de Gestión de TFG y Proyectos Grupales</h1>
                <p class="lead">Bienvenido/a, <?= htmlspecialchars($current_user_name) ?></p>
            </div>

            <div class="quick-actions-section">
                <h2 class="section-title">
                    <i class="bi bi-lightning-fill text-rojo-una"></i>
                    Acciones Rápidas
                </h2>
                
                <div class="row justify-content-center">
                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card" onclick="location.href='<?= $base_url ?>mod/admin/users/tfg_upload.php'">
                            <div class="card-icon">
                                <i class="bi bi-file-earmark-plus-fill"></i>
                            </div>
                            <h5>Nueva Propuesta TFG</h5>
                            <p>Crear propuesta y formar grupo</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card" onclick="location.href='<?= $base_url ?>mod/admin/users/tfg_upload_final_document.php'">
                            <div class="card-icon">
                                <i class="bi bi-file-earmark-check-fill"></i>
                            </div>
                            <h5>Subir Documento Final</h5>
                            <p>Subir versión final del TFG para revisión</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card" onclick="location.href='<?= $base_url ?>historial_documentos.php'">
                            <div class="card-icon">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <h5>Historial de documentos</h5>
                            <p>Ver documentos subidos al sistema</p>
                        </div>
                    </div>
                </div>

            </div>

            <!-- COMENTADO: Actividad Reciente 
            <div class="recent-activity-section">
                <h2 class="section-title">
                    <i class="bi bi-clock-fill text-azul-una"></i>
                    Actividad Reciente
                </h2>
                
                <div class="activity-container">
                    <?php
                    // Usar el usuario REAL autenticado
                    try {
                        $conn = new mysqli($db_host, $usuario, $clave, $db);
                        if ($conn->connect_error) {
                            throw new Exception("Error de conexión: " . $conn->connect_error);
                        }
                        
                        $conn->set_charset("utf8");
                        
                        // Consulta para obtener propuestas del usuario autenticado
                        $sql = "SELECT 
                                    t.id,
                                    t.title,
                                    t.status,
                                    t.created_at,
                                    t.updated_at,
                                    t.proposal_file_path,
                                    rp.project_type_id,
                                    pt.type_name
                                FROM tfg_proposals t
                                LEFT JOIN registered_projects rp ON t.id = rp.tfg_proposal_id
                                LEFT JOIN project_types pt ON rp.project_type_id = pt.id
                                WHERE t.user_id = ?
                                ORDER BY t.updated_at DESC, t.created_at DESC
                                LIMIT 5";
                        
                        $stmt = $conn->prepare($sql);
                        if (!$stmt) {
                            throw new Exception("Error preparando consulta: " . $conn->error);
                        }
                        
                        $stmt->bind_param("s", $current_user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            echo '<div class="row">';
                            while ($row = $result->fetch_assoc()) {
                                // Determinar color del badge según el estado
                                $badge_class = 'badge-una-neutral';
                                $icon_class = 'bi-clock';
                                
                                switch (strtolower($row['status'])) {
                                    case 'aprobado':
                                        $badge_class = 'badge-una-success';
                                        $icon_class = 'bi-check-circle-fill';
                                        break;
                                    case 'rechazado':
                                        $badge_class = 'badge-una-danger';
                                        $icon_class = 'bi-x-circle-fill';
                                        break;
                                    case 'en_revision':
                                        $badge_class = 'badge-una-warning';
                                        $icon_class = 'bi-eye-fill';
                                        break;
                                    case 'pendiente':
                                        $badge_class = 'badge-una-info';
                                        $icon_class = 'bi-hourglass-split';
                                        break;
                                }
                                
                                echo '<div class="col-md-6 mb-3">
                                        <div class="activity-card">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="activity-title">' . htmlspecialchars($row['title']) . '</h6>
                                                <div class="badge-una ' . $badge_class . '">
                                                    <i class="' . $icon_class . '"></i>
                                                    ' . htmlspecialchars($row['status']) . '
                                                </div>
                                            </div>
                                            <div class="activity-meta">
                                                <small class="text-gris-una">
                                                    <i class="bi bi-calendar"></i>
                                                    Creado: ' . date('d/m/Y H:i', strtotime($row['created_at'])) . '
                                                </small>';
                                
                                if ($row['updated_at'] !== $row['created_at']) {
                                    echo '<br><small class="text-gris-una">
                                            <i class="bi bi-arrow-clockwise"></i>
                                            Actualizado: ' . date('d/m/Y H:i', strtotime($row['updated_at'])) . '
                                          </small>';
                                }
                                
                                echo '</div>';
                                
                                // Botón de descarga si tiene archivo
                                if (!empty($row['proposal_file_path'])) {
                                    echo '<div class="mt-2">
                                            <a href="' . $base_url . 'mod/admin/users/tfg_download.php?id=' . $row['id'] . '" 
                                               class="btn-una btn-una-sm btn-una-outline-primary" target="_blank">
                                                <i class="bi bi-download"></i>
                                                Descargar PDF
                                            </a>
                                          </div>';
                                }
                                
                                echo '</div></div>';
                            }
                            echo '</div>';
                        } else {
                            echo '<div class="alert-una alert-una-info">
                                    <i class="bi bi-info-circle"></i>
                                    No hay actividad reciente. ¡Crea tu primera propuesta TFG!
                                  </div>';
                        }
                        
                        $stmt->close();
                        $conn->close();
                        
                    } catch (Exception $e) {
                        error_log("Error en panel estudiante: " . $e->getMessage());
                        echo '<div class="alert-una alert-una-warning">
                                <i class="bi bi-exclamation-triangle"></i>
                                Error al cargar la actividad reciente. Por favor, intenta recargar la página.
                              </div>';
                    }
                    ?>
                </div>
            </div>
            -->
            
        </div>
    </main>

    <!-- =============================== FOOTER =============================== -->
    <footer class="footer-una mt-auto">
        <div class="container">
            <p class="mb-1">&copy; <?= date('Y') ?> Universidad Nacional de Costa Rica</p>
            <small>Escuela de Informática - Proyecto SGPFL v3.0</small>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    
</body>
</html>