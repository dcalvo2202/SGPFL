<?php
// VERIFICAR AUTENTICACIÓN
include("../../login/check.php");

// Obtener base_url de la sesión (configurado durante el login)
$cds_domain = $mySessionController->getVar("cds_domain");
$cds_locate = $mySessionController->getVar("cds_locate");
$base_url = $cds_domain . $cds_locate;

// Configuración portable de rutas
$base_path = realpath(__DIR__ . '/../../../');

// Incluir archivos necesarios con rutas relativas
include_once($base_path . '/inc/db/bdcommon.inc');

// Obtener tipos de proyecto desde la base de datos
try {
    // Usar las variables de bdcommon.inc para la conexión
    $conn_tipos = new mysqli($db_host, $usuario, $clave, $db);
    if ($conn_tipos->connect_error) {
        throw new Exception("Error de conexión: " . $conn_tipos->connect_error);
    }
    $conn_tipos->set_charset("utf8");
    
    // Solo mostrar los 3 tipos principales (Tesis, Proyecto de Graduación, Seminario)
    $result = $conn_tipos->query("SELECT id, type_name, max_members FROM project_types WHERE id IN (1, 2, 3) AND active = 1 ORDER BY id");
    $project_types = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $project_types[] = $row;
        }
    } else {
        // Fallback si no hay datos en BD
        $project_types = [
            ['id' => 1, 'type_name' => 'Tesis', 'max_members' => 2],
            ['id' => 2, 'type_name' => 'Proyecto de Graduación', 'max_members' => 3],
            ['id' => 3, 'type_name' => 'Seminario', 'max_members' => 8]
        ];
    }
    
    $conn_tipos->close();
} catch (Exception $e) {
    error_log("Error al cargar tipos de proyecto: " . $e->getMessage());
    // Fallback en caso de error
    $project_types = [
        ['id' => 1, 'type_name' => 'Tesis', 'max_members' => 2],
        ['id' => 2, 'type_name' => 'Proyecto de Graduación', 'max_members' => 3],
        ['id' => 3, 'type_name' => 'Seminario', 'max_members' => 8]
    ];
}

// OBTENER USUARIO REAL AUTENTICADO
$current_user_id = $mySessionController->getVar("usuario");
$current_user_name = $mySessionController->getVar("nombre");
$current_user_rol = $mySessionController->getVar("rol");

// Verificar que sea estudiante (rol 4)
if ($current_user_rol != 4) {
    header('Location: ' . $base_url . 'dashboard.php');
    exit;
}

// Verificar estado de propuestas existentes del estudiante
$can_submit_proposal = true;
$blocked_message = '';
$existing_proposal_status = null;

try {
    $conn_check = new mysqli($db_host, $usuario, $clave, $db);
    if (!$conn_check->connect_error) {
        $conn_check->set_charset("utf8");
        
        // Buscar la propuesta más reciente del estudiante
        $sql_check = "SELECT id, status, title FROM tfg_proposals WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
        $stmt_check = $conn_check->prepare($sql_check);
        $stmt_check->bind_param("s", $current_user_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            $proposal_data = $result_check->fetch_assoc();
            $existing_proposal_status = $proposal_data['status'];
            
            // Si está en revisión o pendiente, bloquear nueva subida
            if (in_array($existing_proposal_status, ['Pendiente de Revisión', 'En Revisión', 'Cumple requisitos', 'Aprobado'])) {
                $can_submit_proposal = false;
                $blocked_message = 'Ya tienes una propuesta en estado "' . htmlspecialchars($existing_proposal_status) . '". No puedes subir otra propuesta hasta que sea rechazada o finalizada.';
            }
            // Si está rechazada, puede subir otra (no bloquear)
        }
        
        $stmt_check->close();
        $conn_check->close();
    }
} catch (Exception $e) {
    error_log("Error al verificar estado de propuesta: " . $e->getMessage());
}

// URLs portables
$panel_href = $base_url . "Panel_SubirTFG.php";
$form_action = "tfg_upload_process.php";

// Definir CSS adicionales para este formulario
$additional_css = ['inc/css/tfg_upload.css'];
?>
<!DOCTYPE html>
<html lang="es">
<!-- =============================== HEAD =============================== -->
<?php include $base_path . '/head.php'; ?>
<script>
    // Pasar datos del usuario actual a JavaScript
    window.CURRENT_USER_ID = '<?= htmlspecialchars($current_user_id) ?>';
    window.CURRENT_USER_NAME = '<?= htmlspecialchars($current_user_name) ?>';
