<?php
include("mod/login/check.php");
include('lang/lang.es');
// Variables de sesión
$current_user_name = $mySessionController->getVar("nombre");
$current_user_rol  = $mySessionController->getVar("rol");
$cds_domain = $mySessionController->getVar("cds_domain");
$cds_locate = $mySessionController->getVar("cds_locate");
$base_url   = $cds_domain . $cds_locate;
// Verificación de acceso (subdirección o gestor, según BD)
if (!in_array($current_user_rol, [2, 3])) {
    header('Location: dashboard.php');
    exit;
}
$footer_title = "Sistema Gestor de Proyectos Finales de Licenciatura\nEscuela de Informática\nUniversidad Nacional de Costa Rica";
?>

<!DOCTYPE html>
<html lang="es">
<?php include('head.php'); ?>
<body class="fondo-una d-flex flex-column min-vh-100">
    <!-- HEADER -->
    <?php include('header.php'); ?>

    <!-- CONTENIDO -->
    <main class="flex-fill">
        <div class="container my-5">
            <div class="dashboard-header text-center mb-5">
                <h1 style="font-size: 2.3rem; font-weight: 700;">Listado de Actas Generadas</h1>
                <p class="lead">Gestione las actas de exámenes públicos de graduación.</p>
            </div>
            <div class="card shadow-sm border-0">
                <div class="card-body text-dark">
                    <?php
                        $dir = "actas/";
                        if (!is_dir($dir)) {
                            mkdir($dir, 0777, true);
                        }
                        $archivos = array_diff(scandir($dir), ['.', '..']);
                        if (empty($archivos)) {
                            echo "<div class='alert alert-info text-center'>
                                    <i class='bi bi-info-circle-fill'></i> No hay actas generadas.
                                  </div>";
                        } else {
                            echo "<div class='table-responsive'>";
                            echo "<table class='table table-hover align-middle text-center'>";
                            echo "<thead class='table-danger'>
                                    <tr>
                                        <th>Archivo</th>
                                        <th>Fecha de creación</th>
                                        <th>Acciones</th>
                                    </tr>
                                  </thead>
                                  <tbody>";
                            foreach ($archivos as $archivo) {
                                $ruta = $dir . $archivo;
                                $fecha = date("d/m/Y H:i:s", filemtime($ruta));
                                echo "<tr>
                                        <td><i class='bi bi-file-earmark-pdf-fill text-danger me-1'></i> $archivo</td>
                                        <td>$fecha</td>
                                        <td>
                                            <a href='$ruta' target='_blank' class='btn btn-success btn-sm'>
                                                <i class='bi bi-eye-fill'></i> Ver
                                            </a>
                                            <a href='$ruta' download class='btn btn-primary btn-sm'>
                                                <i class='bi bi-download'></i> Descargar
                                            </a>
                                        </td>
                                      </tr>";
                            }
                            echo "</tbody></table></div>";
                        }
                    ?>
                </div>
            </div>
            <div class="text-center mt-4">
                <a href="<?= $base_url ?>generar_acta.php" class="btn btn-danger btn-lg">
                    <i class="bi bi-plus-circle-fill"></i> Generar nueva acta
                </a>
            </div>
            <div class="text-center mt-4">
                <a href="panel_ctfg.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left-circle"></i> Volver al Panel CTFG
                </a>
            </div>
        </div>
    </main>

    <!-- FOOTER -->
    <footer class="footer-una mt-auto">
        <div class="container">
            <p class="mb-1">&copy; <?= date('Y') ?> Universidad Nacional de Costa Rica</p>
            <small>Escuela de Informática - Proyecto SGPFL v3.0</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
