<?php
include("mod/login/check.php");
include('lang/lang.es');

$current_user_id = $mySessionController->getVar("usuario");
$current_user_name = $mySessionController->getVar("nombre");
$current_user_rol = (int)$mySessionController->getVar("rol");

$cds_domain = $mySessionController->getVar("cds_domain");
$cds_locate = $mySessionController->getVar("cds_locate");
$base_url = $cds_domain . $cds_locate;

if ($current_user_rol !== 1) {
    header('Location: dashboard.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="es">
<?php include('head.php'); ?>
<body class="fondo-una d-flex flex-column min-vh-100">

    <?php include 'header.php'; ?>

    <main class="flex-fill">
        <div class="container my-4">
            <div class="dashboard-header text-center mb-4">
                <h1>Panel de Administración</h1>
                <p class="lead">Bienvenido/a, <?= htmlspecialchars($current_user_name) ?></p>
            </div>

            <div class="quick-actions-section mb-4">
                <h2 class="section-title">
                    <i class="bi bi-lightning-fill text-rojo-una"></i>
                    Administrar
                </h2>

                <div class="row justify-content-center">
                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card" onclick="location.href='<?= htmlspecialchars($base_url) ?>admin_parametros.php'" style="border-left: 4px solid #dc3545;">
                            <div class="card-icon"><i class="bi bi-sliders"></i></div>
                            <h5>Parámetros</h5>
                            <p>Configurar parámetros generales del sistema</p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card" onclick="location.href='<?= htmlspecialchars($base_url) ?>admin_modulos.php'" style="border-left: 4px solid #0d6efd;">
                            <div class="card-icon"><i class="bi bi-puzzle-fill"></i></div>
                            <h5>Módulos</h5>
                            <p>Gestionar módulos y sus configuraciones</p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card" onclick="location.href='<?= htmlspecialchars($base_url) ?>admin_roles.php'" style="border-left: 4px solid #ffc107;">
                            <div class="card-icon"><i class="bi bi-diagram-3-fill"></i></div>
                            <h5>Roles</h5>
                            <p>Administrar roles y permisos del sistema</p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card" onclick="location.href='<?= htmlspecialchars($base_url) ?>admin_usuarios.php'" style="border-left: 4px solid #198754;">
                            <div class="card-icon"><i class="bi bi-people-fill"></i></div>
                            <h5>Usuarios</h5>
                            <p>Gestionar cuentas y datos de usuarios</p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card" onclick="location.href='<?= htmlspecialchars($base_url) ?>admin_auditoria.php'" style="border-left: 4px solid #6f42c1;">
                            <div class="card-icon"><i class="bi bi-shield-lock-fill"></i></div>
                            <h5>Auditoría</h5>
                            <p>Consultar accesos y eventos de seguridad</p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card" onclick="location.href='<?= htmlspecialchars($base_url) ?>Panel_RegistroModificaciones.php'" style="border-left: 4px solid #6610f2;">
                            <div class="card-icon"><i class="bi bi-calendar2-week-fill"></i></div>
                            <h5>Registro de Modificaciones</h5>
                            <p>Consultar cambios en fechas de proyectos y prórrogas</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="quick-actions-section mb-4">
                <h2 class="section-title">
                    <i class="bi bi-question-circle-fill text-rojo-una"></i>
                    Ayuda y Referencias
                </h2>

                <div class="row justify-content-center">
                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card" onclick="location.href='<?= htmlspecialchars($base_url) ?>admin_simbologia.php'" style="border-left: 4px solid #6c757d;">
                            <div class="card-icon"><i class="bi bi-info-circle-fill"></i></div>
                            <h5>Simbología</h5>
                            <p>Referencia visual de iconos y acciones</p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card" onclick="window.open('https://escinf.una.ac.cr/index.php/contactenos', '_blank')" style="border-left: 4px solid #20c997;">
                            <div class="card-icon"><i class="bi bi-question-circle-fill"></i></div>
                            <h5>Ayuda</h5>
                            <p>Abrir soporte técnico institucional</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer-una mt-auto">
        <div class="container">
            <p class="mb-1">&copy; <?= date('Y') ?> Universidad Nacional de Costa Rica</p>
            <small>Escuela de Informática - Proyecto SGPFL v3.0</small>
        </div>
    </footer>
</body>
</html>
