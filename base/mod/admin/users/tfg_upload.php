<?php
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
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
    </script>
</body>
</html>