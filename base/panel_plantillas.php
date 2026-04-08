<?php
/**
 * Panel de Plantillas Oficiales
 *
 * Vista unificada con comportamiento diferenciado por rol:
 *   - Estudiante (4):  solo visualiza y descarga plantillas activas
 *   - Gestor (2) / Admin (1): además puede agregar y eliminar plantillas
 *
 * El formulario de agregar usa un panel inline deslizante. 
 * 
 */

// ── Autenticación ────────────────────────────────────────────
include('mod/login/check.php');
include('lang/lang.es');

// ── Variables de sesión ──────────────────────────────────────
$current_user_id   = $mySessionController->getVar('usuario');
$current_user_name = $mySessionController->getVar('nombre');
$current_user_rol  = (int)$mySessionController->getVar('rol');
$cds_domain        = $mySessionController->getVar('cds_domain');
$cds_locate        = $mySessionController->getVar('cds_locate');
$base_url          = $cds_domain . $cds_locate;

// ── Control de acceso ────────────────────────────────────────
$roles_permitidos = [1, 2, 3, 4, 5];
if (!in_array($current_user_rol, $roles_permitidos)) {
    header('Location: dashboard.php');
    exit;
}

// Determinar si el usuario puede gestionar (agregar/eliminar) plantillas
$puede_gestionar = in_array($current_user_rol, [1, 2]);

// ── Cargar datos ─────────────────────────────────────────────
require_once __DIR__ . '/inc/db/bdcommon.inc';
require_once __DIR__ . '/inc/plantillas_functions.php';

$plantillas  = [];
$error_carga = '';

