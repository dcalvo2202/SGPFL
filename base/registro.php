<?php
require_once __DIR__ . '/config.inc';
require_once('includes.php');

$base_url = rtrim($cds_domain, '/') . '/' . trim($cds_locate, '/') . '/';
$favicon_url = $base_url . 'img/logo.webp';

$current_user_id = '';
$current_user_name = '';
$current_user_email = '';
$current_user_rol = 0;
$is_logged_student = false;
$unread_notifications = 0;
$unread_messages = 0;
$current_student_role_counts = [
    'Tutor' => 0,
    'Asesor 1' => 0,
    'Asesor 2' => 0,
];

try {
    require_once __DIR__ . '/lib/mysession/mySession.conf.php';
    require_once __DIR__ . '/lib/mysession/mySession.class.php';
    $mySessionController = mySession::getIstance($_MYSESSION_CONF);
    $current_user_id = (string)($mySessionController->getVar('usuario') ?? '');
    $current_user_name = (string)($mySessionController->getVar('nombre') ?? '');
    $current_user_rol = (int)($mySessionController->getVar('rol') ?? 0);
    $is_logged_student = ($current_user_rol === 4 && $current_user_id !== '');
} catch (Throwable $e) {
    $is_logged_student = false;
}

if ($current_user_id !== '') {
    try {
        require_once __DIR__ . '/inc/alert_functions.php';
        require_once __DIR__ . '/inc/chat_functions.php';
        include_once __DIR__ . '/inc/db/bdcommon.inc';
        $conn_header = new mysqli($db_host, $usuario, $clave, $db);
        if (!$conn_header->connect_error) {
            $conn_header->set_charset('utf8');
            $unread_notifications = getUnreadAlertCount($conn_header, $current_user_id);
            $unread_messages = getTotalUnreadMessages($conn_header, $current_user_id);
            $conn_header->close();
        }
    } catch (Exception $e) {
        $unread_notifications = 0;
        $unread_messages = 0;
    }
}

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

if ($is_logged_student) {
    try {
        include_once __DIR__ . '/inc/db/bdcommon.inc';
        $conn = new mysqli($db_host, $usuario, $clave, $db);
        if (!$conn->connect_error) {
            $conn->set_charset('utf8');

            $stmt_user = $conn->prepare('SELECT nombre, email FROM sis_user WHERE id = ? LIMIT 1');
            if ($stmt_user) {
                $stmt_user->bind_param('s', $current_user_id);
                $stmt_user->execute();
                $result_user = $stmt_user->get_result();
                if ($row_user = $result_user->fetch_assoc()) {
                    $current_user_name = (string)($row_user['nombre'] ?? $current_user_name);
                    $current_user_email = (string)($row_user['email'] ?? '');
                }
                $stmt_user->close();
            }

            $stmt_roles = $conn->prepare(
                "SELECT ear.committee_role, COUNT(*) AS total
                 FROM external_advisor_linked_students eals
                 INNER JOIN external_advisor_profile_requests ear ON eals.advisor_request_id = ear.id
                 WHERE eals.student_id = ? AND ear.status = 'Aprobado'
                 GROUP BY ear.committee_role"
            );
            if ($stmt_roles) {
                $stmt_roles->bind_param('s', $current_user_id);
                $stmt_roles->execute();
                $result_roles = $stmt_roles->get_result();
                while ($row_role = $result_roles->fetch_assoc()) {
                    $role_name = (string)($row_role['committee_role'] ?? '');
                    if (isset($current_student_role_counts[$role_name])) {
                        $current_student_role_counts[$role_name] = (int)($row_role['total'] ?? 0);
                    }
                }
                $stmt_roles->close();
            }

            $conn->close();
        }
    } catch (Exception $e) {
        error_log('Error cargando roles actuales del estudiante: ' . $e->getMessage());
    }
}

