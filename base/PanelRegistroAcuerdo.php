<?php
declare(strict_types=1);

include("mod/login/check.php");
include('lang/lang.es');

$current_user_id = (string)$mySessionController->getVar("usuario");
$current_user_name = (string)$mySessionController->getVar("nombre");
$current_user_rol = (int)$mySessionController->getVar("rol");

if ($current_user_rol !== 2 && $current_user_rol !== 3) {
    header('Location: dashboard.php');
    exit;
}

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
$selected_minute_id = isset($_GET['minute_id']) ? (int)$_GET['minute_id'] : 0;
$active_tab = (isset($_GET['tab']) && $_GET['tab'] === 'asistentes') ? 'asistentes' : 'acuerdos';

$q_project_id = isset($_GET['q_project_id']) ? trim($_GET['q_project_id']) : '';
$q_student    = isset($_GET['q_student'])    ? trim($_GET['q_student'])    : '';
$q_minute_id  = isset($_GET['q_minute_id'])  ? (int)$_GET['q_minute_id']  : 0;

$form_error = '';
$projects = [];
$minutes = [];
$attendees = [];
$selected_project = null;
$selected_minute = null;
$all_agreements = [];

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

    // Si se busca por ID de acuerdo, determinar el proyecto al que pertenece
    if ($q_minute_id > 0 && $selected_project_id === 0) {
        $project_from_agreement = 0;

        $stmt_find_min = $connection->prepare(
            'SELECT project_id FROM project_minutes WHERE id = ? LIMIT 1'
        );
        if ($stmt_find_min !== false) {
            $stmt_find_min->bind_param('i', $q_minute_id);
            $stmt_find_min->execute();
            $row_find_min = $stmt_find_min->get_result()->fetch_assoc();
            $stmt_find_min->close();
            if ($row_find_min) {
                $project_from_agreement = (int)$row_find_min['project_id'];
            }
        }

        if ($project_from_agreement === 0) {
            $stmt_find_def = $connection->prepare(
                'SELECT proyecto_id FROM acuerdo_defensa_publica WHERE id = ? LIMIT 1'
            );
            if ($stmt_find_def !== false) {
                $stmt_find_def->bind_param('i', $q_minute_id);
                $stmt_find_def->execute();
                $row_find_def = $stmt_find_def->get_result()->fetch_assoc();
                $stmt_find_def->close();
                if ($row_find_def) {
                    $project_from_agreement = (int)$row_find_def['proyecto_id'];
                }
            }
        }

        // En acuerdos de aprobación, el ID del acuerdo corresponde al ID del proyecto aprobado.
        if ($project_from_agreement === 0) {
            $stmt_find_app = $connection->prepare(
                'SELECT id_aprobado FROM proyecto_aprobado WHERE id_aprobado = ? AND aprobado = 1 AND documento IS NOT NULL LIMIT 1'
            );
            if ($stmt_find_app !== false) {
                $stmt_find_app->bind_param('i', $q_minute_id);
                $stmt_find_app->execute();
                $row_find_app = $stmt_find_app->get_result()->fetch_assoc();
                $stmt_find_app->close();
                if ($row_find_app) {
                    $project_from_agreement = (int)$row_find_app['id_aprobado'];
                }
            }
        }

        if ($project_from_agreement > 0) {
            $selected_project_id = $project_from_agreement;
            $selected_minute_id = $q_minute_id;
        }
    }

    if ($current_user_rol === 2) {
        $sql_projects = "
            SELECT
                p.id_aprobado,
                p.identificador,
                p.nombre,
                p.fecha_creacion,
                p.fecha_finalizacion,
                c.tutor,
                c.asesor_1,
                c.asesor_2,
                su_tutor.nombre AS tutor_nombre,
                su_a1.nombre AS asesor1_nombre,
                su_a2.nombre AS asesor2_nombre
            FROM proyecto_aprobado p
            INNER JOIN comite c
                ON c.Id = p.comite_id
            LEFT JOIN sis_user su_tutor
                ON su_tutor.id = c.tutor
            LEFT JOIN sis_user su_a1
                ON su_a1.id = c.asesor_1
            LEFT JOIN sis_user su_a2
                ON su_a2.id = c.asesor_2
            WHERE p.aprobado = 1
            ORDER BY p.fecha_creacion DESC, p.nombre ASC
        ";

        $result_projects = $connection->query($sql_projects);
        if ($result_projects === false) {
            throw new RuntimeException('No se pudo consultar la lista de proyectos.');
        }

        while ($row = $result_projects->fetch_assoc()) {
            $projects[] = $row;
        }
    } else {
        $sql_projects = "
            SELECT
                p.id_aprobado,
                p.identificador,
                p.nombre,
                p.fecha_creacion,
                p.fecha_finalizacion,
                c.tutor,
                c.asesor_1,
                c.asesor_2,
                su_tutor.nombre AS tutor_nombre,
                su_a1.nombre AS asesor1_nombre,
                su_a2.nombre AS asesor2_nombre,
                CASE
                    WHEN c.tutor = ? THEN 'TUTOR'
                    WHEN c.asesor_1 = ? THEN 'ASESOR_1'
                    WHEN c.asesor_2 = ? THEN 'ASESOR_2'
                    ELSE 'MIEMBRO_COMITE'
                END AS committee_role
            FROM proyecto_aprobado p
            INNER JOIN comite c
                ON c.Id = p.comite_id
            LEFT JOIN sis_user su_tutor
                ON su_tutor.id = c.tutor
            LEFT JOIN sis_user su_a1
                ON su_a1.id = c.asesor_1
            LEFT JOIN sis_user su_a2
                ON su_a2.id = c.asesor_2
            WHERE p.aprobado = 1
              AND (
                    c.tutor = ?
                 OR c.asesor_1 = ?
                 OR c.asesor_2 = ?
              )
            ORDER BY p.fecha_creacion DESC, p.nombre ASC
        ";

        $stmt_projects = $connection->prepare($sql_projects);
        if ($stmt_projects === false) {
            throw new RuntimeException('No se pudo preparar la consulta de proyectos del comité.');
        }

        $stmt_projects->bind_param(
            'ssssss',
            $current_user_id,
            $current_user_id,
            $current_user_id,
            $current_user_id,
            $current_user_id,
            $current_user_id
        );
        $stmt_projects->execute();
        $result_projects = $stmt_projects->get_result();

        while ($row = $result_projects->fetch_assoc()) {
            $projects[] = $row;
        }

        $stmt_projects->close();
    }

    // Filtro por ID numérico o código identificador del proyecto
    if ($q_project_id !== '') {
        $q_lc = strtolower($q_project_id);
        $projects = array_values(array_filter(
            $projects,
            static fn(array $p): bool =>
                str_contains((string)$p['id_aprobado'], $q_project_id) ||
                str_contains(strtolower((string)$p['identificador']), $q_lc)
        ));
    }

    // Filtro por estudiante (cédula o nombre) — busca en minutas del proyecto
    if ($q_student !== '' && !empty($projects)) {
        $search_like = '%' . $q_student . '%';
        $stmt_stu = $connection->prepare("
            SELECT DISTINCT pm.project_id
            FROM project_minute_attendees pma
            INNER JOIN sis_user su ON su.id = pma.user_id
            INNER JOIN project_minutes pm ON pm.id = pma.minute_id
            WHERE pma.participant_role = 'ESTUDIANTE'
              AND (su.id LIKE ? OR su.nombre LIKE ?)
        ");
        if ($stmt_stu !== false) {
            $stmt_stu->bind_param('ss', $search_like, $search_like);
            $stmt_stu->execute();
            $result_stu = $stmt_stu->get_result();
            $student_project_ids = [];
            while ($row_stu = $result_stu->fetch_assoc()) {
                $student_project_ids[] = (int)$row_stu['project_id'];
            }
            $stmt_stu->close();
            $projects = array_values(array_filter(
                $projects,
                static fn(array $p): bool => in_array((int)$p['id_aprobado'], $student_project_ids, true)
            ));
        }
    }

    $available_project_ids = array_map(
        static fn(array $project): int => (int)$project['id_aprobado'],
        $projects
    );

    // Si se filtra por ID/código y no se seleccionó proyecto aún, autoseleccionar coincidencia exacta.
    if ($selected_project_id === 0 && $q_project_id !== '' && !empty($projects)) {
        foreach ($projects as $project) {
            $project_id_value = (int)$project['id_aprobado'];
            $project_code_value = strtolower((string)$project['identificador']);

            if ((string)$project_id_value === $q_project_id || $project_code_value === strtolower($q_project_id)) {
                $selected_project_id = $project_id_value;
                break;
            }
        }
    }

    if ($selected_project_id > 0) {
        if (!in_array($selected_project_id, $available_project_ids, true)) {
            $form_error = 'El proyecto seleccionado no está disponible para su perfil.';
        } else {
            foreach ($projects as $project) {
                if ((int)$project['id_aprobado'] === $selected_project_id) {
                    $selected_project = $project;
                    break;
                }
            }

            $sql_minutes = "
                SELECT
                    pm.id,
                    pm.project_id,
                    pm.session_date AS fecha_referencia,
                    pm.file_name,
                    pm.mime_type,
                    pm.file_size,
                    pm.created_at,
                    pm.updated_at,
                    pm.uploaded_by,
                    su.nombre AS uploaded_by_name,
                    SUM(CASE WHEN pma.attended = 1 THEN 1 ELSE 0 END) AS attended_count,
                    COUNT(pma.id) AS total_participants,
                    'MINUTA' AS tipo_acuerdo,
                    NULL AS codigo_acuerdo,
                    NULL AS fecha_defensa
                FROM project_minutes pm
                LEFT JOIN sis_user su
                    ON su.id = pm.uploaded_by
                LEFT JOIN project_minute_attendees pma
                    ON pma.minute_id = pm.id
                WHERE pm.project_id = ?
                GROUP BY
                    pm.id,
                    pm.project_id,
                    pm.session_date,
                    pm.file_name,
                    pm.mime_type,
                    pm.file_size,
                    pm.created_at,
                    pm.updated_at,
                    pm.uploaded_by,
                    su.nombre

                UNION ALL

                SELECT
                    adp.id,
                    adp.proyecto_id AS project_id,
                    adp.fecha_defensa AS fecha_referencia,
                    adp.archivo_nombre AS file_name,
                    adp.mime_type,
                    adp.file_size,
                    adp.creado_en AS created_at,
                    adp.actualizado_en AS updated_at,
                    adp.subido_por AS uploaded_by,
                    su2.nombre AS uploaded_by_name,
                    0 AS attended_count,
                    0 AS total_participants,
                    'DEFENSA_PUBLICA' AS tipo_acuerdo,
                    adp.codigo_acuerdo,
                    adp.fecha_defensa
                FROM acuerdo_defensa_publica adp
                LEFT JOIN sis_user su2
                    ON su2.id = adp.subido_por
                WHERE adp.proyecto_id = ?

                UNION ALL

                SELECT
                    pa.id_aprobado AS id,
                    pa.id_aprobado AS project_id,
                    pa.fecha_creacion AS fecha_referencia,
                    CONCAT(pa.identificador, ' - ', pa.nombre) AS file_name,
                    'application/pdf' AS mime_type,
                    OCTET_LENGTH(pa.documento) AS file_size,
                    pa.fecha_creacion AS created_at,
                    pa.fecha_creacion AS updated_at,
                    NULL AS uploaded_by,
                    'Sistema' AS uploaded_by_name,
                    NULL AS attended_count,
                    NULL AS total_participants,
                    'APROBACION' AS tipo_acuerdo,
                    NULL AS codigo_acuerdo,
                    NULL AS fecha_defensa
                FROM proyecto_aprobado pa
                WHERE pa.id_aprobado = ? AND pa.aprobado = 1 AND pa.documento IS NOT NULL

                ORDER BY fecha_referencia DESC, created_at DESC, id DESC
            ";

            $stmt_minutes = $connection->prepare($sql_minutes);
            if ($stmt_minutes === false) {
                throw new RuntimeException('No se pudo preparar la consulta de acuerdos del proyecto.');
            }

            $stmt_minutes->bind_param('iii', $selected_project_id, $selected_project_id, $selected_project_id);
            $stmt_minutes->execute();
            $result_minutes = $stmt_minutes->get_result();

            while ($row = $result_minutes->fetch_assoc()) {
                $all_agreements[] = $row;
            }

            $stmt_minutes->close();

            if ($selected_minute_id <= 0 && !empty($all_agreements)) {
                $selected_minute_id = (int)$all_agreements[0]['id'];
            }

            foreach ($all_agreements as $minute) {
                if ((int)$minute['id'] === $selected_minute_id) {
                    $selected_minute = $minute;
                    break;
                }
            }

            if ($selected_minute_id > 0 && $selected_minute === null) {
                $form_error = 'El acuerdo seleccionado no pertenece al proyecto indicado.';
            }

            if ($form_error === '' && $selected_minute !== null) {
                if ($selected_minute['tipo_acuerdo'] === 'MINUTA') {
                    $sql_attendees = "
                        SELECT
                            pma.user_id,
                            su.nombre,
                            su.email,
                            pma.participant_role,
                            pma.attended,
                            pma.created_at
                        FROM project_minute_attendees pma
                        INNER JOIN sis_user su
                            ON su.id = pma.user_id
                        WHERE pma.minute_id = ?
                        ORDER BY
                            FIELD(pma.participant_role, 'TUTOR', 'ASESOR_1', 'ASESOR_2', 'ESTUDIANTE'),
                            su.nombre ASC
                    ";

                    $stmt_attendees = $connection->prepare($sql_attendees);
                    if ($stmt_attendees === false) {
                        throw new RuntimeException('No se pudo preparar la consulta de asistentes del acuerdo.');
                    }

                    $stmt_attendees->bind_param('i', $selected_minute_id);
                    $stmt_attendees->execute();
                    $result_attendees = $stmt_attendees->get_result();

                    while ($row = $result_attendees->fetch_assoc()) {
                        $attendees[] = $row;
                    }

                    $stmt_attendees->close();
                }
            }
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
<body class="fondo-una d-flex flex-column min-vh-100">

<?php include 'header.php'; ?>

<main class="flex-fill">
    <div class="container my-5">

        <div class="dashboard-header text-center mb-5">
            <h1>Consulta de acuerdos del Comité Asesor</h1>
            <p class="lead">
                Panel de consulta para visualizar acuerdos previos registrados en las minutas y el detalle de asistentes.
            </p>
        </div>

        <!-- Búsqueda rápida -->
        <div class="card document-table mb-4">
            <div class="card-body p-4">
                <h2 class="minute-section-title mb-3">
                    <i class="bi bi-funnel"></i> Búsqueda y filtros
                </h2>
                <form method="get" action="PanelRegistroAcuerdo.php" class="row g-3 align-items-end">
                    <div class="col-lg-4">
                        <label for="q_project_id" class="form-label fw-semibold">ID / código de proyecto</label>
                        <input
                            type="text"
                            id="q_project_id"
                            name="q_project_id"
                            class="form-control"
                            placeholder="Ej. 12 o UNA-CTFG-…"
                            value="<?php echo h($q_project_id); ?>"
                        >
                    </div>
                    <div class="col-lg-4">
                        <label for="q_student" class="form-label fw-semibold">Estudiante (cédula o nombre)</label>
                        <input
                            type="text"
                            id="q_student"
                            name="q_student"
                            class="form-control"
                            placeholder="Ej. 504410118 o López"
                            value="<?php echo h($q_student); ?>"
                        >
                    </div>
                    <div class="col-lg-2">
                        <label for="q_minute_id" class="form-label fw-semibold">ID de acuerdo</label>
                        <input
                            type="number"
                            id="q_minute_id"
                            name="q_minute_id"
                            class="form-control"
                            placeholder="Ej. 3"
                            min="1"
                            value="<?php echo $q_minute_id > 0 ? h((string)$q_minute_id) : ''; ?>"
                        >
                    </div>
                    <div class="col-lg-2">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-search me-1"></i> Buscar
                            </button>
                            <?php if ($q_project_id !== '' || $q_student !== '' || $q_minute_id > 0): ?>
                                <a href="PanelRegistroAcuerdo.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-x-circle me-1"></i> Limpiar
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
                <?php if ($q_project_id !== '' || $q_student !== '' || $q_minute_id > 0): ?>
                    <div class="mt-3">
                        <small class="text-muted">Filtros activos:
                            <?php if ($q_project_id !== ''): ?>
                                <span class="badge bg-light text-dark border me-1">Proyecto: <?php echo h($q_project_id); ?></span>
                            <?php endif; ?>
                            <?php if ($q_student !== ''): ?>
                                <span class="badge bg-light text-dark border me-1">Estudiante: <?php echo h($q_student); ?></span>
                            <?php endif; ?>
                            <?php if ($q_minute_id > 0): ?>
                                <span class="badge bg-light text-dark border me-1">Acuerdo ID: <?php echo $q_minute_id; ?></span>
                            <?php endif; ?>
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        </div>

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
                            No se encontraron proyectos aprobados con acuerdos para mostrar.
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

                    <form method="get" action="PanelRegistroAcuerdo.php" class="row g-3 align-items-end">
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
                                        <?php if ($current_user_rol === 3): ?>
                                            (<?php echo h((string)$project['committee_role']); ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-lg-3">
                            <div class="d-grid">
                                <button type="submit" class="btn btn-danger">
                                    <i class="bi bi-search me-2"></i> Ver acuerdos
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($form_error === '' && $selected_project !== null): ?>
            <div class="card document-table mb-4">
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
                        <div>
                            <h2 class="minute-section-title mb-2">
                                <i class="bi bi-journal-text"></i> Proyecto seleccionado
                            </h2>
                            <p class="text-muted mb-0 selected-project-text">
                                <strong><?php echo h((string)$selected_project['identificador']); ?></strong>
                                — <?php echo h((string)$selected_project['nombre']); ?>
                            </p>
                        </div>
                    </div>

                    <ul class="nav nav-tabs mb-4" id="acuerdosTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a
                                class="nav-link <?php echo $active_tab === 'acuerdos' ? 'active' : ''; ?>"
                                id="acuerdos-tab"
                                href="PanelRegistroAcuerdo.php?project_id=<?php echo h((string)$selected_project_id); ?>&tab=acuerdos"
                                role="tab"
                                aria-controls="acuerdos-panel"
                                aria-selected="<?php echo $active_tab === 'acuerdos' ? 'true' : 'false'; ?>"
                            >
                                Lista de acuerdos
                            </a>
                        </li>

                        <li class="nav-item" role="presentation">
                            <a
                                class="nav-link <?php echo $active_tab === 'asistentes' ? 'active' : ''; ?>"
                                id="asistentes-tab"
                                href="PanelRegistroAcuerdo.php?project_id=<?php echo h((string)$selected_project_id); ?>&minute_id=<?php echo h((string)$selected_minute_id); ?>&tab=asistentes"
                                role="tab"
                                aria-controls="asistentes-panel"
                                aria-selected="<?php echo $active_tab === 'asistentes' ? 'true' : 'false'; ?>"
                            >
                                Asistentes por acuerdo
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content pt-3" id="acuerdosTabContent">
                        <?php if ($active_tab === 'acuerdos'): ?>
                        <div id="acuerdos-panel" role="tabpanel" aria-labelledby="acuerdos-tab">
                            <?php if (empty($all_agreements)): ?>
                                <div class="minute-empty-state">
                                    <i class="bi bi-file-earmark-text"></i>
                                    <h5>Sin acuerdos registrados</h5>
                                    <p class="mb-0">No hay registros en para este proyecto.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Tipo</th>
                                                <th>ID</th>
                                                <th>Fecha referencia</th>
                                                <th>Archivo</th>
                                                <th>Tamaño</th>
                                                <th>Subido por</th>
                                                <th>Información adicional</th>
                                                <th>Registrado en</th>
                                                <th class="width-170">Detalle</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($all_agreements as $minute): ?>
                                            <tr>
                                                <td>
                                                    <?php if ($minute['tipo_acuerdo'] === 'MINUTA'): ?>
                                                        <span class="badge bg-primary">
                                                            <i class="bi bi-journal-text me-1"></i>Minuta
                                                        </span>
                                                    <?php elseif ($minute['tipo_acuerdo'] === 'DEFENSA_PUBLICA'): ?>
                                                        <span class="badge bg-success">
                                                            <i class="bi bi-mortarboard me-1"></i>Defensa
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark">
                                                            <i class="bi bi-check-circle me-1"></i>Aprobación
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo h((string)$minute['id']); ?></td>
                                                <td><?php echo h((string)$minute['fecha_referencia']); ?></td>
                                                <td>
                                                    <?php if ($minute['tipo_acuerdo'] === 'MINUTA'): ?>
                                                        <a
                                                            href="mod/admin/users/minute_download.php?minute_id=<?php echo h((string)$minute['id']); ?>"
                                                            title="Descargar <?php echo h((string)$minute['file_name']); ?>"
                                                        >
                                                            <i class="bi bi-file-earmark-arrow-down me-1"></i><?php echo h((string)$minute['file_name']); ?>
                                                        </a>
                                                    <?php elseif ($minute['tipo_acuerdo'] === 'DEFENSA_PUBLICA'): ?>
                                                        <a
                                                            href="mod/admin/users/defense_agreement_download.php?defense_id=<?php echo h((string)$minute['id']); ?>"
                                                            title="Descargar <?php echo h((string)$minute['file_name']); ?>"
                                                        >
                                                            <i class="bi bi-file-earmark-arrow-down me-1"></i><?php echo h((string)$minute['file_name']); ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <a
                                                            href="mod/admin/users/approval_agreement_download.php?approval_id=<?php echo h((string)$minute['id']); ?>"
                                                            title="Descargar <?php echo h((string)$minute['file_name']); ?>"
                                                        >
                                                            <i class="bi bi-file-earmark-arrow-down me-1"></i><?php echo h((string)$minute['file_name']); ?>
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo h(formatBytes((int)$minute['file_size'])); ?></td>
                                                <td><?php echo h((string)$minute['uploaded_by_name']); ?></td>
                                                <td>
                                                    <?php if ($minute['tipo_acuerdo'] === 'MINUTA'): ?>
                                                        Asistentes: <?php echo h((string)$minute['attended_count']); ?>/<?php echo h((string)$minute['total_participants']); ?>
                                                    <?php elseif ($minute['tipo_acuerdo'] === 'DEFENSA_PUBLICA'): ?>
                                                        Código: <?php echo h((string)$minute['codigo_acuerdo']); ?>
                                                    <?php else: ?>
                                                        <small class="text-muted">Sin asistentes registrados</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo h((string)$minute['created_at']); ?></td>
                                                <td>
                                                    <a
                                                        class="btn btn-outline-primary btn-sm"
                                                        href="PanelRegistroAcuerdo.php?project_id=<?php echo h((string)$selected_project_id); ?>&minute_id=<?php echo h((string)$minute['id']); ?>&tab=asistentes"
                                                    >
                                                        Ver detalles
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php else: ?>
                        <div id="asistentes-panel" role="tabpanel" aria-labelledby="asistentes-tab">
                            <?php if (empty($all_agreements)): ?>
                                <div class="minute-empty-state">
                                    <i class="bi bi-people"></i>
                                    <h5>No hay acuerdos para consultar detalles</h5>
                                    <p class="mb-0">Seleccione un proyecto con registros.</p>
                                </div>
                            <?php else: ?>
                                <form method="get" action="PanelRegistroAcuerdo.php" class="row g-3 align-items-end mb-4">
                                    <input type="hidden" name="project_id" value="<?php echo h((string)$selected_project_id); ?>">
                                    <input type="hidden" name="tab" value="asistentes">

                                    <div class="col-lg-9">
                                        <label for="minute_id" class="form-label fw-semibold">Acuerdo / Minuta</label>
                                        <select name="minute_id" id="minute_id" class="form-select" required>
                                            <?php foreach ($all_agreements as $minute): ?>
                                                <option
                                                    value="<?php echo h((string)$minute['id']); ?>"
                                                    <?php echo $selected_minute_id === (int)$minute['id'] ? 'selected' : ''; ?>
                                                >
                                                    #<?php echo h((string)$minute['id']); ?> -
                                                    <?php if ($minute['tipo_acuerdo'] === 'MINUTA'): ?>
                                                        [MINUTA] Fecha: <?php echo h((string)$minute['fecha_referencia']); ?> -
                                                    <?php elseif ($minute['tipo_acuerdo'] === 'DEFENSA_PUBLICA'): ?>
                                                        [DEFENSA] <?php echo h((string)$minute['codigo_acuerdo']); ?> -
                                                    <?php else: ?>
                                                        [APROBACIÓN] Fecha: <?php echo h((string)$minute['fecha_referencia']); ?> -
                                                    <?php endif; ?>
                                                    <?php echo h((string)$minute['file_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-lg-3">
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-danger">
                                                <i class="bi bi-people-fill me-2"></i> Ver detalle
                                            </button>
                                        </div>
                                    </div>
                                </form>

                                <?php if ($selected_minute !== null): ?>
                                    <div class="alert alert-light border shadow-sm">
                                        <strong>Acuerdo seleccionado:</strong>
                                        #<?php echo h((string)$selected_minute['id']); ?>
                                        <?php if ($selected_minute['tipo_acuerdo'] === 'MINUTA'): ?>
                                            - Minuta de sesión <?php echo h((string)$selected_minute['fecha_referencia']); ?>,
                                            archivo <?php echo h((string)$selected_minute['file_name']); ?>.
                                        <?php elseif ($selected_minute['tipo_acuerdo'] === 'DEFENSA_PUBLICA'): ?>
                                            - Acuerdo de Defensa Pública (Código: <?php echo h((string)$selected_minute['codigo_acuerdo']); ?>),
                                            fecha de defensa <?php echo h((string)$selected_minute['fecha_defensa']); ?>,
                                            archivo <?php echo h((string)$selected_minute['file_name']); ?>.
                                        <?php else: ?>
                                            - Acuerdo de Aprobación,
                                            fecha de creación <?php echo h((string)$selected_minute['fecha_referencia']); ?>,
                                            archivo <?php echo h((string)$selected_minute['file_name']); ?>.
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($selected_minute !== null && $selected_minute['tipo_acuerdo'] === 'DEFENSA_PUBLICA'): ?>
                                    <div class="minute-empty-state">
                                        <i class="bi bi-mortarboard"></i>
                                        <h5>Acuerdo de Defensa Pública</h5>
                                        <p class="mb-0">Este tipo de acuerdo no tiene registro de asistentes. Vea la información en la pestaña de acuerdos.</p>
                                    </div>
                                <?php elseif ($selected_minute !== null && $selected_minute['tipo_acuerdo'] === 'APROBACION'): ?>
                                    <div class="minute-empty-state">
                                        <i class="bi bi-check-circle"></i>
                                        <h5>Acuerdo de Aprobación</h5>
                                        <p class="mb-0">Este tipo de acuerdo no tiene registro de asistentes. Vea la información en la pestaña de acuerdos.</p>
                                    </div>
                                <?php elseif (empty($attendees)): ?>
                                    <div class="minute-empty-state">
                                        <i class="bi bi-person-x"></i>
                                        <h5>No hay asistentes registrados</h5>
                                        <p class="mb-0">Este acuerdo no tiene registros en project_minute_attendees.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th>ID usuario</th>
                                                    <th>Nombre</th>
                                                    <th>Correo</th>
                                                    <th>Rol</th>
                                                    <th>Asistencia</th>
                                                    <th>Registrado en</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach ($attendees as $attendee): ?>
                                                <tr>
                                                    <td><?php echo h((string)$attendee['user_id']); ?></td>
                                                    <td><?php echo h((string)$attendee['nombre']); ?></td>
                                                    <td><?php echo h((string)($attendee['email'] ?? '')); ?></td>
                                                    <td><?php echo h((string)$attendee['participant_role']); ?></td>
                                                    <td>
                                                        <?php if ((int)$attendee['attended'] === 1): ?>
                                                            <span class="badge bg-success">Asistió</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">No asistió</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo h((string)$attendee['created_at']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
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
    .dashboard-header h1 {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        font-weight: 700;
        color: var(--azul-una);
        margin-bottom: 0.5rem;
    }

    .dashboard-header .lead {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #6c757d;
        max-width: 920px;
        margin: 0 auto;
    }

    .document-table {
        background: #fff;
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

    .selected-project-text {
        font-size: 1.45rem;
        line-height: 1.5;
    }

    .selected-project-text strong {
        font-size: 1.45rem;
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

    .table thead th {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        font-weight: 600;
        font-size: 0.95rem;
        color: #034991;
        border-bottom: 2px solid #dee2e6;
    }

    .nav-tabs .nav-link {
        font-weight: 600;
        color: #034991;
    }

    .nav-tabs .nav-link.active {
        color: #c8151a;
    }
</style>

</body>
</html>
