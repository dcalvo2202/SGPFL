<?php
// ================== VERIFICAR AUTENTICACIÓN ==================
include("mod/login/check.php");
include('includes.php');
include('lang/lang.es');

// ================== VARIABLES DE SESIÓN ==================
$current_user_id   = $mySessionController->getVar("usuario");
$current_user_name = $mySessionController->getVar("nombre");
$current_user_rol  = $mySessionController->getVar("rol");

$cds_domain = $mySessionController->getVar("cds_domain");
$cds_locate = $mySessionController->getVar("cds_locate");
$base_url   = $cds_domain . $cds_locate;

// ================== CONTROL DE ACCESO ==================
// Rol 3 = Gestor Académico (ajústalo según tu base de datos)
if ($current_user_rol != 3) {
    header('Location: dashboard.php');
    exit;
}

// ================== LÓGICA DEL PANEL ==================
require_once 'PanelGestorLogic.php';
$panel = new PanelGestor();

$revisiones_ctfg         = $panel->getRevisionesPendientes();
$asignaciones_pendientes = $panel->getAsignacionesPendientes();
$reuniones_ctfg          = $panel->getProximaReunion();
$avisos_generales        = $panel->getAvisos();
$calificaciones          = $panel->getCalificaciones();
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
                <h1 style="font-size: 2.5rem; font-weight: 700;">Panel del Gestor Académico</h1>
                <p class="lead">Bienvenido/a, <?= htmlspecialchars($current_user_name) ?>. Supervise revisiones, reuniones y calificaciones de TFG.</p>
            </div>

            <!-- =============================== AVISOS Y FECHAS =============================== -->
            <div class="row justify-content-center mb-4">
                <div class="col-md-5">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <h5 class="fw-bold text-primary"><i class="bi bi-megaphone-fill me-2"></i>Avisos Generales</h5>
                            <p class="mb-0"><?= htmlspecialchars($avisos_generales ?? "No hay avisos registrados.") ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <h5 class="fw-bold text-primary"><i class="bi bi-calendar-event-fill me-2"></i>Próxima Reunión</h5>
                            <p class="mb-0"><?= htmlspecialchars($reuniones_ctfg ?? "No hay reuniones próximas.") ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- =============================== ACCIONES RÁPIDAS =============================== -->
            <div class="quick-actions-section">
                <h2 class="section-title mb-4">
                    <i class="bi bi-lightning-fill text-rojo-una"></i>
                    Acciones Rápidas
                </h2>
                
                <div class="row justify-content-center">
                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card" onclick="location.href='<?= $base_url ?>gestor_revisiones.php'">
                            <div class="card-icon">
                                <i class="bi bi-journal-check"></i>
                            </div>
                            <h5>Revisiones Pendientes</h5>
                            <p>Gestione revisiones asignadas por la Comisión TFG.</p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card" onclick="location.href='<?= $base_url ?>gestor_asignaciones.php'">
                            <div class="card-icon">
                                <i class="bi bi-person-lines-fill"></i>
                            </div>
                            <h5>Asignaciones Pendientes</h5>
                            <p>Administre asignaciones de asesores y revisores.</p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card" onclick="location.href='<?= $base_url ?>gestor_calificaciones.php'">
                            <div class="card-icon">
                                <i class="bi bi-clipboard-data-fill"></i>
                            </div>
                            <h5>Gestión de Calificaciones</h5>
                            <p>Consulte o modifique notas de proyectos registrados.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- =============================== TABLAS DE DATOS =============================== -->
            <div class="card mt-5 shadow-sm border-0">
                <div class="card-body">
                    <h4 class="fw-bold text-primary mb-4"><i class="bi bi-table me-2"></i>Resumen de Calificaciones</h4>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Estudiante</th>
                                    <th>Proyecto</th>
                                    <th>Calificación</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($calificaciones)): ?>
                                    <?php foreach ($calificaciones as $cal): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($cal["estudiante"]) ?></td>
                                            <td><?= htmlspecialchars($cal["proyecto"]) ?></td>
                                            <td><strong><?= htmlspecialchars($cal["nota"]) ?></strong></td>
                                            <td>
                                                <a href="editar_calificacion.php?id=<?= urlencode($cal['id']) ?>" class="btn btn-sm btn-warning">
                                                    <i class="bi bi-pencil-square"></i> Editar
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center text-muted">No hay calificaciones registradas.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- =============================== GESTIÓN DE ACTAS =============================== -->
            <div class="quick-actions-section mt-5">
                <h2 class="section-title mb-4 text-center">
                    <i class="bi bi-file-earmark-text-fill text-rojo-una"></i>
                    Gestión de Actas de Examen Final
                </h2>
            
                <div class="row justify-content-center">
                    <!-- Generar nueva acta -->
                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card" onclick="location.href='<?= $base_url ?>generar_acta.php'">
                            <div class="card-icon">
                                <i class="bi bi-file-earmark-plus-fill"></i>
                            </div>
                            <h5>Generar Nueva Acta</h5>
                            <p>Crear acta de examen público en formato PDF.</p>
                        </div>
                    </div>
            
                    <!-- Ver listado de actas -->
                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card" onclick="location.href='<?= $base_url ?>listar_actas.php'">
                            <div class="card-icon">
                                <i class="bi bi-collection-fill"></i>
                            </div>
                            <h5>Listar Actas Existentes</h5>
                            <p>Ver o descargar actas generadas previamente.</p>
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
