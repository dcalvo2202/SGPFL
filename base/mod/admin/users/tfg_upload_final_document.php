<?php
// VERIFICAR AUTENTICACIÓN
include("../../login/check.php");

// Obtener base_url de la sesión
$cds_domain = $mySessionController->getVar("cds_domain");
$cds_locate = $mySessionController->getVar("cds_locate");
$base_url = $cds_domain . $cds_locate;

// Configuración portable de rutas
$base_path = realpath(__DIR__ . '/../../../');

// OBTENER USUARIO REAL AUTENTICADO
$current_user_id = $mySessionController->getVar("usuario");
$current_user_name = $mySessionController->getVar("nombre");
$current_user_rol = $mySessionController->getVar("rol");

// Verificar que sea estudiante (rol 4)
if ($current_user_rol != 4) {
    header('Location: ' . $base_url . 'dashboard.php');
    exit;
}

// Incluir funciones necesarias
require_once(__DIR__ . '/../../../inc/tfg_final_functions.php');

// Verificar si puede subir documento final
$upload_check = canUploadFinalDocument($current_user_id);

// URLs portables
$panel_href = $base_url . "Panel_SubirTFG.php";
$form_action = "tfg_upload_final_process.php";

// Definir CSS adicionales para este formulario
$additional_css = ['inc/css/tfg_upload.css'];
?>
<!DOCTYPE html>
<html lang="es">
<!-- =============================== HEAD =============================== -->
<?php include $base_path . '/head.php'; ?>
<style>
/* Forzar color blanco en headers azules - debe cargarse después de todos los CSS */
.section-card .section-header *,
.section-card .section-header h1,
.section-card .section-header h2,
.section-card .section-header h3,
.section-card .section-header h4,
.section-card .section-header h5,
.section-card .section-header h6 {
    color: white !important;
}
</style>
<body class="d-flex flex-column min-vh-100 fondo-una">
    <!-- =============================== HEADER =============================== -->
    <?php include $base_path . '/header.php'; ?>
    <!-- =============================== CONTENIDO PRINCIPAL =============================== -->
    <main class="flex-fill">
        <div class="container my-5">
            
            <div class="dashboard-header text-center mb-4">
                <h1>
                    <i class="bi bi-file-earmark-check-fill"></i> Subir Documento Final del TFG
                </h1>
                <p class="lead text-muted">Versión final del TFG para revisión previa a la defensa pública</p>
            </div>

            <?php if (!$upload_check['can_upload']): ?>
                <!-- Mostrar mensaje de error si no puede subir -->
                <?php if (isset($upload_check['is_rejected']) && $upload_check['is_rejected']): ?>
                    <!-- Caso especial: Documento rechazado - Redirigir a HU-020 -->
                    <div class="alert alert-danger" role="alert">
                        <h5><i class="bi bi-exclamation-triangle-fill"></i> Documento Final Rechazado</h5>
                        <p class="mb-3"><?= htmlspecialchars($upload_check['message']) ?></p>
                        <div class="d-flex gap-2">
                            <a href="<?= $base_url ?>mod/admin/users/tfg_upload_correction.php?id=<?= $upload_check['document_id'] ?>" class="btn btn-warning">
                                <i class="bi bi-file-earmark-arrow-up-fill"></i> Subir Correcciones (HU-020)
                            </a>
                            <a href="<?= htmlspecialchars($panel_href) ?>" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Volver al Panel
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Otros casos de bloqueo -->
                    <div class="alert alert-danger" role="alert">
                        <h5><i class="bi bi-x-circle-fill"></i> No puedes subir el documento final</h5>
                        <p class="mb-3"><?= htmlspecialchars($upload_check['message']) ?></p>
                        <a href="<?= htmlspecialchars($panel_href) ?>" class="btn btn-primary">
                            <i class="bi bi-arrow-left"></i> Volver al Panel
                        </a>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <!-- Información del estado del proyecto -->
                <div class="alert alert-info mb-4" role="alert">
                    <h5 class="mb-3"><i class="bi bi-info-circle-fill"></i> Estado de tu Proyecto</h5>
                    <p class="mb-2">
                        <strong>Estado:</strong> 
                        <span class="status-badge status-<?= strtolower(str_replace([' ', 'ó'], ['-', 'o'], $upload_check['project_status'])) ?>">
                            <i class="bi bi-<?= ($upload_check['project_status'] === 'Vigente') ? 'check-circle-fill' : (($upload_check['project_status'] === 'Prórroga Activa') ? 'exclamation-triangle-fill' : 'x-circle-fill') ?>"></i>
                            <?= htmlspecialchars($upload_check['project_status']) ?>
                        </span>
                    </p>
                    <?php if ($upload_check['project_status'] === 'Prórroga Activa'): ?>
                        <p class="mb-0 mt-2 text-warning">
                            <i class="bi bi-exclamation-triangle-fill"></i> Tu proyecto tiene una prórroga activa. Asegúrate de cumplir con la nueva fecha límite.
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Requisitos del documento -->
                <div class="alert alert-warning mb-4" role="alert">
                    <h5 class="mb-3"><i class="bi bi-exclamation-triangle-fill"></i> Requisitos del Documento Final</h5>
                    <p><strong>Según la Tabla 4 de la Instrucción de Dirección, tu documento debe incluir:</strong></p>
                    <ul>
                        <li><strong>Capítulo I:</strong> Introducción (Antecedentes, Delimitación, Justificación, Objetivos, Contribuciones)</li>
                        <li><strong>Capítulo II:</strong> Marco Teórico (Revisión de Literatura, Conceptos Clave)</li>
                        <li><strong>Capítulo III:</strong> Metodología</li>
                        <li><strong>Capítulo IV:</strong> Resultados</li>
                        <li><strong>Capítulo V:</strong> Conclusiones y Recomendaciones</li>
                        <li><strong>Referencias Bibliográficas:</strong> En formato APA (última versión)</li>
                    </ul>
                    <p class="mb-0">
                        <i class="bi bi-check-circle"></i> <strong>Firma del Tutor:</strong> 
                        Asegúrate de que el documento incluye la firma digital del tutor en la portada. 
                        La CTFG verificará esto manualmente.
                    </p>
                </div>

                <!-- Formulario de subida -->
                <form id="tfgFinalForm" action="<?= htmlspecialchars($form_action) ?>" method="POST" enctype="multipart/form-data">
                    
                    <input type="hidden" name="proposal_id" value="<?= htmlspecialchars($upload_check['proposal_id']) ?>">
                    <input type="hidden" name="project_status" value="<?= htmlspecialchars($upload_check['project_status']) ?>">
                    
                    <div class="section-card">
                        <div class="section-header">
                            <h3><i class="bi bi-file-pdf"></i> Documento Final del TFG</h3>
                        </div>
                        <div class="section-body">
                            
                            <div class="mb-4">
                                <label for="document" class="form-label required">
                                    <i class="bi bi-file-earmark-pdf"></i> Documentos en formato PDF
                                </label>
                                <input type="file" 
                                       class="form-control form-control-lg" 
                                       id="document" 
                                       name="documents[]" 
                                       accept="application/pdf"
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
                                <div class="form-text">
                                    <i class="bi bi-info-circle"></i> 
                                    Tamaño máximo: <strong>20 MB por archivo</strong>. Solo archivos PDF. Puede seleccionar múltiples archivos.
                                </div>
                                <div id="filesList" class="mt-2"></div>
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">
                                    <i class="bi bi-chat-left-text"></i> Notas adicionales (opcional)
                                </label>
                                <textarea class="form-control" 
                                          id="notes" 
                                          name="notes" 
                                          rows="3" 
                                          placeholder="Comentarios o aclaraciones sobre el documento..."></textarea>
                                <div class="form-text">
                                    <i class="bi bi-info-circle"></i> 
                                    La CTFG verificará manualmente que el documento cumple con todos los requisitos de la Tabla 4.
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="<?= htmlspecialchars($panel_href) ?>" class="btn btn-secondary btn-lg">
                            <i class="bi bi-arrow-left"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-danger btn-lg" id="btnSubmit">
                            <i class="bi bi-upload"></i> Subir Documento Final
                        </button>
                    </div>

                </form>
            <?php endif; ?>

        </div>
    </main>

    <!-- =============================== FOOTER =============================== -->
    <?php include $base_path . '/footer.php'; ?>

    <?php if ($upload_check['can_upload']): ?>
    <script>
        // Array para almacenar todos los archivos acumulados
        let accumulatedFiles = [];
        
        const fileInput = document.getElementById('document');
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
            
            // Calcular tamaño total de forma segura
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
                fileIcon.className = 'bi bi-file-earmark-pdf-fill';
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
                fileInfoSpan.title = fileName; // Tooltip con nombre completo
                
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
            // Obtener los archivos recién seleccionados directamente del evento
            const newFiles = Array.from(e.target.files || []);
            
            if (newFiles.length > 0) {
                let hasError = false;
                
                // Validar cada archivo nuevo
                for (let i = 0; i < newFiles.length; i++) {
                    const file = newFiles[i];
                    
                    // Verificar que el archivo tenga propiedades válidas
                    if (!file || !file.name || typeof file.size !== 'number') {
                        continue;
                    }
                    
                    // Verificar que el archivo no esté vacío (0 bytes)
                    if (file.size === 0) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Archivo vacío',
                            text: `El archivo "${file.name}" está vacío (0 bytes) y no puede ser subido`,
                            confirmButtonColor: '#CD1719'
                        });
                        hasError = true;
                        break;
                    }
                    
                    // Validar tamaño individual (20MB)
                    if (file.size > 20 * 1024 * 1024) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Archivo muy grande',
                            text: `El archivo "${file.name}" excede el tamaño máximo de 20 MB`,
                            confirmButtonColor: '#CD1719'
                        });
                        hasError = true;
                        break;
                    }
                    
                    // Validar tipo
                    if (file.type !== 'application/pdf') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Tipo de archivo no válido',
                            text: `El archivo "${file.name}" no es un PDF válido`,
                            confirmButtonColor: '#CD1719'
                        });
                        hasError = true;
                        break;
                    }
                    
                    // Verificar si ya existe un archivo con el mismo nombre
                    const exists = accumulatedFiles.some(f => f.name === file.name);
                    if (!exists) {
                        accumulatedFiles.push(file);
                    }
                }
                
                if (hasError) {
                    return;
                }
                
                // Actualizar display y sincronizar
                updateFilesDisplay();
                syncFilesToInput();
            }
        });
        
        // Manejo del formulario
        document.getElementById('tfgFinalForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validar que se haya seleccionado al menos un archivo
            if (accumulatedFiles.length === 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Archivo requerido',
                    text: 'Debes seleccionar al menos un archivo PDF',
                    confirmButtonColor: '#CD1719'
                });
                return;
            }
            
            const filesCount = accumulatedFiles.length;
            
            // Confirmar subida
            Swal.fire({
                title: '¿Subir documento(s) final(es)?',
                html: `
                    <p>Estás a punto de subir <strong>${filesCount} documento(s)</strong> final(es) de tu TFG.</p>
                    <p><strong>Una vez subido(s), será(n) enviado(s) a la CTFG para revisión.</strong></p>
                    <p>La CTFG verificará manualmente que el documento cumple con todos los requisitos de la Tabla 4.</p>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#CD1719',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, subir documento',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Mostrar loading
                    Swal.fire({
                        title: 'Subiendo documento...',
                        html: 'Por favor espera mientras se procesa el archivo',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Enviar formulario
                    const formData = new FormData(this);
                    
                    fetch(this.action, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        Swal.close();
                        
                        if (data.success) {

                            // Notificar al estudiante y la secretaria
                            fetch('send_tfg_mail.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: 'tipo=Documento Final TFG'
                            });

                            // Mostrar éxito
                            Swal.fire({
                                icon: 'success',
                                title: '¡Documento subido!',
                                html: data.message + '<br><br>La CTFG ha sido notificada y procederá con la revisión.',
                                confirmButtonColor: '#034991'
                            }).then(() => {
                                window.location.href = '<?= $panel_href ?>?success=final_uploaded';
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error al subir',
                                text: data.message,
                                confirmButtonColor: '#CD1719'
                            });
                        }
                    })
                    .catch(error => {
                        Swal.close();
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Ocurrió un error al procesar la solicitud',
                            confirmButtonColor: '#CD1719'
                        });
                        console.error('Error:', error);
                    });
                }
            });
        });
    </script>
    <?php endif; ?>
</body>
</html>