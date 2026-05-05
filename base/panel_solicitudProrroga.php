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
    $mensaje_estado = 'No tiene un proyecto registrado para solicitar extensión.';
} else {
    // Verificar si el proyecto está concluido o cancelado
    $conn_check = new mysqli($db_host, $usuario, $clave, $db);
    $sql_estado = "SELECT estado FROM proyecto_aprobado WHERE proposal_id = ? LIMIT 1";
    $stmt_estado = $conn_check->prepare($sql_estado);
    if ($stmt_estado) {
        $stmt_estado->bind_param("i", $proposal_id);
        $stmt_estado->execute();
        $result_estado = $stmt_estado->get_result();
        if ($row_estado = $result_estado->fetch_assoc()) {
            $estado = strtolower($row_estado['estado'] ?? '');
            if (in_array($estado, ['concluido', 'cancelado'])) {
                $puede_solicitar = false;
                $mensaje_estado = 'El proyecto está concluido. No puede solicitar extensión.';
            }
        }
        $stmt_estado->close();
    }
    $conn_check->close();
}

// Información de la prórroga a solicitar
$info_prorroga = ($proxima_prorroga == 1) 
    ? ['numero' => '1ra', 'duracion' => '1 año (365 días)']
    : ['numero' => '2da', 'duracion' => '6 meses (180 días)'];

