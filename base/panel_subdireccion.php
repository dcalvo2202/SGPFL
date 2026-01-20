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
if ($current_user_rol != 2 && $current_user_rol != 1) {
    header('Location: login.php');
    exit; // Detiene la ejecución del script para asegurar que no se muestre nada más.
}

// 4. NOTIFICACIÓN INTERNA: Solicitudes de Asesor Externo en revisión
$external_profiles_pending = 0;
try {
    // Importante: mod/login/check.php usa la variable $usuario para el ID de sesión.
    // bdcommon.inc también usa $usuario para el usuario de MySQL.
    // Para evitar colisiones, cargamos la config de BD en un scope local.
    $dbcfg = (function (string $path): array {
        $db_host = null;
        $usuario = null;
        $clave = null;
        $db = null;
        require $path;
        return [
            'host' => (string)$db_host,
            'user' => (string)$usuario,
            'pass' => (string)$clave,
            'name' => (string)$db,
        ];
    })(__DIR__ . '/inc/db/bdcommon.inc');

    $conn_notif = new mysqli($dbcfg['host'], $dbcfg['user'], $dbcfg['pass'], $dbcfg['name']);
    if ($conn_notif->connect_error) {
        error_log('Error de conexión (notificaciones HU-011): ' . $conn_notif->connect_error);
    } else {
        $conn_notif->set_charset('utf8');

        // Consulta simple (según el estado real en BD: "En Revision" sin tilde)
        $rs = $conn_notif->query("SELECT COUNT(*) AS c FROM external_advisor_profile_requests WHERE status = 'En Revision'");
        if ($rs) {
            $rowc = $rs->fetch_assoc();
            $external_profiles_pending = (int)($rowc['c'] ?? 0);
        } else {
            error_log('Error contando external_advisor_profile_requests: ' . $conn_notif->error);
        }

        $conn_notif->close();
    }
} catch (Exception $e) {
    error_log('Error contando solicitudes de asesor externo en revisión: ' . $e->getMessage());
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
    <?php include 'header.php'; ?> 

    <!-- =============================== CONTENIDO PRINCIPAL =============================== -->
    <main class="flex-fill">
        <div class="container my-5">
            
            <div class="dashboard-header text-center mb-5">
                <h1 style="font-size: 2.5rem; font-weight: 700;">Panel del Gestor Académico</h1>
                <p class="lead">Bienvenido, <?= htmlspecialchars($current_user_name) ?>. Desde aquí puede gestionar las propuestas de TFG.</p>
            </div>

            <?php if ($external_profiles_pending > 0): ?>
                <div id="external-profiles-alert" class="alert alert-warning" role="alert">
                    <i class="bi bi-bell-fill"></i>
                    Hay <strong><?= (int)$external_profiles_pending ?></strong> solicitud(es) de <strong>Perfil Académico de Asesor Externo</strong> en estado <strong>En Revisión</strong>.
                </div>
                <script>
                    // Oculta la notificación automáticamente luego de 5 segundos
                    setTimeout(function () {
                        var alertEl = document.getElementById('external-profiles-alert');
                        if (alertEl) {
                            alertEl.style.display = 'none';
                        }
                    }, 5000);
                </script>
            <?php endif; ?>

            <!-- Sección de Acciones Rápidas -->
            <div class="quick-actions-section">
                <h2 class="section-title">
                    <i class="bi bi-lightning-fill text-rojo-una"></i>
                    Acciones Rápidas
                </h2>
                
                <div class="row justify-content-center">
                    <div class="col-md-6 col-lg-4">
                        <!-- Tarjeta que redirige al panel de revisión de propuestas -->
                        <div class="quick-action-card" onclick="location.href='<?= htmlspecialchars($base_url) ?>panel_revision_tfg.php'" style="border-left: 4px solid #dc3545;">
                            <div class="card-icon">
                                <i class="bi bi-clipboard2-check-fill"></i>
                            </div>
                            <h5>Revisar Propuestas</h5>
                            <p>Ver y gestionar las propuestas de TFG pendientes de aprobación.</p>
                        </div>
                    </div>
                    <!-- Aprobar Proyectos -->
                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card" onclick="location.href='<?= htmlspecialchars($base_url) ?>proyecto_aprobado.php'" style="border-left: 4px solid #198754;">
                            <div class="card-icon">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                            <h5>Aprobar Proyectos</h5>
                            <p>Gestionar y aprobar proyectos de TFG.</p>
                        </div>
                    </div> 
                    <!-- Revisar Documentos Finales -->
                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card" onclick="location.href='<?= htmlspecialchars($base_url) ?>panel_ctfg_review_final_documents.php'" style="border-left: 4px solid #0d6efd;">
                            <div class="card-icon">
                                <i class="bi bi-file-earmark-check-fill"></i>
                            </div>
                            <h5>Revisar Documentos Finales</h5>
                            <p>Ver y gestionar documentos finales de TFG pendientes de revisión.</p>
                        </div>
                    </div>
                    <!-- Proyectos Registrados -->
                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card" onclick="location.href='<?= htmlspecialchars($base_url) ?>ProyectosRegistrados.php'" style="border-left: 4px solid #ffc107;">
                            <div class="card-icon">
                                <i class="bi bi-list-task"></i>
                            </div>
                            <h5>Proyectos Registrados</h5>
                            <p>Buscar y consultar proyectos aprobados, sin aprobar o en corrección.</p>
                        </div>
                    </div>
                    <!-- HU-027: Archivo Histórico -->
                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card" onclick="location.href='<?= htmlspecialchars($base_url) ?>panel_archivo_historico.php'" style="border-left: 4px solid #6c757d;">
                            <div class="card-icon" style="color: #6c757d;">
                                <i class="bi bi-archive-fill"></i>
                            </div>
                            <h5>Archivo Histórico</h5>
                            <p>Consultar proyectos concluidos o cancelados (Art. 68 RGPEA).</p>
                        </div>
                    </div>
                    <!-- HU-012: Revisar Solicitudes de Asesor Externo -->
                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card" onclick="location.href='<?= htmlspecialchars($base_url) ?>panel_revisar_asesor_externo.php'" style="border-left: 4px solid #17a2b8;">
                            <div class="card-icon" style="color: #17a2b8;">
                                <i class="bi bi-person-badge-fill"></i>
                            </div>
                            <h5>Asesores Externos</h5>
                            <p>Revisar y aprobar solicitudes de perfil académico de asesores externos.</p>
                            <?php if ($external_profiles_pending > 0): ?>
                                <span class="badge bg-warning text-dark"><?= (int)$external_profiles_pending ?> pendiente(s)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
            </div>
            <!-- =============================== GESTIÓN DE ACTAS =============================== -->
            <div class="quick-actions-section mt-5">
                <h2 class="section-title mb-4 text-center">
                    <i class="bi bi-file-earmark-text-fill text-rojo-una"></i>
                    Gestión de Actas de Examen Final
                </h2>

                <div class="row justify-content-center">
                    <!-- Generar nueva acta -->
                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card" onclick="location.href='<?= $base_url ?>generar_acta.php'">
                            <div class="card-icon">
                                <i class="bi bi-file-earmark-plus-fill"></i>
                            </div>
                            <h5>Generar Nueva Acta</h5>
                            <p>Crear acta de examen público en formato PDF.</p>
                        </div>
                    </div>
            
                    <!-- Ver listado de actas -->
                    <div class="col-md-6 col-lg-4">
                        <div class="quick-action-card" onclick="location.href='<?= $base_url ?>listar_actas.php'">
                            <div class="card-icon">
                                <i class="bi bi-collection-fill"></i>
                            </div>
                            <h5>Listar Actas Existentes</h5>
                            <p>Ver o descargar actas generadas previamente.</p>
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
