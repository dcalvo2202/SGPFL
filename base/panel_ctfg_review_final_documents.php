<?php
// VERIFICAR AUTENTICACIÓN Y PERMISOS
include("mod/login/check.php");

include('lang/lang.es');
include('inc/db/db.php');

$current_user_id = $mySessionController->getVar("usuario");
$current_user_name = $mySessionController->getVar("nombre");
$current_user_rol = $mySessionController->getVar("rol");
$base_url = $mySessionController->getVar("cds_domain") . $mySessionController->getVar("cds_locate");

// Solo CTFG, Gestor Académico y Admin
if (!in_array((int)$current_user_rol, [1, 2, 3], true)) {
    header('Location: dashboard.php');
    exit;
}

function formato_legible_final_review($mime_type)
{
    $mime = strtolower((string)$mime_type);
    $parts = explode('/', $mime);
    $mime = isset($parts[1]) ? $parts[1] : $mime;

    $tipos = [
        'pdf' => 'PDF',
        'vnd.openxmlformats-officedocument.wordprocessingml.document' => 'DOCX',
        'vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'XLSX',
        'vnd.openxmlformats-officedocument.presentationml.presentation' => 'PPTX',
        'msword' => 'DOC',
        'vnd.ms-excel' => 'XLS',
        'plain' => 'TXT',
        'jpeg' => 'JPEG',
        'png' => 'PNG'
    ];

    return $tipos[$mime] ?? strtoupper($mime);
}

function formatear_tamano_mb_final_review($bytes)
{
    return number_format(((float)$bytes) / (1024 * 1024), 2) . ' MB';
}

function build_in_clause_final_review(array $ids)
{
    if (empty($ids)) {
        return ['placeholders' => '', 'types' => ''];
    }

    return [
        'placeholders' => implode(',', array_fill(0, count($ids), '?')),
        'types' => str_repeat('i', count($ids))
    ];
}

$submissions = [];
$proposal_ids = [];
$document_ids = [];

$conn = new mysqli($db_host, $usuario, $clave, $db);
if ($conn->connect_error) {
    die('Conexión fallida: ' . $conn->connect_error);
}
$conn->set_charset('utf8');

$sql = "SELECT fd.id AS document_id,
               fd.proposal_id,
               fd.status,
               fd.project_status,
               fd.submitted_at,
               fd.submitted_by,
               tp.title AS proposal_title,
               f.id AS main_file_id,
               f.file_name AS main_file_name,
               f.file_size AS main_file_size,
               f.mime_type AS main_file_mime,
               f.version AS main_version,
               u.nombre AS submitted_by_name,
               u.id AS submitted_by_id
        FROM tfg_final_documents fd
        INNER JOIN tfg_proposals tp ON fd.proposal_id = tp.id
        INNER JOIN tfg_files f ON fd.file_id = f.id
        INNER JOIN sis_user u ON fd.submitted_by = u.id
        WHERE fd.status = 'Pendiente de Revision'
        ORDER BY fd.submitted_at DESC";

$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $document_id = (int)$row['document_id'];
        $proposal_id = (int)$row['proposal_id'];

        $submissions[$document_id] = $row;
        $document_ids[] = $document_id;
        $proposal_ids[] = $proposal_id;
    }
}

$document_ids = array_values(array_unique(array_filter($document_ids)));
$proposal_ids = array_values(array_unique(array_filter($proposal_ids)));

$members_by_proposal = [];
if (!empty($proposal_ids)) {
    $in = build_in_clause_final_review($proposal_ids);
    $sql_members = "SELECT rp.tfg_proposal_id AS proposal_id, pm.user_id, pm.role, u.nombre
                    FROM registered_projects rp
                    INNER JOIN project_members pm ON pm.project_id = rp.id
                    INNER JOIN sis_user u ON pm.user_id = u.id
                    WHERE rp.tfg_proposal_id IN ({$in['placeholders']})
                      AND pm.status = 'Activo'
                    ORDER BY rp.tfg_proposal_id,
                             CASE WHEN pm.role = 'Líder' THEN 0 ELSE 1 END,
                             u.nombre ASC";

    $stmt_members = $conn->prepare($sql_members);
    if ($stmt_members) {
        $stmt_members->bind_param($in['types'], ...$proposal_ids);
        $stmt_members->execute();
        $result_members = $stmt_members->get_result();

        while ($member = $result_members->fetch_assoc()) {
            $members_by_proposal[(int)$member['proposal_id']][] = [
                'id' => $member['user_id'],
                'name' => $member['nombre'],
                'role' => $member['role']
            ];
        }

        $stmt_members->close();
    }
}

