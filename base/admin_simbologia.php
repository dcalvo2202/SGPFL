<?php
include("mod/login/check.php");
include('lang/lang.es');

$current_user_rol = (int)$mySessionController->getVar("rol");
$vocab = $mySessionController->getVar("vocab");
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
                <h1>Simbología del Sistema</h1>
                <p class="lead">Referencia rápida para administración</p>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <?php
                        ob_start();
                        include(__DIR__ . '/home_legacy.php');
                        $home_content = ob_get_clean();
                        $home_content = preg_replace('/<head>.*?<\/head>/is', '', $home_content);
                        $home_content = str_ireplace('<form>', '<form onsubmit="return false;" action="javascript:void(0);">', $home_content);
                        $home_content = preg_replace(
                            '/<input[^>]*type=["\']image["\'][^>]*src=["\']([^"\']+)["\'][^>]*>/i',
                            '<img src="$1" alt="Ejemplo de imagen" class="img-fluid border rounded p-2" style="max-width: 220px; background: #fff;">',
                            $home_content
                        );
                        echo $home_content;
                    ?>

                    <div class="mt-4 text-center">
                        <a href="<?= htmlspecialchars($base_url) ?>dashboard.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left-circle"></i> Volver al Panel Principal
                        </a>
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
