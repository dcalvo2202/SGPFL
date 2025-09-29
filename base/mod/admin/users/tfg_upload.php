<?php
<<<<<<< HEAD
// Cargar configuración para rutas portables
$cfg = __DIR__ . '/../../../inc/db/bdcommon.inc';
$panel_href = "../../../panel_subir_propuesta_tfg.php"; // Ruta relativa por defecto
$form_action = "tfg_upload_process.php";      // Acción por defecto

// Si el archivo de configuración existe, se usan rutas absolutas
if (is_file($cfg)) {
    include $cfg; // Este archivo debería definir $base_url
    if (!empty($base_url)) {
        $base = rtrim($base_url, '/');
        $panel_href = $base . '/panel_subir_propuesta_tfg.php';
        // Ajusta esta ruta según la ubicación real de tu script de procesamiento
        $form_action = $base . '/mod/admin/users/tfg_upload_process.php'; 
    }
}
=======
// Configuración portable de rutas
$base_path = realpath(__DIR__ . '/../../../');
$relative_base = '../../../';

// Incluir archivos necesarios con rutas relativas
include_once($base_path . '/inc/db/bdcommon.inc');

// Obtener tipos de proyecto (temporalmente hardcoded)
$project_types = [
    ['id' => 1, 'type_name' => 'Proyecto Individual', 'max_members' => 1],
    ['id' => 2, 'type_name' => 'Proyecto en Pareja', 'max_members' => 2],
    ['id' => 3, 'type_name' => 'Proyecto Grupal Pequeño', 'max_members' => 3],
    ['id' => 4, 'type_name' => 'Proyecto Grupal Grande', 'max_members' => 4]
];

// URLs portables
$panel_href = $relative_base . "panel_estudiante.php";
$form_action = "tfg_upload_process.php";