$tutor_taken = $current_student_role_counts['Tutor'] > 0;
$advisor1_taken = $current_student_role_counts['Asesor 1'] > 0;
$advisor2_taken = $current_student_role_counts['Asesor 2'] > 0;
$advisor_slots_available = (!$advisor1_taken || !$advisor2_taken);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Solicitud Comité Asesor - SGPFL</title>

    <link rel="icon" type="image/webp" href="<?= htmlspecialchars($favicon_url) ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <link href="<?= htmlspecialchars($base_url . 'inc/css/estilo.css') ?>" rel="stylesheet">
    <link href="<?= htmlspecialchars($base_url . 'inc/css/panel_estudiante.css') ?>" rel="stylesheet">
    <link href="<?= htmlspecialchars($base_url . 'inc/css/tfg_upload.css') ?>" rel="stylesheet">
    <link href="<?= htmlspecialchars($base_url . 'inc/css/registro.css') ?>" rel="stylesheet">

</head>
<body class="d-flex flex-column min-vh-100 fondo-una">

<!-- =============================== HEADER =============================== -->
<?php if (!empty($current_user_id)): ?>
    <?php include 'header.php'; ?>
<?php else: ?>
    <header class="navbar-una sticky-top" style="background: linear-gradient(135deg, #CD1719, #A01215) !important; padding: 1.25rem 0;">
        <div class="container-fluid px-4">
            <div class="header-left d-flex align-items-center">
                <img src="<?= htmlspecialchars($base_url) ?>img/logo.webp" alt="Logo UNA" class="logo-una" style="height: 70px;">
                <div class="header-text ms-3">
                    <h5 class="mb-0 text-white fw-bold">Universidad Nacional de Costa Rica</h5>
                    <small class="text-light opacity-85">Escuela de Informática</small>
                </div>
            </div>
            <div class="header-right text-end">
                <div class="header-actions user-details">
                    <a href="login.php" class="btn btn-outline-light btn-sm ms-1" style="font-size: 1.05rem; padding: 0.55rem 1.1rem;">
                        <i class="bi bi-box-arrow-in-right"></i> Iniciar sesión
                    </a>
                </div>
            </div>
        </div>
    </header>
<?php endif; ?>

