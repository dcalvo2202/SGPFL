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
<?php include 'head.php'; ?>
<body class="fondo-una d-flex flex-column min-vh-100">

    <?php include 'header.php'; ?>

    <main class="flex-fill">
        <div class="container my-5">

            <div class="dashboard-header text-center mb-4">
                <h1 style="font-size: 2.5rem; font-weight: 700;">Revisión de Propuestas de TFG</h1>
                <p class="lead mb-0">Consulte todos los archivos de cada propuesta y resuelva la revisión completa en bloque.</p>
            </div>

            <div class="alert alert-light border shadow-sm mb-4" role="alert">
                <div class="d-flex gap-3 align-items-start">
                    <i class="bi bi-folder-check fs-4 text-primary"></i>
                    <div>
                        <strong>Modo de revisión actual</strong>
                        <div class="small text-muted mt-1">
                            En esta vista se muestran los documentos individuales de cada propuesta, pero la decisión sigue aplicándose a la propuesta completa.
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <?php include 'mod/admin/users/tfg_review_list.php'; ?>
                </div>
            </div>

            <div class="text-center mt-4">
                <a href="panel_subdireccion.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left-circle"></i> Volver al Panel Principal
                </a>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>