// Usuario actual (temporal para desarrollo)
$current_user_id = '112170040';
>>>>>>> HU-002
?>
<!DOCTYPE html>
<html lang="es">
<head>
<<<<<<< HEAD
    <meta charset="UTF-8" />
    <title>Subir Propuesta de TFG</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root{
            --brand-blue:#005eb8;
            --brand-red:#dc3545;
            --brand-dark:#27323a;
        }
        body{background:#fff;font-family:"Roboto","Segoe UI",Arial,sans-serif;}
        .page-header{
            background: linear-gradient(90deg, var(--brand-blue) 70%, var(--brand-red) 100%);
            color:#fff;
            padding:2rem 0;
            margin-bottom:2rem;
            box-shadow:0 2px 10px rgba(0,0,0,.08);
        }
        .page-header h1{font-size:1.6rem;margin:0;}
        .card{border-radius:14px;box-shadow:0 4px 14px rgba(0,0,0,.06);border:1px solid rgba(0,0,0,.06);}
        .form-label{font-weight:600;color:var(--brand-blue)}
        .btn-primary{background:var(--brand-blue);border-color:var(--brand-blue)}
        .btn-primary:hover{background:var(--brand-red);border-color:var(--brand-red)}
        .small-muted{color:#6c757d;font-size:.925rem}
        .badge-rule{background:rgba(0,94,184,.1);color:var(--brand-blue);font-weight:600}
        
        /* Estilo para el borde verde de validación correcta (opcional pero recomendado) */
        .form-control.is-valid {
            border-color: #198754 !important;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(.375em + .1875rem) center;
            background-size: calc(.75em + .375rem) calc(.75em + .375rem);
        }
    </style>
</head>
<body>
    <header class="page-header">
        <div class="container d-flex flex-wrap align-items-center justify-content-between gap-3">
            <h1 class="m-0">Subir Propuesta de Trabajo Final de Graduación</h1>
            <a href="<?php echo htmlspecialchars($panel_href); ?>" class="btn btn-light">
                ← Volver al Panel de Estudiante
            </a>
        </div>
    </header>

    <div class="container pb-5">
        <div class="row justify-content-center">
            <div class="col-lg-7 col-md-9">
                <div class="card">
                    <div class="card-body p-4 p-md-5">
                        <h2 class="h4 mb-3">Datos de la propuesta</h2>
                        <p class="small-muted mb-4">
                            Asegúrate de ingresar un título único y al menos dos disciplinas separadas por coma.
                            El documento debe ser PDF o DOCX y no exceder 10 MB.
                        </p>

                        <form id="tfgForm" action="<?php echo htmlspecialchars($form_action); ?>" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="title" class="form-label">Título único</label>
                                <input type="text" class="form-control" id="title" name="title" maxlength="255" required placeholder="Ej.: Sistema Gestor de Proyectos de Graduación">
                            </div>

                            <div class="mb-3">
                                <label for="disciplines" class="form-label">Disciplinas</label>
                                <input type="text" class="form-control" id="disciplines" name="disciplines" required pattern="[^,]+,\s*[^,]+.*" placeholder="Ej.: Informática, Matemáticas">
                                <div class="form-text">
                                    Ingrese al menos dos disciplinas separadas por coma.
                                    <span class="badge badge-rule rounded-pill ms-2">Mínimo 2</span>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="document" class="form-label">Documento (PDF/DOCX, máx 10MB)</label>
                                <input type="file" class="form-control" id="document" name="document" accept=".pdf,.docx" required>
                                <div class="form-text">Formatos permitidos: .pdf, .docx — Tamaño máximo: 10 MB</div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">Enviar propuesta</button>
                            </div>
                        </form>

                        <hr class="my-4">

                        <div class="small-muted">
                            Al enviar, su propuesta quedará con estado <strong>Pendiente de Revisión</strong>.
                            Recibirá actualizaciones en su panel.
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <a href="<?php echo htmlspecialchars($panel_href); ?>" class="btn btn-outline-primary">
                        ← Volver al Panel de Estudiante
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        (function () {
            'use strict';
            const form = document.getElementById('tfgForm');
            const titleInput = document.getElementById('title');
            const disciplinesInput = document.getElementById('disciplines');
            const fileInput = document.getElementById('document');

            form.addEventListener('submit', function (event) {
                event.preventDefault(); // Prevenimos el envío para validarlo primero

                let errors = [];

                // 1. Validar Título
                if (titleInput.value.trim() === '') {
                    errors.push("El campo 'Título' es obligatorio.");
                }

                // 2. Validar Disciplinas usando el pattern del HTML
                if (!disciplinesInput.checkValidity() || disciplinesInput.value.trim() === '') {
                    errors.push("Debe ingresar al menos dos disciplinas separadas por coma.");
                }

                // 3. Validar Archivo
                const MAX_BYTES = 10 * 1024 * 1024; // 10MB
                const allowedExtensions = ['pdf', 'docx'];
                if (fileInput.files.length === 0) {
                    errors.push("Debe seleccionar un documento.");
                } else {
                    const file = fileInput.files[0];
                    const fileExtension = (file.name.split('.').pop() || '').toLowerCase();
                    if (!allowedExtensions.includes(fileExtension)) {
                        errors.push("El formato del archivo no es válido (solo PDF o DOCX).");
                    }
                    if (file.size > MAX_BYTES) {
                        errors.push(`El archivo supera el tamaño máximo de 10 MB.`);
                    }
                }

                // Decidir qué hacer: mostrar alerta de error o enviar formulario
                if (errors.length > 0) {
                    const errorHtml = '<ul style="text-align: left; list-style-position: inside;">' + 
                                      errors.map(e => `<li>${e}</li>`).join('') + 
                                      '</ul>';

                    Swal.fire({
                        icon: 'error',
                        title: 'Por favor, corrige lo siguiente:',
                        html: errorHtml,
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#005eb8' // Color azul institucional
                    });

                } else {
                    // Si no hay errores, se envía el formulario
                    Swal.fire({
                        title: 'Enviando propuesta...',
                        text: 'Por favor espera.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    form.submit();
                }
            });

            // Opcional: Feedback positivo en tiempo real (borde verde)
            [titleInput, disciplinesInput, fileInput].forEach(input => {
                input.addEventListener('input', () => {
                    if (input.checkValidity()) {
                        input.classList.add('is-valid');
                    } else {
                        input.classList.remove('is-valid');
                    }
                });
                fileInput.addEventListener('change', () => {
                     // Simple validación para el archivo, la robusta se hace al enviar
                    if(fileInput.files.length > 0) fileInput.classList.add('is-valid');
                });
            });

        })();
=======
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Propuesta TFG - SGPFL</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link rel="stylesheet" href="<?= $relative_base ?>inc/css/estilo.css">
    <link rel="stylesheet" href="<?= $relative_base ?>inc/css/tfg_upload.css">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .section-header h3 {
            color: white;
        }
    </style>
    </head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-una">
        <div class="container">
            <div class="logo-una">UNA</div>
            <a class="navbar-brand" href="<?= htmlspecialchars($panel_href) ?>">
                <i class="bi bi-mortarboard-fill"></i> SGPFL - ESCINF
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav-main-menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="nav-main-menu">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= htmlspecialchars($panel_href) ?>">
                            <i class="bi bi-house-fill"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#">
                            <i class="bi bi-file-earmark-plus-fill"></i> Nueva Propuesta TFG
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container tfg-upload-container">
        <div class="row mb-4">
            <div class="col-12">
                <h1><i class="bi bi-file-earmark-plus-fill"></i> Nueva Propuesta de TFG</h1>
                <p class="lead text-muted">Complete la información de su propuesta y forme su grupo de trabajo</p>
            </div>
        </div>

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
                        <label for="inp-disciplines" class="form-label-tfg">
                            <i class="bi bi-tags-fill"></i> Disciplinas *
                        </label>
                        <input type="text" 
                               class="form-control-tfg" 
                               id="inp-disciplines" 
                               name="disciplines" 
                               required 
                               placeholder="Ej.: Informática, Matemáticas, Ingeniería">
                        <small class="text-muted">Ingrese al menos dos disciplinas separadas por coma</small>
                    </div>

                    <div class="form-group-tfg">
                        <label for="inp-document" class="form-label-tfg">
                            <i class="bi bi-file-pdf-fill"></i> Documento de la Propuesta *
                        </label>
                        <input type="file" 
                               class="form-control-tfg" 
                               id="inp-document" 
                               name="document" 
                               accept=".pdf,.docx" 
                               required>
                        <small class="text-muted">Formatos permitidos: PDF, DOCX | Tamaño máximo: 10 MB</small>
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
    </div>

    <footer class="footer-una">
        <div class="container">
            <p>
                Copyright © 2025. Todos los derechos reservados. 
                USTDS-Escuela de Informática-UNA<br>
                Contacto: escinf@una.ac.cr | Tel: +506 2562-4000 ext. 2200
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= $relative_base ?>inc/js/tfg_upload.js"></script>
    
    <script>
        // Debug para verificar que todo esté funcionando
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Página cargada completamente');
            console.log('TfgManager disponible:', !!window.tfgManager);
            console.log('SweetAlert disponible:', typeof Swal !== 'undefined');
            
            // Verificar formulario
            const form = document.getElementById('tfgGroupForm');
            console.log('Formulario encontrado:', !!form);
            if (form) {
                console.log('Action del formulario:', form.action);
                console.log('Method del formulario:', form.method);
            }
        });
>>>>>>> HU-002
    </script>
</body>
</html>