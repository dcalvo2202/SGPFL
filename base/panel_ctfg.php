<?php
// ================== VERIFICAR AUTENTICACIÓN ==================
include("mod/login/check.php");
include('lang/lang.es');
require_once __DIR__ . '/mod/admin/users/CommitteeMinuteProjectRepository.php';
// ================== VARIABLES DE SESIÓN ==================
$current_user_id   = $mySessionController->getVar("usuario");
$current_user_name = $mySessionController->getVar("nombre");
$current_user_rol  = $mySessionController->getVar("rol");

// ================== URL BASE ==================
$cds_domain = $mySessionController->getVar("cds_domain");
$cds_locate = $mySessionController->getVar("cds_locate");
$base_url   = $cds_domain . $cds_locate;

include_once(__DIR__ . "/inc/db/bdcommon.inc");

// Este arreglo se llena solo cuando el usuario CTFG también está ligado
// como asesor interno (tutor/asesor de comité) a uno o más estudiantes.
$advisor_linked_students = [];
$committee_projects = [];
$has_committee_projects = false;
try {
    $conn = new mysqli($db_host, $usuario, $clave, $db);
    $conn->set_charset("utf8");

    // Consulta de vínculos activos por cédula del asesor interno.
    // Si hay filas, el panel mostrará la sección opcional "Acciones Asesor".
    $sql = "SELECT DISTINCT eals.student_id, su.nombre as student_name
            FROM external_advisor_linked_students eals
            INNER JOIN sis_user su ON su.id = eals.student_id
            WHERE eals.internal_advisor_id = ?
            ORDER BY su.nombre ASC";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $current_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $advisor_linked_students[] = $row;
        }
        $stmt->close();
    }
    
    // Proyectos donde este usuario CTFG también participa como comité asesor
    $committee_project_repository = new CommitteeMinuteProjectRepository($conn);
    $committee_projects = $committee_project_repository->findProjectsByCommitteeMember((string)$current_user_id);
    $has_committee_projects = !empty($committee_projects);

    $conn->close();
} catch (Exception $e) {
    // No bloquea el panel CTFG: solo registra el error y continúa.
    error_log("Error obteniendo estudiantes vinculados del asesor: " . $e->getMessage());
}

// ================== CONTROL DE ACCESO ==================
// Rol 3 = Comisión TFG (ajustar según tu base de datos)
if ($current_user_rol != 3) {
    header('Location: dashboard.php');
    exit;
}

