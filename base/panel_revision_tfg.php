<?php
// VERIFICAR AUTENTICACIÓN
include("mod/login/check.php");

// Obtener base_url de la sesión
$cds_domain = $mySessionController->getVar("cds_domain");
$cds_locate = $mySessionController->getVar("cds_locate");
$base_url = $cds_domain . $cds_locate;

include('includes.php');
include('lang/lang.es');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Secretaría - Revisión de Propuestas</title>
    <link href="<?= $base_url ?>lib/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= $base_url ?>lib/font-awesome/css/font-awesome.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Revisión de Propuestas de TFG</h2>
        <?php include 'mod/admin/users/tfg_review_list.php'; ?>
    </div>

    <script src="<?= $base_url ?>lib/jquery-3.1.0.min.js"></script>
    <script src="<?= $base_url ?>lib/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>