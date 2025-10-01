<!DOCTYPE html>
<html lang="es">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <title><?= $page_title ?></title>
        <style>
            body {
                background-image: url('img/fondo_global.png'); /* Ruta a tu imagen */
                background-size: cover;                 /* La imagen cubre toda la pantalla */
                background-position: center;            /* Centrada */
                background-repeat: no-repeat;           /* No repetir */
                font-family: Arial, sans-serif;
                color: white; /* Opcional, para que el texto se vea mejor */
            }
        </style>
        <?php
            include('includes.php');
            include('lang/lang.es'); /*Añadir conexion a base de datos*/ 
            $footer_title = "Sistema Gestor de Proyectos Finales de Licenciatura\nEscuela de Informatica\nUniversidad Nacional de Costa Rica";
            $proxima_fecha   = "14/09/2025 – Entrega del capítulo 2";
            $tarea_pendiente = "Subir versión corregida del capítulo 2";
            $documento_enviado = "Avance 1 – Revisado con observaciones";
            $notificacion    = "[10/09/2025] Nueva fecha de entrega asignada";
        ?>
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-..." crossorigin="anonymous">

        <!-- Bootstrap JS Bundle (incluye Popper) -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-..." crossorigin="anonymous"></script>
    </head>
    <body class="d-flex flex-column min-vh-100">
        <div class="page-container flex-grow-1">
            <h3 class="mb-4 text-center">Bienvenido, Estudiante de Licenciatura</h3>
            <header class="d-flex justify-content-end gap-3 p-3">
                <a href="/base/mod/login/logout.php" class="btn btn-outline-dark">Logout</a>
                <a href="/" class="btn btn-outline-dark">Fechas importantes</a>
                <a href="/" class="btn btn-outline-dark">Enviar documentos</a>
                <a href="index.php" class="btn btn-outline-dark">Inicio</a>
            </header>
            <main class="flex-grow-1 container py-4">
                <div class="mb-3">
                    <label class="form-label fw-bold">Próximas fechas:</label>
                    <div class="alert alert-info"><?= $proxima_fecha ?></div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Tareas pendientes:</label>
                    <div class="alert alert-warning"><?= $tarea_pendiente ?></div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Documentos enviados:</label>
                    <div class="alert alert-success"><?= $documento_enviado ?></div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Notificaciones:</label>
                    <div class="alert alert-primary"><?= $notificacion ?></div>
                </div>
            </main>
        </div>
        <footer class="bg-dark text-white py-4 text-center">
            <p class="text-center small"><?= nl2br($footer_title) ?></p>
        </footer>
    </body>
</html>
