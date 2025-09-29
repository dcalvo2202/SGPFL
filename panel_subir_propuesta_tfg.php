<?php
// El panel principal inicia la sesión.
session_start();

// Suponiendo que 'includes.php' define variables como $page_title, $footer_title
// y carga la configuración de la base de datos para que $base_url esté disponible.
include('includes.php'); 
include('lang/lang.es');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) : 'Panel de Estudiante' ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body, .page-container { display: flex; flex-direction: column; min-height: 100vh; }
        main { flex-grow: 1; }
    </style>
</head>
<body>
    <div class="page-container">
        
        <header class="bg-light border-bottom p-3">
            <div class="container">
                <h1 class="h3">Panel de Estudiante</h1>
                </div>
        </header>

        <main class="container py-4 py-md-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h2 class="card-title h4 border-bottom pb-3 mb-3">Gestión de Propuesta de TFG</h2>
                    <p class="text-muted">Desde aquí puedes enviar una nueva propuesta o revisar el estado de la que ya enviaste.</p>
                    
                    <a href="mod/admin/users/tfg_upload.php" class="btn btn-primary btn-lg mt-2 mb-4">
                        <i class="bi bi-cloud-arrow-up-fill me-2"></i>Subir Nueva Propuesta
                    </a>

                    <hr>

                    <div class="mt-4">
                        <?php
                            // Se incluye el archivo que contiene la lógica y ahora también imprime directamente el HTML.
                            // Ya no se usa la variable $status_html.
                            include __DIR__ . '/mod/admin/users/tfg_status.php';
                        ?>
                    </div>
                </div>
            </div>
        </main>

        <footer class="bg-dark text-white py-4 text-center mt-auto">
            <p class="text-muted mb-0"><?= isset($footer_title) ? htmlspecialchars($footer_title) : '© ' . date('Y') . ' Universidad Nacional' ?></p>
        </footer>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
