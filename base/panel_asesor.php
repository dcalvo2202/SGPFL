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

// Verificar que sea asesor (rol 5 según la base de datos)
if ($current_user_rol != 5) {
    header('Location: dashboard.php');
    exit;
}

// INCLUIR ARCHIVOS NECESARIOS
include_once(__DIR__ . "/inc/db/bdcommon.inc");
include_once(__DIR__ . "/inc/db/db.php");

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
                <h1>Sistema Integrado de Gestión de TFG y Proyectos Grupales</h1>
                <p class="lead">Bienvenido/a, <?= htmlspecialchars($current_user_name) ?></p>
            </div>

            <div class="quick-actions-section">
                <h2 class="section-title">
                    <i class="bi bi-lightning-fill text-rojo-una"></i>
                    Seguimiento de Estudiantes Asignados
                </h2>
                
                <!-- Debe buscar con el estudiante relacionado a este asesor -->
                <div class="row justify-content-center">
                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card" onclick="location.href='<?= $base_url ?>historial_documentos.php'" style="border-left: 4px solid #6c757d;">
                            <div class="card-icon">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <h5>Historial de documentos</h5>
                            <p>Ver documentos subidos al sistema</p>
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    
</body>
</html>