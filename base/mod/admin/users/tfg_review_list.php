<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

include __DIR__ . '/../../../inc/db/bdcommon.inc';

// Asegurarse de que $base_url esté disponible para enlaces
if (!isset($base_url)) {
    include_once __DIR__ . '/../../../lib/mysession/mySession.class.php';
    include_once __DIR__ . '/../../../lib/mysession/mySession.conf.php';
    $mySessionController = mySession::getIstance($_MYSESSION_CONF);
    $base_url = $mySessionController->getVar("cds_domain") . $mySessionController->getVar("cds_locate");
}

function formato_legible_revision($mime_type)
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
        'vnd.ms-powerpoint' => 'PPT',
        'plain' => 'TXT',
        'jpeg' => 'JPEG',
        'png' => 'PNG',
        'gif' => 'GIF',
        'zip' => 'ZIP',
        'x-rar-compressed' => 'RAR',
        'x-zip-compressed' => 'ZIP'
    ];

    return $tipos[$mime] ?? strtoupper($mime);
}

function formatear_tamano_mb_revision($bytes)
{
    return number_format(((float)$bytes) / (1024 * 1024), 2) . ' MB';
}

function build_in_clause_review(array $ids)
{
    if (empty($ids)) {
        return ['placeholders' => '', 'types' => ''];
    }

    return [
        'placeholders' => implode(',', array_fill(0, count($ids), '?')),
        'types' => str_repeat('i', count($ids))
    ];
}

$conn = new mysqli($db_host, $usuario, $clave, $db);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
$conn->set_charset("utf8");

$proposals = [];
$project_ids = [];
$proposal_ids = [];

$sql = "SELECT p.id, p.title, p.file_name, p.mime_type, p.file_size, p.status, p.created_at,
               p.user_id, u.nombre AS owner_name, u.id AS owner_id, rp.id AS project_id
        FROM tfg_proposals p
        INNER JOIN sis_user u ON p.user_id = u.id
        LEFT JOIN registered_projects rp ON rp.tfg_proposal_id = p.id
        WHERE p.status = 'Pendiente de Revision'
        ORDER BY p.created_at DESC";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $proposal_id = (int)$row['id'];
        $project_id = isset($row['project_id']) ? (int)$row['project_id'] : 0;

        $proposals[$proposal_id] = $row;
        $proposal_ids[] = $proposal_id;

        if ($project_id > 0) {
            $project_ids[] = $project_id;
        }
    }
}

$project_ids = array_values(array_unique(array_filter($project_ids)));
$proposal_ids = array_values(array_unique(array_filter($proposal_ids)));

$members_by_project = [];
if (!empty($project_ids)) {
    $in = build_in_clause_review($project_ids);
    $sql_members = "SELECT pm.project_id, pm.user_id, pm.role, u.nombre
                    FROM project_members pm
                    INNER JOIN sis_user u ON pm.user_id = u.id
                    WHERE pm.project_id IN ({$in['placeholders']})
                      AND pm.status = 'Activo'
                    ORDER BY pm.project_id,
                             CASE WHEN pm.role = 'Líder' THEN 0 ELSE 1 END,
                             u.nombre ASC";

    $stmt_members = $conn->prepare($sql_members);
    if ($stmt_members) {
        $stmt_members->bind_param($in['types'], ...$project_ids);
        $stmt_members->execute();
        $result_members = $stmt_members->get_result();

        while ($member = $result_members->fetch_assoc()) {
            $members_by_project[(int)$member['project_id']][] = [
                'id' => $member['user_id'],
                'name' => $member['nombre'],
                'role' => $member['role']
            ];
        }

        $stmt_members->close();
    }
}

