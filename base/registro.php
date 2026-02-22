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
    <link href="<?= htmlspecialchars($base_url . 'inc/css/registro.css') ?>" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="d-flex flex-column min-vh-100 fondo-una">

<!-- =============================== HEADER =============================== -->
<header class="navbar-una registro-navbar">
    <div class="container-fluid px-4">
        <div class="header-left d-flex align-items-center">
            <img src="<?= htmlspecialchars($base_url) ?>img/logo.webp" alt="Logo UNA" class="logo-una">
            <div class="header-text ms-3">
                <h5 class="mb-0 text-white fw-bold">Universidad Nacional de Costa Rica</h5>
                 <small class="text-light opacity-85">Escuela de Informática</small>
            </div>
        </div>
        <div class="header-right text-end">

            <div class="user-details">
           
                <a href="login.php" 
                   class="btn btn-outline-light btn-sm ms-2 registro-back-btn">
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
                                <select class="form-control-tfg" id="inp-tipo-tel" name="id_tipo_tel" required>
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

            <!-- ===================== SECCIÓN ESTUDIANTE A ASESORAR ===================== -->
            <div class="section-card">
                <div class="section-header">
                    <h3><i class="bi bi-mortarboard-fill"></i> Estudiante a asesorar</h3>
                </div>
                <div class="section-body">

                    <h5 class="alert-tfg alert-tfg-info mb-3">
                        <i class="bi bi-info-circle"></i>
                        <div>
                            <strong>Instrucciones:</strong> Busque y seleccione el estudiante al que desea asesorar en su Trabajo Final de Graduación.
                        </div>
                    </h5>

                    <div class="form-group-tfg">
                        <label class="form-label-tfg" for="inp-search-student">
                            <i class="bi bi-search"></i> Buscar estudiante *
                        </label>
                        <div class="search-student-container">
                            <div class="input-group">
                                <input type="text" class="form-control-tfg" id="inp-search-student" 
                                       placeholder="Escriba el nombre o cédula del estudiante..." autocomplete="off">
                                <button type="button" class="btn btn-primary" id="btn-search-student">
                                    <i class="bi bi-search"></i> Buscar
                                </button>
                            </div>
                            <small class="text-muted">Ingrese al menos 2 caracteres para iniciar la búsqueda</small>
                        </div>
                    </div>

                    <!-- Resultados de búsqueda -->
                    <div id="div-search-student-results" class="search-results-container" style="display: none;"></div>

                    <!-- Estudiante seleccionado -->
                    <div id="div-selected-student" class="selected-student-container" style="display: none;">
                        <label class="form-label-tfg">
                            <i class="bi bi-person-check-fill text-success"></i> Estudiante seleccionado
                        </label>
                        <div class="selected-student-card">
                            <div class="student-info">
                                <span id="selected-student-name"></span>
                                <small id="selected-student-email" class="text-muted"></small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="btn-remove-student" title="Quitar estudiante">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Campo oculto para el ID del estudiante -->
                    <input type="hidden" id="inp-linked-student-id" name="linked_student_id" value="">

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

                    <h5 class="alert-tfg alert-tfg-info">
                        <i class="bi bi-info-circle"></i>
                        <div>
                            <strong>Importante:</strong> Esta solicitud no crea una cuenta automáticamente. La Subdirección revisará la información y enviará un correo con el resultado.
                        </div>
                    </h5>

                    <div class="text-center mt-4">

                        <div class="registro-actions">
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
            customFileInput.setAttribute('role', 'button');
            customFileInput.tabIndex = 0;

            const selectButton = document.createElement('span');
            selectButton.className = 'custom-file-select-btn';
            selectButton.textContent = buttonText;

            const fileNameDisplay = document.createElement('span');
            fileNameDisplay.className = 'custom-file-name';
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

            customFileInput.addEventListener('click', function (e) {
                e.stopPropagation();
                input.click();
            });

            customFileInput.addEventListener('keydown', function (e) {
                // Accesibilidad: Enter/Espacio abren el selector
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    input.click();
                }
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
            initStudentSearch();
        });

        // ===== Búsqueda de Estudiante =====
        function initStudentSearch() {
            const searchInput = document.getElementById('inp-search-student');
            const searchBtn = document.getElementById('btn-search-student');
            const resultsDiv = document.getElementById('div-search-student-results');
            const selectedDiv = document.getElementById('div-selected-student');
            const hiddenInput = document.getElementById('inp-linked-student-id');
            const selectedName = document.getElementById('selected-student-name');
            const selectedEmail = document.getElementById('selected-student-email');
            const removeBtn = document.getElementById('btn-remove-student');

            if (!searchInput || !searchBtn || !resultsDiv) return;

            let searchTimeout = null;

            // Búsqueda mientras escribe (debounced)
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimeout);
                const term = this.value.trim();

                if (term.length < 2) {
                    resultsDiv.style.display = 'none';
                    resultsDiv.innerHTML = '';
                    return;
                }

                searchTimeout = setTimeout(() => searchStudents(term), 300);
            });

            // Búsqueda al presionar Enter
            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const term = this.value.trim();
                    if (term.length >= 2) {
                        searchStudents(term);
                    }
                }
            });

            // Búsqueda al hacer clic en botón
            searchBtn.addEventListener('click', function () {
                const term = searchInput.value.trim();
                if (term.length >= 2) {
                    searchStudents(term);
                } else {
                    Swal.fire({
                        icon: 'info',
                        title: 'Búsqueda',
                        text: 'Ingrese al menos 2 caracteres para buscar.',
                        confirmButtonText: 'Aceptar'
                    });
                }
            });

            // Quitar estudiante seleccionado
            if (removeBtn) {
                removeBtn.addEventListener('click', function () {
                    clearSelection();
                });
            }

            function searchStudents(term) {
                resultsDiv.innerHTML = '<div class="no-results-message"><i class="bi bi-hourglass-split"></i> Buscando...</div>';
                resultsDiv.style.display = 'block';

                fetch('mod/admin/users/search_users.php?term=' + encodeURIComponent(term))
                    .then(response => response.json())
                    .then(data => {
                        displayResults(data);
                    })
                    .catch(error => {
                        console.error('Error en búsqueda:', error);
                        resultsDiv.innerHTML = '<div class="no-results-message text-danger"><i class="bi bi-exclamation-circle"></i> Error al buscar estudiantes</div>';
                    });
            }

            function displayResults(students) {
                if (!students || students.length === 0) {
                    resultsDiv.innerHTML = '<div class="no-results-message"><i class="bi bi-person-x"></i> No se encontraron estudiantes</div>';
                    return;
                }

                let html = '';
                students.forEach(student => {
                    html += `
                        <div class="search-result-item" data-id="${escapeHtml(student.id)}" data-name="${escapeHtml(student.nombre)}" data-email="${escapeHtml(student.email)}">
                            <div class="student-info">
                                <span class="student-name">${escapeHtml(student.nombre)}</span>
                                <span class="student-email">${escapeHtml(student.email)}</span>
                            </div>
                            <button type="button" class="btn-select">
                                <i class="bi bi-check-lg"></i> Seleccionar
                            </button>
                        </div>
                    `;
                });
                resultsDiv.innerHTML = html;

                // Agregar eventos de clic
                resultsDiv.querySelectorAll('.search-result-item').forEach(item => {
                    item.addEventListener('click', function () {
                        selectStudent(
                            this.dataset.id,
                            this.dataset.name,
                            this.dataset.email
                        );
                    });
                });
            }

            function selectStudent(id, name, email) {
                hiddenInput.value = id;
                selectedName.textContent = name;
                selectedEmail.textContent = email;
                selectedDiv.style.display = 'block';
                resultsDiv.style.display = 'none';
                searchInput.value = '';

                // Feedback visual
                Swal.fire({
                    icon: 'success',
                    title: 'Estudiante seleccionado',
                    text: name,
                    timer: 1500,
                    showConfirmButton: false
                });
            }

            function clearSelection() {
                hiddenInput.value = '';
                selectedName.textContent = '';
                selectedEmail.textContent = '';
                selectedDiv.style.display = 'none';
            }

            function escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        }
    })();
</script>
</body>
</html>
