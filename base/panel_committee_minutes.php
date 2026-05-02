<?php
declare(strict_types=1);

include("mod/login/check.php");
include('lang/lang.es');

require_once __DIR__ . '/mod/admin/users/CommitteeMinuteProjectRepository.php';
require_once __DIR__ . '/mod/admin/users/CommitteeMinuteParticipantsRepository.php';
require_once __DIR__ . '/mod/admin/users/CommitteeMinuteListRepository.php';

$current_user_id = (string)$mySessionController->getVar("usuario");
$current_user_name = (string)$mySessionController->getVar("nombre");
$current_user_rol = (int)$mySessionController->getVar("rol");
$base_url = (string)$mySessionController->getVar("cds_domain") . (string)$mySessionController->getVar("cds_locate");

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function formatBytes(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }

    if ($bytes < 1048576) {
        return round($bytes / 1024, 2) . ' KB';
    }

    return round($bytes / 1048576, 2) . ' MB';
}

$selected_project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$form_error = '';
$projects = [];
$participants = [];
$minutes = [];
$selected_project = null;

try {
    $dbcfg = (function (string $path): array {
        $db_host = null;
        $usuario = null;
        $clave = null;
        $db = null;
        require $path;

        return [
            'host' => (string)$db_host,
            'user' => (string)$usuario,
            'pass' => (string)$clave,
            'name' => (string)$db,
        ];
    })(__DIR__ . '/inc/db/bdcommon.inc');

    $connection = new mysqli($dbcfg['host'], $dbcfg['user'], $dbcfg['pass'], $dbcfg['name']);
    if ($connection->connect_error) {
        throw new RuntimeException('No se pudo conectar a la base de datos.');
    }

    $connection->set_charset('utf8');

    $project_repository = new CommitteeMinuteProjectRepository($connection);
    $participant_repository = new CommitteeMinuteParticipantsRepository($connection);
    $minute_list_repository = new CommitteeMinuteListRepository($connection);

    $projects = $project_repository->findProjectsByCommitteeMember($current_user_id);

    $available_project_ids = array_map(
        static fn(array $project): int => (int)$project['id_aprobado'],
        $projects
    );

    if ($selected_project_id > 0) {
        if (!in_array($selected_project_id, $available_project_ids, true)) {
            $form_error = 'El proyecto seleccionado no pertenece al comité asesor del usuario actual.';
        } else {
            foreach ($projects as $project) {
                if ((int)$project['id_aprobado'] === $selected_project_id) {
                    $selected_project = $project;
                    break;
                }
            }

            $participants = $participant_repository->findParticipantsByProjectId($selected_project_id);
            $minutes = $minute_list_repository->findMinutesByProjectId($selected_project_id);
        }
    }

    $connection->close();
} catch (Throwable $e) {
    $form_error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<?php include 'head.php'; ?>
<link rel="stylesheet" href="inc/css/admin_panels_responsive.css">
<link rel="stylesheet" href="inc/css/committee-minutes.css">

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('minute-upload-form');
    const saveButton = document.getElementById('minute-save-button');

    if (!form || !saveButton) {
        return;
    }

    form.addEventListener('submit', function (event) {
        const confirmed = window.confirm(
            '¿Desea registrar esta minuta para la fecha seleccionada?'
        );

        if (!confirmed) {
            event.preventDefault();
            return;
        }

        saveButton.disabled = true;
        saveButton.innerHTML = '<i class="bi bi-hourglass-split me-2"></i> Guardando...';
    });
});
</script>

<body class="fondo-una d-flex flex-column min-vh-100">

<?php include 'header.php'; ?>

