<?php
// VERIFICAR AUTENTICACIÓN Y PERMISOS
include("mod/login/check.php");

// 1. INCLUIR ARCHIVOS NECESARIOS
include('lang/lang.es');

// 2. OBTENER VARIABLES DE SESIÓN
$current_user_id = $mySessionController->getVar("usuario");
$current_user_name = $mySessionController->getVar("nombre");
$current_user_rol = $mySessionController->getVar("rol");
$base_url = $mySessionController->getVar("cds_domain") . $mySessionController->getVar("cds_locate");

// 3. CONTROL DE ACCESO POR ROL (Solo Gestor Académico - rol 2)
if ($current_user_rol != 2) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<!-- =============================== HEAD =============================== -->
<?php include 'head.php'; ?>
<body class="fondo-una d-flex flex-column min-vh-100">

    <!-- =============================== HEADER =============================== -->
    <?php include 'header.php'; ?>

    <!-- =============================== CONTENIDO PRINCIPAL =============================== -->
    <main class="flex-fill">
        <div class="container my-5">
            
            <div class="dashboard-header text-center mb-5">
                <h1 style="font-size: 2.5rem; font-weight: 700;">Revisión de Propuestas de TFG</h1>
                <p class="lead">A continuación se muestran las propuestas que requieren aprobación.</p>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <?php 
                    // Se incluye la lista de propuestas, que contiene la lógica de la tabla y el script.
                    include 'mod/admin/users/tfg_review_list.php'; 
                    ?>
                </div>
            </div>
             <div class="text-center mt-4">
                <a href="panel_subdireccion.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left-circle"></i> Volver al Panel Principal
                </a>
            </div>
        </div>
    </main>

    <!-- =============================== FOOTER =============================== -->
    <?php include 'footer.php'; ?>
    
    <!-- Scripts de JS se cargan en los archivos que los necesitan -->

</body>
</html>