/*require_once 'PanelCTFGLogic.php';
$panel = new PanelCTFG();

$revisiones_ctfg = $panel->getRevisionesPendientes();
$asignaciones_pendientes = $panel->getAsignacionesPendientes();
$reuniones_ctfg = $panel->getProximaReunion();
$avisos_generales = $panel->getAvisos();
*/
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
                <h1 class="page-title">Panel de la Comisión de Trabajos Finales de Graduación</h1>
                <p class="lead">Bienvenido, <?= htmlspecialchars($current_user_name) ?>. Gestione las propuestas y documentos finales de TFG.</p>
            </div>
            <!-- Sección de Gestión de Evaluación y Seguimiento TFG -->
            <div class="quick-actions-section">
                <h2 class="section-title">
                    <i class="bi bi-lightning-fill text-rojo-una"></i>
                    Gestión de Evaluación y Seguimiento TFG
                </h2>
                <div class="row justify-content-center">
                    <!-- Revisar Documentos Finales -->
                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card border-accent-blue" onclick="location.href='<?= htmlspecialchars($base_url) ?>panel_ctfg_review_final_documents.php'">
                            <div class="card-icon">
                                <i class="bi bi-file-earmark-check-fill"></i>
                            </div>
                            <h5>Revisar Documentos Finales</h5>
                            <p>Ver y gestionar documentos finales de TFG pendientes de revisión.</p>
                        </div>
                    </div>
                    <!-- Aprobar Proyectos -->
                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card border-accent-green" onclick="location.href='<?= htmlspecialchars($base_url) ?>proyecto_aprobado.php'">
                            <div class="card-icon">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                            <h5>Aprobar Proyectos</h5>
                            <p>Gestionar y aprobar proyectos de TFG.</p>
                        </div>
                    </div>
                    <!-- Proyectos Registrados -->
                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card border-accent-yellow" onclick="location.href='<?= htmlspecialchars($base_url) ?>ProyectosRegistrados.php'">
                            <div class="card-icon">
                                <i class="bi bi-list-task"></i>
                            </div>
                            <h5>Proyectos Registrados</h5>
                            <p>Buscar y consultar proyectos aprobados, sin aprobar o en corrección.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card border-accent-cyan" onclick="location.href='<?= htmlspecialchars($base_url) ?>panel_revisar_asesor_externo.php'">
                            <div class="card-icon icon-cyan">
                                <i class="bi bi-person-badge-fill"></i>
                            </div>
                            <h5>Solicitudes Comité Asesor</h5>
                            <p>Revisar y aprobar solicitudes de tutor, asesor interno y asesor externo.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card border-accent-purple" onclick="location.href='<?= htmlspecialchars($base_url) ?>panel_comites_asesores.php'">
                            <div class="card-icon icon-purple">
                                <i class="bi bi-diagram-3-fill"></i>
                            </div>
                            <h5>Gestión de Comités</h5>
                            <p>Aprobar o rechazar comités propuestos desde solicitudes aprobadas y listar comités actuales.</p>
                        </div>
                    </div>
                    <!-- HU-027: Archivo Histórico -->
                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card border-accent-gray" onclick="location.href='<?= htmlspecialchars($base_url) ?>panel_archivo_historico.php'">
                            <div class="card-icon icon-muted">
                                <i class="bi bi-archive-fill"></i>
                            </div>
                            <h5>Archivo Histórico</h5>
                            <p>Consultar proyectos concluidos o cancelados (Art. 68 RGPEA).</p>
                        </div>
                    </div>
                    <!-- Revisión de Prórrogas -->
                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card border-accent-cyan" onclick="location.href='<?= htmlspecialchars($base_url) ?>panel_aprobarProrroga.php'">
                            <div class="card-icon icon-cyan">
                                <i class="bi bi-file-earmark-check-fill"></i>
                            </div>
                            <h5>Revisión de Prórrogas</h5>
                            <p>Revisar y aprobar solicitudes de prórroga de estudiantes.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card" onclick="location.href='<?= $base_url ?>panel_plantillas.php'" class="border-3accent-purple">
                            <div class="card-icon">
                                <i class="bi bi-file-earmark-arrow-down-fill"></i>
                            </div>
                            <h5>Plantillas Oficiales</h5>
                            <p>Manejo de plantillas para el TFG</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card border-accent-red" onclick="location.href='<?= htmlspecialchars($base_url) ?>PanelRegistroAcuerdo.php'">
                            <div class="card-icon icon-red">
                                <i class="bi bi-journal-check"></i>
                            </div>
                            <h5>Consulta de Acuerdos</h5>
                            <p>Consultar minutas previas y el detalle de asistentes de sus proyectos de comité.</p>
                        </div>
                    </div>
                    <!--div class="col-md-6 col-lg-4">
                        <div class="quick-action-card" onclick="location.href='<?= htmlspecialchars($base_url) ?>listar_actas.php'">
                            <div class="card-icon">
                                <i class="bi bi-list-task"></i>
                            </div>
                            <h5>Listar Actas</h5>
                            <p>Buscar y consultar actas de reuniones de la comisión.</p>
                        </div>
                    </div-->
                </div>
            </div>

            <?php if (!empty($advisor_linked_students) || $has_committee_projects): ?>
            <!-- Sección opcional: solo se renderiza cuando el usuario tiene estudiantes vinculados como asesor -->
            <div class="quick-actions-section mt-5">
                <h2 class="section-title">
                    <i class="bi bi-person-badge-fill text-rojo-una"></i>
                    Acciones Asesor
                </h2>

                <div class="row justify-content-center">
                    <?php if (!empty($advisor_linked_students)): ?>
                        <div class="col-md-6 col-lg-4 d-flex">
                            <!-- Acceso directo al historial compartido del/los estudiante(s) vinculados -->
                            <div class="quick-action-card w-100 d-flex flex-column justify-content-center border-accent-gray" onclick="location.href='<?= htmlspecialchars($base_url) ?>historial_documentos.php'">
                                <div class="card-icon icon-muted">
                                    <i class="bi bi-clock-history"></i>
                                </div>
                                <h5>Historial de documentos</h5>
                                <p>Tienes <?= count($advisor_linked_students) ?> estudiante(s) vinculado(s). Ver documentos del grupo asignado.</p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($has_committee_projects): ?>
                        <div class="col-md-6 col-lg-4 d-flex">
                            <div class="quick-action-card w-100 d-flex flex-column justify-content-center border-accent-red" onclick="location.href='<?= htmlspecialchars($base_url) ?>panel_committee_minutes.php'">
                                <div class="card-icon icon-red">
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
            <?php endif; ?>
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
</body>
</html>
