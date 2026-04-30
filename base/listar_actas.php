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
<style>
    /* Estilos responsive para listar_actas.php */
    .dashboard-header h1 {
        font-size: 2.3rem;
        font-weight: 700;
    }

    .dashboard-header p {
        font-size: 1.1rem;
    }

    .actas-table-wrapper {
        width: 100%;
    }

    .actas-table-wrapper .table td,
    .actas-table-wrapper .table th {
        word-break: break-word;
        text-align: left;
    }

    @media (max-width: 754px) {
        .dashboard-header h1 {
            font-size: 1.8rem;
        }

        .dashboard-header p {
            font-size: 1rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            font-size: 0.95rem;
        }

        .btn-sm {
            padding: 0.35rem 0.6rem;
            font-size: 0.85rem;
        }
    }

    @media (max-width: 576px) {
        .dashboard-header {
            margin-bottom: 1.5rem !important;
        }

        .dashboard-header h1 {
            font-size: 1.4rem;
            line-height: 1.3;
        }

        .dashboard-header p {
            font-size: 1rem;
            margin-bottom: 1rem;
        }

        .card {
            margin-bottom: 1rem !important;
        }

        .card-body {
            padding: 1rem !important;
        }

        /* Convertir tabla a cards en móvil */
        .actas-table-wrapper .table {
            font-size: 1rem;
        }

        .actas-table-wrapper .table thead {
            display: none;
        }

        .actas-table-wrapper .table tbody tr {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            padding: 0.75rem;
            margin-bottom: 0.75rem;
            background-color: #fff;
            align-items: flex-start;
        }

        .actas-table-wrapper .table td {
            display: flex;
            flex-direction: column;
            padding: 0.25rem 0 !important;
            border: none !important;
            text-align: left;
            align-items: flex-start;
            width: 100%;
        }

        .actas-table-wrapper .table td::before {
            font-weight: 600;
            color: #034991;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
            text-align: left;
            display: block;
            width: 100%;
        }

        /* Agregar labels a cada celda */
        .actas-table-wrapper .table td:nth-child(1)::before { content: "Archivo"; }
        .actas-table-wrapper .table td:nth-child(2)::before { content: "Fecha de creación"; }
        .actas-table-wrapper .table td:nth-child(3)::before { content: "Acciones"; }

        /* Contenedor de acciones (botones) */
        .actas-table-wrapper .table td:nth-child(3) {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .btn-sm {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            font-size: 0.85rem;
            padding: 0.4rem 0.6rem;
            width: 100%;
            margin: 0 !important;
            border-radius: 0.25rem;
        }

        .btn {
            white-space: nowrap;
        }
    }

    @media (max-width: 420px) {
        .dashboard-header h1 {
            font-size: 1.2rem;
        }

        .dashboard-header p {
            font-size: 1rem;
        }

        .actas-table-wrapper .table {
            font-size: 1rem;
        }

        .actas-table-wrapper .table tbody tr {
            padding: 0.5rem;
            margin-bottom: 0.5rem;
            gap: 0.4rem;
        }

        .actas-table-wrapper .table td {
            padding: 0.3rem 0 !important;
        }

        .actas-table-wrapper .table td::before {
            font-size: 1rem;
            margin-bottom: 0.15rem;
        }

        .btn-sm {
            padding: 0.35rem 0.5rem;
            font-size: 0.8rem;
            gap: 0.25rem;
        }

        .bi {
            font-size: 1.25rem;
        }

        .card-body {
            padding: 0.75rem !important;
        }
    }

    @media (max-width: 360px) {
        .dashboard-header h1 {
            font-size: 1rem;
        }

        .dashboard-header p {
            font-size: 1rem;
        }

        .actas-table-wrapper .table {
            font-size: 1rem;
        }

        .actas-table-wrapper .table tbody tr {
            padding: 0.4rem;
            margin-bottom: 0.4rem;
            gap: 0.3rem;
        }

        .actas-table-wrapper .table td {
            padding: 0.2rem 0 !important;
        }

        .actas-table-wrapper .table td::before {
            font-size: 0.95rem;
            margin-bottom: 0.1rem;
        }

        .btn-sm {
            padding: 0.3rem 0.4rem;
            font-size: 1rem;
            gap: 0.2rem;
        }

        .bi {
            font-size: 1.25rem;
        }

        .card-body {
            padding: 0.5rem !important;
        }

        .card {
            margin-bottom: 0.75rem !important;
        }

        .dashboard-header {
            margin-bottom: 1rem !important;
        }
    }
</style>
<body class="fondo-una d-flex flex-column min-vh-100">
    <!-- HEADER -->
    <?php include('header.php'); ?>

    <!-- CONTENIDO -->
    <main class="flex-fill">
        <div class="container my-5">
            <div class="dashboard-header text-center mb-5">
                <h1>Listado de Actas Generadas</h1>
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
                            echo "<div class='actas-table-wrapper'>";
                            echo "<div class='table-responsive'>";
                            echo "<table class='table table-hover align-middle'>";
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
                            echo "</tbody></table></div></div>";
                        }
                    ?>
                </div>
            </div>
            <div class="text-center mt-4">
                <a href="<?= $base_url ?>generar_acta.php" class="btn btn-success">
                    <i class="bi bi-plus-circle-fill"></i> Generar nueva acta
                </a>
            </div>
            <div class="text-center mt-4">
                <a href="dashboard.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left-circle"></i> Volver al Panel Principal
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
