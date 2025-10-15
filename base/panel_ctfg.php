<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel CTFG - SGPFL UNA</title>
    <?php
        include('includes.php');
        include('lang/lang.es');
    ?>
    
    <!-- Bootstrap CSS y Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Estilos personalizados -->
    <link href="inc/css/estilo.css" rel="stylesheet">
    <link href="inc/css/panel_estudiante.css" rel="stylesheet">
</head>
<body class="fondo-una d-flex flex-column min-vh-100">

    <?php
    // Obtener datos del usuario de sesión
    include('lib/mysession/mySession.conf.php');
    include('lib/mysession/mySession.class.php');
    $mySessionController = mySession::getIstance($_MYSESSION_CONF);
    $current_user_name = $mySessionController->getVar('nombre') ?? 'Usuario CTFG';
    $current_user_id = $mySessionController->getVar('usuario') ?? '';
    $base_url = $mySessionController->getVar('cds_domain') . $mySessionController->getVar('cds_locate');
    ?>

    <!-- =============================== HEADER =============================== -->
    <?php include 'header.php'; ?> 

    <!-- =============================== CONTENIDO PRINCIPAL =============================== -->
    <main class="flex-fill">
        <div class="container my-5">
            
            <div class="dashboard-header text-center mb-5">
                <h1 style="font-size: 2.5rem; font-weight: 700;">Panel de la Comisión de TFG</h1>
                <p class="lead">Bienvenido, <?= htmlspecialchars($current_user_name) ?>. Gestione las propuestas y documentos finales de TFG.</p>
            </div>

            <!-- Sección de Acciones Rápidas -->
            <div class="quick-actions-section">
                <h2 class="section-title">
                    <i class="bi bi-lightning-fill text-rojo-una"></i>
                    Acciones Rápidas
                </h2>
                
                <div class="row justify-content-center">
                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card" onclick="location.href='<?= htmlspecialchars($base_url) ?>panel_ctfg_review_final_documents.php'">
                            <div class="card-icon">
                                <i class="bi bi-file-earmark-check-fill"></i>
                            </div>
                            <h5>Revisar Documentos Finales</h5>
                            <p>Ver y gestionar documentos finales de TFG pendientes de revisión.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card" onclick="location.href='<?= htmlspecialchars($base_url) ?>proyecto_aprobado.php'">
                            <div class="card-icon">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                            <h5>Aprobar Proyectos</h5>
                            <p>Gestionar y aprobar proyectos de TFG.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card" onclick="location.href='<?= htmlspecialchars($base_url) ?>index.php'">
                            <div class="card-icon">
                                <i class="bi bi-house-fill"></i>
                            </div>
                            <h5>Inicio</h5>
                            <p>Volver al panel principal del sistema.</p>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </main>

    <!-- =============================== FOOTER =============================== -->
    <footer class="footer-una mt-auto">
        <div class="container">
            <p class="mb-1">&copy; <?= date('Y') ?> Universidad Nacional de Costa Rica</p>
            <small>Escuela de Informática - Proyecto SGPFL v3.0</small>
        </div>
    </footer>

</body>
</html>
