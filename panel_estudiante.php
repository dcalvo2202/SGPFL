<?php
// Incluir configuración de sesiones
include('lib/mysession/mySession.conf.php');
include('lib/mysession/mySession.class.php');

// Inicializar sesión personalizada
$mySessionController = mySession::getIstance($_MYSESSION_CONF);

// Verificar autenticación
$usuario_sesion = $mySessionController->getVar('usuario');
$rol_sesion = $mySessionController->getVar('rol');

if (empty($usuario_sesion) || $rol_sesion != 4) {
    header('Location: index.php');
    exit();
}

// Incluir archivos necesarios
include('inc/db/db.php');
include('lang/lang.es');

$footer_title = "Sistema Gestor de Proyectos Finales de Licenciatura\nEscuela de Informatica\nUniversidad Nacional de Costa Rica";
$proxima_fecha   = "14/09/2025 – Entrega del capítulo 2";
$tarea_pendiente = "Subir versión corregida del capítulo 2";
$documento_enviado = "Avance 1 – Revisado con observaciones";
$notificacion    = "[10/09/2025] Nueva fecha de entrega asignada";

$usuario = $usuario_sesion ?? 'Estudiante';
?>
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <title>Panel Estudiante - SGPFL</title>
        <style>
            body {
                background-image: url('img/fondo_global.png');
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
                font-family: Arial, sans-serif;
                color: white;
            }
        </style>
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    </head>
    <body class="d-flex flex-column min-vh-100">
        <!-- JQuery -->
        <script src="lib/jquery-3.1.0.min.js"></script>
        <!-- SweetAlert2 -->
        <script src="lib/sweetalert2/sweetalert2-v11-23-0.js"></script>
        
        <div class="page-container flex-grow-1">
            <h3 class="mb-4 text-center">Bienvenido, <?= htmlspecialchars($usuario) ?></h3>
            <header class="d-flex justify-content-end gap-3 p-3">
                <a href="logout.php" class="btn btn-outline-dark">Logout</a>
                <a href="#" class="btn btn-outline-dark">Fechas importantes</a>
                <a href="#" class="btn btn-outline-dark">Enviar documentos</a>
                <a href="index.php" class="btn btn-outline-dark">Inicio</a>
            </header>
            <main class="flex-grow-1 container py-4">
                <div class="row">
                    <div class="col-md-8 mx-auto">
                        <div class="card mb-4" style="background-color: rgba(255,255,255,0.9);">
                            <div class="card-header">
                                <h5 class="card-title mb-0 text-dark">Panel del Estudiante</h5>
                            </div>
                            <div class="card-body text-dark">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Próximas fechas importantes:</label>
                                    <div class="alert alert-info"><?= $proxima_fecha ?></div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Tareas pendientes:</label>
                                    <div class="alert alert-warning"><?= $tarea_pendiente ?></div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Último documento enviado:</label>
                                    <div class="alert alert-success"><?= $documento_enviado ?></div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Notificaciones:</label>
                                    <div class="alert alert-primary"><?= $notificacion ?></div>
                                </div>

                                <div class="text-center mt-4">
                                    <a href="tfg_upload.php" class="btn btn-primary btn-lg me-2">Editar Propuesta TFG</a>
                                    <a href="#" class="btn btn-secondary btn-lg">Ver Progreso</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
        <footer class="bg-dark text-white text-center p-3 mt-auto">
            <small><?= nl2br($footer_title) ?></small>
        </footer>
    </body>
</html>

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
