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

// Obtener tipos de proyecto
$project_types = [
    ['id' => 1, 'type_name' => 'Individual', 'max_members' => 1],
    ['id' => 2, 'type_name' => 'Por Parejas', 'max_members' => 2],
    ['id' => 3, 'type_name' => 'Seminario', 'max_members' => 5]
];

// OBTENER USUARIO REAL AUTENTICADO
$current_user_id = $mySessionController->getVar("usuario");
$current_user_name = $mySessionController->getVar("nombre");
$current_user_rol = $mySessionController->getVar("rol");

// Verificar que sea estudiante (rol 4)
if ($current_user_rol != 4) {
    header('Location: ' . $base_url . 'dashboard.php');
    exit;
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
    </main>
    <!-- =============================== FOOTER =============================== -->
    <?php include $base_path . '/footer.php'; ?>
    <script src="<?= $base_url ?>inc/js/tfg_upload.js"></script>
</body>
</html>