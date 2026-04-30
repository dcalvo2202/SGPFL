<?php
include("mod/login/check.php");
include('lang/lang.es');

require_once __DIR__ . '/inc/db/db.php';
require_once __DIR__ . '/lib/mysession/mySession.conf.php';
require_once __DIR__ . '/lib/mysession/mySession.class.php';

$mySessionController = mySession::getIstance($_MYSESSION_CONF);
$current_user_id = $mySessionController->getVar("usuario");

$page_title = 'Detalle de Solicitud de Prórroga';

// Obtener ID de la solicitud
$request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Mensajes de la sesión
$msg_success = '';
$msg_error = '';
if ($mySessionController->getVar('prorroga_success')) {
    $msg_success = $mySessionController->getVar('prorroga_success');
    $mySessionController->delete('prorroga_success');
}
if ($mySessionController->getVar('prorroga_error')) {
    $msg_error = $mySessionController->getVar('prorroga_error');
    $mySessionController->delete('prorroga_error');
}

// Obtener datos de la solicitud
$solicitud = null;
$conn = new mysqli($db_host, $usuario, $clave, $db);
if (!$conn->connect_error && $request_id > 0) {
    $conn->set_charset("utf8");
    $sql = "SELECT er.*, tp.title as proyecto_titulo, tp.user_id as estudiante_id
            FROM tfg_extension_requests er
            JOIN tfg_proposals tp ON er.proposal_id = tp.id
            WHERE er.id = ?";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $solicitud = $result->fetch_assoc();
        $stmt->close();
    }
    $conn->close();
}

