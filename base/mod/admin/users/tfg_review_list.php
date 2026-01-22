<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
include __DIR__ . '/../../../inc/db/bdcommon.inc';

$conn = new mysqli($db_host, $usuario, $clave, $db);
if ($conn->connect_error) die("Conexión fallida: " . $conn->connect_error);

// Asegurarse de que $base_url esté disponible para el enlace de descarga
if (!isset($base_url)) {
    // Si el script se carga en un contexto sin $base_url, se reconstruye desde la sesión.
    include_once __DIR__ . '/../../../lib/mysession/mySession.class.php';
    include_once __DIR__ . '/../../../lib/mysession/mySession.conf.php';
    $mySessionController = mySession::getIstance($_MYSESSION_CONF);
    $base_url = $mySessionController->getVar("cds_domain") . $mySessionController->getVar("cds_locate");
}

$sql = "SELECT p.*, u.nombre as estudiante, u.id as estudiante_id
        FROM tfg_proposals p 
        JOIN sis_user u ON p.user_id = u.id 
        WHERE p.status = 'Pendiente de Revisión'
        ORDER BY p.created_at DESC";

$result = $conn->query($sql);
?>

<?php if ($result && $result->num_rows > 0): ?>
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>Estudiante</th>
                <th>Título de la Propuesta</th>
                <th>Fecha de Envío</th>
                <th class="text-center" style="width: 420px;">Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td>
                    <div class="fw-bold"><?= htmlspecialchars($row['estudiante']) ?></div>
                    <small class="text-muted"><?= htmlspecialchars($row['estudiante_id']) ?></small>
                </td>
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                <td class="text-center">
                    <div class="btn-group" role="group">
                        <?php $download_url = $base_url . 'mod/admin/users/tfg_download.php?id=' . $row['id']; ?>
                        <a href="<?= htmlspecialchars($download_url) ?>" class="btn btn-sm btn-primary text-white" title="Descargar Propuesta">
                            <i class="bi bi-download"></i> Descargar
                        </a>
                        <button class="btn btn-sm btn-success" onclick="updateStatus(<?= $row['id']; ?>, 'Cumple requisitos')">
                            <i class="bi bi-check-circle"></i> Cumple Requisitos
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="updateStatus(<?= $row['id']; ?>, 'No cumple requisitos')">
                            <i class="bi bi-x-circle"></i> No Cumple Requisitos
                        </button>
                    </div>
                </td>
            </tr>
        <?php endwhile; ?>
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

<!-- Custom Modal for Comments -->
<div id="commentModal" class="custom-modal-overlay" style="display:none;">
    <div class="custom-modal-content">
        <h4 id="modalTitle">Revisión de Propuesta</h4>
        <p id="modalText"></p>
        <textarea id="modalComments" class="form-control" placeholder="Escriba aquí sus observaciones..." rows="4"></textarea>
        <small id="modalError" class="text-danger" style="display:none;">Los comentarios son obligatorios para esta acción.</small>
        <div class="custom-modal-actions">
            <button id="modalCancel" class="btn btn-secondary">Cancelar</button>
            <button id="modalConfirm" class="btn btn-primary">Enviar Revisión</button>
        </div>
    </div>
</div>

<!-- Custom Alert Modal -->
<div id="alertModal" class="custom-modal-overlay" style="display:none;">
    <div class="custom-modal-content">
        <h4 id="alertTitle"></h4>
        <p id="alertMessage"></p>
        <div class="custom-modal-actions">
            <button id="alertOk" class="btn btn-primary">Aceptar</button>
        </div>
    </div>
</div>


<style>
.custom-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.6);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1060; /* Bootstrap's modal z-index is 1050, so we go higher */
}

.custom-modal-content {
    background-color: #fff;
    padding: 25px;
    border-radius: 0.5rem;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15);
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

.custom-modal-content h4 {
    margin-top: 0;
    margin-bottom: 15px;
    font-weight: 500;
}

.custom-modal-actions {
    margin-top: 20px;
    text-align: right;
}

.custom-modal-actions button {
    margin-left: 10px;
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
</style>

<script>
// --- State and Elements ---
let currentProposal = { id: null, status: null };

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

// --- Custom Alert Function ---
function showCustomAlert(title, message, isSuccess) {
    alertTitle.textContent = title;
    alertMessage.textContent = message;
    alertModal.style.display = 'flex';
    alertOk.onclick = () => {
        alertModal.style.display = 'none';
        if (isSuccess) {
            location.reload();
        }
    };
}

// --- Main Function to Open Comment Modal ---
function updateStatus(id, status) {
    currentProposal = { id, status };
    const actionText = status === 'Cumple requisitos' ? 'marcar como "Cumple requisitos"' : 'marcar como "No cumple requisitos"';
    
    modalText.textContent = `¿Desea ${actionText} esta propuesta?`;
    modalComments.value = '';
    modalError.style.display = 'none';
    commentModal.style.display = 'flex';
    modalComments.focus();
}

// --- Event Listeners ---
modalCancel.addEventListener('click', () => {
    commentModal.style.display = 'none';
});

modalConfirm.addEventListener('click', () => {
    const comments = modalComments.value;

    if (currentProposal.status === 'No cumple requisitos' && comments.trim() === '') {
        modalError.style.display = 'block';
        return;
    }
    
    modalError.style.display = 'none';
    commentModal.style.display = 'none';

    const body = `id=${currentProposal.id}&status=${encodeURIComponent(currentProposal.status)}&comments=${encodeURIComponent(comments)}`;

    fetch('mod/admin/users/tfg_update_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body
    })
    .then(response => {
        const contentType = response.headers.get("content-type");
        if (contentType && contentType.indexOf("application/json") !== -1) {
            return response.json();
        } else {
            return response.text().then(text => {
                console.error("Respuesta no JSON del servidor:", text);
                throw new Error("Respuesta inesperada del servidor. La sesión puede haber expirado.");
            });
        }
    })
    .then(data => {
        if (data.success) {
            showCustomAlert('¡Éxito!', 'El estado de la propuesta ha sido actualizado.', true);
        } else {
            showCustomAlert('Error', 'No se pudo actualizar el estado: ' + data.message, false);
        }
    })
    .catch(error => {
        console.error('Error en la solicitud:', error);
        showCustomAlert('Error en la Operación', error.message, false);
    });
});

// Close modal if clicking on the overlay
window.addEventListener('click', (event) => {
    if (event.target == commentModal) {
        commentModal.style.display = 'none';
    }
    if (event.target == alertModal) {
        alertModal.style.display = 'none';
    }
});
</script>