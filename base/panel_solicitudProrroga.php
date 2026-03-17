<?php
include("mod/login/check.php");
include('lang/lang.es');

require_once __DIR__ . '/inc/db/db.php';
require_once __DIR__ . '/lib/mysession/mySession.conf.php';
require_once __DIR__ . '/lib/mysession/mySession.class.php';
$mySessionController = mySession::getIstance($_MYSESSION_CONF);

// Mensajes de la sesión (usando mySession)
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

// Obtener variables de sesión
$current_user_id = $mySessionController->getVar("usuario");
$current_user_name = $mySessionController->getVar("nombre");
$current_user_rol = $mySessionController->getVar("rol");
$base_url = $mySessionController->getVar("cds_domain") . $mySessionController->getVar("cds_locate");

// Variables para el control de prórrogas
$titulo_proyecto = '';
$proposal_id = null;
$prorrogas_aprobadas = 0;
$tiene_solicitud_pendiente = false;
$puede_solicitar = true;
$mensaje_estado = '';
$proxima_prorroga = 1; // 1 = Primera (1 año), 2 = Segunda (6 meses)

$conn = new mysqli($db_host, $usuario, $clave, $db);
if (!$conn->connect_error) {
    // Obtener el título e ID de la propuesta asociada al estudiante.
    // Puede ser el creador de la propuesta o un miembro activo del proyecto registrado.
    $sql = "SELECT DISTINCT tp.id, tp.title
            FROM tfg_proposals tp
            LEFT JOIN registered_projects rp ON rp.tfg_proposal_id = tp.id
            LEFT JOIN project_members pm ON pm.project_id = rp.id AND pm.status = 'Activo'
            WHERE tp.user_id = ? OR pm.user_id = ?
            ORDER BY tp.created_at DESC
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ss", $current_user_id, $current_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $proposal_id = $row['id'];
            $titulo_proyecto = htmlspecialchars($row['title']);
        }
        $stmt->close();
    }
    

    if ($proposal_id) {
        // Contar prórrogas aprobadas
        $sql_aprobadas = "SELECT COUNT(*) as total FROM tfg_extension_requests 
                          WHERE proposal_id = ? AND status = 'aprobada'";
        $stmt = $conn->prepare($sql_aprobadas);
        if ($stmt) {
            $stmt->bind_param("i", $proposal_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $prorrogas_aprobadas = (int)$row['total'];
            }
            $stmt->close();
        }
        
        // Verificar si tiene solicitud pendiente
        $sql_pendiente = "SELECT id FROM tfg_extension_requests 
                          WHERE proposal_id = ? AND status = 'pendiente' LIMIT 1";
        $stmt = $conn->prepare($sql_pendiente);
        if ($stmt) {
            $stmt->bind_param("i", $proposal_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $tiene_solicitud_pendiente = ($result->num_rows > 0);
            $stmt->close();
        }
    }
    
    $conn->close();
}

// Obtener historial de solicitudes (aprobadas y rechazadas)
$historial_solicitudes = [];
$conn = new mysqli($db_host, $usuario, $clave, $db);
if (!$conn->connect_error && $proposal_id) {
    $sql_historial = "SELECT id, extension_number, reason, status, request_date, response_date, response_comment 
                       FROM tfg_extension_requests 
                       WHERE proposal_id = ? AND status IN ('aprobada', 'rechazada')
                       ORDER BY request_date DESC";
    $stmt = $conn->prepare($sql_historial);
    if ($stmt) {
        $stmt->bind_param("i", $proposal_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $historial_solicitudes[] = $row;
        }
        $stmt->close();
    }
    $conn->close();
}

// Determinar estado y próxima prórroga
$proxima_prorroga = $prorrogas_aprobadas + 1;

if ($prorrogas_aprobadas >= 2) {
    $puede_solicitar = false;
    $mensaje_estado = 'Ya ha utilizado sus 2 prórrogas permitidas. No puede solicitar más.';
} elseif ($tiene_solicitud_pendiente) {
    $puede_solicitar = false;
    $mensaje_estado = 'Ya tiene una solicitud de prórroga pendiente de revisión.';
} elseif (empty($proposal_id)) {
    $puede_solicitar = false;
    $mensaje_estado = 'No tiene un proyecto registrado para solicitar prórroga.';
}

// Información de la prórroga a solicitar
$info_prorroga = ($proxima_prorroga == 1) 
    ? ['numero' => '1ra', 'duracion' => '1 año (365 días)']
    : ['numero' => '2da', 'duracion' => '6 meses (180 días)'];