$inlineStyles = <<<'CSS'
.detalle-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.1);
}
.info-row {
    padding: 15px 0;
    border-bottom: 1px solid #eee;
}
.info-row:last-child {
    border-bottom: none;
}
.info-label {
    font-weight: 600;
    color: #034991;
    margin-bottom: 5px;
}
.info-value {
    color: #333;
}
.motivo-box {
    background: #f8f9fa;
    border-left: 4px solid #034991;
    padding: 20px;
    border-radius: 0 8px 8px 0;
    margin: 20px 0;
}
.btn-aprobar {
    background-color: #28a745;
    color: #fff;
    padding: 12px 40px;
    font-weight: 600;
    border: none;
    border-radius: 4px;
    transition: all 0.3s ease;
}
.btn-aprobar:hover {
    background-color: #218838;
    color: #fff;
    transform: translateY(-2px);
}
.btn-rechazar {
    background-color: #dc3545;
    color: #fff;
    padding: 12px 40px;
    font-weight: 600;
    border: none;
    border-radius: 4px;
    transition: all 0.3s ease;
}
.btn-rechazar:hover {
    background-color: #c82333;
    color: #fff;
    transform: translateY(-2px);
}
.btn-volver {
    background-color: #6c757d;
    color: #fff;
    padding: 10px 30px;
    border: none;
    border-radius: 4px;
}
.btn-volver:hover {
    background-color: #5a6268;
    color: #fff;
}
.status-badge {
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
}
.status-pendiente { background: #ffc107; color: #212529; }
.status-aprobada { background: #28a745; color: #fff; }
.status-rechazada { background: #dc3545; color: #fff; }
CSS;
?>

<!doctype html>
<html lang="es">
<?php include __DIR__ . '/head.php'; ?>
<body class="fondo-una d-flex flex-column min-vh-100">
  <?php include 'header.php'; ?>
  <main class="flex-fill">
    <div class="container my-5">
      
      <?php if (!$solicitud): ?>
      <!-- Solicitud no encontrada -->
      <div class="card shadow-sm" style="max-width: 600px; margin: 0 auto;">
        <div class="card-body text-center py-5">
          <i class="fa fa-exclamation-triangle fa-4x text-warning mb-4"></i>
          <h4>Solicitud no encontrada</h4>
          <p class="text-muted">La solicitud que buscas no existe o ha sido eliminada.</p>
          <a href="panel_aprobarProrroga.php" class="btn btn-volver mt-3">
            <i class="fa fa-arrow-left"></i> Volver al listado
          </a>
        </div>
      </div>
      
      <?php else: ?>
      
      <div class="detalle-card" style="max-width: 800px; margin: 0 auto;">
        <!-- Header -->
        <div class="card-header text-center" style="background:#034991; color:#fff; font-weight:600; padding:20px;">
          <i class="fa fa-file-text-o"></i> Detalle de Solicitud de Prórroga
        </div>
        
        <div class="card-body" style="padding:30px;">
          
          <?php if (!empty($msg_success)): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle"></i> <?php echo htmlspecialchars($msg_success); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <?php endif; ?>
          
          <?php if (!empty($msg_error)): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-exclamation-circle"></i> <?php echo htmlspecialchars($msg_error); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <?php endif; ?>
          
          <!-- Título del proyecto -->
          <h4 style="color:#034991; font-weight:600; margin-bottom:25px; text-align:center;">
            <?php echo htmlspecialchars($solicitud['proyecto_titulo']); ?>
          </h4>
          
          <!-- Información de la solicitud -->
          <div class="row">
            <div class="col-md-6">
              <div class="info-row">
                <div class="info-label"><i class="fa fa-user"></i> Estudiante</div>
                <div class="info-value"><?php echo htmlspecialchars($solicitud['estudiante_id']); ?></div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="info-row">
                <div class="info-label"><i class="fa fa-calendar"></i> Fecha de Solicitud</div>
                <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($solicitud['request_date'])); ?></div>
              </div>
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6">
              <div class="info-row">
                <div class="info-label"><i class="fa fa-clock-o"></i> Tipo de Prórroga</div>
                <div class="info-value">
                  <?php if ($solicitud['extension_number'] == 1): ?>
                    <span class="badge" style="background:#17a2b8; color:#fff; padding:5px 12px;">
                      1ra Prórroga - 1 año (365 días)
                    </span>
                  <?php else: ?>
                    <span class="badge" style="background:#ffc107; color:#212529; padding:5px 12px;">
                      2da Prórroga - 6 meses (180 días)
                    </span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="info-row">
                <div class="info-label"><i class="fa fa-info-circle"></i> Estado</div>
                <div class="info-value">
                  <span class="status-badge status-<?php echo $solicitud['status']; ?>">
                    <?php echo ucfirst($solicitud['status']); ?>
                  </span>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Motivo -->
          <div class="info-label mt-4"><i class="fa fa-comment"></i> Motivo de la Solicitud</div>
          <div class="motivo-box">
            <?php echo nl2br(htmlspecialchars($solicitud['reason'])); ?>
          </div>
          
          <!-- Documento oficial de la solicitud -->
          <?php 
          $documentos = [];
          if (!empty($solicitud['documento_path'])) {
              $documentos = json_decode($solicitud['documento_path'], true);
              if (!is_array($documentos)) {
                  $documentos = [];
              }
          }
          ?>
          <?php if (!empty($documentos)): ?>
          <div class="info-label mt-4"><i class="fa fa-file-pdf-o"></i> Documento oficial de la solicitud</div>
          <div class="motivo-box" style="border-left-color: #CD1719;">
            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
              <?php foreach ($documentos as $index => $doc_path): ?>
                <?php 
                  $nombre_archivo = basename($doc_path);
                  $ruta_completa = __DIR__ . '/' . $doc_path;
                ?>
                <a href="<?php echo htmlspecialchars($doc_path); ?>" 
                   target="_blank" 
                   class="btn btn-sm" 
                   style="background: linear-gradient(135deg, #CD1719 0%, #8B0000 100%); color: white; padding: 8px 15px; border-radius: 20px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                  <i class="fa fa-download"></i>
                  <?php echo htmlspecialchars($nombre_archivo); ?>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
          <?php else: ?>
          <div class="info-label mt-4"><i class="fa fa-file-pdf-o"></i> Documento oficial de la solicitud</div>
          <div class="motivo-box" style="border-left-color: #6c757d;">
            <span class="text-muted"><i class="fa fa-info-circle"></i> No se adjuntó ningún documento a esta solicitud.</span>
          </div>
          <?php endif; ?>
          
          <?php if ($solicitud['status'] === 'pendiente'): ?>
          <!-- Formulario de respuesta -->
          <hr style="margin: 30px 0;">
          <h5 style="color:#034991; font-weight:600; margin-bottom:20px;">
            <i class="fa fa-gavel"></i> Responder Solicitud
          </h5>
          
          <form method="POST" action="mod/admin/users/responder_prorroga.php" id="formRespuesta">
            <input type="hidden" name="request_id" value="<?php echo $solicitud['id']; ?>">
            
            <div class="form-group mb-4">
              <label for="comentario" style="font-weight:600; color:#034991;">
                Comentario (opcional)
              </label>
              <textarea 
                id="comentario" 
                name="comentario" 
                class="form-control" 
                rows="3" 
                placeholder="Agregue un comentario para el estudiante..."
                style="resize:vertical;"
              ></textarea>
            </div>
            
            <div class="text-center mt-4">
              <button type="submit" name="accion" value="aprobar" class="btn btn-aprobar mr-3">
                <i class="fa fa-check"></i> Aprobar
              </button>
              <button type="submit" name="accion" value="rechazar" class="btn btn-rechazar">
                <i class="fa fa-times"></i> Rechazar
              </button>
            </div>
          </form>
          
          <?php else: ?>
          <!-- Información de respuesta -->
          <?php if ($solicitud['response_date']): ?>
          <hr style="margin: 30px 0;">
          <h5 style="color:#034991; font-weight:600; margin-bottom:20px;">
            <i class="fa fa-reply"></i> Respuesta
          </h5>
          <div class="row">
            <div class="col-md-6">
              <div class="info-row">
                <div class="info-label">Respondido por</div>
                <div class="info-value"><?php echo htmlspecialchars($solicitud['responded_by']); ?></div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="info-row">
                <div class="info-label">Fecha de Respuesta</div>
                <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($solicitud['response_date'])); ?></div>
              </div>
            </div>
          </div>
          <?php if ($solicitud['status'] === 'aprobada' && !empty($solicitud['fecha_actualizada'])): ?>
          <div class="row">
            <div class="col-md-12">
              <div class="info-row">
                <div class="info-label"><i class="fa fa-calendar-check-o"></i> Fecha actualizada del proyecto</div>
                <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($solicitud['fecha_actualizada'])); ?></div>
              </div>
            </div>
          </div>
          <?php endif; ?>
          <?php if (!empty($solicitud['response_comment'])): ?>
          <div class="info-label mt-3">Comentario</div>
          <div class="motivo-box" style="border-left-color: <?php echo ($solicitud['status'] === 'aprobada') ? '#28a745' : '#dc3545'; ?>;">
            <?php echo nl2br(htmlspecialchars($solicitud['response_comment'])); ?>
          </div>
          <?php endif; ?>
          <?php endif; ?>
          <?php endif; ?>
          
          <!-- Botón volver -->
          <div class="text-center mt-4 pt-3" style="border-top: 1px solid #eee;">
            <a href="panel_aprobarProrroga.php" class="btn btn-volver">
              <i class="fa fa-arrow-left"></i> Volver al listado
            </a>
          </div>
          
        </div>
      </div>
      
      <?php endif; ?>
      
    </div>
  </main>
  <?php include 'footer.php'; ?>
</body>
</html>