$files_by_proposal = [];
if (!empty($proposal_ids)) {
    $in = build_in_clause_review($proposal_ids);
    $sql_files = "SELECT f.id, f.proposal_id, f.file_name, f.mime_type, f.file_size, f.upload_date,
                         f.uploaded_by, u.nombre AS uploaded_by_name
                  FROM tfg_files f
                  LEFT JOIN sis_user u ON f.uploaded_by = u.id
                  WHERE f.proposal_id IN ({$in['placeholders']})
                  ORDER BY f.proposal_id ASC, f.id ASC";

    $stmt_files = $conn->prepare($sql_files);
    if ($stmt_files) {
        $stmt_files->bind_param($in['types'], ...$proposal_ids);
        $stmt_files->execute();
        $result_files = $stmt_files->get_result();

        while ($file = $result_files->fetch_assoc()) {
            $files_by_proposal[(int)$file['proposal_id']][] = $file;
        }

        $stmt_files->close();
    }
}

$proposal_cards = [];

foreach ($proposals as $proposal_id => $proposal) {
    $project_id = isset($proposal['project_id']) ? (int)$proposal['project_id'] : 0;
    $members = $members_by_project[$project_id] ?? [];

    if (empty($members)) {
        $members[] = [
            'id' => $proposal['owner_id'],
            'name' => $proposal['owner_name'],
            'role' => 'Líder'
        ];
    }

    $documents = [];

    if (!empty($proposal['file_name'])) {
        $documents[] = [
            'id' => (int)$proposal['id'],
            'name' => $proposal['file_name'],
            'role_label' => 'Principal',
            'uploaded_by' => $proposal['owner_name'],
            'uploaded_at' => $proposal['created_at'],
            'mime_label' => formato_legible_revision($proposal['mime_type']),
            'size_label' => formatear_tamano_mb_revision($proposal['file_size']),
            'download_url' => $base_url . 'mod/admin/users/tfg_download_file.php?id=' . (int)$proposal['id']
        ];
    }

    foreach ($files_by_proposal[$proposal_id] ?? [] as $file) {
        $documents[] = [
            'id' => (int)$file['id'],
            'name' => $file['file_name'],
            'role_label' => 'Adjunto',
            'uploaded_by' => $file['uploaded_by_name'] ?: $file['uploaded_by'],
            'uploaded_at' => $file['upload_date'],
            'mime_label' => formato_legible_revision($file['mime_type']),
            'size_label' => formatear_tamano_mb_revision($file['file_size']),
            'download_url' => $base_url . 'mod/admin/users/tfg_download_file.php?id=' . (int)$file['id']
        ];
    }

    $proposal_cards[] = [
        'id' => (int)$proposal['id'],
        'title' => $proposal['title'],
        'created_at' => $proposal['created_at'],
        'created_at_label' => date('d/m/Y H:i', strtotime($proposal['created_at'])),
        'members' => $members,
        'documents' => $documents,
        'documents_count' => count($documents)
    ];
}

$conn->close();
?>
<?php if (!empty($proposal_cards)): ?>
<div class="review-helper-banner alert alert-info border-0 mb-4">
    <div class="d-flex align-items-start gap-3">
        <i class="bi bi-info-circle-fill fs-4"></i>
        <div>
            <strong>Revisión por propuesta</strong>
            <div class="small mt-1">
                Puede abrir cada propuesta para ver todos los archivos subidos y aprobar o rechazar la propuesta completa en bloque.
            </div>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-hover align-middle review-table">
        <thead class="table-light">
            <tr>
                <th>Estudiantes</th>
                <th>Título de la propuesta</th>
                <th>Fecha de envío</th>
                <th>Documentos</th>
                <th class="text-center" style="width: 180px;">Acción</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($proposal_cards as $proposal): ?>
            <tr>
                <td>
                    <div class="student-list">
                        <?php foreach ($proposal['members'] as $index => $member): ?>
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
                    <div class="fw-semibold"><?= htmlspecialchars($proposal['title']) ?></div>
                    <small class="text-muted">ID propuesta: <?= (int)$proposal['id'] ?></small>
                </td>
                <td><?= htmlspecialchars($proposal['created_at_label']) ?></td>
                <td>
                    <span class="badge rounded-pill text-bg-primary">
                        <?= (int)$proposal['documents_count'] ?> documento<?= (int)$proposal['documents_count'] === 1 ? '' : 's' ?>
                    </span>
                </td>
                <td class="text-center">
                    <button type="button"
                            class="btn btn-sm btn-primary text-white js-open-documents"
                            data-proposal-id="<?= (int)$proposal['id'] ?>">
                        <i class="bi bi-folder2-open"></i> Ver documentos
                    </button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
