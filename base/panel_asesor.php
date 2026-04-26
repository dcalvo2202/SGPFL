<?php
// VERIFICAR AUTENTICACIÓN USANDO EL SISTEMA ESTÁNDAR
include("mod/login/check.php");
include('lang/lang.es');
require_once __DIR__ . '/mod/admin/users/CommitteeMinuteProjectRepository.php';

// Obtener variables de sesión
$current_user_id = $mySessionController->getVar("usuario");
$current_user_name = $mySessionController->getVar("nombre");
$current_user_rol = $mySessionController->getVar("rol");

// Obtener base_url de la sesión (configurado durante el login)
$cds_domain = $mySessionController->getVar("cds_domain");
$cds_locate = $mySessionController->getVar("cds_locate");
$base_url = $cds_domain . $cds_locate;

// Permitir rol asesor (5) o cualquier usuario con solicitud de comité aprobada.
$has_committee_approval = false;
if ($current_user_rol != 5) {
    include_once(__DIR__ . "/inc/db/bdcommon.inc");
    $conn = new mysqli($db_host, $usuario, $clave, $db);
    if (!$conn->connect_error) {
        $conn->set_charset('utf8');
        $stmt = $conn->prepare("SELECT id FROM external_advisor_profile_requests WHERE applicant_id = ? AND status = 'Aprobado' LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $current_user_id);
            $stmt->execute();
            $has_committee_approval = $stmt->get_result()->num_rows > 0;
            $stmt->close();
        }
        $conn->close();
    }
}

if ($current_user_rol != 5 && !$has_committee_approval) {
    header('Location: dashboard.php');
    exit;
}

// INCLUIR ARCHIVOS NECESARIOS
include_once(__DIR__ . "/inc/db/bdcommon.inc");
include_once(__DIR__ . "/inc/db/db.php");

$committee_projects = [];
$has_committee_projects = false;

try {
    $committee_conn = new mysqli($db_host, $usuario, $clave, $db);
    if (!$committee_conn->connect_error) {
        $committee_conn->set_charset('utf8');

        $committee_project_repository = new CommitteeMinuteProjectRepository($committee_conn);
        $committee_projects = $committee_project_repository->findProjectsByCommitteeMember((string)$current_user_id);
        $has_committee_projects = !empty($committee_projects);

        $committee_conn->close();
    }
} catch (Throwable $e) {
    $committee_projects = [];
    $has_committee_projects = false;
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
                    <div class="col-md-6 col-lg-4 d-flex">
                        <div class="quick-action-card w-100 d-flex flex-column justify-content-center" onclick="location.href='<?= $base_url ?>historial_documentos.php'" style="border-left: 4px solid #6c757d;">
                            <div class="card-icon">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <h5>Historial de documentos</h5>
                            <p>Ver documentos subidos al sistema</p>
                        </div>
                    </div>

                    <?php if ($has_committee_projects): ?>
                        <div class="col-md-6 col-lg-4 d-flex">
                            <div class="quick-action-card w-100 d-flex flex-column justify-content-center" onclick="location.href='<?= htmlspecialchars($base_url) ?>panel_committee_minutes.php'" style="border-left: 4px solid #c8151a;">
                                <div class="card-icon" style="color: #c8151a;">
                                    <i class="bi bi-file-earmark-text-fill"></i>
                                </div>
                                <h5>Registrar Minutas</h5>
                                <p>Registrar acuerdos y minutas por sesión del Comité Asesor.</p>
                                <span class="badge bg-danger mt-2 align-self-center">
                                    <?= count($committee_projects) ?> proyecto(s)
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>
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