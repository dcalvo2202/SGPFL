<?php
include("mod/login/check.php");
include('includes.php');
include('lang/lang.es');

$current_user_name = $mySessionController->getVar("nombre");
$current_user_rol  = $mySessionController->getVar("rol");
$cds_domain = $mySessionController->getVar("cds_domain");
$cds_locate = $mySessionController->getVar("cds_locate");
$base_url   = $cds_domain . $cds_locate;

if (!in_array($current_user_rol, [2, 3])) {
    header('Location: dashboard.php');
    exit;
}
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
                <h1 style="font-size: 2.3rem; font-weight: 700;">Generar Acta de Examen Público</h1>
                <p class="lead">Complete el formulario para crear el documento PDF oficial.</p>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body text-dark">
                    <form action="procesar_acta.php" method="POST" target="_blank" class="needs-validation" novalidate>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nombre" class="form-label fw-bold">Nombre del Estudiante:</label>
                                <input type="text" name="nombre" id="nombre" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="id_estudiante" class="form-label fw-bold">ID del Estudiante:</label>
                                <input type="text" name="id_estudiante" id="id_estudiante" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label for="nota" class="form-label fw-bold">Calificación:</label>
                                <input type="number" step="0.01" name="nota" id="nota" class="form-control" min="0" max="10" required>
                            </div>
                            <div class="col-md-8">
                                <label for="tribunal" class="form-label fw-bold">Tribunal Evaluador:</label>
                                <textarea name="tribunal" id="tribunal" class="form-control" rows="2" placeholder="Ejemplo: Dr. Luis Vargas, M.Sc. Ana Pérez, Ing. Carlos Soto" required></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="fecha" class="form-label fw-bold">Fecha del Examen:</label>
                                <input type="date" name="fecha" id="fecha" class="form-control" required>
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bi bi-file-earmark-pdf-fill"></i> Generar Acta en PDF
                            </button>
                            <a href="listar_actas.php" class="btn btn-secondary btn-lg ms-3">
                                <i class="bi bi-arrow-left-circle"></i> Volver al listado
                            </a>
                        </div>
                    </form>
                </div>
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
