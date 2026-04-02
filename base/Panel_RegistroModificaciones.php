<?php
// VERIFICAR AUTENTICACIÓN Y PERMISOS
include("mod/login/check.php");

// 1. INCLUIR ARCHIVOS NECESARIOS
include('lang/lang.es');

//panel_revision_tfg.php

// 2. OBTENER VARIABLES DE SESIÓN
$current_user_id = $mySessionController->getVar("usuario");
$current_user_name = $mySessionController->getVar("nombre");
$current_user_rol = $mySessionController->getVar("rol");
$base_url = $mySessionController->getVar("cds_domain") . $mySessionController->getVar("cds_locate");

// 3. CONTROL DE ACCESO POR ROL (Solo Gestor Académico - rol 2)
/*if ($current_user_rol != 2) {
    header('Location: dashboard.php');
    exit;
}*/
?>

<!DOCTYPE html>
<html lang="es">
<!-- =============================== HEAD =============================== -->
<?php include 'head.php'; ?>
<body class="fondo-una d-flex flex-column min-vh-100">

    <!-- =============================== HEADER =============================== -->
    <?php include 'header.php'; ?>

        <!-- =============================== FOOTER =============================== -->
    <?php include 'footer.php'; ?>
    
    <!-- Scripts de JS se cargan en los archivos que los necesitan -->
</body>
</html>

