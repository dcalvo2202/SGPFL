<?php
// Incluir configuración de sesiones
include('lib/mysession/mySession.conf.php');
include('lib/mysession/mySession.class.php');

// Inicializar sesión personalizada
$mySessionController = mySession::getIstance($_MYSESSION_CONF);

// Verificar autenticación
$usuario_sesion = $mySessionController->getVar('usuario');
$rol_sesion = $mySessionController->getVar('rol');

// Obtener base_url de la sesión
$cds_domain = $mySessionController->getVar('cds_domain');
$cds_locate = $mySessionController->getVar('cds_locate');
$base_url = $cds_domain . $cds_locate;

if (empty($usuario_sesion) || $rol_sesion != 4) {
    header('Location: index.php');
    exit();
}

// Incluir archivos necesarios
include('inc/db/db.php');
include('lang/lang.es');

// HU-020: Verificar si el estudiante tiene documentos finales rechazados
$rejected_documents = [];
try {
    $conn = new mysqli($db_host, $usuario, $clave, $db);
    if (!$conn->connect_error) {
        $conn->set_charset("utf8");
        
        $sql = "SELECT fd.id, fd.status, tp.title, 
                       (SELECT COUNT(*) FROM tfg_document_reviews 
                        WHERE document_id = fd.id AND review_type = 'Correccion Estudiante') as corrections_count
                FROM tfg_final_documents fd
                INNER JOIN tfg_proposals tp ON fd.proposal_id = tp.id
                WHERE fd.submitted_by = ? AND fd.status = 'Rechazado'";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $usuario_sesion);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $rejected_documents[] = $row;
        }
        
        $stmt->close();
        $conn->close();
    }
} catch (Exception $e) {
    error_log("Error al obtener documentos rechazados: " . $e->getMessage());
}

// ================== LÓGICA DEL PANEL ==================
require_once 'PanelEstudianteLogic.php';
$panel = new PanelEstudiante();

// Obtención dinámica de datos
$proxima_fecha      = $panel->getProximaFecha($usuario_sesion);
$tarea_pendiente    = $panel->getTareaPendiente($usuario_sesion);
$documento_enviado  = $panel->getDocumentoEnviado($usuario_sesion);
$notificacion       = $panel->getNotificacion($usuario_sesion);
//$rejected_documents = $panel->getDocumentosRechazados($usuario_sesion);

$usuario = $usuario_sesion ?? 'Estudiante';
?>
<!DOCTYPE html>
<html lang="es">
    <?php include('head.php'); ?>
    <body class="d-flex flex-column min-vh-100">
        <!-- JQuery -->
        <script src="<?= $base_url ?>lib/jquery-3.1.0.min.js"></script>
        <!-- SweetAlert2 -->
        <script src="<?= $base_url ?>lib/sweetalert2/sweetalert2-v11-23-0.js"></script>
        
        <div class="page-container flex-grow-1">
            <h3 class="mb-4 text-center">Bienvenido, <?= htmlspecialchars($usuario) ?></h3>
            <header class="d-flex justify-content-end gap-3 p-3">
                <a href="<?= $base_url ?>mod/login/logout.php" class="btn btn-outline-dark">Logout</a>
                <a href="<?= $base_url ?>" class="btn btn-outline-dark">Fechas importantes</a>
                <a href="<?= $base_url ?>" class="btn btn-outline-dark">Enviar documentos</a>
                <a href="<?= $base_url ?>historial_documentos.php" class="btn btn-outline-dark">Historial de documentos</a>
                <a href="<?= $base_url ?>index.php" class="btn btn-outline-dark">Inicio</a>
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

                                <?php if (!empty($rejected_documents)): ?>
                                <!-- HU-020: Sección de correcciones requeridas -->
                                <div class="mb-3">
                                    <label class="form-label fw-bold text-danger">
                                        <i class="bi bi-exclamation-triangle-fill"></i> Correcciones Requeridas:
                                    </label>
                                    <?php foreach ($rejected_documents as $doc): ?>
                                    <div class="alert alert-danger">
                                        <h6 class="alert-heading">Documento: <?= htmlspecialchars($doc['title']) ?></h6>
                                        <p class="mb-2">El CTFG ha solicitado correcciones en su documento final.</p>
                                        <p class="mb-2"><strong>Correcciones enviadas:</strong> <?= $doc['corrections_count'] ?> / 3</p>
                                        <hr>
                                        <a href="<?= $base_url ?>mod/admin/users/tfg_upload_correction.php?id=<?= $doc['id'] ?>" 
                                           class="btn btn-warning btn-sm">
                                            <i class="bi bi-file-earmark-arrow-up-fill"></i> Subir Corrección
                                        </a>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>

                                <div class="text-center mt-4">
                                    <a href="<?= $base_url ?>Panel_SubirTFG.php" class="btn btn-primary btn-lg me-2">Editar Propuesta TFG</a>
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
