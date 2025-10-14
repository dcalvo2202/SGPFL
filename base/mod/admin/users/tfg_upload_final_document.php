<?php
// VERIFICAR AUTENTICACIÓN
include("../../login/check.php");

// Obtener base_url de la sesión
$cds_domain = $mySessionController->getVar("cds_domain");
$cds_locate = $mySessionController->getVar("cds_locate");
$base_url = $cds_domain . $cds_locate;

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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subir Documento Final - SGPFL</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <link rel="stylesheet" href="<?= $base_url ?>inc/css/estilo.css">
    <link rel="stylesheet" href="<?= $base_url ?>inc/css/panel_estudiante.css">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 600;
            font-size: 0.95rem;
        }
        .status-vigente {
            background-color: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }
        .status-prorroga {
            background-color: #fff3cd;
            color: #997404;
            border: 1px solid #ffecb5;
        }
        .status-vencido {
            background-color: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
        }
        .section-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        .section-header {
            background: linear-gradient(135deg, #034991, #023670);
            color: white;
            padding: 1.5rem;
        }
        .section-header h3 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
            color: white !important;
        }
        .section-body {
            padding: 2rem;
        }
        .form-label.required::after {
            content: " *";
            color: #CD1719;
        }
    </style>
</head>
<body class="fondo-una d-flex flex-column min-vh-100">

    <!-- =============================== HEADER =============================== -->
    <header class="navbar-una" style="background: linear-gradient(135deg, #CD1719, #A01215) !important; padding: 1.25rem 0;">
        <div class="container-fluid px-4">
            <div class="header-left d-flex align-items-center">
                <img src="<?= htmlspecialchars($base_url) ?>img/logo.webp" alt="Logo UNA" class="logo-una" style="height: 70px;">
                <div class="header-text ms-3">
                    <h5 class="mb-0 text-white fw-bold">Universidad Nacional de Costa Rica</h5>
                    <small class="text-light opacity-85">Escuela de Informática</small>
                </div>
            </div>
            <div class="header-right text-end">
                <div class="user-info text-white mb-2">
                    <i class="bi bi-person-circle fs-5"></i>
                    <span class="ms-2 fw-semibold"><?= htmlspecialchars($current_user_name) ?></span>
                </div>
                <div class="user-details">
                    <small class="text-light opacity-75">ID: <?= htmlspecialchars($current_user_id) ?></small>
                    <a href="<?= htmlspecialchars($base_url) ?>Panel_SubirTFG.php" class="btn btn-outline-light btn-sm ms-2" style="font-size: 0.85rem; padding: 0.4rem 0.85rem;">
                        <i class="bi bi-arrow-left"></i> Volver
                    </a>
                    <a href="<?= htmlspecialchars($base_url) ?>mod/login/logout.php" class="btn btn-outline-light btn-sm ms-2" style="font-size: 0.85rem; padding: 0.4rem 0.85rem;">
                        <i class="bi bi-box-arrow-right"></i> Salir
                    </a>
                </div>
            </div>
        </div>
    </header>

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
    <footer class="footer-una mt-auto">
        <div class="container">
            <p class="mb-1">&copy; <?= date('Y') ?> Universidad Nacional de Costa Rica</p>
            <small>Escuela de Informática - Proyecto SGPFL v3.0</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
</body>
</html>
