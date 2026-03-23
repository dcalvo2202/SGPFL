<?php
include("mod/login/check.php");
include('lang/lang.es');

$current_user_name = $mySessionController->getVar("nombre");
$current_user_rol = (int)$mySessionController->getVar("rol");
$base_url = $mySessionController->getVar("cds_domain") . $mySessionController->getVar("cds_locate");

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
                <h1>Ayuda y Soporte</h1>
                <p class="lead">Administración - <?= htmlspecialchars($current_user_name) ?></p>
            </div>

            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <p class="mb-4">Para asistencia técnica institucional, usa el portal oficial de soporte.</p>
                    <a href="https://escinf.una.ac.cr/index.php/contactenos" target="_blank" class="btn btn-info btn-lg">
                        <i class="fa fa-external-link"></i> Abrir Soporte UNA
                    </a>
                </div>
            </div>

            <div class="mt-4 text-center">
                <a href="<?= htmlspecialchars($base_url) ?>dashboard.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left-circle"></i> Volver al Panel Principal
                </a>
            </div>
        </div>
    </main>

    <footer class="footer-una mt-auto"><div class="container"><p class="mb-1">&copy; <?= date('Y') ?> Universidad Nacional de Costa Rica</p><small>Escuela de Informática - Proyecto SGPFL v3.0</small></div></footer>
</body>
</html>
