<?php
// HU-020: Subir versiones corregidas del documento TFG

$base_path = realpath(__DIR__ . '/../../../');

// IMPORTANTE: Guardar variables de BD ANTES de include check.php
// porque check.php sobrescribe $usuario con el usuario de sesión
require_once($base_path . '/inc/db/bdcommon.inc');
$db_user = $usuario;  // Guardar 'root'
$db_pass = $clave;    // Guardar ''
$db_name = $db;       // Guardar 'base_db'

include("../../login/check.php");

// Obtener variables de sesión
$current_user_id = $mySessionController->getVar("usuario");
$current_user_rol = $mySessionController->getVar("rol");
$cds_domain = $mySessionController->getVar("cds_domain");
$cds_locate = $mySessionController->getVar("cds_locate");
$base_url = $cds_domain . $cds_locate;

// Verificar que sea estudiante (rol 4)
if ($current_user_rol != 4) {
    header('Location: ' . $base_url . 'dashboard.php');
    exit;
}

// Obtener document_id de la URL
$document_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($document_id <= 0) {
    header('Location: ' . $base_url . 'panel_estudiante.php');
    exit;
}

// Obtener información del documento y las observaciones
try {
    // Incluir variables de BD para evitar conflictos con $usuario de sesión
    include($base_path . '/inc/db/bdcommon.inc');
    
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }
    $conn->set_charset("utf8");
    
    // Verificar que el documento pertenece al estudiante y está rechazado
    $sql = "SELECT fd.id, fd.proposal_id, fd.status, tp.title, 
                   (SELECT corrections_summary FROM tfg_document_reviews 
                    WHERE document_id = fd.id AND review_type = 'Revision CTFG' 
                    ORDER BY reviewed_at DESC LIMIT 1) as corrections_summary,
                   (SELECT COUNT(*) FROM tfg_document_reviews 
                    WHERE document_id = fd.id AND review_type = 'Correccion Estudiante') as corrections_count
            FROM tfg_final_documents fd
            INNER JOIN tfg_proposals tp ON fd.proposal_id = tp.id
            WHERE fd.id = ? AND fd.submitted_by = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $document_id, $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('Location: ' . $base_url . 'panel_estudiante.php');
        exit;
    }
    
    $document = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    // Verificar que el documento esté rechazado
    if ($document['status'] !== 'Rechazado') {
        header('Location: ' . $base_url . 'panel_estudiante.php');
        exit;
    }
    
} catch (Exception $e) {
    die("Error: " . htmlspecialchars($e->getMessage()));
}

$additional_css = ['inc/css/tfg_upload.css'];
$page_title = "Subir Correcciones - TFG";
?>
<!DOCTYPE html>
<html lang="es">
<?php include $base_path . '/head.php'; ?>
<script>
    window.CURRENT_USER_ID = '<?= htmlspecialchars($current_user_id) ?>';
    window.DOCUMENT_ID = <?= $document_id ?>;
    window.MAX_WORDS = 500;