$files_by_document = [];
if (!empty($document_ids)) {
    $in = build_in_clause_final_review($document_ids);
    $sql_files = "SELECT f.id,
                         f.final_document_id,
                         f.file_name,
                         f.mime_type,
                         f.file_size,
                         f.upload_date,
                         f.uploaded_by,
                         f.version,
                         f.document_type,
                         u.nombre AS uploaded_by_name
                  FROM tfg_files f
                  LEFT JOIN sis_user u ON f.uploaded_by = u.id
                  WHERE f.final_document_id IN ({$in['placeholders']})
                  ORDER BY f.final_document_id ASC, f.id ASC";

    $stmt_files = $conn->prepare($sql_files);
    if ($stmt_files) {
        $stmt_files->bind_param($in['types'], ...$document_ids);
        $stmt_files->execute();
        $result_files = $stmt_files->get_result();

        while ($file = $result_files->fetch_assoc()) {
            $files_by_document[(int)$file['final_document_id']][] = $file;
        }

        $stmt_files->close();
    }
}

$submission_cards = [];

foreach ($submissions as $document_id => $submission) {
    $proposal_id = (int)$submission['proposal_id'];
    $current_version = (float)$submission['main_version'];
    $members = $members_by_proposal[$proposal_id] ?? [];

    if (empty($members)) {
        $members[] = [
            'id' => $submission['submitted_by_id'],
            'name' => $submission['submitted_by_name'],
            'role' => 'Líder'
        ];
    }

    $documents = [];
    $seen_file_ids = [];

    $documents[] = [
        'id' => (int)$submission['main_file_id'],
        'name' => $submission['main_file_name'],
        'role_label' => 'Principal',
        'uploaded_by' => $submission['submitted_by_name'],
        'uploaded_at' => $submission['submitted_at'],
        'mime_label' => formato_legible_final_review($submission['main_file_mime']),
        'size_label' => formatear_tamano_mb_final_review($submission['main_file_size']),
        'download_url' => $base_url . 'mod/admin/users/tfg_download_file.php?id=' . (int)$submission['main_file_id']
    ];
    $seen_file_ids[(int)$submission['main_file_id']] = true;

    foreach ($files_by_document[$document_id] ?? [] as $file) {
        if ((float)$file['version'] !== $current_version) {
            continue;
        }

        $file_id = (int)$file['id'];
        if (isset($seen_file_ids[$file_id])) {
            continue;
        }

        $documents[] = [
            'id' => $file_id,
            'name' => $file['file_name'],
            'role_label' => 'Adjunto',
            'uploaded_by' => $file['uploaded_by_name'] ?: $file['uploaded_by'],
            'uploaded_at' => $file['upload_date'],
            'mime_label' => formato_legible_final_review($file['mime_type']),
            'size_label' => formatear_tamano_mb_final_review($file['file_size']),
            'download_url' => $base_url . 'mod/admin/users/tfg_download_file.php?id=' . $file_id
        ];
        $seen_file_ids[$file_id] = true;
    }

    // Correcciones: los adjuntos actuales pueden quedar sin final_document_id.
    if ($current_version > 1) {
        $sql_orphan = "SELECT f.id,
                              f.file_name,
                              f.mime_type,
                              f.file_size,
                              f.upload_date,
                              f.uploaded_by,
                              f.document_type,
                              u.nombre AS uploaded_by_name
                       FROM tfg_files f
                       LEFT JOIN sis_user u ON f.uploaded_by = u.id
                       WHERE f.uploaded_by = ?
                         AND f.document_type = 'Correccion TFG Anexo'
                         AND f.final_document_id IS NULL
                         AND f.proposal_id IS NULL
                         AND f.upload_date BETWEEN DATE_SUB(?, INTERVAL 10 MINUTE) AND DATE_ADD(?, INTERVAL 10 MINUTE)
                       ORDER BY f.id ASC";

        $stmt_orphan = $conn->prepare($sql_orphan);
        if ($stmt_orphan) {
            $submitted_by = $submission['submitted_by'];
            $submitted_at = $submission['submitted_at'];
            $stmt_orphan->bind_param('sss', $submitted_by, $submitted_at, $submitted_at);
            $stmt_orphan->execute();
            $result_orphan = $stmt_orphan->get_result();

            while ($file = $result_orphan->fetch_assoc()) {
                $file_id = (int)$file['id'];
                if (isset($seen_file_ids[$file_id])) {
                    continue;
                }

                $documents[] = [
                    'id' => $file_id,
                    'name' => $file['file_name'],
                    'role_label' => 'Adjunto',
                    'uploaded_by' => $file['uploaded_by_name'] ?: $file['uploaded_by'],
                    'uploaded_at' => $file['upload_date'],
                    'mime_label' => formato_legible_final_review($file['mime_type']),
                    'size_label' => formatear_tamano_mb_final_review($file['file_size']),
                    'download_url' => $base_url . 'mod/admin/users/tfg_download_file.php?id=' . $file_id
                ];
                $seen_file_ids[$file_id] = true;
            }

            $stmt_orphan->close();
        }
    }

    $submission_cards[] = [
        'id' => $document_id,
        'proposal_id' => $proposal_id,
        'title' => $submission['proposal_title'],
        'submitted_at' => $submission['submitted_at'],
        'submitted_at_label' => date('d/m/Y H:i', strtotime($submission['submitted_at'])),
        'project_status' => $submission['project_status'],
        'version_label' => number_format($current_version, 1),
        'members' => $members,
        'documents' => $documents,
        'documents_count' => count($documents)
    ];
}

