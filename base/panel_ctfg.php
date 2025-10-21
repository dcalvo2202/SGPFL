<?php
// ================== VERIFICAR AUTENTICACIÓN ==================
include("mod/login/check.php");
include('includes.php');
include('lang/lang.es');

// ================== VARIABLES DE SESIÓN ==================
$current_user_id   = $mySessionController->getVar("usuario");
$current_user_name = $mySessionController->getVar("nombre");
$current_user_rol  = $mySessionController->getVar("rol");

// ================== URL BASE ==================
$cds_domain = $mySessionController->getVar("cds_domain");
$cds_locate = $mySessionController->getVar("cds_locate");
$base_url   = $cds_domain . $cds_locate;

// ================== CONTROL DE ACCESO ==================
// Rol 3 = Comisión TFG (ajustar según tu base de datos)
if ($current_user_rol != 3) {
    header('Location: dashboard.php');
    exit;
}

require_once 'PanelCTFGLogic.php';
$panel = new PanelCTFG();

$revisiones_ctfg = $panel->getRevisionesPendientes();
$asignaciones_pendientes = $panel->getAsignacionesPendientes();
$reuniones_ctfg = $panel->getProximaReunion();
$avisos_generales = $panel->getAvisos();
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
            <div class="dashboard-header text-center mb-5">
                <h1 style="font-size: 2.5rem; font-weight: 700;">Panel de la Comisión de Trabajos Finales de Graduación</h1>
                <p class="lead">Bienvenido, <?= htmlspecialchars($current_user_name) ?>. Gestione las propuestas y documentos finales de TFG.</p>
            </div>
            <!-- Sección de Acciones Rápidas -->
            <div class="quick-actions-section">
                <h2 class="section-title">
                    <i class="bi bi-lightning-fill text-rojo-una"></i>
                    Acciones Rápidas
                </h2>
                <div class="row justify-content-center">
                    <!-- Revisar Documentos Finales -->
                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card" onclick="location.href='<?= htmlspecialchars($base_url) ?>panel_ctfg_review_final_documents.php'">
                            <div class="card-icon">
                                <i class="bi bi-file-earmark-check-fill"></i>
                            </div>
                            <h5>Revisar Documentos Finales</h5>
                            <p>Ver y gestionar documentos finales de TFG pendientes de revisión.</p>
                        </div>
                    </div>
                    <!-- Aprobar Proyectos -->
                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card" onclick="location.href='<?= htmlspecialchars($base_url) ?>proyecto_aprobado.php'">
                            <div class="card-icon">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                            <h5>Aprobar Proyectos</h5>
                            <p>Gestionar y aprobar proyectos de TFG.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card" onclick="location.href='<?= htmlspecialchars($base_url) ?>index.php'">
                            <div class="card-icon">
                                <i class="bi bi-house-fill"></i>
                            </div>
                            <h5>Inicio</h5>
                            <p>Volver al panel principal del sistema.</p>
                        </div>
                    </div>
                    <!-- Nuevo panel: Proyectos Registrados -->
                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card" onclick="location.href='<?= htmlspecialchars($base_url) ?>ProyectosRegistrados.php'">
                            <div class="card-icon">
                                <i class="bi bi-list-task"></i>
                            </div>
                            <h5>Proyectos Registrados</h5>
                            <p>Buscar y consultar proyectos aprobados, sin aprobar o en corrección.</p>
                        </div>
                    </div>
                </div>
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
     <!-- =============================== SCRIPTS =============================== -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>
