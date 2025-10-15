<?php 
include("mod/login/check.php");
// includes.php comentado porque causa problemas con rutas relativas en subdirectorios
// include('includes.php');
include('lang/lang.es');

// Obtener variables de sesión
$current_user_id = $mySessionController->getVar("usuario");
$current_user_name = $mySessionController->getVar("nombre");
$current_user_rol = $mySessionController->getVar("rol");
$cds_domain = $mySessionController->getVar("cds_domain");
$cds_locate = $mySessionController->getVar("cds_locate");
$base_url = $cds_domain . $cds_locate;

?>
<header class="navbar-una" style="background: linear-gradient(135deg, #CD1719, #A01215) !important;">
    <div class="container-fluid px-4">
        <div class="header-left d-flex align-items-center">
            <img src="<?= $base_url ?>img/logo.webp" alt="Logo UNA" class="logo-una">
            <div class="header-text ms-3">
                <h5 class="mb-0 text-white fw-bold">Universidad Nacional de Costa Rica</h5>
                <small class="text-light opacity-85">Escuela de Informática</small>
            </div>
        </div>
        <div class="header-right text-end">
            <div class="user-info text-white mb-2">
                <i class="bi bi-person-circle fs-5"></i>
                <span class="ms-2 fw-semibold"><?= htmlspecialchars($current_user_name) ?></span>
            </div>
            <div class="user-details">
                <span class="text-light opacity-75">ID: <?= htmlspecialchars($current_user_id) ?></span>
                <button onclick="window.history.back()" class="btn btn-outline-light btn-sm ms-3" title="Volver a la página anterior">
                    <i class="bi bi-arrow-left"></i> Volver
                </button>
                <a href="mod/login/logout.php" class="btn btn-outline-light btn-sm ms-2">
                    <i class="bi bi-box-arrow-right"></i> Salir
                </a>
            </div>
        </div>
    </div>
</header>