$conn->close();

$submission_cards_json = json_encode(
    $submission_cards,
    JSON_UNESCAPED_UNICODE
    | JSON_HEX_TAG
    | JSON_HEX_APOS
    | JSON_HEX_QUOT
    | JSON_HEX_AMP
    | JSON_INVALID_UTF8_SUBSTITUTE
);

if ($submission_cards_json === false) {
    error_log('panel_ctfg_review_final_documents.php: json_encode($submission_cards) falló: ' . json_last_error_msg());
    $submission_cards_json = '[]';
}
?>
<!DOCTYPE html>
<html lang="es">
<?php include 'head.php'; ?>
<body class="fondo-una d-flex flex-column min-vh-100">

    <?php include 'header.php'; ?>

    <main class="flex-fill">
        <div class="container my-5">

            <div class="dashboard-header text-center mb-4">
                <h1 style="font-size: 2.5rem; font-weight: 700;">Revisión de Documentos Finales de TFG</h1>
                <p class="lead mb-0">Consulte todos los PDFs de cada entrega final y resuelva la revisión completa en bloque.</p>
            </div>

            <div class="alert alert-light border shadow-sm mb-4" role="alert">
                <div class="d-flex gap-3 align-items-start">
                    <i class="bi bi-folder-check fs-4 text-primary"></i>
                    <div>
                        <strong>Modo de revisión actual</strong>
                        <div class="small text-muted mt-1">
                            En esta vista se muestran los documentos individuales de cada entrega final, pero la decisión sigue aplicándose al envío completo.
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($submission_cards)): ?>
            <div class="review-helper-banner alert alert-info border-0 mb-4">
                <div class="d-flex align-items-start gap-3">
                    <i class="bi bi-info-circle-fill fs-4"></i>
                    <div>
                        <strong>Revisión por entrega final</strong>
                        <div class="small mt-1">
                            Puede abrir cada entrega para ver todos los archivos PDF subidos y aprobarla o solicitar correcciones en bloque.
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle review-table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Estudiantes</th>
                                    <th>Título del TFG</th>
                                    <th>Fecha de envío</th>
                                    <th>Estado del proyecto</th>
                                    <th>Documentos</th>
                                    <th class="text-center" style="width: 180px;">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($submission_cards as $submission): ?>
                                <tr>
                                    <td>
                                        <div class="student-list">
                                            <?php foreach ($submission['members'] as $member): ?>
                                                <div class="student-chip <?= $member['role'] === 'Líder' ? 'student-chip-leader' : '' ?>">
                                                    <span class="student-name"><?= htmlspecialchars($member['name']) ?></span>
                                                    <small class="student-meta">
                                                        <?= htmlspecialchars($member['id']) ?>
                                                        <?php if ($member['role'] === 'Líder'): ?>
                                                            · Líder
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($submission['title']) ?></div>
                                        <small class="text-muted">ID entrega: <?= (int)$submission['id'] ?> · Versión actual: <?= htmlspecialchars($submission['version_label']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($submission['submitted_at_label']) ?></td>
                                    <td>
                                        <span class="badge rounded-pill <?= $submission['project_status'] === 'Vigente' ? 'text-bg-success' : 'text-bg-warning' ?>">
                                            <?= htmlspecialchars($submission['project_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill text-bg-primary">
                                            <?= (int)$submission['documents_count'] ?> documento<?= (int)$submission['documents_count'] === 1 ? '' : 's' ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button type="button"
                                                class="btn btn-sm btn-primary text-white js-open-documents"
                                                data-document-id="<?= (int)$submission['id'] ?>">
                                            <i class="bi bi-folder2-open"></i> Ver documentos
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="empty-state text-center">
                        <i class="bi bi-check2-circle"></i>
                        <h3>¡Todo al día!</h3>
                        <p class="mb-0">No hay documentos finales pendientes de revisión en este momento.</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="text-center mt-4">
                <a href="panel_ctfg.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left-circle"></i> Volver al Panel Principal
                </a>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <div id="documentsModal" class="custom-modal-overlay" style="display:none;">
        <div class="custom-modal-content custom-modal-lg">
            <div class="custom-modal-header">
                <div>
                    <h4 id="documentsModalTitle" class="mb-1">Documentos del envío final</h4>
                    <p id="documentsModalSubtitle" class="text-muted mb-0 small"></p>
                </div>
                <button type="button" class="btn-close" aria-label="Cerrar" onclick="closeDocumentsModal()"></button>
            </div>

            <div class="documents-section mb-3">
                <h6 class="section-subtitle">Integrantes del grupo</h6>
                <div id="documentsModalMembers" class="member-badges"></div>
            </div>

            <div class="documents-section">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="section-subtitle mb-0">Archivos subidos</h6>
                    <span id="documentsModalCount" class="badge rounded-pill text-bg-secondary"></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle documents-table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tipo</th>
                                <th>Nombre</th>
                                <th>Subido por</th>
                                <th>Fecha</th>
                                <th>Formato</th>
                                <th>Tamaño</th>
                                <th class="text-center">Descarga</th>
                            </tr>
                        </thead>
                        <tbody id="documentsModalBody"></tbody>
                    </table>
                </div>
            </div>

            <div class="review-guidance alert alert-light border mt-3 mb-0">
                Los comentarios pueden indicar cuáles documentos requieren corrección. La aprobación o solicitud de correcciones se aplica a toda la entrega final actual.
            </div>

            <div class="custom-modal-actions mt-4">
                <button type="button" class="btn btn-secondary" onclick="closeDocumentsModal()">Cerrar</button>
                <button type="button" class="btn btn-success" onclick="openReviewModal('Aprobado para Defensa')">
                    <i class="bi bi-check-circle"></i> Aprobar para Defensa
                </button>
                <button type="button" class="btn btn-danger" onclick="openReviewModal('Correcciones Requeridas')">
                    <i class="bi bi-x-circle"></i> Correcciones Requeridas
                </button>
            </div>
        </div>
    </div>

    <div id="commentModal" class="custom-modal-overlay" style="display:none;">
        <div class="custom-modal-content">
            <h4 id="modalTitle">Revisión de documento final</h4>
            <p id="modalText"></p>
            <textarea id="modalComments" class="form-control" placeholder="Escriba aquí sus observaciones..." rows="4"></textarea>
            <small id="modalError" class="text-danger" style="display:none;">Los comentarios son obligatorios para esta acción.</small>
            <div class="custom-modal-actions">
                <button id="modalCancel" class="btn btn-secondary" type="button">Cancelar</button>
                <button id="modalConfirm" class="btn btn-primary" type="button">Enviar revisión</button>
            </div>
        </div>
    </div>

    <div id="alertModal" class="custom-modal-overlay" style="display:none;">
        <div class="custom-modal-content">
            <h4 id="alertTitle"></h4>
            <p id="alertMessage"></p>
            <div class="custom-modal-actions">
                <button id="alertOk" class="btn btn-primary" type="button">Aceptar</button>
            </div>
        </div>
    </div>

    <style>
    .review-helper-banner {
        background: linear-gradient(135deg, rgba(3, 73, 145, 0.10), rgba(3, 73, 145, 0.04));
    }

    .review-table th,
    .review-table td {
        vertical-align: middle;
    }

    .student-list {
        display: flex;
        flex-direction: column;
        gap: 0.55rem;
    }

    .student-chip {
        display: inline-flex;
        flex-direction: column;
        padding: 0.55rem 0.75rem;
        border-radius: 0.75rem;
        background: #f8f9fa;
        border: 1px solid #e9ecef;
    }

    .student-chip-leader {
        border-color: rgba(3, 73, 145, 0.25);
        background: rgba(3, 73, 145, 0.07);
    }

    .student-name {
        font-weight: 600;
        line-height: 1.15;
    }

    .student-meta {
        color: #6c757d;
    }

    .custom-modal-overlay {
        position: fixed;
        inset: 0;
        background-color: rgba(0, 0, 0, 0.6);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1060;
        padding: 1rem;
    }

    .custom-modal-content {
        background-color: #fff;
        padding: 1.5rem;
        border-radius: 0.75rem;
        width: min(100%, 540px);
        box-shadow: 0 0.75rem 1.5rem rgba(0,0,0,.18);
        animation: fadeIn 0.25s ease-out;
    }

    .custom-modal-lg {
        width: min(100%, 1100px);
        max-height: 92vh;
        overflow-y: auto;
    }

    .custom-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .member-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem;
    }

    .member-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.45rem 0.75rem;
        border-radius: 999px;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
    }

    .member-badge-leader {
        background: rgba(3, 73, 145, 0.08);
        border-color: rgba(3, 73, 145, 0.25);
    }

    .documents-table td,
    .documents-table th {
        white-space: nowrap;
    }

    .documents-table td:nth-child(2),
    .documents-table th:nth-child(2) {
        white-space: normal;
        min-width: 260px;
    }

    .role-pill {
        display: inline-flex;
        align-items: center;
        padding: 0.22rem 0.6rem;
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 600;
        background: rgba(3, 73, 145, 0.08);
        color: #034991;
    }

    .role-pill-attachment {
        background: rgba(108, 117, 125, 0.12);
        color: #495057;
    }

    .section-subtitle {
        font-weight: 700;
        color: #1f2937;
    }

    .custom-modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .empty-state {
        padding: 4rem 2rem;
        text-align: center;
    }

    .empty-state i {
        font-size: 4rem;
        color: #28a745;
        margin-bottom: 1.5rem;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-14px); }
        to { opacity: 1; transform: translateY(0); }
    }
    </style>

    <script>
    const submissionsData = Array.isArray(<?= $submission_cards_json ?>) ? <?= $submission_cards_json ?> : [];

    let activeSubmission = null;
    let pendingReview = { id: null, status: null };

    const documentsModal = document.getElementById('documentsModal');
    const documentsModalTitle = document.getElementById('documentsModalTitle');
    const documentsModalSubtitle = document.getElementById('documentsModalSubtitle');
    const documentsModalMembers = document.getElementById('documentsModalMembers');
    const documentsModalBody = document.getElementById('documentsModalBody');
    const documentsModalCount = document.getElementById('documentsModalCount');

    const commentModal = document.getElementById('commentModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalText = document.getElementById('modalText');
    const modalComments = document.getElementById('modalComments');
    const modalError = document.getElementById('modalError');
    const modalConfirm = document.getElementById('modalConfirm');
    const modalCancel = document.getElementById('modalCancel');

    const alertModal = document.getElementById('alertModal');
    const alertTitle = document.getElementById('alertTitle');
    const alertMessage = document.getElementById('alertMessage');
    const alertOk = document.getElementById('alertOk');

    document.querySelectorAll('.js-open-documents').forEach((button) => {
        button.addEventListener('click', () => {
            openDocumentsModal(button.dataset.documentId);
        });
    });

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function normalizeList(value) {
        if (Array.isArray(value)) {
            return value;
        }

        if (!value) {
            return [];
        }

        if (typeof value === 'object') {
            return Object.values(value);
        }

        return [];
    }

    function openDocumentsModal(documentId) {
        activeSubmission = submissionsData.find((submission) => Number(submission.id) === Number(documentId));

        if (!activeSubmission) {
            showCustomAlert('Error', 'No se encontró la entrega seleccionada.', false);
            return;
        }

        const members = normalizeList(activeSubmission.members);
        const documents = normalizeList(activeSubmission.documents);

        documentsModalTitle.textContent = activeSubmission.title;
        documentsModalSubtitle.textContent = `Entrega final #${activeSubmission.id} · Enviada el ${activeSubmission.submitted_at_label} · Versión ${activeSubmission.version_label}`;
        documentsModalCount.textContent = `${documents.length} documento${documents.length === 1 ? '' : 's'}`;

        documentsModalMembers.innerHTML = '';
        members.forEach((member) => {
            const memberHtml = `
            <div class="member-badge ${(member && member.role === 'Líder') ? 'member-badge-leader' : ''}">
                <span>${escapeHtml((member && member.name) ? member.name : '-')}</span>
                <small class="text-muted">${escapeHtml((member && member.id) ? member.id : '')}${(member && member.role === 'Líder') ? ' · Líder' : ''}</small>
            </div>`;
            documentsModalMembers.insertAdjacentHTML('beforeend', memberHtml);
        });

        documentsModalBody.innerHTML = '';
        documents.forEach((document) => {
            const documentHtml = `
            <tr>
                <td>
                    <span class="role-pill ${(document && document.role_label === 'Adjunto') ? 'role-pill-attachment' : ''}">
                        ${escapeHtml((document && document.role_label) ? document.role_label : '-')}
                    </span>
                </td>
                <td>${escapeHtml((document && document.name) ? document.name : '-')}</td>
                <td>${escapeHtml((document && document.uploaded_by) ? document.uploaded_by : '-')}</td>
                <td>${escapeHtml(((document && document.uploaded_at) ? document.uploaded_at : '').replace('T', ' ').slice(0, 16))}</td>
                <td>${escapeHtml((document && document.mime_label) ? document.mime_label : '-')}</td>
                <td>${escapeHtml((document && document.size_label) ? document.size_label : '-')}</td>
                <td class="text-center">
                    <a href="${escapeHtml((document && document.download_url) ? document.download_url : '#')}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-download"></i> Descargar
                    </a>
                </td>
            </tr>`;
            documentsModalBody.insertAdjacentHTML('beforeend', documentHtml);
        });

        documentsModal.style.display = 'flex';
    }

    function closeDocumentsModal() {
        documentsModal.style.display = 'none';
    }

    function openReviewModal(status) {
        if (!activeSubmission) {
            showCustomAlert('Error', 'No hay una entrega activa para revisar.', false);
            return;
        }

        pendingReview = { id: activeSubmission.id, status };

        modalTitle.textContent = 'Revisión de documento final';
        modalText.textContent = status === 'Aprobado para Defensa'
            ? `¿Desea aprobar para defensa la entrega final "${activeSubmission.title}"?`
            : `¿Desea marcar la entrega final "${activeSubmission.title}" con correcciones requeridas?`;

        modalComments.value = '';
        modalError.style.display = 'none';
        modalComments.placeholder = status === 'Aprobado para Defensa'
            ? 'Comentarios opcionales para la aprobación...'
            : 'Escriba aquí las correcciones requeridas (obligatorio)...';

        closeDocumentsModal();
        commentModal.style.display = 'flex';
        modalComments.focus();
    }

    function closeCommentModal() {
        commentModal.style.display = 'none';
    }

    function showCustomAlert(title, message, reloadOnClose = false) {
        alertTitle.textContent = title;
        alertMessage.textContent = message;
        alertModal.style.display = 'flex';
        alertOk.onclick = () => {
            alertModal.style.display = 'none';
            if (reloadOnClose) {
                window.location.reload();
            }
        };
    }

    modalCancel.addEventListener('click', closeCommentModal);

    modalConfirm.addEventListener('click', () => {
        const comments = modalComments.value.trim();

        if (pendingReview.status === 'Correcciones Requeridas' && comments === '') {
            modalError.style.display = 'block';
            return;
        }

        modalError.style.display = 'none';
        commentModal.style.display = 'none';

        const body = new URLSearchParams({
            document_id: pendingReview.id,
            status: pendingReview.status,
            comments: comments
        });

        fetch('mod/admin/users/process_final_document_review.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        })
        .then((response) => {
            const contentType = response.headers.get('content-type') || '';

            if (contentType.includes('application/json')) {
                return response.json();
            }

            return response.text().then((text) => {
                console.error('Respuesta no JSON del servidor:', text);
                throw new Error('Respuesta inesperada del servidor. La sesión puede haber expirado.');
            });
        })
        .then((data) => {
            if (data.success) {
                const message = pendingReview.status === 'Aprobado para Defensa'
                    ? 'El documento final ha sido aprobado correctamente.'
                    : 'Las correcciones requeridas se registraron correctamente.';
                showCustomAlert('¡Éxito!', message, true);
                return;
            }

            showCustomAlert('Error', 'No se pudo actualizar el estado: ' + data.message, false);
        })
        .catch((error) => {
            console.error('Error en la solicitud:', error);
            showCustomAlert('Error en la operación', error.message, false);
        });
    });

    window.addEventListener('click', (event) => {
        if (event.target === documentsModal) {
            closeDocumentsModal();
        }

        if (event.target === commentModal) {
            closeCommentModal();
        }

        if (event.target === alertModal) {
            alertModal.style.display = 'none';
        }
    });
    </script>
</body>
</html>