try {
    $conn = new mysqli($db_host, $usuario, $clave, $db);
    if ($conn->connect_error) {
        throw new Exception('Error de conexión: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8');

    $plantillas = $puede_gestionar
        ? getTodasLasPlantillas($conn)
        : getPlantillasActivas($conn);

    $conn->close();
} catch (Exception $e) {
    error_log('HU-022 panel_plantillas.php: ' . $e->getMessage());
    $error_carga = 'No se pudieron cargar las plantillas. Intente recargar la página.';
}

// ── Agrupar por tipo ─────────────────────────────────────────
$por_tipo = [];
foreach ($plantillas as $p) {
    $por_tipo[$p['tipo']][] = $p;
}
?>
<!DOCTYPE html>
<html lang="es">
<?php include('head.php'); ?>
<body class="fondo-una d-flex flex-column min-vh-100">

    <!-- ── HEADER ─────────────────────────────────────────── -->
    <?php include 'header.php'; ?>

    <!-- ── PANEL INLINE: Agregar Plantilla (solo Gestor/Admin) ──
         Se usa un panel deslizante CSS en lugar de Bootstrap Modal
         para evitar el warning WAI-ARIA de aria-hidden con foco activo.
    ─────────────────────────────────────────────────────────── -->
    <!-- Estilos del panel deslizante y tarjetas de plantillas -->
    <style>
        /* ── Panel deslizante ── */
        #panelAgregarPlantilla {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1055;
            transform: translateY(-100%);
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            background: #fff;
            box-shadow: 0 8px 32px rgba(0,0,0,0.18);
            /* Altura máxima: deja siempre espacio visible debajo del panel */
            max-height: 80vh;
            overflow-y: auto;
        }
        /* Campos del formulario con tamaño de letra consistente */
        #panelAgregarPlantilla .form-label,
        #panelAgregarPlantilla .form-control,
        #panelAgregarPlantilla .form-select {
            font-size: 1.2rem;
        }
        /* ── Input file personalizado en español ──
           El input nativo se oculta visualmente pero sigue activo para
           el formulario. Un wrapper visible lo reemplaza con botón azul
           y texto en español, eliminando "Choose file" / "No file chosen".
        ── */
        #inputArchivo {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
            pointer-events: none;
        }
        .archivo-wrapper {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            border: 1px solid #ced4da;
            border-radius: 6px;
            padding: 0.3rem 0.5rem;
            background: #fff;
            min-height: 2.8rem;
            cursor: pointer;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .archivo-wrapper:focus-within,
        .archivo-wrapper.activo {
            border-color: var(--azul-una);
            box-shadow: 0 0 0 3px rgba(3,73,145,0.15);
        }
        .archivo-btn {
            flex-shrink: 0;
            background: var(--azul-una);
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 0.3rem 0.9rem;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s;
            white-space: nowrap;
        }
        .archivo-btn:hover { background: #023370; }
        .archivo-nombre {
            font-size: 1rem;
            color: #6c757d;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            flex: 1;
        }
        .archivo-nombre.seleccionado { color: #212529; }
        /* ── Tarjetas de plantillas: texto más legible ── */
        .card-una h5 {
            font-size: 1.2rem !important;
        }
        .card-una p.text-muted {
            font-size: 1.2rem !important;
        }
        .card-una .d-flex span {
            font-size: 1.2rem;
        }
        .card-una p[style*="0.78rem"] {
            font-size: 1.2rem !important;
        }
        /* Responsive: en móvil el panel ocupa toda la pantalla */
        @media (max-width: 576px) {
            #panelAgregarPlantilla {
                max-height: 100vh;
            }
        }
        /* ── Botones SweetAlert2 del mismo tamaño ── */
        .swal-btn-igual {
            min-width: 120px !important;
            font-size: 1rem !important;
            padding: 0.5rem 1.2rem !important;
        }
    </style>

    <?php if ($puede_gestionar): ?>
    <div id="panelAgregarPlantilla"
         role="region"
         aria-label="Formulario para agregar plantilla"
         aria-hidden="true">

        <!-- Cabecera del panel -->
        <div style="background: linear-gradient(135deg, var(--azul-una), var(--rojo-una)); padding: 1.1rem 1.5rem; display: flex; justify-content: space-between; align-items: center;">
            <h2 id="panelAgregarTitulo" style="color: #fff; font-size: 1.25rem; margin: 0; font-weight: 700;">
                <i class="bi bi-file-earmark-plus-fill me-2"></i>Agregar Nueva Plantilla
            </h2>
            <!-- Botón cerrar: Nielsen #3 — el usuario siempre puede salir -->
            <button id="btnCerrarPanel"
                    type="button"
                    aria-label="Cerrar formulario"
                    style="background: rgba(255,255,255,0.2); border: none; border-radius: 8px; color: #fff; width: 36px; height: 36px; font-size: 1.2rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s;">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <!-- Cuerpo del formulario -->
        <div class="container py-4" style="max-width: 720px;">
            <form id="formAgregarPlantilla" enctype="multipart/form-data" novalidate>
                <div class="row g-3">

                    <!-- Nombre -->
                    <div class="col-12">
                        <label for="inputNombre" class="form-label fw-semibold">
                            Nombre de la plantilla <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               class="form-control"
                               id="inputNombre"
                               name="nombre"
                               placeholder="Ej: Plantilla de Propuesta TFG — Anexo 1"
                               maxlength="255"
                               required>
                    </div>

                    <!-- Descripción -->
                    <div class="col-12">
                        <label for="inputDescripcion" class="form-label fw-semibold">Descripción</label>
                        <textarea class="form-control"
                                  id="inputDescripcion"
                                  name="descripcion"
                                  rows="2"
                                  maxlength="500"
                                  placeholder="Breve descripción del uso de esta plantilla..."></textarea>
                    </div>

                    <!-- En móvil cada campo ocupa toda la fila; en md+ se dividen -->
                    <div class="col-12 col-md-4">
                        <label for="selectTipo" class="form-label fw-semibold">
                            Tipo <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="selectTipo" name="tipo" required>
                            <option value="" disabled selected>Seleccione un tipo...</option>
                            <option value="Propuesta">Propuesta</option>
                            <option value="Informe Final">Informe Final</option>
                            <option value="Acta">Acta</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-8">
                        <label for="inputArchivo" class="form-label fw-semibold">
                            Archivo (PDF o DOCX, máx. 20 MB) <span class="text-danger">*</span>
                        </label>
                        <!-- Input real oculto: el wrapper visible lo reemplaza en español -->
                        <input type="file"
                               id="inputArchivo"
                               name="archivo"
                               accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                               required
                               tabindex="-1"
                               aria-hidden="true">
                        <!-- Wrapper personalizado: botón azul + texto en español -->
                        <div class="archivo-wrapper" id="archivoWrapper" role="button" tabindex="0"
                             aria-label="Seleccionar archivo PDF o DOCX">
                            <button type="button" class="archivo-btn" id="btnSeleccionarArchivo">
                                <i class="bi bi-folder2-open me-1"></i>Seleccionar archivo
                            </button>
                            <span class="archivo-nombre" id="archivoNombre">Ningún archivo seleccionado</span>
                        </div>
                        <!-- Retroalimentación de tamaño/error: Nielsen #1 -->
                        <div id="archivoInfo" class="form-text mt-1" style="display:none;"></div>
                    </div>

                    <!-- Botones: en móvil apilados, en md+ en línea a la derecha -->
                    <div class="col-12 d-flex flex-column flex-sm-row gap-2 justify-content-end pt-2 border-top">
                        <button type="button"
                                id="btnCancelarForm"
                                class="btn btn-una-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancelar
                        </button>
                        <button type="submit"
                                class="btn btn-una-primary"
                                id="btnGuardarPlantilla">
                            <i class="bi bi-cloud-upload-fill me-1"></i>Guardar Plantilla
                        </button>
                    </div>

                </div>
            </form>
        </div>
    </div>

    <!-- Overlay semitransparente detrás del panel -->
    <div id="panelOverlay"
         aria-hidden="true"
         style="
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 1054;
            transition: opacity 0.3s;
         "></div>
    <?php endif; ?>

    <!-- ── CONTENIDO PRINCIPAL ───────────────────────────── -->
    <main class="flex-fill" id="contenidoPrincipal">
        <div class="container my-5">

            <!-- Encabezado de página -->
            <div class="dashboard-header text-center mb-5">
                <h1>Plantillas Oficiales</h1>
                <p class="lead">
                    <?php if ($puede_gestionar): ?>
                        Gestione las plantillas disponibles para los estudiantes (Anexo 1 — Instrucción de Dirección).
                    <?php else: ?>
                        Descargue las plantillas oficiales para estandarizar sus entregas de TFG.
                    <?php endif; ?>
                </p>
            </div>

            <!-- Alerta de error de carga -->
            <?php if ($error_carga): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?= htmlspecialchars($error_carga) ?>
                </div>
            <?php endif; ?>

            <!-- ── BARRA DE ACCIONES SUPERIOR ─────────────── -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                
                <a href="<?= htmlspecialchars($base_url) ?>dashboard.php"
                   class="btn btn-secondary">
                    <i class="bi bi-arrow-left-circle me-2"></i>Volver al Panel Principal
                </a>

                <?php if ($puede_gestionar): ?>
                    <!-- El botón abre el panel inline, no un modal de Bootstrap -->
                    <button id="btnAbrirPanel"
                            type="button"
                            class="btn btn-una-primary"
                            aria-expanded="false"
                            aria-controls="panelAgregarPlantilla">
                        <i class="bi bi-plus-circle-fill me-2"></i>Agregar Plantilla
                    </button>
                <?php endif; ?>
            </div>

            <!-- ── LISTADO DE PLANTILLAS ───────────────────── -->
            <?php if (empty($plantillas)): ?>
                <!-- Estado vacío: Nielsen #1 — visibilidad del estado del sistema -->
                <div class="empty-state">
                    <i class="bi bi-folder2-open" style="font-size: 3rem; color: var(--azul-una);"></i>
                    <h3 class="mt-3">No hay plantillas disponibles</h3>
                    <p>
                        <?= $puede_gestionar
                            ? 'Agregue la primera plantilla usando el botón superior.'
                            : 'Aún no se han publicado plantillas. Consulte con el Gestor Académico.' ?>
                    </p>
                </div>
            <?php else: ?>
                <?php foreach ($por_tipo as $tipo => $items): ?>
                    <div class="quick-actions-section mb-5">
                        <h2 class="section-title">
                            <i class="bi bi-folder-fill me-2" style="color: var(--azul-una);"></i>
                            <?= htmlspecialchars($tipo) ?>
                            <span class="badge <?= getColorPorTipo($tipo) ?> ms-2" style="font-size: 0.9rem;">
                                <?= count($items) ?>
                            </span>
                        </h2>

                        <div class="row g-3">
                            <?php foreach ($items as $plantilla): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="card-una h-100 d-flex flex-column"
                                         style="<?= (!($plantilla['activo'] ?? 1)) ? 'opacity:0.6;' : '' ?>">

                                        <!-- Icono y nombre -->
                                        <div class="d-flex align-items-start gap-3 mb-3">
                                            <i class="bi <?= getIconoPorMime($plantilla['mime_type']) ?>"
                                               style="font-size:2.5rem; color:<?= $plantilla['mime_type'] === 'application/pdf' ? '#dc3545' : '#0d6efd' ?>; flex-shrink:0;"></i>
                                            <div>
                                                <h5 class="mb-1" style="color:var(--azul-una); font-size:1rem;">
                                                    <?= htmlspecialchars($plantilla['nombre']) ?>
                                                </h5>
                                                <span class="badge <?= $plantilla['mime_type'] === 'application/pdf' ? 'bg-danger' : 'bg-primary' ?>" style="font-size:0.7rem;">
                                                    <?= $plantilla['mime_type'] === 'application/pdf' ? 'PDF' : 'DOCX' ?>
                                                </span>
                                                <?php if ($puede_gestionar && !($plantilla['activo'] ?? 1)): ?>
                                                    <span class="badge bg-secondary ms-1" style="font-size:0.7rem;">Oculta</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Descripción -->
                                        <?php if (!empty($plantilla['descripcion'])): ?>
                                            <p class="text-muted mb-3" style="font-size:0.9rem; flex-grow:1;">
                                                <?= htmlspecialchars($plantilla['descripcion']) ?>
                                            </p>
                                        <?php else: ?>
                                            <div class="flex-grow-1"></div>
                                        <?php endif; ?>

                                        <!-- Metadatos -->
                                        <div class="d-flex justify-content-between align-items-center mb-3"
                                             style="font-size:0.8rem; color:var(--gris-una);">
                                            <span><i class="bi bi-hdd me-1"></i><?= formatearTamano((int)$plantilla['file_size']) ?></span>
                                            <span><i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y', strtotime($plantilla['created_at'])) ?></span>
                                        </div>

                                        <!-- Quién subió (solo Gestor/Admin) -->
                                        <?php if ($puede_gestionar && !empty($plantilla['subido_por_nombre'])): ?>
                                            <p style="font-size:0.78rem; color:var(--gris-una);" class="mb-3">
                                                <i class="bi bi-person-fill me-1"></i>
                                                <?= htmlspecialchars($plantilla['subido_por_nombre']) ?>
                                            </p>
                                        <?php endif; ?>

                                        <!-- Acciones -->
                                        <div class="d-flex gap-2 mt-auto">
                                            <a href="<?= htmlspecialchars($base_url) ?>descargar_plantilla.php?id=<?= (int)$plantilla['id'] ?>"
                                               class="btn btn-una-primary btn-sm flex-grow-1"
                                               title="Descargar <?= htmlspecialchars($plantilla['file_name']) ?>">
                                                <i class="bi bi-download me-1"></i>Descargar
                                            </a>

                                            <?php if ($puede_gestionar): ?>
                                                <!-- Botón ocultar/mostrar: cambia según estado actual -->
                                                <button class="btn btn-sm btn-toggle-visibilidad
                                                               <?= ($plantilla['activo'] ?? 1) ? 'btn-outline-secondary' : 'btn-outline-success' ?>"
                                                        data-id="<?= (int)$plantilla['id'] ?>"
                                                        data-activo="<?= (int)($plantilla['activo'] ?? 1) ?>"
                                                        title="<?= ($plantilla['activo'] ?? 1) ? 'Ocultar plantilla' : 'Hacer visible' ?>">
                                                    <i class="bi <?= ($plantilla['activo'] ?? 1) ? 'bi-eye-slash-fill' : 'bi-eye-fill' ?>"></i>
                                                </button>

                                                <!-- Botón eliminar -->
                                                <button class="btn btn-sm btn-outline-danger btn-eliminar-plantilla"
                                                        data-id="<?= (int)$plantilla['id'] ?>"
                                                        data-nombre="<?= htmlspecialchars($plantilla['nombre'], ENT_QUOTES) ?>"
                                                        title="Eliminar plantilla">
                                                    <i class="bi bi-trash3-fill"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>

                                    </div><!-- /.card-una -->
                                </div><!-- /.col -->
                            <?php endforeach; ?>
                        </div><!-- /.row -->
                    </div><!-- /.quick-actions-section -->
                <?php endforeach; ?>
            <?php endif; ?>

        </div><!-- /.container -->
    </main>

    <!-- ── FOOTER ─────────────────────────────────────────── -->
    <footer class="footer-una mt-auto">
        <div class="container">
            <p class="mb-1">&copy; <?= date('Y') ?> Universidad Nacional de Costa Rica</p>
            <small>Escuela de Informática — Proyecto SGPFL v3.0</small>
        </div>
    </footer>

    <!-- ── SCRIPTS ────────────────────────────────────────── -->
    <script>
    (function () {
        'use strict';

        const BASE_URL = <?= json_encode($base_url) ?>;

        <?php if ($puede_gestionar): ?>
        // ── Referencias DOM del panel ──────────────────────────────
        const panel          = document.getElementById('panelAgregarPlantilla');
        const overlay        = document.getElementById('panelOverlay');
        const btnAbrir       = document.getElementById('btnAbrirPanel');
        const btnCerrar      = document.getElementById('btnCerrarPanel');
        const btnCancelar    = document.getElementById('btnCancelarForm');
        const form           = document.getElementById('formAgregarPlantilla');
        const inputArchivo   = document.getElementById('inputArchivo');
        const archivoInfo    = document.getElementById('archivoInfo');
        const archivoNombre  = document.getElementById('archivoNombre');
        const archivoWrapper = document.getElementById('archivoWrapper');
        const btnSeleccionar = document.getElementById('btnSeleccionarArchivo');

        // ── Selector de archivo personalizado ─────────────────────
        function abrirSelector() { inputArchivo.click(); }
        btnSeleccionar.addEventListener('click', abrirSelector);
        // Enter/Space sobre el wrapper también abre el selector (accesibilidad)
        archivoWrapper.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); abrirSelector(); }
        });
        // Actualizar nombre visible al seleccionar archivo
        inputArchivo.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) {
                archivoNombre.textContent = 'Ningún archivo seleccionado';
                archivoNombre.classList.remove('seleccionado');
                archivoWrapper.classList.remove('activo');
                archivoInfo.style.display = 'none';
                return;
            }
            archivoNombre.textContent = file.name;
            archivoNombre.classList.add('seleccionado');
            archivoWrapper.classList.add('activo');
            const mb = (file.size / 1048576).toFixed(2);
            archivoInfo.textContent = 'Tamaño: ' + mb + ' MB';
            archivoInfo.style.display = 'block';
            archivoInfo.className = file.size > 20 * 1024 * 1024
                ? 'form-text text-danger mt-1'
                : 'form-text text-muted mt-1';
        });

        // ── Abrir panel deslizante ────────────────────────────────
        function abrirPanel() {
            overlay.style.display = 'block';
            overlay.offsetHeight; // forzar reflow para activar transición CSS
            overlay.style.opacity = '1';
            panel.style.transform = 'translateY(0)';
            panel.setAttribute('aria-hidden', 'false');
            btnAbrir.setAttribute('aria-expanded', 'true');
            document.getElementById('inputNombre').focus();
        }

        // ── Cerrar panel y devolver foco al botón disparador ──────
        function cerrarPanel() {
            panel.style.transform = 'translateY(-100%)';
            overlay.style.opacity = '0';
            panel.setAttribute('aria-hidden', 'true');
            btnAbrir.setAttribute('aria-expanded', 'false');
            setTimeout(function () {
                overlay.style.display = 'none';
                form.reset();
                archivoNombre.textContent = 'Ningún archivo seleccionado';
                archivoNombre.classList.remove('seleccionado');
                archivoWrapper.classList.remove('activo');
                archivoInfo.style.display = 'none';
            }, 350);
            btnAbrir.focus();
        }

        btnAbrir.addEventListener('click', abrirPanel);
        btnCerrar.addEventListener('click', cerrarPanel);
        btnCancelar.addEventListener('click', cerrarPanel);
        overlay.addEventListener('click', cerrarPanel);

        // Cerrar con Escape (Nielsen #3)
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && panel.getAttribute('aria-hidden') === 'false') {
                cerrarPanel();
            }
        });

        // ── Envío del formulario ──────────────────────────────────
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            if (!form.checkValidity()) { form.reportValidity(); return; }

            const btn = document.getElementById('btnGuardarPlantilla');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';

            try {
                const resp = await fetch(BASE_URL + 'plantilla_upload_process.php', {
                    method: 'POST',
                    body: new FormData(form),
                });
                const data = await resp.json();

                if (data.ok) {
                    cerrarPanel();
                    await Swal.fire({
                        icon: 'success',
                        title: '¡Plantilla guardada!',
                        text: 'La plantilla se agregó correctamente.',
                        confirmButtonColor: '#034991',
                        timer: 2500,
                        timerProgressBar: true,
                    });
                    location.reload();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al guardar',
                        text: data.error || 'Ocurrió un error inesperado.',
                        confirmButtonColor: '#CD1719',
                    });
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de red',
                    text: 'No se pudo conectar con el servidor.',
                    confirmButtonColor: '#CD1719',
                });
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-cloud-upload-fill me-1"></i>Guardar Plantilla';
            }
        });
        <?php endif; ?>

        // ── Cambiar visibilidad de plantilla (ocultar / mostrar) ───────
        document.querySelectorAll('.btn-toggle-visibilidad').forEach(function (btn) {
            btn.addEventListener('click', async function () {
                const id          = this.dataset.id;
                const activoActual = parseInt(this.dataset.activo);
                const nuevoActivo  = activoActual === 1 ? 0 : 1;
                const accion       = nuevoActivo === 0 ? 'ocultar' : 'hacer visible';

                // Confirmar antes de cambiar (Nielsen #5)
                const result = await Swal.fire({
                    icon: 'question',
                    title: nuevoActivo === 0 ? '¿Ocultar plantilla?' : '¿Mostrar plantilla?',
                    text: nuevoActivo === 0
                        ? 'Los estudiantes no podrán ver esta plantilla.'
                        : 'La plantilla volverá a ser visible para los estudiantes.',
                    showCancelButton: true,
                    confirmButtonText: nuevoActivo === 0 ? 'Sí, ocultar' : 'Sí, mostrar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: nuevoActivo === 0 ? '#6c757d' : '#198754',
                    cancelButtonColor: '#6c757d',
                    customClass: {
                        confirmButton: 'swal-btn-igual',
                        cancelButton:  'swal-btn-igual',
                    },
                });

                if (!result.isConfirmed) return;

                try {
                    const fd = new FormData();
                    fd.append('id',     id);
                    fd.append('activo', nuevoActivo);

                    const resp = await fetch(BASE_URL + 'plantilla_toggle_activo.php', {
                        method: 'POST', body: fd,
                    });
                    const data = await resp.json();

                    if (data.ok) {
                        // Actualizar UI sin recargar: cambiar icono, color y data-activo
                        this.dataset.activo = nuevoActivo;
                        if (nuevoActivo === 0) {
                            this.classList.replace('btn-outline-secondary', 'btn-outline-success');
                            this.querySelector('i').className = 'bi bi-eye-fill';
                            this.title = 'Hacer visible';
                            // Marcar la tarjeta como oculta visualmente
                            this.closest('.card-una').style.opacity = '0.6';
                        } else {
                            this.classList.replace('btn-outline-success', 'btn-outline-secondary');
                            this.querySelector('i').className = 'bi bi-eye-slash-fill';
                            this.title = 'Ocultar plantilla';
                            this.closest('.card-una').style.opacity = '1';
                        }
                        // Notificación breve sin bloquear (Nielsen #1)
                        Swal.fire({
                            icon: 'success',
                            title: nuevoActivo === 0 ? 'Plantilla ocultada' : 'Plantilla visible',
                            timer: 1500,
                            timerProgressBar: true,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end',
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.error || 'No se pudo cambiar la visibilidad.',
                            confirmButtonColor: '#CD1719',
                        });
                    }
                } catch (err) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de red',
                        text: 'No se pudo conectar con el servidor.',
                        confirmButtonColor: '#CD1719',
                    });
                }
            });
        });

        // ── Eliminar plantilla ────────────────────────────────────
        document.querySelectorAll('.btn-eliminar-plantilla').forEach(function (btn) {
            btn.addEventListener('click', async function () {
                const id     = this.dataset.id;
                const nombre = this.dataset.nombre;

                const result = await Swal.fire({
                    icon: 'warning',
                    title: '¿Eliminar plantilla?',
                    html: 'La plantilla <strong>' + nombre + '</strong> será eliminada permanentemente.',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#CD1719',
                    cancelButtonColor: '#6c757d',
                    customClass: {
                        confirmButton: 'swal-btn-igual',
                        cancelButton:  'swal-btn-igual',
                    },
                });

                if (!result.isConfirmed) return;

                try {
                    const fd = new FormData();
                    fd.append('id', id);
                    const resp = await fetch(BASE_URL + 'plantilla_delete_process.php', {
                        method: 'POST', body: fd,
                    });
                    const data = await resp.json();

                    if (data.ok) {
                        // Timer + botón: el usuario puede continuar inmediatamente (Nielsen #1 y #3)
                        await Swal.fire({
                            icon: 'success',
                            title: 'Plantilla eliminada',
                            text: 'La plantilla fue eliminada correctamente.',
                            confirmButtonText: 'Continuar',
                            confirmButtonColor: '#034991',
                            timer: 3000,
                            timerProgressBar: true,
                            showConfirmButton: true,
                        });
                        location.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al eliminar',
                            text: data.error || 'No se pudo eliminar la plantilla.',
                            confirmButtonColor: '#CD1719',
                        });
                    }
                } catch (err) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de red',
                        text: 'No se pudo conectar con el servidor.',
                        confirmButtonColor: '#CD1719',
                    });
                }
            });
        });

    })();
    </script>

</body>
</html>
