<?php
session_start();
require_once __DIR__ . '/inc/db/db.php';

// Título y estilos para head.php
$page_title   = 'Notas del proyecto';
$inlineStyles = <<<'CSS'
main { padding: 24px 0; }
.page-title { font-weight: 700; color: #092567; margin-bottom: 14px; text-align: center; }
CSS;

// Id y nombre del proyecto
$proyecto_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$proyecto_nombre = trim($_GET['nombre'] ?? '');

// Si no viene el nombre en la URL, lo consultamos por ID
if ($proyecto_nombre === '' && $proyecto_id > 0) {
    if ($stmt = mysqli_prepare($id_con, "SELECT nombre FROM proyecto_aprobado WHERE id_aprobado = ?")) {
        mysqli_stmt_bind_param($stmt, "i", $proyecto_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($res && ($row = mysqli_fetch_assoc($res))) {
            $proyecto_nombre = (string)$row['nombre'];
        }
        mysqli_stmt_close($stmt);
    }
}

if ($proyecto_nombre !== '') {
    $page_title = 'Notas del proyecto - ' . $proyecto_nombre;
}
?>
<!doctype html>
<html lang="es">
<?php include __DIR__ . '/head.php'; ?>
<body class="fondo-una d-flex flex-column min-vh-100">
    <main class="container flex-fill">
        <div class="row align-items-center mt-3 mb-4">
            <div class="col-auto">
                <a class="btn btn-outline-secondary" href="ProyectosRegistrados.php" title="Volver">
                    <i class="bi bi-arrow-left"></i> Volver
                </a>
            </div>
            <div class="col text-center">
                <h1 class="page-title mb-0">
                    Notas del proyecto <?php echo $proyecto_nombre !== '' ? htmlspecialchars($proyecto_nombre) : ($proyecto_id > 0 ? '#'.$proyecto_id : ''); ?>
                </h1>
            </div>
            <!-- Columna espejo invisible para mantener el título exactamente centrado -->
            <div class="col-auto d-none d-sm-block" style="visibility:hidden;">
                <a class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
            </div>
        </div>

        <div class="alert alert-info">
            Aquí irá el contenido para ver/agregar notas del proyecto.
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>