<div class="empty-state text-center">
    <i class="bi bi-check2-circle"></i>
    <h3>¡Todo al día!</h3>
    <p class="mb-0">No hay propuestas pendientes de revisión en este momento.</p>
</div>
<?php endif; ?>

<div id="documentsModal" class="custom-modal-overlay" style="display:none;">
    <div class="custom-modal-content custom-modal-lg">
        <div class="custom-modal-header">
            <div>
                <h4 id="documentsModalTitle" class="mb-1">Documentos de la propuesta</h4>
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
            Los comentarios pueden indicar cuáles documentos requieren corrección. La aprobación o rechazo se aplica a la propuesta completa.
        </div>

        <div class="custom-modal-actions mt-4">
            <button type="button" class="btn btn-secondary" onclick="closeDocumentsModal()">Cerrar</button>
            <button type="button" class="btn btn-success" onclick="openReviewModal('Cumple requisitos')">
                <i class="bi bi-check-circle"></i> Cumple requisitos
            </button>
            <button type="button" class="btn btn-danger" onclick="openReviewModal('No cumple requisitos')">
                <i class="bi bi-x-circle"></i> No cumple requisitos
            </button>
        </div>
    </div>
</div>

<div id="commentModal" class="custom-modal-overlay" style="display:none;">
    <div class="custom-modal-content">
        <h4 id="modalTitle">Revisión de propuesta</h4>
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

<?php
$proposal_cards_json = json_encode(
    $proposal_cards,
    JSON_UNESCAPED_UNICODE
    | JSON_HEX_TAG
    | JSON_HEX_APOS
    | JSON_HEX_QUOT
    | JSON_HEX_AMP
    | JSON_INVALID_UTF8_SUBSTITUTE
);

if ($proposal_cards_json === false) {
    error_log('tfg_review_list.php: json_encode($proposal_cards) falló: ' . json_last_error_msg());
    $proposal_cards_json = '[]';
}
?>
<script>
const proposalsData = Array.isArray(<?= $proposal_cards_json ?>) ? <?= $proposal_cards_json ?> : [];

let activeProposal = null;
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
        openDocumentsModal(button.dataset.proposalId);
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

function openDocumentsModal(proposalId) {
    activeProposal = proposalsData.find((proposal) => Number(proposal.id) === Number(proposalId));

    if (!activeProposal) {
        showCustomAlert('Error', 'No se encontró la propuesta seleccionada.', false);
        return;
    }

    const members = normalizeList(activeProposal.members);
    const documents = normalizeList(activeProposal.documents);

    documentsModalTitle.textContent = activeProposal.title;
    documentsModalSubtitle.textContent = `Propuesta #${activeProposal.id} · Enviada el ${activeProposal.created_at_label}`;
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
    if (!activeProposal) {
        showCustomAlert('Error', 'No hay una propuesta activa para revisar.', false);
        return;
    }

    pendingReview = { id: activeProposal.id, status };

    modalTitle.textContent = 'Revisión de propuesta';
    modalText.textContent = status === 'Cumple requisitos'
        ? `¿Desea marcar la propuesta "${activeProposal.title}" como "Cumple requisitos"?`
        : `¿Desea marcar la propuesta "${activeProposal.title}" como "No cumple requisitos"?`;

    modalComments.value = '';
    modalError.style.display = 'none';

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
    const comments = modalComments.value;

    if (pendingReview.status === 'No cumple requisitos' && comments.trim() === '') {
        modalError.style.display = 'block';
        return;
    }

    modalError.style.display = 'none';
    commentModal.style.display = 'none';

    const body = new URLSearchParams({
        id: pendingReview.id,
        status: pendingReview.status,
        comments: comments
    });

    fetch('mod/admin/users/tfg_update_status.php', {
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
            showCustomAlert('¡Éxito!', 'El estado de la propuesta ha sido actualizado correctamente.', true);
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
