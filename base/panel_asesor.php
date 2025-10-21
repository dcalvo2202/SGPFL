<?php
// ================== CONFIGURACIÓN DE SESIÓN Y DEPENDENCIAS ==================
include('lib/mysession/mySession.conf.php');
include('lib/mysession/mySession.class.php');

$mySessionController = mySession::getIstance($_MYSESSION_CONF);

// Variables de sesión
$current_user_id = $mySessionController->getVar('usuario');
$current_user_name = $mySessionController->getVar('nombre');
$current_user_rol = $mySessionController->getVar('rol');
$cds_domain = $mySessionController->getVar('cds_domain');
$cds_locate = $mySessionController->getVar('cds_locate');
$base_url = $cds_domain . $cds_locate;

// Verificar rol (por ejemplo, rol 2 = Asesor)
if (empty($current_user_id) || $current_user_rol != 2) {
    header('Location: index.php');
    exit();
}

// ================== CARGAR LÓGICA DEL PANEL ==================
require_once 'PanelAsesorLogic.php';
$panel = new PanelAsesor();

$revisiones_pendientes = $panel->getRevisionesPendientes();
$proximas_reuniones = $panel->getProximaReunion();
$observaciones = $panel->getObservaciones();

$footer_title = "Sistema Gestor de Proyectos Finales de Licenciatura\nEscuela de Informática\nUniversidad Nacional de Costa Rica";
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

            <!-- Encabezado principal -->
            <div class="dashboard-header text-center mb-5">
                <h1 style="font-size: 2.3rem; font-weight: 700;">
                    Sistema Integrado de Gestión de TFG y Proyectos Grupales
                </h1>
                <p class="lead">Bienvenido/a, <?= htmlspecialchars($current_user_name) ?> (Asesor)</p>
            </div>

            <!-- Panel principal -->
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-body">
                            <h4 class="card-title text-center mb-4 fw-bold text-primary">
                                <i class="bi bi-people-fill me-2"></i>Panel del Comité Asesor
                            </h4>

                            <!-- Revisiones pendientes -->
                            <div class="mb-4">
                                <h6><i class="bi bi-clipboard-check text-warning me-2"></i>Revisiones pendientes:</h6>
                                <?php if (!empty($revisiones_pendientes)): ?>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($revisiones_pendientes as $rev): ?>
                                            <li class="list-group-item"><?= htmlspecialchars($rev) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <div class="alert alert-info">No hay revisiones pendientes.</div>
                                <?php endif; ?>
                            </div>

                            <!-- Próximas reuniones -->
                            <div class="mb-4">
                                <h6><i class="bi bi-calendar-event text-info me-2"></i>Próximas reuniones:</h6>
                                <div class="alert alert-primary"><?= htmlspecialchars($proximas_reuniones) ?></div>
                            </div>

                            <!-- Observaciones generales -->
                            <div class="mb-4">
                                <h6><i class="bi bi-chat-dots text-success me-2"></i>Observaciones generales:</h6>
                                <div class="alert alert-success"><?= htmlspecialchars($observaciones) ?></div>
                            </div>

                            <!-- Acciones rápidas -->
                            <div class="text-center mt-4">
                                <a href="<?= $base_url ?>Panel_SubirTFG.php" class="btn btn-primary btn-lg me-2">
                                    <i class="bi bi-upload"></i> Subir revisión
                                </a>
                                <a href="<?= $base_url ?>historial_documentos.php" class="btn btn-secondary btn-lg">
                                    <i class="bi bi-clock-history"></i> Ver historial
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección de accesos directos -->
            <div class="quick-actions-section mt-5">
                <h2 class="section-title text-center mb-4">
                    <i class="bi bi-lightning-fill text-danger"></i> Acciones Rápidas
                </h2>

                <div class="row justify-content-center">
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card h-100 text-center p-4 border-0 shadow-sm"
                             onclick="location.href='<?= $base_url ?>mod/admin/users/tfg_pending_reviews.php'">
                            <div class="mb-3 text-warning">
                                <i class="bi bi-journal-text" style="font-size: 2.5rem;"></i>
                            </div>
                            <h5>Revisar propuestas</h5>
                            <p>Gestiona las propuestas TFG asignadas a tu comité</p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card h-100 text-center p-4 border-0 shadow-sm"
                             onclick="location.href='<?= $base_url ?>mod/admin/users/tfg_meetings.php'">
                            <div class="mb-3 text-info">
                                <i class="bi bi-calendar-week" style="font-size: 2.5rem;"></i>
                            </div>
                            <h5>Ver calendario</h5>
                            <p>Consulta las reuniones agendadas con los estudiantes</p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card h-100 text-center p-4 border-0 shadow-sm"
                             onclick="location.href='<?= $base_url ?>dashboard.php'">
                            <div class="mb-3 text-danger">
                                <i class="bi bi-house-fill" style="font-size: 2.5rem;"></i>
                            </div>
                            <h5>Volver al inicio</h5>
                            <p>Regresa al panel principal del sistema</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- =============================== FOOTER =============================== -->
    <footer class="footer-una mt-auto bg-dark text-white text-center py-3">
        <div class="container">
            <p class="mb-1">&copy; <?= date('Y') ?> Universidad Nacional de Costa Rica</p>
            <small><?= nl2br($footer_title) ?></small>
        </div>
    </footer>

    <!-- =============================== LIBRERÍAS =============================== -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</body>
</html>
