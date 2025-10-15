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
                <h1 style="font-size: 2.5rem; font-weight: 700;">
                    <i class="bi bi-file-earmark-check-fill"></i> Subir Documento Final del TFG
                </h1>
                <p class="lead text-muted">Versión final del TFG para revisión previa a la defensa pública</p>
            </div>

            <?php if (!$upload_check['can_upload']): ?>
                <!-- Mostrar mensaje de error si no puede subir -->
                <div class="alert alert-danger" role="alert">
                    <h5><i class="bi bi-x-circle-fill"></i> No puedes subir el documento final</h5>
                    <p class="mb-3"><?= htmlspecialchars($upload_check['message']) ?></p>
                    <a href="<?= htmlspecialchars($panel_href) ?>" class="btn btn-primary">
                        <i class="bi bi-arrow-left"></i> Volver al Panel
                    </a>
                </div>
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
                                    <i class="bi bi-file-earmark-pdf"></i> Documento en formato PDF
                                </label>
                                <input type="file" 
                                       class="form-control form-control-lg" 
                                       id="document" 
                                       name="document" 
                                       accept="application/pdf"
                                       required>
                                <div class="form-text">
                                    <i class="bi bi-info-circle"></i> 
                                    Tamaño máximo: <strong>20 MB</strong>. Solo archivos PDF.
                                </div>
                                <div id="fileInfo" class="mt-2 text-muted" style="display:none;">
                                    <i class="bi bi-file-earmark-pdf-fill text-danger"></i> 
                                    <span id="fileName"></span> 
                                    (<span id="fileSize"></span>)
                                </div>
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

    <script>
        // Mostrar información del archivo seleccionado
        document.getElementById('document').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const fileInfo = document.getElementById('fileInfo');
                const fileName = document.getElementById('fileName');
                const fileSize = document.getElementById('fileSize');
                
                fileName.textContent = file.name;
                fileSize.textContent = formatFileSize(file.size);
                fileInfo.style.display = 'block';
                
                // Validar tamaño (20MB = 20,971,520 bytes)
                if (file.size > 20 * 1024 * 1024) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Archivo muy grande',
                        text: 'El archivo excede el tamaño máximo de 20 MB',
                        confirmButtonColor: '#CD1719'
                    });
                    e.target.value = '';
                    fileInfo.style.display = 'none';
                }
                
                // Validar tipo
                if (file.type !== 'application/pdf') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Tipo de archivo no válido',
                        text: 'Solo se permiten archivos PDF',
                        confirmButtonColor: '#CD1719'
                    });
                    e.target.value = '';
                    fileInfo.style.display = 'none';
                }
            }
        });
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }
        
        // Manejo del formulario
        document.getElementById('tfgFinalForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validar que se haya seleccionado un archivo
            const fileInput = document.getElementById('document');
            if (!fileInput.files || fileInput.files.length === 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Archivo requerido',
                    text: 'Debes seleccionar un archivo PDF',
                    confirmButtonColor: '#CD1719'
                });
                return;
            }
            
            // Confirmar subida
            Swal.fire({
                title: '¿Subir documento final?',
                html: `
                    <p>Estás a punto de subir el documento final de tu TFG.</p>
                    <p><strong>Una vez subido, será enviado a la CTFG para revisión.</strong></p>
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
    <!-- =============================== FOOTER =============================== -->
    <?php include $base_path . '/footer.php'; ?>
</body>
</html>