$page_title = 'Solicitud de Prórroga';
$inlineStyles = <<<'CSS'
/* Estilos unificados (UNA) */
body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;}
.dashboard-header h1{font-weight:700;color:var(--azul-una);margin-bottom:.5rem;}
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
        <div class="card-header text-center card-header-3centered">
          Solicitud de Prórroga Proyecto
        </div>
        <div class="card-body card-body-24">
          <?php if (!empty($titulo_proyecto)): ?>
          <h4 class="text-center mb-4 text-azul-una fw-600">
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
          <div class="alert alert-border-4info mb-4">
            <strong>Estado de Prórrogas:</strong>
            <ul class="mb-0 mt-2">
              <li>Prórrogas aprobadas: <strong><?php echo $prorrogas_aprobadas; ?>/2</strong></li>
              <li>1ra Prórroga: 1 año (365 días)</li>
              <li>2da Prórroga: 6 meses (180 días)</li>
            </ul>
          </div>
          
          <?php if (!$puede_solicitar): ?>
          <!-- Mensaje de bloqueo -->
          <div class="alert alert-border-5warning text-center">
            <i class="fa fa-exclamation-triangle"></i>
            <strong><?php echo htmlspecialchars($mensaje_estado); ?></strong>
          </div>
          <?php else: ?>
          
          <!-- Información de prórroga a solicitar -->
          <div class="alert alert-border-success mb-4">
            <strong>Solicitando:</strong> <?php echo $info_prorroga['numero']; ?> Prórroga 
            (<?php echo $info_prorroga['duracion']; ?>)
          </div>
          
          <form method="POST" action="mod/admin/users/procesar_prorroga.php" id="formProrroga" enctype="multipart/form-data">
            <input type="hidden" name="proposal_id" value="<?php echo $proposal_id; ?>">
            <input type="hidden" name="extension_number" value="<?php echo $proxima_prorroga; ?>">
            
            <!-- Panel de Motivo -->
            <div class="form-group mb-4">
              <label for="motivo" class="form-6label-3accent">
                Motivo (menos de 200 palabras)
              </label>
              <textarea 
                id="motivo" 
                name="motivo" 
                class="form-control form-8textarea" 
                rows="5" 
                maxlength="200" 
                placeholder="Escriba el motivo de su solicitud de prórroga..."
                required
              ></textarea>
              <small class="mt-3sm text-muted">
                <span id="charCount">0</span>/200 caracteres
              </small>
            </div>
            
            <script>
              document.getElementById('motivo').addEventListener('input', function() {
                document.getElementById('charCount').textContent = this.value.length;
              });
            </script>
            
            <!-- Panel de Documento de Soporte -->
            <div class="form-group mb-4">
              <label class="form-6label-3accent">
                <i class="fa fa-file-pdf-o"></i> Documentos en formato PDF (opcional)
              </label>
              <input type="file" 
                     class="form-control" 
                     id="documento_prorroga" 
                     name="documento_prorroga[]" 
                     accept=".pdf,application/pdf"
                     multiple
                     style="display: none;">
              <div id="customFileInput" class="custom-file-input-wrapper" style="
                  width: 100%;
                  padding: 12px;
                  border: 2px solid #e1e8ed;
                  border-radius: 6px;
                  background: white;
                  display: flex;
                  align-items: center;
                  gap: 12px;
                  cursor: pointer;
                  transition: border-color 0.3s ease;
                  min-height: 48px;
              ">
                  <span id="selectButton" style="
                      background: #CD1719;
                      color: white;
                      padding: 8px 16px;
                      border-radius: 4px;
                      font-weight: 500;
                      font-size: 14px;
                      cursor: pointer;
                      user-select: none;
                      flex-shrink: 0;
                  ">Seleccionar archivos</span>
                  <span id="fileNameDisplay" style="
                      color: #6c757d;
                      font-size: 14px;
                      flex: 1;
                      overflow: hidden;
                      text-overflow: ellipsis;
                      white-space: nowrap;
                  ">Puede seleccionar varios archivos PDF</span>
              </div>
              <small class="mt-3sm text-muted">
                <i class="fa fa-info-circle"></i> Tamaño máximo: <strong>20 MB por archivo</strong>. Solo archivos PDF. Puede seleccionar múltiples archivos.
              </small>
              <div id="filesList" class="mt-2"></div>
            </div>
            
            <!-- Botón Aceptar -->
            <div class="text-center mt-4">
              <button type="submit" class="btn btn-success">
                Enviar Solicitud
              </button>
            </div>
          </form>
          
          <script>
          // Array para almacenar todos los archivos acumulados
          let accumulatedFiles = [];
          
          const fileInput = document.getElementById('documento_prorroga');
          const customFileInput = document.getElementById('customFileInput');
          const selectButton = document.getElementById('selectButton');
          const fileNameDisplay = document.getElementById('fileNameDisplay');
          const filesList = document.getElementById('filesList');
          
          // Efectos hover para el input personalizado
          customFileInput.addEventListener('mouseenter', function() {
              this.style.borderColor = '#CD1719';
              selectButton.style.background = '#a81315';
          });
          
          customFileInput.addEventListener('mouseleave', function() {
              this.style.borderColor = '#e1e8ed';
              selectButton.style.background = '#CD1719';
          });
          
          // Click para abrir selector de archivos
          customFileInput.addEventListener('click', function(e) {
              e.stopPropagation();
              fileInput.click();
          });
          
          function formatFileSize(bytes) {
              if (bytes === 0) return '0 Bytes';
              const k = 1024;
              const sizes = ['Bytes', 'KB', 'MB', 'GB'];
              const i = Math.floor(Math.log(bytes) / Math.log(k));
              return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
          }
          
          // Función para actualizar la visualización de archivos
          function updateFilesDisplay() {
              filesList.innerHTML = '';
              
              if (accumulatedFiles.length === 0) {
                  fileNameDisplay.textContent = 'Puede seleccionar varios archivos PDF';
                  fileNameDisplay.style.color = '#6c757d';
                  return;
              }
              
              // Calcular tamaño total
              let totalSize = 0;
              accumulatedFiles.forEach(file => {
                  if (file && typeof file.size === 'number') {
                      totalSize += file.size;
                  }
              });
              
              // Actualizar texto del display
              if (accumulatedFiles.length === 1) {
                  const fileName = accumulatedFiles[0].name || 'Archivo';
                  const fileSize = (accumulatedFiles[0] && typeof accumulatedFiles[0].size === 'number') ? accumulatedFiles[0].size : 0;
                  fileNameDisplay.textContent = `${fileName} (${formatFileSize(fileSize)})`;
              } else {
                  fileNameDisplay.textContent = `${accumulatedFiles.length} archivos seleccionados (${formatFileSize(totalSize)} en total)`;
              }
              fileNameDisplay.style.color = '#2c3e50';
              
              // Crear contenedor de archivos con estilo de grid
              const filesContainer = document.createElement('div');
              filesContainer.style.cssText = `
                  display: flex;
                  flex-wrap: wrap;
                  gap: 8px;
                  margin-top: 8px;
              `;
              
              accumulatedFiles.forEach((file, index) => {
                  const fileSize = (file && typeof file.size === 'number') ? file.size : 0;
                  const size = formatFileSize(fileSize);
                  const fileName = (file && file.name) ? file.name : 'Archivo';
                  
                  const fileItem = document.createElement('div');
                  fileItem.style.cssText = `
                      display: inline-flex;
                      align-items: center;
                      background: linear-gradient(135deg, #CD1719 0%, #8B0000 100%);
                      color: white;
                      padding: 8px 12px;
                      border-radius: 20px;
                      font-size: 13px;
                      gap: 8px;
                      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                      transition: transform 0.2s, box-shadow 0.2s;
                  `;
                  
                  // Icono de archivo PDF
                  const fileIcon = document.createElement('i');
                  fileIcon.className = 'fa fa-file-pdf-o';
                  fileIcon.style.fontSize = '16px';
                  
                  // Nombre y tamaño del archivo
                  const fileInfoSpan = document.createElement('span');
                  fileInfoSpan.style.cssText = `
                      max-width: 150px;
                      overflow: hidden;
                      text-overflow: ellipsis;
                      white-space: nowrap;
                  `;
                  fileInfoSpan.textContent = `${fileName} (${size})`;
                  fileInfoSpan.title = fileName;
                  
                  // Botón X para eliminar
                  const removeBtn = document.createElement('button');
                  removeBtn.type = 'button';
                  removeBtn.innerHTML = '&times;';
                  removeBtn.style.cssText = `
                      background: rgba(255,255,255,0.3);
                      border: none;
                      color: white;
                      width: 22px;
                      height: 22px;
                      border-radius: 50%;
                      cursor: pointer;
                      font-size: 16px;
                      font-weight: bold;
                      display: flex;
                      align-items: center;
                      justify-content: center;
                      padding: 0;
                      line-height: 1;
                      transition: background 0.2s, transform 0.2s;
                  `;
                  removeBtn.title = 'Eliminar archivo';
                  
                  // Efectos hover para el botón X
                  removeBtn.addEventListener('mouseenter', function() {
                      this.style.background = 'rgba(0,0,0,0.5)';
                      this.style.transform = 'scale(1.1)';
                  });
                  removeBtn.addEventListener('mouseleave', function() {
                      this.style.background = 'rgba(255,255,255,0.3)';
                      this.style.transform = 'scale(1)';
                  });
                  
                  // Evento para eliminar archivo
                  removeBtn.addEventListener('click', function(e) {
                      e.stopPropagation();
                      accumulatedFiles.splice(index, 1);
                      updateFilesDisplay();
                      syncFilesToInput();
                  });
                  
                  // Hover effect para el item completo
                  fileItem.addEventListener('mouseenter', function() {
                      this.style.transform = 'translateY(-2px)';
                      this.style.boxShadow = '0 4px 8px rgba(0,0,0,0.2)';
                  });
                  fileItem.addEventListener('mouseleave', function() {
                      this.style.transform = 'translateY(0)';
                      this.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
                  });
                  
                  fileItem.appendChild(fileIcon);
                  fileItem.appendChild(fileInfoSpan);
                  fileItem.appendChild(removeBtn);
                  filesContainer.appendChild(fileItem);
              });
              
              filesList.appendChild(filesContainer);
          }
          
          // Función para sincronizar archivos al input (usando DataTransfer)
          function syncFilesToInput() {
              try {
                  const dataTransfer = new DataTransfer();
                  accumulatedFiles.forEach(file => {
                      dataTransfer.items.add(file);
                  });
                  fileInput.files = dataTransfer.files;
              } catch (e) {
                  console.error('Error sincronizando archivos:', e);
              }
          }
          
          // Mostrar información de los archivos seleccionados (múltiples con acumulación)
          fileInput.addEventListener('change', function(e) {
              const newFiles = Array.from(e.target.files || []);
              
              if (newFiles.length > 0) {
                  for (let i = 0; i < newFiles.length; i++) {
                      const file = newFiles[i];
                      
                      // Verificar que el archivo tenga propiedades válidas
                      if (!file || !file.name || typeof file.size !== 'number') {
                          continue;
                      }
                      
                      // Verificar que el archivo no esté vacío (0 bytes)
                      if (file.size === 0) {
                          alert('El archivo "' + file.name + '" está vacío.');
                          continue;
                      }
                      
                      // Validar tamaño individual (20MB)
                      if (file.size > 20 * 1024 * 1024) {
                          alert('El archivo "' + file.name + '" excede el tamaño máximo de 20 MB.');
                          continue;
                      }
                      
                      // Validar tipo
                      if (file.type !== 'application/pdf') {
                          alert('El archivo "' + file.name + '" no es un PDF válido.');
                          continue;
                      }
                      
                      // Verificar si ya existe un archivo con el mismo nombre
                      const exists = accumulatedFiles.some(f => f.name === file.name);
                      if (!exists) {
                          accumulatedFiles.push(file);
                      }
                  }
                  
                  // Actualizar display y sincronizar
                  updateFilesDisplay();
                  syncFilesToInput();
              }
          });
          </script>
          <?php endif; ?>
        </div>
      </div>
      
      <!-- Historial de Solicitudes -->
      <?php if (!empty($historial_solicitudes)): ?>
      <div class="card form-card shadow-sm mx-auto mt-4" style="max-width: 800px;">
        <div class="card-header text-center card-header-3centered">
          <i class="fa fa-history"></i> Historial de Solicitudes
        </div>
        <div class="card-body card-body-24">
          <div class="table-responsive">
            <table class="table table-bordered table-hover">
              <thead class="bg-azul-una text-white">
                <tr>
                  <th class="text-center width-md">Prórroga</th>
                  <th class="text-center width-9wide">Fecha Solicitud</th>
                  <th class="text-center width-10sm">Estado</th>
                  <th>Motivo</th>
                  <th class="width-narrow">Comentarios</th>
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
                      <span class="badge badge-success-custom">Aprobada</span>
                    <?php else: ?>
                      <span class="badge badge-danger-custom">Rechazada</span>
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
          <div class="alert alert-info mt-3 mb-0">
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