<main class="flex-fill">
    <div class="container my-5">

        <div class="dashboard-header text-center mb-5">
            <h1>Registro de minutas del Comité Asesor</h1>
            <p class="lead">
                Seleccione un proyecto donde usted forme parte del comité asesor,
                cargue la minuta oficial en PDF y marque los asistentes de la sesión.
            </p>
        </div>

        <?php
        $minute_flash = null;

        if (isset($_SESSION['minute_flash']) && is_array($_SESSION['minute_flash'])) {
            $minute_flash = $_SESSION['minute_flash'];
            unset($_SESSION['minute_flash']);
        }
        ?>

        <?php if (is_array($minute_flash) && isset($minute_flash['message'], $minute_flash['type'])): ?>
            <div class="alert alert-<?php echo htmlspecialchars((string)$minute_flash['type'], ENT_QUOTES, 'UTF-8'); ?> shadow-sm" role="alert">
                <?php echo htmlspecialchars((string)$minute_flash['message'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <?php if ($form_error !== ''): ?>
            <div class="alert alert-danger shadow-sm">
                <?php echo h($form_error); ?>
            </div>
        <?php endif; ?>

        <?php if ($form_error === '' && empty($projects)): ?>
            <div class="card document-table mb-4">
                <div class="card-body p-4">
                    <div class="minute-empty-state">
                        <i class="bi bi-folder2-open"></i>
                        <h5>No hay proyectos disponibles</h5>
                        <p class="mb-0">
                            En este momento no tiene proyectos asignados como miembro del comité asesor.
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($form_error === '' && !empty($projects)): ?>
            <div class="card document-table mb-4">
                <div class="card-body p-4">
                    <h2 class="minute-section-title mb-4">
                        <i class="bi bi-folder-check"></i> Selección de proyecto
                    </h2>

                    <form method="get" action="panel_committee_minutes.php" class="row g-3 align-items-end">
                        <div class="col-lg-9">
                            <label for="project_id" class="form-label fw-semibold">Proyecto</label>
                            <select name="project_id" id="project_id" class="form-select" required>
                                <option value="">Seleccione un proyecto</option>
                                <?php foreach ($projects as $project): ?>
                                    <option
                                        value="<?php echo h((string)$project['id_aprobado']); ?>"
                                        <?php echo $selected_project_id === (int)$project['id_aprobado'] ? 'selected' : ''; ?>
                                    >
                                        <?php echo h((string)$project['identificador']); ?>
                                        -
                                        <?php echo h((string)$project['nombre']); ?>
                                        (<?php echo h((string)$project['committee_role']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-lg-3">
                            <div class="d-grid">
                                <button type="submit" class="btn btn-danger">
                                    <i class="bi bi-search me-2"></i> Cargar asistentes
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="alert alert-info mt-4 mb-0">
                        Solo se listan proyectos donde el usuario autenticado figura como
                        <strong>tutor</strong>, <strong>asesor 1</strong> o <strong>asesor 2</strong>.
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($form_error === '' && $selected_project !== null): ?>
            <div class="card document-table mb-4">
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
                        <div>
                            <h2 class="minute-section-title mb-2">
                                <i class="bi bi-file-earmark-text"></i> Datos de la minuta
                            </h2>
                            <p class="text-muted mb-0">
                                Proyecto seleccionado:
                                <strong><?php echo h((string)$selected_project['identificador']); ?></strong>
                                — <?php echo h((string)$selected_project['nombre']); ?>
                            </p>
                        </div>
                    </div>

                    <form id="minute-upload-form" action="mod/admin/users/minute_upload_process.php" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="project_id" value="<?php echo h((string)$selected_project_id); ?>">

                        <div class="row g-4">
                            <div class="col-md-6">
                                <label for="session_date" class="form-label fw-semibold">Fecha de sesión</label>
                                <input
                                    type="date"
                                    name="session_date"
                                    id="session_date"
                                    class="form-control"
                                    required
                                >
                                <div class="form-text">
                                    Solo se permite una minuta por fecha de sesión para cada proyecto.
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="minute_pdf" class="form-label fw-semibold">Minuta en PDF</label>
                                <input
                                    type="file"
                                    name="minute_pdf"
                                    id="minute_pdf"
                                    class="form-control"
                                    accept=".pdf,application/pdf"
                                    required
                                >
                            </div>
                        </div>

                        <div class="alert alert-light border shadow-sm mt-4 mb-4" role="alert">
                            <div class="d-flex gap-3 align-items-start">
                                <i class="bi bi-info-circle fs-4 text-primary"></i>
                                <div>
                                    <strong>Estado actual del flujo</strong>
                                    <div class="small text-muted mt-1">
                                        La minuta se registrará al enviar este formulario.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h3 class="minute-subtitle mb-3">Asistentes de la sesión</h3>

                        <?php if (empty($participants)): ?>
                            <div class="minute-empty-state">
                                <i class="bi bi-people"></i>
                                <h5>No se encontraron participantes</h5>
                                <p class="mb-0">Este proyecto no tiene participantes válidos para registrar asistencia.</p>
                            </div>
                        <?php else: ?>
                            <div class="committee-minutes-participants-table-wrapper">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead>
                                        <tr>
                                            <th class="width-110">Asistió</th>
                                            <th>ID Usuario</th>
                                            <th>Nombre</th>
                                            <th>Correo</th>
                                            <th>Rol</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($participants as $participant): ?>
                                        <tr>
                                            <td>
                                                <div class="form-check d-flex justify-content-center">
                                                    <input
                                                        class="form-check-input"
                                                        type="checkbox"
                                                        name="attendees[]"
                                                        value="<?php echo h((string)$participant['user_id']); ?>"
                                                        id="attendee_<?php echo h((string)$participant['user_id']); ?>"
                                                    >
                                                </div>
                                            </td>
                                            <td><?php echo h((string)$participant['user_id']); ?></td>
                                            <td><?php echo h((string)$participant['nombre']); ?></td>
                                            <td><?php echo h((string)($participant['email'] ?? '')); ?></td>
                                            <td><?php echo h((string)$participant['participant_role']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex flex-column flex-md-row justify-content-center gap-2 mt-4">
                            <button type="submit" id="minute-save-button" class="btn btn-danger">
                                <i class="bi bi-save me-2"></i> Guardar minuta
                            </button>

                            <a href="panel_committee_minutes.php" class="btn btn-outline-secondary">
                                <i class="bi bi-eraser me-2"></i> Limpiar selección
                            </a>
                        </div>

                        <div class="small text-muted mt-2 text-center">
                            Después del guardado, la minuta aparecerá en el listado inferior.
                        </div>
                    </form>
                </div>
            </div>

            <div class="card document-table mb-4">
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
                        <div>
                            <h2 class="minute-section-title mb-2">
                                <i class="bi bi-journal-text"></i> Minutas registradas del proyecto
                            </h2>
                            <p class="text-muted mb-0">
                                Historial de minutas registradas para el proyecto seleccionado, ordenadas por fecha de sesión.
                            </p>
                        </div>
                    </div>

                    <?php if (empty($minutes)): ?>
                        <div class="minute-empty-state">
                            <i class="bi bi-file-earmark-text"></i>
                            <h5>No hay minutas registradas</h5>
                            <p class="mb-0">
                                Aún no se han registrado minutas para este proyecto.
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="committee-minutes-list-table-wrapper">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Fecha de sesión</th>
                                        <th>Archivo</th>
                                        <th>Tamaño</th>
                                        <th>Subida por</th>
                                        <th>Asistieron</th>
                                        <th>Total</th>
                                        <th>Registrada en</th>
                                        <th class="width-140">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($minutes as $minute): ?>
                                    <tr>
                                        <td><?php echo h((string)$minute['id']); ?></td>
                                        <td><?php echo h((string)$minute['session_date']); ?></td>
                                        <td><?php echo h((string)$minute['file_name']); ?></td>
                                        <td><?php echo h(formatBytes((int)$minute['file_size'])); ?></td>
                                        <td><?php echo h((string)$minute['uploaded_by_name']); ?></td>
                                        <td><?php echo h((string)$minute['attended_count']); ?></td>
                                        <td><?php echo h((string)$minute['total_participants']); ?></td>
                                        <td><?php echo h((string)$minute['created_at']); ?></td>
                                        <td>
                                            <a
                                                href="mod/admin/users/minute_download.php?minute_id=<?php echo h((string)$minute['id']); ?>"
                                                class="btn btn-outline-primary btn-sm"
                                            >
                                                <i class="bi bi-download me-1"></i> Descargar
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left-circle"></i> Volver al panel principal
            </a>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>

<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .dashboard-header h1 {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        font-weight: 700;
        color: #034991;
        margin-bottom: 0.5rem;
    }

    .dashboard-header .lead {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #6c757d;
        max-width: 920px;
        margin: 0 auto;
    }

    .document-table {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        overflow: hidden;
        border: 0;
    }

    .minute-section-title {
        color: #c8151a;
        font-size: 1.35rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .minute-subtitle {
        color: #034991;
        font-size: 1.15rem;
        font-weight: 700;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .table thead th {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        font-weight: 600;
        font-size: 0.95rem;
        color: #034991;
        border-bottom: 2px solid #dee2e6;
    }

    .table tbody td {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        vertical-align: middle;
    }

    .minute-empty-state {
        padding: 2.5rem 1.5rem;
        text-align: center;
        color: #6c757d;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .minute-empty-state i {
        font-size: 2.5rem;
        color: #adb5bd;
        margin-bottom: 0.75rem;
        display: block;
    }
</style>

</body>
</html>