<main class="flex-fill">
    <div class="container my-5">

        <div class="dashboard-header text-center mb-4">
            <h1 style="font-size: 2.25rem; font-weight: 700;">
                <i class="bi bi-mortarboard-fill"></i> Solicitud de Integrante de Comité Asesor
            </h1>
            <p class="lead text-muted">
                Reutilice este formulario para postularse como asesor externo, asesor interno o tutor. La solicitud quedará en estado <strong>En Revisión</strong>.
            </p>
        </div>

        <form action="registro_process.php" method="POST" enctype="multipart/form-data">

            <div class="section-card">
                <div class="section-header">
                    <h3><i class="bi bi-diagram-3-fill"></i> Tipo de postulación</h3>
                </div>
                <div class="section-body">

                    <div class="form-group-tfg">
                        <label class="form-label-tfg" for="inp-postulation-type">
                            <i class="bi bi-ui-checks-grid"></i> Seleccione su tipo de postulación *
                        </label>
                        <select class="form-control-tfg" id="inp-postulation-type" name="postulation_type" required>
                            <option value="" disabled selected hidden>Seleccione tipo de postulación</option>
                            <option value="Asesor Externo" <?= $advisor_slots_available ? '' : 'disabled' ?>>Asesor externo</option>
                            <option value="Asesor Interno" <?= $advisor_slots_available ? '' : 'disabled' ?>>Asesor interno</option>
                            <option value="Tutor" <?= $tutor_taken ? 'disabled' : '' ?>>Tutor</option>
                        </select>
                        <?php if ($is_logged_student): ?>
                            <small class="text-muted d-block mt-1">Las opciones ya asignadas a su proyecto aparecen deshabilitadas.</small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group-tfg" id="subrole-wrapper">
                        <label class="form-label-tfg" for="inp-committee-subrole">
                            <i class="bi bi-person-lines-fill"></i> Subrol del comité *
                        </label>
                        <select class="form-control-tfg" id="inp-committee-subrole" name="committee_subrole">
                            <option value="" disabled selected hidden>Seleccione subrol</option>
                            <option value="Asesor 1" <?= $advisor1_taken ? 'disabled' : '' ?>>Asesor 1</option>
                            <option value="Asesor 2" <?= $advisor2_taken ? 'disabled' : '' ?>>Asesor 2</option>
                        </select>
                        <small class="text-muted">Aplica cuando la postulación es para asesor externo o asesor interno.</small>
                    </div>

                    <input type="hidden" id="inp-committee-role" name="committee_role" value="">

                </div>
            </div>

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
                               placeholder="Ej: 123456789" value="<?= htmlspecialchars($is_logged_student ? '' : $current_user_id) ?>">
                        <small class="text-muted">Debe coincidir con el identificador que se usará al crear su cuenta.</small>
                    </div>

                    <div class="form-group-tfg">
                        <label class="form-label-tfg" for="inp-full-name">
                            <i class="bi bi-person-fill"></i> Nombre completo *
                        </label>
                        <input type="text" class="form-control-tfg" id="inp-full-name" name="full_name" maxlength="255" required
                               placeholder="Ingrese su nombre completo" value="<?= htmlspecialchars($is_logged_student ? '' : $current_user_name) ?>">
                    </div>

                    <div class="form-group-tfg">
                        <label class="form-label-tfg" for="inp-email">
                            <i class="bi bi-envelope-fill"></i> Correo electrónico *
                        </label>
                        <input type="email" class="form-control-tfg" id="inp-email" name="email" maxlength="100" required
                               placeholder="correo@ejemplo.com" value="<?= htmlspecialchars($is_logged_student ? '' : $current_user_email) ?>">
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

                    <?php if ($is_logged_student): ?>
                        <h5 class="alert-tfg alert-tfg-info mb-3">
                            <i class="bi bi-info-circle"></i>
                            <div>
                                <strong>Solicitud para tu proyecto:</strong> Este bloque identifica a tu grupo actual como estudiante a asesorar.
                            </div>
                        </h5>
                        <div class="selected-student-container">
                            <label class="form-label-tfg">
                                <i class="bi bi-person-check-fill text-success"></i> Estudiante seleccionado
                            </label>
                            <div class="selected-student-card">
                                <div class="student-info">
                                    <span><?= htmlspecialchars($current_user_name) ?></span>
                                    <small class="text-muted"><?= htmlspecialchars($current_user_email) ?></small>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" id="inp-linked-student-id" name="linked_student_id" value="<?= htmlspecialchars($current_user_id) ?>">
                    <?php else: ?>
                        <h5 class="alert-tfg alert-tfg-info mb-3">
                            <i class="bi bi-info-circle"></i>
                            <div>
                                <strong>Instrucciones:</strong> Busque y seleccione el estudiante al que desea asesorar en su Trabajo Final de Graduación. (Al seleccionar un estudiante se vincula con el resto del grupo de TFG)
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
                    <?php endif; ?>

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
                            Currículum actualizado (PDF) *
                        </label>
                        <input type="file" class="form-control-tfg" id="inp-cv" name="cv_document" accept=".pdf,.docx" required>
                        <small class="text-muted">Tamaño máximo: 5 MB | Formatos permitidos: PDF, DOCX</small>
                    </div>

                    <div class="form-group-tfg">
                        <label class="form-label-tfg" for="inp-id-copy">
                            <i class="bi bi-person-badge"></i> Fotocopia de cédula (JPG/PNG) *
                            
                        </label>
                        <input type="file" class="form-control-tfg" id="inp-id-copy" name="id_copy_document" accept=".pdf,.jpg,.jpeg,.png" required>
                        <small class="text-muted">Tamaño máximo: 2 MB | Formatos permitidos: PDF, JPG, PNG</small>
                    </div>

                    <div class="form-group-tfg">
                        <label class="form-label-tfg" for="inp-cover-letter">
                            <i class="bi bi-filetype-pdf"></i> Carta de solicitud (PDF) *
                        </label>
                        <input type="file" class="form-control-tfg" id="inp-cover-letter" name="cover_letter_document" accept=".pdf" required>
                        <small class="text-muted">Tamaño máximo: 5 MB | Formato permitido: PDF</small>
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
        // Inyectar estilos CSS para SweetAlert2
        const style = document.createElement('style');
        style.textContent = `
            .registro-loader-gif {
                width: 18px;
                height: 18px;
                object-fit: contain;
                margin-right: 0.5rem;
                vertical-align: middle;
                filter: hue-rotate(200deg) saturate(5) brightness(0.85);
            }

            .swal2-popup .swal2-actions {
                gap: 0.5rem;
                align-items: center;
            }
            
            .swal2-popup .swal2-styled {
                min-width: 110px;
                padding: 10px 20px;
                font-size: 1.2rem;
                margin: 0;
            }
            
            .swal2-popup .swal2-confirm,
            .swal2-popup .swal2-cancel {
                flex: 1;
                margin: 0 !important;
            }
        `;
        document.head.appendChild(style);

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

        // Validación de tamaño máximo de archivos
        function bindFileSizeValidation() {
            const fileValidationRules = {
                'inp-cv': {
                    maxMB: 5,
                    label: 'Currículum',
                    allowedExts: ['pdf', 'docx']
                },
                'inp-id-copy': {
                    maxMB: 2,
                    label: 'Fotocopia de cédula',
                    allowedExts: ['pdf', 'jpg', 'jpeg', 'png']
                },
                'inp-cover-letter': {
                    maxMB: 5,
                    label: 'Carta de solicitud',
                    allowedExts: ['pdf']
                }
            };

            Object.keys(fileValidationRules).forEach(function (inputId) {
                const input = document.getElementById(inputId);
                if (!input) return;

                const { maxMB, label, allowedExts } = fileValidationRules[inputId];
                const maxBytes = maxMB * 1024 * 1024;

                input.addEventListener('change', function () {
                    const file = input.files && input.files[0] ? input.files[0] : null;

                    if (!file) {
                        input.setCustomValidity('');
                        return;
                    }

                    const extension = file.name.includes('.')
                        ? file.name.split('.').pop().toLowerCase()
                        : '';

                    if (!allowedExts.includes(extension)) {
                        input.setCustomValidity(
                            `${label} debe tener formato: ${allowedExts.join(', ').toUpperCase()}`
                        );
                        input.reportValidity?.();
                        return;
                    }

                    if (file.size > maxBytes) {
                        const fileSize = formatBytes(file.size);
                        const maxSize = formatBytes(maxBytes);

                        input.setCustomValidity(
                            `${label} excede el tamaño máximo. ` +
                            `Archivo: ${fileSize}, Máximo permitido: ${maxSize}`
                        );
                        input.reportValidity?.();
                        return;
                    }

                    input.setCustomValidity('');
                });
            });
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
                'inp-postulation-type': {
                    valueMissing: 'Debe seleccionar el tipo de postulación.',
                },
                'inp-committee-subrole': {
                    valueMissing: 'Debe seleccionar el subrol para asesor externo o interno.',
                },
                'inp-cv': {
                    valueMissing: 'Debe adjuntar el currículum en formato PDF o DOCX.',
                    fileSize: 'El currículum excede el tamaño máximo permitido (5 MB).',
                },
                'inp-id-copy': {
                    valueMissing: 'Debe adjuntar la fotocopia de cédula en formato PDF, JPG o PNG.',
                    fileSize: 'La fotocopia de cédula excede el tamaño máximo permitido (2 MB).',
                },
                'inp-cover-letter': {
                    valueMissing: 'Debe adjuntar la carta de solicitud en formato PDF.',
                    fileSize: 'La carta de solicitud excede el tamaño máximo permitido (5 MB).',
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
                    // Si el mensaje personalizado ya coniene "excede", mantenerlo (viene de validación de tamaño)
                    if (el.validationMessage && el.validationMessage.includes('excede')) {
                        return;
                    }

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

                    // Si hay un mensaje personalizado por tamaño de archivo y está vacío, usar genérico
                    if (el.customValidity === '' && messages.fileSize) {
                        el.setCustomValidity(messages.fileSize);
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
            const submitButton = form.querySelector('button[type="submit"]');

            function setSubmitButtonProcessing(isProcessing) {
                if (!submitButton) return;

                if (isProcessing) {
                    if (!submitButton.dataset.originalHtml) {
                        submitButton.dataset.originalHtml = submitButton.innerHTML;
                    }
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<img src="<?= htmlspecialchars($base_url . 'img/loader_circle.gif') ?>" alt="Procesando" class="registro-loader-gif">Procesando la información...';
                    return;
                }

                submitButton.disabled = false;
                if (submitButton.dataset.originalHtml) {
                    submitButton.innerHTML = submitButton.dataset.originalHtml;
                }
            }

            function handleSubmit(e) {
                // Si el formulario no es válido, mostrar errores
                if (!form.checkValidity()) {
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
                    return;
                }

                // Validar que se haya seleccionado un estudiante
                e.preventDefault();

                const linkedStudentId = document.getElementById('inp-linked-student-id');
                if (!linkedStudentId || !linkedStudentId.value || linkedStudentId.value.trim() === '') {
                    setSubmitButtonProcessing(false);
                    Swal.fire({
                        icon: 'warning',
                        title: 'Estudiante requerido',
                        text: 'Debe buscar y seleccionar el estudiante que desea asesorar antes de enviar la solicitud.',
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#034991'
                    });

                    // Hacer scroll hacia la sección de estudiantes
                    const studentSection = document.getElementById('inp-search-student');
                    if (studentSection) {
                        studentSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        setTimeout(() => studentSection.focus(), 500);
                    }
                    return;
                }

                // Si el formulario es válido y hay estudiante, mostrar confirmación antes de enviar
                Swal.fire({
                    title: 'Confirmar envío',
                    text: '¿Está seguro de enviar la solicitud? Por favor, revise que toda la información sea correcta.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#034991',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, enviar',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true
                }).then(function (result) {
                    if (result.isConfirmed) {
                        setSubmitButtonProcessing(true);
                        // Remover el evento listeners para evitar mostrar el diálogo nuevamente
                        form.removeEventListener('submit', handleSubmit);
                        form.submit();
                    }
                });
            }

            form.addEventListener('submit', handleSubmit);
        }

        function initPostulationTypeWorkflow() {
            const postulationType = document.getElementById('inp-postulation-type');
            const subroleWrapper = document.getElementById('subrole-wrapper');
            const subroleInput = document.getElementById('inp-committee-subrole');
            const committeeRoleInput = document.getElementById('inp-committee-role');

            if (!postulationType || !subroleWrapper || !subroleInput || !committeeRoleInput) {
                return;
            }

            function syncRoleFields() {
                const type = postulationType.value;

                if (type === 'Tutor') {
                    subroleWrapper.style.display = 'none';
                    subroleInput.required = false;
                    subroleInput.value = '';
                    subroleInput.setCustomValidity('');
                    committeeRoleInput.value = 'Tutor';
                    return;
                }

                if (type === 'Asesor Externo' || type === 'Asesor Interno') {
                    subroleWrapper.style.display = '';
                    subroleInput.required = true;
                    committeeRoleInput.value = subroleInput.value || '';
                    return;
                }

                subroleWrapper.style.display = 'none';
                subroleInput.required = false;
                subroleInput.value = '';
                subroleInput.setCustomValidity('');
                committeeRoleInput.value = '';
            }

            postulationType.addEventListener('change', syncRoleFields);
            subroleInput.addEventListener('change', function () {
                committeeRoleInput.value = subroleInput.value || '';
            });

            syncRoleFields();
        }

        document.addEventListener('DOMContentLoaded', function () {
            bindCustomValidationMessages();
            bindFileSizeValidation();
            bindCustomSingleFileInput('inp-cv', 'Seleccionar archivo', 'Ningún archivo seleccionado');
            bindCustomSingleFileInput('inp-id-copy', 'Seleccionar archivo', 'Ningún archivo seleccionado');
            bindCustomSingleFileInput('inp-cover-letter', 'Seleccionar archivo', 'Ningún archivo seleccionado');
            bindFormValidationFallback();
            initPostulationTypeWorkflow();
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
