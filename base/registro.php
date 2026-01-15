<?php
require_once __DIR__ . '/config.inc';
require_once('includes.php');

$base_url = rtrim($cds_domain, '/') . '/' . trim($cds_locate, '/') . '/';
$favicon_url = $base_url . 'img/logo.webp';

$tipo_tel_options = [];
try {
    include_once __DIR__ . '/inc/db/bdcommon.inc';
    $conn = new mysqli($db_host, $usuario, $clave, $db);
    if (!$conn->connect_error) {
        $conn->set_charset('utf8');
        $rs = $conn->query('SELECT id_tipo_tel, desc_tipo_tel FROM sis_tipo_tel ORDER BY desc_tipo_tel');
        if ($rs) {
            while ($row = $rs->fetch_assoc()) {
                $tipo_tel_options[] = $row;
            }
            $rs->free();
        }
        $conn->close();
    }
} catch (Exception $e) {
    error_log('Error cargando tipos de teléfono: ' . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Registro Asesor Externo - SGPFL</title>

    <link rel="icon" type="image/webp" href="<?= htmlspecialchars($favicon_url) ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <link href="<?= htmlspecialchars($base_url . 'inc/css/estilo.css') ?>" rel="stylesheet">
    <link href="<?= htmlspecialchars($base_url . 'inc/css/panel_estudiante.css') ?>" rel="stylesheet">
    <link href="<?= htmlspecialchars($base_url . 'inc/css/tfg_upload.css') ?>" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="d-flex flex-column min-vh-100 fondo-una">

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

            <div class="user-details">
           
                <a href="login.php" 
                   class="btn btn-outline-light btn-sm ms-2" 
                   style="font-size: 1.25rem; padding: 0.55rem 1.1rem;">
                        <i class="bi bi-arrow-left"></i> Regresar a iniciar sesión
                </a>
            </div>
        </div>
    </div>
</header>

<main class="flex-fill">
    <div class="container my-5">

        <div class="dashboard-header text-center mb-4">
            <h1 style="font-size: 2.25rem; font-weight: 700;">
                <i class="bi bi-mortarboard-fill"></i> Solicitud de Registro - Asesor Externo
            </h1>
            <p class="lead text-muted">
                Complete la información y adjunte los documentos requeridos. Su solicitud quedará en estado <strong>En Revisión</strong>.
            </p>
        </div>

        <form action="registro_process.php" method="POST" enctype="multipart/form-data">

            <div class="section-card">
                <div class="section-header">
                    <h3><i class="bi bi-person-vcard-fill"></i> Información del solicitante</h3>
                </div>
                <div class="section-body">

                    <div class="form-group-tfg">
                        <label class="form-label-tfg" for="inp-applicant-id">
                            <i class="bi bi-person-badge-fill"></i> Cédula / Identificación *
                        </label>
                        <input type="text" class="form-control-tfg" id="inp-applicant-id" name="applicant_id" maxlength="50" required
                               placeholder="Ej: 123456789">
                        <small class="text-muted">Debe coincidir con el identificador que se usará al crear su cuenta.</small>
                    </div>

                    <div class="form-group-tfg">
                        <label class="form-label-tfg" for="inp-full-name">
                            <i class="bi bi-person-fill"></i> Nombre completo *
                        </label>
                        <input type="text" class="form-control-tfg" id="inp-full-name" name="full_name" maxlength="255" required
                               placeholder="Ingrese su nombre completo">
                    </div>

                    <div class="form-group-tfg">
                        <label class="form-label-tfg" for="inp-email">
                            <i class="bi bi-envelope-fill"></i> Correo electrónico *
                        </label>
                        <input type="email" class="form-control-tfg" id="inp-email" name="email" maxlength="100" required
                               placeholder="correo@ejemplo.com">
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group-tfg">
                                <label class="form-label-tfg" for="inp-telefono">
                                    <i class="bi bi-telephone-fill"></i> Teléfono
                                </label>
                                <input type="text" class="form-control-tfg" id="inp-telefono" name="telefono" maxlength="15"
                                       placeholder="Ej: 88888888">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group-tfg">
                                <label class="form-label-tfg" for="inp-tipo-tel">
                                    <i class="bi bi-telephone"></i> Tipo de teléfono
                                </label>
                                <select class="form-control-tfg" id="inp-tipo-tel" name="id_tipo_tel">
                                    <option value="">[Seleccione]</option>
                                    <?php foreach ($tipo_tel_options as $opt): ?>
                                        <option value="<?= htmlspecialchars($opt['id_tipo_tel']) ?>">
                                            <?= htmlspecialchars($opt['desc_tipo_tel']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <h3><i class="bi bi-building"></i> Perfil académico</h3>
                </div>
                <div class="section-body">

                    <div class="form-group-tfg">
                        <label class="form-label-tfg" for="inp-institution">
                            <i class="bi bi-building-fill"></i> Institución/Organización de afiliación *
                        </label>
                        <input type="text" class="form-control-tfg" id="inp-institution" name="institution" maxlength="255" required
                               placeholder="Ej: Universidad / Empresa / Organización">
                    </div>

                    <div class="form-group-tfg">
                        <label class="form-label-tfg" for="inp-specialization">
                            <i class="bi bi-award-fill"></i> Área de especialización *
                        </label>
                        <input type="text" class="form-control-tfg" id="inp-specialization" name="specialization" maxlength="255" required
                               placeholder="Ej: Ingeniería de Software, Redes, Datos, etc.">
                    </div>

                </div>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <h3><i class="bi bi-file-earmark-arrow-up-fill"></i> Documentos</h3>
                </div>
                <div class="section-body">

                    <div class="form-group-tfg">
                        <label class="form-label-tfg" for="inp-cv">
                            <i class="bi bi-filetype-pdf"></i> 
                            Currículum actualizado (PDF/DOCX) *
                        </label>
                        <input type="file" class="form-control-tfg" id="inp-cv" name="cv_document" accept=".pdf,.docx" required>
                        <small class="text-muted">Tamaño máximo: 5 MB | Formatos permitidos: PDF, DOCX</small>
                    </div>

                    <div class="form-group-tfg">
                        <label class="form-label-tfg" for="inp-id-copy">
                            <i class="bi bi-person-badge"></i> Fotocopia de cédula (PDF/JPG/PNG) *
                            
                        </label>
                        <input type="file" class="form-control-tfg" id="inp-id-copy" name="id_copy_document" accept=".pdf,.jpg,.jpeg,.png" required>
                        <small class="text-muted">Tamaño máximo: 2 MB | Formatos permitidos: PDF, JPG, PNG</small>
                    </div>

                    <h4 class="alert-tfg alert-tfg-info">
                        <i class="bi bi-info-circle"></i>
                        <div>
                            <strong>Importante:</strong> Esta solicitud no crea una cuenta automáticamente. La Subdirección revisará la información y enviará un correo con el resultado.
                        </div>
                    </h4>

                    <div class="text-center mt-4">

                        <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px; font-size: 16px;">
                        <a href="login.php" class="btn-tfg btn-tfg-secondary">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                        <button type="submit" class="btn-tfg btn-tfg-primary">
                            <i class="bi bi-send-fill"></i> Enviar solicitud
                        </button>
                        </div>
                    </div>
                    </div>

                </div>
            </div>

        </form>

    </div>
</main>

<footer class="footer-una mt-auto">
    <div class="container">
        <p class="mb-1">&copy; <?= date('Y') ?> Universidad Nacional de Costa Rica</p>
        <small>Escuela de Informática - Proyecto SGPFL v3.0</small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function () {
        function formatBytes(bytes) {
            if (!Number.isFinite(bytes) || bytes < 0) return '';
            if (bytes === 0) return '0 B';
            const units = ['B', 'KB', 'MB', 'GB'];
            const k = 1024;
            const i = Math.min(Math.floor(Math.log(bytes) / Math.log(k)), units.length - 1);
            const value = bytes / Math.pow(k, i);
            const decimals = i === 0 ? 0 : 2;
            return value.toFixed(decimals) + ' ' + units[i];
        }

        // Excepción personalizada para mensajes de validación
        function bindCustomValidationMessages() {
            const rules = {
                'inp-applicant-id': {
                    valueMissing: 'Debe ingresar la cédula / identificador.',
                },
                'inp-full-name': {
                    valueMissing: 'Debe ingresar su nombre completo.',
                },
                'inp-email': {
                    valueMissing: 'Debe ingresar su correo electrónico.',
                    typeMismatch: 'Debe ingresar un correo electrónico válido (ej: correo@ejemplo.com).',
                },
                'inp-institution': {
                    valueMissing: 'Debe ingresar la institución/organización de afiliación.',
                },
                'inp-specialization': {
                    valueMissing: 'Debe ingresar el área de especialización.',
                },
                'inp-cv': {
                    valueMissing: 'Debe adjuntar el currículum (PDF o DOCX).',
                },
                'inp-id-copy': {
                    valueMissing: 'Debe adjuntar la fotocopia de cédula (PDF, JPG o PNG).',
                },
            };

            Object.keys(rules).forEach(function (id) {
                const el = document.getElementById(id);
                if (!el) return;

                const messages = rules[id];

                const clear = function () {
                    el.setCustomValidity('');
                };

                el.addEventListener('input', clear);
                el.addEventListener('change', clear);

                el.addEventListener('invalid', function () {
                    // Prioridad: requerido -> tipo/email -> patrón
                    if (el.validity.valueMissing && messages.valueMissing) {
                        el.setCustomValidity(messages.valueMissing);
                        return;
                    }
                    if (el.validity.typeMismatch && messages.typeMismatch) {
                        el.setCustomValidity(messages.typeMismatch);
                        return;
                    }
                    if (el.validity.patternMismatch && messages.patternMismatch) {
                        el.setCustomValidity(messages.patternMismatch);
                        return;
                    }

                    // Fallback genérico (en español) por si aparece otra regla
                    el.setCustomValidity('Revise este campo e intente nuevamente.');
                });
            });
        }

        // Reemplaza el input file nativo por un componente traducido (sin "Choose File")
        function bindCustomSingleFileInput(inputId, buttonText, emptyText) {
            const input = document.getElementById(inputId);
            if (!input) return;

            // Evitar doble inicialización
            if (input.dataset.customized === '1') return;
            input.dataset.customized = '1';

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
            selectButton.textContent = buttonText;

            const fileNameDisplay = document.createElement('span');
            fileNameDisplay.style.cssText = `
                color: #6c757d;
                font-size: 14px;
                flex: 1;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            `;
            fileNameDisplay.textContent = emptyText;

            customFileInput.appendChild(selectButton);
            customFileInput.appendChild(fileNameDisplay);

            // Mensaje de error visible (para cuando el input está oculto y el navegador no muestra bubble)
            const errorText = document.createElement('div');
            errorText.className = 'text-danger mt-1';
            errorText.style.fontSize = '1.20rem';
            errorText.style.display = 'none';

            // "Ocultar" el input nativo sin usar display:none (para no romper validación/submit)
            input.style.position = 'absolute';
            input.style.left = '-9999px';
            input.style.width = '1px';
            input.style.height = '1px';
            input.style.opacity = '0';

            input.parentNode.insertBefore(customFileInput, input);
            input.parentNode.insertBefore(errorText, input.nextSibling);

            customFileInput.addEventListener('mouseenter', function () {
                this.style.borderColor = '#034991';
                selectButton.style.background = '#023366';
            });

            customFileInput.addEventListener('mouseleave', function () {
                this.style.borderColor = '#e1e8ed';
                selectButton.style.background = '#034991';
            });

            customFileInput.addEventListener('click', function (e) {
                e.stopPropagation();
                input.click();
            });

            function updateDisplay() {
                const file = input.files && input.files[0] ? input.files[0] : null;
                if (!file) {
                    fileNameDisplay.textContent = emptyText;
                    fileNameDisplay.style.color = '#6c757d';
                } else {
                    fileNameDisplay.textContent = `${file.name} (${formatBytes(file.size)})`;
                    fileNameDisplay.style.color = '#2c3e50';
                }
            }

            function clearError() {
                errorText.textContent = '';
                errorText.style.display = 'none';
            }

            input.addEventListener('change', function () {
                clearError();
                updateDisplay();
            });

            input.addEventListener('invalid', function () {
                // Mostrar mensaje personalizado (setCustomValidity) de forma visible
                const msg = input.validationMessage || 'Revise este campo e intente nuevamente.';
                errorText.textContent = msg;
                errorText.style.display = 'block';
            });

            input.addEventListener('input', clearError);

            updateDisplay();
        }

        function bindFormValidationFallback() {
            const form = document.querySelector('form[action="registro_process.php"]');
            if (!form) return;

            form.addEventListener('submit', function (e) {
                if (form.checkValidity()) return;
                e.preventDefault();

                // Hallar primer campo inválido y mostrar su mensaje (en español)
                const firstInvalid = form.querySelector(':invalid');
                if (!firstInvalid) return;

                // Intentar bubble nativa cuando sea posible
                if (typeof firstInvalid.reportValidity === 'function') {
                    try {
                        firstInvalid.reportValidity();
                        return;
                    } catch (_) {
                        // continuar a Swal
                    }
                }

                const msg = firstInvalid.validationMessage || 'Debe completar los campos requeridos.';
                Swal.fire({
                    icon: 'error',
                    title: 'Revise el formulario',
                    text: msg,
                    confirmButtonText: 'Aceptar'
                });
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            bindCustomValidationMessages();
            bindCustomSingleFileInput('inp-cv', 'Seleccionar archivo', 'Ningún archivo seleccionado');
            bindCustomSingleFileInput('inp-id-copy', 'Seleccionar archivo', 'Ningún archivo seleccionado');
            bindFormValidationFallback();
        });
    })();
</script>
</body>
</html>
