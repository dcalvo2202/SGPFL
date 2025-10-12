<?php
// VERIFICAR AUTENTICACIÓN Y PERMISOS
include("mod/login/check.php");

// --- INICIO DE LA LÓGICA DE LA PÁGINA ---

// 1. INCLUIR ARCHIVOS NECESARIOS
// Incluye las dependencias de estilos, scripts y configuración del idioma.
include('includes.php');
include('lang/lang.es');

// 2. OBTENER VARIABLES DE SESIÓN
// Se obtienen los datos del usuario que ha iniciado sesión.
$current_user_id = $mySessionController->getVar("usuario");
$current_user_name = $mySessionController->getVar("nombre");
$current_user_rol = $mySessionController->getVar("rol");

// Construir la URL base para los enlaces y recursos.
$base_url = $mySessionController->getVar("cds_domain") . $mySessionController->getVar("cds_locate");

// 3. CONTROL DE ACCESO POR ROL
// Se verifica que el usuario tenga el rol de 'Gestor Académico' (ID 2).
// Si no lo tiene, se le redirige al panel principal para evitar accesos no autorizados.
if ($current_user_rol != 2) {
    header('Location: dashboard.php');
    exit; // Detiene la ejecución del script para asegurar que no se muestre nada más.
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del Gestor Académico - SGPFL UNA</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/webp" href="<?= htmlspecialchars($favicon_url) ?>">

    <!-- Bootstrap CSS y Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Estilos personalizados -->
    <link href="<?= htmlspecialchars($base_url . 'inc/css/estilo.css') ?>" rel="stylesheet">
    <link href="<?= htmlspecialchars($base_url . 'inc/css/panel_estudiante.css') ?>" rel="stylesheet">
</head>
<body class="fondo-una d-flex flex-column min-vh-100">

    <!-- =============================== HEADER =============================== -->
    <!-- Este encabezado es idéntico al de los otros paneles para mantener la consistencia. -->
    <header class="navbar-una" style="background: linear-gradient(135deg, #CD1719, #A01215) !important;">
        <div class="container-fluid px-4">
            <div class="header-left d-flex align-items-center">
                <img src="<?= htmlspecialchars($base_url) ?>img/logo.webp" alt="Logo UNA" class="logo-una">
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
                    <small class="text-light opacity-75">ID: <?= htmlspecialchars($current_user_id) ?></small>
                    <a href="mod/login/logout.php" class="btn btn-outline-light btn-sm ms-3">
                        <i class="bi bi-box-arrow-right"></i> Salir
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- =============================== CONTENIDO PRINCIPAL =============================== -->
    <main class="flex-fill">
        <div class="container my-5">
            
            <div class="dashboard-header text-center mb-5">
                <h1 style="font-size: 2.5rem; font-weight: 700;">Panel del Gestor Académico</h1>
                <p class="lead">Bienvenido, <?= htmlspecialchars($current_user_name) ?>. Desde aquí puede gestionar las propuestas de TFG.</p>
            </div>

            <!-- Sección de Acciones Rápidas -->
            <div class="quick-actions-section">
                <h2 class="section-title">
                    <i class="bi bi-lightning-fill text-rojo-una"></i>
                    Acciones Rápidas
                </h2>
                
                <div class="row justify-content-center">
                    <div class="col-md-6 col-lg-4">
                        <!-- Tarjeta que redirige al panel de revisión de propuestas -->
                        <div class="quick-action-card" onclick="location.href='<?= htmlspecialchars($base_url) ?>panel_revision_tfg.php'">
                            <div class="card-icon">
                                <i class="bi bi-clipboard2-check-fill"></i>
                            </div>
                            <h5>Revisar Propuestas</h5>
                            <p>Ver y gestionar las propuestas de TFG pendientes de aprobación.</p>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </main>

    <!-- =============================== FOOTER =============================== -->
    <!-- Este pie de página también es idéntico al de los otros paneles. -->
    <footer class="footer-una mt-auto">
        <div class="container">
            <p class="mb-1">&copy; <?= date('Y') ?> Universidad Nacional de Costa Rica</p>
            <small>Escuela de Informática - Proyecto SGPFL v3.0</small>
        </div>
    </footer>
    
    <!-- Los scripts de JS se cargan desde includes.php -->

</body>
</html>