$page_title = 'Solicitud de Prórroga';
$inlineStyles = <<<'CSS'
/* Estilos unificados (UNA) */
body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;}
.dashboard-header h1{font-size:2.5rem;font-weight:700;color:#034991;margin-bottom:.5rem;}
.dashboard-header .lead{color:#6c757d;}
CSS;
?>
<!doctype html>
<html lang="es">
<?php include __DIR__ . '/head.php'; ?>
<body class="fondo-una d-flex flex-column min-vh-100">
  <?php include 'header.php'; ?>
  <main class="flex-fill">
    <div class="container my-5">
      <div class="card form-card shadow-sm mx-auto" style="max-width: 800px;">
        <div class="card-header text-center" style="background:#f8f9fa;font-weight:600;color:#034991;">
          Solicitud de Prórroga Proyecto
        </div>
        <div class="card-body" style="padding:24px;">
          <?php if (!empty($titulo_proyecto)): ?>
          <h4 class="text-center mb-4" style="color:#034991; font-weight:600;">
            <?php echo $titulo_proyecto; ?>
          </h4>
          <?php endif; ?>
          
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
          
          <!-- Estado de Prórrogas -->
          <div class="alert alert-info mb-4" style="border-left:4px solid #034991;">
            <strong>Estado de Prórrogas:</strong>
            <ul class="mb-0 mt-2">
              <li>Prórrogas aprobadas: <strong><?php echo $prorrogas_aprobadas; ?>/2</strong></li>
              <li>1ra Prórroga: 1 año (365 días)</li>
              <li>2da Prórroga: 6 meses (180 días)</li>
            </ul>
          </div>
          
          <?php if (!$puede_solicitar): ?>
          <!-- Mensaje de bloqueo -->
          <div class="alert alert-warning text-center" style="border-left:4px solid #ffc107;">
            <i class="fa fa-exclamation-triangle"></i>
            <strong><?php echo htmlspecialchars($mensaje_estado); ?></strong>
          </div>
          <?php else: ?>
          
          <!-- Información de prórroga a solicitar -->
          <div class="alert alert-success mb-4" style="border-left:4px solid #28a745;">
            <strong>Solicitando:</strong> <?php echo $info_prorroga['numero']; ?> Prórroga 
            (<?php echo $info_prorroga['duracion']; ?>)
          </div>
          
          <form method="POST" action="mod/admin/users/procesar_prorroga.php" id="formProrroga">
            <input type="hidden" name="proposal_id" value="<?php echo $proposal_id; ?>">
            <input type="hidden" name="extension_number" value="<?php echo $proxima_prorroga; ?>">
            
            <!-- Panel de Motivo -->
            <div class="form-group mb-4">
              <label for="motivo" style="font-weight:600; color:#034991; margin-bottom:8px; display:block;">
                Motivo (menos de 200 palabras)
              </label>
              <textarea 
                id="motivo" 
                name="motivo" 
                class="form-control" 
                rows="5" 
                maxlength="200" 
                placeholder="Escriba el motivo de su solicitud de prórroga..."
                style="resize:vertical; border:1px solid #ced4da; border-radius:4px;"
                required
              ></textarea>
              <small class="text-muted" style="display:block; margin-top:5px;">
                <span id="charCount">0</span>/200 caracteres
              </small>
            </div>
            
            <script>
              document.getElementById('motivo').addEventListener('input', function() {
                document.getElementById('charCount').textContent = this.value.length;
              });
            </script>
            
            <!-- Botón Aceptar -->
            <div class="text-center mt-4">
              <button type="submit" class="btn" style="background-color:#28a745; color:#fff; padding:10px 40px; font-weight:600; border:none; border-radius:4px;">
                Enviar Solicitud
              </button>
            </div>
          </form>
          <?php endif; ?>
        </div>
      </div>
      
      <!-- Historial de Solicitudes -->
      <?php if (!empty($historial_solicitudes)): ?>
      <div class="card form-card shadow-sm mx-auto mt-4" style="max-width: 800px;">
        <div class="card-header text-center" style="background:#f8f9fa;font-weight:600;color:#034991;">
          <i class="fa fa-history"></i> Historial de Solicitudes
        </div>
        <div class="card-body" style="padding:24px;">
          <div class="table-responsive">
            <table class="table table-bordered table-hover" style="font-size:14px;">
              <thead style="background-color:#034991; color:#fff;">
                <tr>
                  <th class="text-center" style="width:80px;">Prórroga</th>
                  <th class="text-center" style="width:120px;">Fecha Solicitud</th>
                  <th class="text-center" style="width:100px;">Estado</th>
                  <th>Motivo</th>
                  <th style="width:150px;">Comentarios</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($historial_solicitudes as $solicitud): ?>
                <tr>
                  <td class="text-center">
                    <strong><?php echo ($solicitud['extension_number'] == 1) ? '1ra' : '2da'; ?></strong>
                  </td>
                  <td class="text-center">
                    <?php echo date('d/m/Y', strtotime($solicitud['request_date'])); ?>
                  </td>
                  <td class="text-center">
                    <?php if ($solicitud['status'] == 'aprobada'): ?>
                      <span class="badge badge-success" style="padding:5px 10px; color:#000;">Aprobada</span>
                    <?php else: ?>
                      <span class="badge badge-danger" style="padding:5px 10px; color:#000;">Rechazada</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php echo htmlspecialchars(substr($solicitud['reason'], 0, 100)); ?>
                    <?php if (strlen($solicitud['reason']) > 100): ?>...
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if (!empty($solicitud['response_comment'])): ?>
                      <small><?php echo htmlspecialchars($solicitud['response_comment']); ?></small>
                    <?php else: ?>
                      <small class="text-muted">Sin comentarios</small>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php if ($historial_solicitudes[0]['status'] == 'aprobada' && !empty($historial_solicitudes[0]['response_date'])): ?>
          <div class="alert alert-info mt-3 mb-0" style="font-size:13px;">
            <i class="fa fa-info-circle"></i>
            <strong>Última prórroga aprobada:</strong> 
            <?php echo date('d/m/Y', strtotime($historial_solicitudes[0]['response_date'])); ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
      
    </div>

    <div class="text-center mt-4 mb-4">
      <a href="panel_ctfg.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left-circle"></i> Volver al panel principal
      </a>
    </div>
  </main>
  <?php include 'footer.php'; ?>
</body>
</html>