</script>
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
                <h1 style="font-size: 2.5rem; font-weight: 700;">
                    <i class="bi bi-file-earmark-plus-fill"></i> Nueva Propuesta de TFG
                </h1>
                <p class="lead text-muted">Complete la información de su propuesta y forme su grupo de trabajo</p>
            </div>

            <?php if (!$can_submit_proposal): ?>
            <!-- Mensaje de bloqueo si ya tiene propuesta en revisión -->
            <div class="alert alert-warning" role="alert">
                <h5><i class="bi bi-exclamation-triangle-fill"></i> No puedes subir una nueva propuesta</h5>
                <p class="mb-3"><?= $blocked_message ?></p>
                <a href="<?= htmlspecialchars($panel_href) ?>" class="btn btn-primary">
                    <i class="bi bi-arrow-left"></i> Volver al Panel
                </a>
            </div>
            <?php else: ?>
            <form id="tfgGroupForm" action="<?= htmlspecialchars($form_action) ?>" method="POST" enctype="multipart/form-data">
            
            <div class="section-card">
                <div class="section-header">
                    <h3><i class="bi bi-file-text-fill"></i> Información de la Propuesta TFG</h3>
                </div>
                
                <div class="section-body">
                    <div class="form-group-tfg">
                        <label for="inp-title" class="form-label-tfg">
                            <i class="bi bi-card-heading"></i> Título de la Propuesta *
                        </label>
                        <input type="text" 
                               class="form-control-tfg" 
                               id="inp-title" 
                               name="title" 
                               maxlength="255" 
                               required
                               placeholder="Ingrese el título único de su propuesta TFG">
                        <small class="text-muted">Este título debe ser único en el sistema (10-255 caracteres)</small>
                    </div>
    
                    <div class="form-group-tfg">
                        <label for="inp-document" class="form-label-tfg">
                            <i class="bi bi-file-pdf-fill"></i> Documentos de la Propuesta *
                        </label>
                        <input type="file" 
                               class="form-control-tfg" 
                               id="inp-document" 
                               name="documents[]" 
                               accept=".pdf,.docx" 
                               multiple
                               required>
                        <small class="text-muted">Formatos permitidos: PDF, DOCX | Tamaño máximo: 10 MB por archivo | Puede seleccionar múltiples archivos</small>
                        <div id="files-list" class="mt-2"></div>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <h3><i class="bi bi-people-fill"></i> Formación del Grupo de Trabajo</h3>
                </div>
                
                <div class="section-body">
                    <div class="form-group-tfg">
                        <label for="sel-project-type" class="form-label-tfg">
                            <i class="bi bi-diagram-2-fill"></i> Tipo de Proyecto *
                        </label>
                        <select class="form-control-tfg" id="sel-project-type" name="project_type_id" required>
                            <option value="">Seleccione el tipo de proyecto</option>
                            <?php foreach ($project_types as $type): ?>
                                <option value="<?= $type['id'] ?>" data-max-members="<?= $type['max_members'] ?>">
                                    <?= htmlspecialchars($type['type_name']) ?> 
                                    (Máximo <?= $type['max_members'] ?> miembros)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group-tfg">
                        <label for="txt-project-description" class="form-label-tfg">
                            <i class="bi bi-text-paragraph"></i> Descripción del Proyecto *
                        </label>
                        <textarea class="form-control-tfg" 
                                  id="txt-project-description" 
                                  name="project_description" 
                                  rows="4" 
                                  maxlength="500" 
                                  required
                                  placeholder="Describa brevemente el alcance y objetivos del proyecto grupal (mínimo 20 caracteres)"></textarea>
                    </div>

                    <div class="form-group-tfg">
                        <label class="form-label-tfg">
                            <i class="bi bi-star-fill"></i> Líder del Proyecto
                        </label>
                        <div class="alert-tfg alert-tfg-info">
                            <i class="bi bi-info-circle"></i>
                            <div>
                                <strong>Usted será automáticamente el líder de este proyecto</strong><br>
                                <small>ID de usuario: <?= htmlspecialchars($current_user_id) ?></small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group-tfg">
                        <label for="inp-search-members" class="form-label-tfg">
                            <i class="bi bi-person-plus-fill"></i> Agregar Miembros al Grupo (Opcional)
                        </label>
                        <div style="display: flex; gap: 0;">
                            <input type="text" 
                                   class="form-control-tfg" 
                                   id="inp-search-members" 
                                   placeholder="Buscar por nombre, email o ID de estudiante"
                                   style="border-radius: 6px 0 0 6px; flex: 1;">
                            <button type="button" class="btn-tfg btn-tfg-secondary" id="btn-search-members" style="border-radius: 0 6px 6px 0;">
                                <i class="bi bi-search"></i> Buscar
                            </button>
                        </div>
                        <small class="text-muted">Puede agregar miembros ahora o después desde el panel de proyectos</small>
                    </div>

                    <div id="div-search-results" style="display: none;"></div>

                    <div class="form-group-tfg">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <h6><i class="bi bi-people"></i> Miembros del Grupo:</h6>
                            <small class="text-muted">Total: <span id="member-count">1/1</span></small>
                        </div>
                        <div id="list-selected-members">
                            </div>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <h3><i class="bi bi-check-circle-fill"></i> Confirmación y Envío</h3>
                </div>
                
                <div class="section-body">
                    <div class="alert-tfg alert-tfg-info">
                        <i class="bi bi-info-circle-fill"></i>
                        <div>
                            <strong>Al enviar esta propuesta:</strong>
                            <ul style="margin: 8px 0 0 16px;">
                                <li>Su propuesta TFG quedará con estado "Pendiente de Revisión"</li>
                                <li>Se creará automáticamente el proyecto grupal asociado</li>
                                <li>Usted será registrado como líder del proyecto</li>
                                <li>Los miembros seleccionados serán agregados al grupo</li>
                                <li>Podrá gestionar el proyecto desde el panel principal</li>
                            </ul>
                        </div>
                    </div>

                    <div class="form-group-tfg">
                        <div style="display: flex; align-items: flex-start; gap: 12px;">
                            <input type="checkbox" id="chk-terms" name="accept_terms" required style="margin-top: 4px;">
                            <label for="chk-terms" style="cursor: pointer;">
                                Acepto los términos y condiciones del Sistema de Gestión de TFG y autorizo la creación del proyecto grupal asociado *
                            </label>
                        </div>
                    </div>

                    <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                        <a href="<?= htmlspecialchars($panel_href) ?>" class="btn-tfg btn-tfg-secondary">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                        <button type="submit" class="btn-tfg btn-tfg-primary">
                            <i class="bi bi-send-fill"></i> Enviar Propuesta y Crear Grupo
                        </button>
                    </div>
                </div>
            </div>
        </form>
        <?php endif; ?>

        </div>
    </main>
    <!-- =============================== FOOTER =============================== -->
    <?php include $base_path . '/footer.php'; ?>
    <script src="<?= $base_url ?>inc/js/tfg_upload.js?v=<?= time() ?>"></script>
    <?php if ($can_submit_proposal): ?>
    <script>
    // Traducir input file a español y manejar múltiples archivos con acumulación
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('inp-document');
        const filesList = document.getElementById('files-list');
        
        if (fileInput) {
            // Array para almacenar todos los archivos acumulados
            let accumulatedFiles = [];
            
            // Guardar el input original para mantener su funcionalidad
            const originalInput = fileInput;
            
            // Crear contenedor con el estilo de casilla
            const customFileInput = document.createElement('div');
            customFileInput.className = 'custom-file-input-wrapper';
            customFileInput.style.cssText = `
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
            `;
            
            // Crear botón interno
            const selectButton = document.createElement('span');
            selectButton.style.cssText = `
                background: #034991;
                color: white;
                padding: 8px 16px;
                border-radius: 4px;
                font-weight: 500;
                font-size: 14px;
                cursor: pointer;
                user-select: none;
                flex-shrink: 0;
            `;
            selectButton.textContent = 'Seleccionar archivos';
            
            // Crear texto del nombre del archivo
            const fileNameDisplay = document.createElement('span');
            fileNameDisplay.id = 'file-name-display';
            fileNameDisplay.style.cssText = `
                color: #6c757d;
                font-size: 14px;
                flex: 1;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            `;
            fileNameDisplay.textContent = 'Puede seleccionar varios archivos';
            
            // Ensamblar el componente
            customFileInput.appendChild(selectButton);
            customFileInput.appendChild(fileNameDisplay);
            
            // Reemplazar el input original con el personalizado
            originalInput.style.display = 'none';
            originalInput.parentNode.insertBefore(customFileInput, originalInput);
            
            // Efectos hover
            customFileInput.addEventListener('mouseenter', function() {
                this.style.borderColor = '#034991';
                selectButton.style.background = '#023366';
            });
            
            customFileInput.addEventListener('mouseleave', function() {
                this.style.borderColor = '#e1e8ed';
                selectButton.style.background = '#034991';
            });
            
            // Evento click para abrir selector
            customFileInput.addEventListener('click', function(e) {
                e.stopPropagation();
                originalInput.click();
            });
            
            // Función para actualizar la visualización de archivos
            function updateFilesDisplay() {
                filesList.innerHTML = '';
                
                if (accumulatedFiles.length === 0) {
                    fileNameDisplay.textContent = 'Ningún archivo seleccionado';
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
                    const fileSize = ((accumulatedFiles[0].size || 0) / (1024 * 1024)).toFixed(2);
                    fileNameDisplay.textContent = `${fileName} (${fileSize} MB)`;
                } else {
                    const totalSizeMB = (totalSize / (1024 * 1024)).toFixed(2);
                    fileNameDisplay.textContent = `${accumulatedFiles.length} archivos seleccionados (${totalSizeMB} MB en total)`;
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
                    const size = (fileSize / (1024 * 1024)).toFixed(2);
                    const fileName = (file && file.name) ? file.name : 'Archivo';
                    
                    const fileItem = document.createElement('div');
                    fileItem.style.cssText = `
                        display: inline-flex;
                        align-items: center;
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        color: white;
                        padding: 8px 12px;
                        border-radius: 20px;
                        font-size: 13px;
                        gap: 8px;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                        transition: transform 0.2s, box-shadow 0.2s;
                    `;
                    
                    // Icono de archivo
                    const fileIcon = document.createElement('i');
                    fileIcon.className = fileName.endsWith('.pdf') ? 'bi bi-file-earmark-pdf-fill' : 'bi bi-file-earmark-word-fill';
                    fileIcon.style.fontSize = '16px';
                    
                    // Nombre y tamaño del archivo
                    const fileInfo = document.createElement('span');
                    fileInfo.style.cssText = `
                        max-width: 150px;
                        overflow: hidden;
                        text-overflow: ellipsis;
                        white-space: nowrap;
                    `;
                    fileInfo.textContent = `${fileName} (${size} MB)`;
                    fileInfo.title = fileName; // Tooltip con nombre completo
                    
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
                        this.style.background = 'rgba(255,0,0,0.7)';
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
                    fileItem.appendChild(fileInfo);
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
                    originalInput.files = dataTransfer.files;
                } catch (e) {
                    console.error('Error sincronizando archivos:', e);
                }
            }
            
            // Actualizar cuando se seleccionan archivos (acumular, no reemplazar)
            originalInput.addEventListener('change', function(e) {
                // Obtener los archivos recién seleccionados directamente del evento
                const newFiles = Array.from(e.target.files || []);
                
                if (newFiles.length > 0) {
                    // Filtrar archivos que ya existen por nombre y solo agregar los nuevos
                    newFiles.forEach(newFile => {
                        // Verificar que el archivo tenga propiedades válidas
                        if (newFile && newFile.name && typeof newFile.size === 'number' && newFile.size > 0) {
                            // Verificar si ya existe un archivo con el mismo nombre
                            const exists = accumulatedFiles.some(f => f.name === newFile.name);
                            if (!exists) {
                                accumulatedFiles.push(newFile);
                            }
                        }
                    });
                    
                    // Calcular tamaño total
                    let totalSize = 0;
                    accumulatedFiles.forEach(file => {
                        if (file && typeof file.size === 'number') {
                            totalSize += file.size;
                        }
                    });
                    
                    // Validar tamaño total (máximo 10 MB para todos los archivos)
                    if (totalSize > 10 * 1024 * 1024) {
                        alert('El tamaño total de los archivos excede 10 MB. Por favor, elimine algunos archivos.');
                    }
                    
                    // Actualizar display y sincronizar
                    updateFilesDisplay();
                    syncFilesToInput();
                }
            });
        }
    });
    </script>
    <?php endif; ?>
</body>
</html>