</script>
<body class="d-flex flex-column min-vh-100 fondo-una">
    <?php include $base_path . '/header.php'; ?>

    <main class="flex-fill">
        <div class="container my-5">
            <div class="dashboard-header text-center mb-4">
                <h1><i class="bi bi-file-earmark-arrow-up-fill"></i> Subir Correcciones del TFG</h1>
                <p class="lead">Documento: <strong><?= htmlspecialchars($document['title']) ?></strong></p>
                <p class="text-muted">Corrección número: <strong><?= ($document['corrections_count'] + 1) ?></strong></p>
            </div>

            <!-- Observaciones del CTFG -->
            <div class="section-card mb-4">
                <div class="section-header">
                    <h3><i class="bi bi-chat-left-text-fill"></i> Observaciones del CTFG</h3>
                </div>
                <div class="section-body">
                    <div class="alert alert-warning">
                        <strong><i class="bi bi-exclamation-triangle-fill"></i> Correcciones Requeridas:</strong>
                        <p class="mt-2" style="white-space: pre-wrap;"><?= htmlspecialchars($document['corrections_summary'] ?? 'No se encontraron observaciones.') ?></p>
                    </div>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle-fill"></i> <strong>Importante:</strong> Debe subir <u>todos</u> los archivos nuevamente, no solo los archivos que fueron modificados.
                    </div>
                </div>
            </div>

            <form id="correctionForm" action="tfg_upload_correction_process.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="document_id" value="<?= $document_id ?>">
                
                <!-- Documento Corregido -->
                <div class="section-card">
                    <div class="section-header">
                        <h3><i class="bi bi-file-pdf-fill"></i> Documento Corregido</h3>
                    </div>
                    <div class="section-body">
                        <div class="form-group-tfg">
                            <label for="inp-document" class="form-label-tfg">
                                <i class="bi bi-upload"></i> Subir Documento(s) Corregido(s) (PDF) *
                            </label>
                            <input type="file" 
                                   class="form-control-tfg" 
                                   id="inp-document" 
                                   name="documents[]" 
                                   accept=".pdf"
                                   multiple
                                   required
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
                                    background: #034991;
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
                            <small class="text-muted">Solo PDF | Tamaño máximo: 20 MB por archivo | Puede seleccionar múltiples archivos</small>
                            <div id="filesList" class="mt-2"></div>
                        </div>

                        <div class="form-group-tfg">
                            <label for="txt-corrections-summary" class="form-label-tfg">
                                <i class="bi bi-list-check"></i> Resumen de Cambios Realizados *
                            </label>
                            <textarea class="form-control-tfg" 
                                      id="txt-corrections-summary" 
                                      name="corrections_summary" 
                                      rows="6" 
                                      required
                                      placeholder="Describa los cambios realizados para atender las observaciones del CTFG (máximo 500 palabras)"></textarea>
                            <small class="text-muted">Palabras: <span id="word-count">0</span> / 500</small>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="chk-all-addressed" 
                                   name="all_addressed" 
                                   required>
                            <label class="form-check-label" for="chk-all-addressed">
                                <strong>Confirmo que he atendido TODAS las observaciones del CTFG *</strong>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Botón de envío -->
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-lg btn-primary px-5">
                        <i class="bi bi-send-fill"></i> Enviar Correcciones
                    </button>
                    <a href="<?= $base_url ?>Panel_SubirTFG.php" class="btn btn-lg btn-secondary px-5 ms-3">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </main>

    <?php include $base_path . '/footer.php'; ?>

    <script>
    // Contador de palabras
    const textarea = document.getElementById('txt-corrections-summary');
    const wordCountSpan = document.getElementById('word-count');
    
    textarea.addEventListener('input', function() {
        const text = this.value.trim();
        const words = text ? text.split(/\s+/).length : 0;
        wordCountSpan.textContent = words;
        
        if (words > window.MAX_WORDS) {
            wordCountSpan.classList.add('text-danger');
            wordCountSpan.classList.remove('text-muted');
        } else {
            wordCountSpan.classList.add('text-muted');
            wordCountSpan.classList.remove('text-danger');
        }
    });

    // =============================== MULTI-FILE UPLOAD ===============================
    let accumulatedFiles = [];
    
    const fileInput = document.getElementById('inp-document');
    const customFileInput = document.getElementById('customFileInput');
    const selectButton = document.getElementById('selectButton');
    const fileNameDisplay = document.getElementById('fileNameDisplay');
    const filesList = document.getElementById('filesList');
    
    // Efectos hover
    customFileInput.addEventListener('mouseenter', function() {
        this.style.borderColor = '#034991';
        selectButton.style.background = '#023366';
    });
    customFileInput.addEventListener('mouseleave', function() {
        this.style.borderColor = '#e1e8ed';
        selectButton.style.background = '#034991';
    });
    
    // Click para abrir selector
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
    
    function updateFilesDisplay() {
        filesList.innerHTML = '';
        
        if (accumulatedFiles.length === 0) {
            fileNameDisplay.textContent = 'Puede seleccionar varios archivos PDF';
            fileNameDisplay.style.color = '#6c757d';
            return;
        }
        
        let totalSize = 0;
        accumulatedFiles.forEach(file => { if (file && typeof file.size === 'number') totalSize += file.size; });
        
        if (accumulatedFiles.length === 1) {
            fileNameDisplay.textContent = `${accumulatedFiles[0].name} (${formatFileSize(accumulatedFiles[0].size)})`;
        } else {
            fileNameDisplay.textContent = `${accumulatedFiles.length} archivos seleccionados (${formatFileSize(totalSize)} en total)`;
        }
        fileNameDisplay.style.color = '#2c3e50';
        
        const filesContainer = document.createElement('div');
        filesContainer.style.cssText = 'display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px;';
        
        accumulatedFiles.forEach((file, index) => {
            const size = formatFileSize(file.size || 0);
            const fileName = file.name || 'Archivo';
            
            const fileItem = document.createElement('div');
            fileItem.style.cssText = 'display: inline-flex; align-items: center; background: linear-gradient(135deg, #034991 0%, #023366 100%); color: white; padding: 8px 12px; border-radius: 20px; font-size: 13px; gap: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: transform 0.2s, box-shadow 0.2s;';
            
            const fileIcon = document.createElement('i');
            fileIcon.className = 'bi bi-file-earmark-pdf-fill';
            fileIcon.style.fontSize = '16px';
            
            const fileInfoSpan = document.createElement('span');
            fileInfoSpan.style.cssText = 'max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;';
            fileInfoSpan.textContent = `${fileName} (${size})`;
            fileInfoSpan.title = fileName;
            
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.innerHTML = '&times;';
            removeBtn.style.cssText = 'background: rgba(255,255,255,0.3); border: none; color: white; width: 22px; height: 22px; border-radius: 50%; cursor: pointer; font-size: 16px; font-weight: bold; display: flex; align-items: center; justify-content: center; padding: 0; line-height: 1; transition: background 0.2s, transform 0.2s;';
            removeBtn.title = 'Eliminar archivo';
            
            removeBtn.addEventListener('mouseenter', function() { this.style.background = 'rgba(255,0,0,0.7)'; this.style.transform = 'scale(1.1)'; });
            removeBtn.addEventListener('mouseleave', function() { this.style.background = 'rgba(255,255,255,0.3)'; this.style.transform = 'scale(1)'; });
            removeBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                accumulatedFiles.splice(index, 1);
                updateFilesDisplay();
                syncFilesToInput();
            });
            
            fileItem.addEventListener('mouseenter', function() { this.style.transform = 'translateY(-2px)'; this.style.boxShadow = '0 4px 8px rgba(0,0,0,0.2)'; });
            fileItem.addEventListener('mouseleave', function() { this.style.transform = 'translateY(0)'; this.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)'; });
            
            fileItem.appendChild(fileIcon);
            fileItem.appendChild(fileInfoSpan);
            fileItem.appendChild(removeBtn);
            filesContainer.appendChild(fileItem);
        });
        
        filesList.appendChild(filesContainer);
    }
    
    function syncFilesToInput() {
        try {
            const dataTransfer = new DataTransfer();
            accumulatedFiles.forEach(file => dataTransfer.items.add(file));
            fileInput.files = dataTransfer.files;
        } catch (e) { console.error('Error sincronizando archivos:', e); }
    }
    
    fileInput.addEventListener('change', function(e) {
        const newFiles = Array.from(e.target.files || []);
        if (newFiles.length > 0) {
            let hasError = false;
            for (const file of newFiles) {
                if (!file || !file.name || typeof file.size !== 'number') continue;
                if (file.size === 0) {
                    Swal.fire({ icon: 'error', title: 'Archivo vacío', text: `El archivo "${file.name}" está vacío (0 bytes).`, confirmButtonColor: '#034991' });
                    hasError = true; break;
                }
                if (file.size > 20 * 1024 * 1024) {
                    Swal.fire({ icon: 'error', title: 'Archivo muy grande', text: `El archivo "${file.name}" excede 20 MB.`, confirmButtonColor: '#034991' });
                    hasError = true; break;
                }
                if (file.type !== 'application/pdf') {
                    Swal.fire({ icon: 'error', title: 'Tipo no válido', text: `El archivo "${file.name}" no es un PDF válido.`, confirmButtonColor: '#034991' });
                    hasError = true; break;
                }
                const exists = accumulatedFiles.some(f => f.name === file.name);
                if (!exists) accumulatedFiles.push(file);
            }
            if (!hasError) {
                updateFilesDisplay();
                syncFilesToInput();
            }
        }
    });

    // =============================== FORM SUBMIT ===============================
    document.getElementById('correctionForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const text = textarea.value.trim();
        const words = text ? text.split(/\s+/).length : 0;
        
        if (words > window.MAX_WORDS) {
            Swal.fire({
                icon: 'error',
                title: 'Resumen muy largo',
                text: `El resumen excede el límite de ${window.MAX_WORDS} palabras (actualmente: ${words} palabras).`
            });
            return false;
        }

        if (accumulatedFiles.length === 0) {
            Swal.fire({
                icon: 'error',
                title: 'Archivo requerido',
                text: 'Debe seleccionar al menos un archivo PDF con las correcciones.'
            });
            return false;
        }
        
        // Enviar formulario con AJAX
        const formData = new FormData(this);
        
        Swal.fire({
            title: 'Enviando correcciones...',
            text: 'Por favor espere',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        fetch('tfg_upload_correction_process.php', {
            method: 'POST',
            body: formData
        })
        .then(async response => {
            const text = await response.text();

            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Respuesta no JSON del servidor:', text);
                throw new Error('El servidor no devolvió JSON válido. Revise la consola del navegador.');
            }
        })
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: data.message,
                    html: `<p style="margin: 15px 0; font-size: 15px; color: #666;">${data.details}</p>
                           <div style="background-color: #f0f4f8; padding: 15px; border-radius: 8px; margin-top: 15px; text-align: left;">
                               <p style="margin: 5px 0;"><strong>Versión del documento:</strong> ${data.version}</p>
                               <p style="margin: 5px 0;"><strong>Ciclo de corrección:</strong> ${data.corrections_count}</p>
                           </div>`,
                    confirmButtonText: 'Ir al panel principal',
                    confirmButtonColor: '#034991',
                    allowOutsideClick: false,
                    didOpen: () => {
                        setTimeout(() => {
                            Swal.getConfirmButton().focus();
                        }, 100);
                    }
                }).then(() => {
                    window.location.href = '<?= $base_url ?>Panel_SubirTFG.php';
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al enviar',
                    text: data.message,
                    confirmButtonColor: '#034991'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: error.message || 'No se pudo enviar las correcciones. Por favor, intente nuevamente.',
                confirmButtonColor: '#034991'
            });
        });
    });
    </script>
</body>
</html>
