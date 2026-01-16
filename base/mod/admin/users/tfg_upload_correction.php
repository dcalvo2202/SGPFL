<?php
// HU-020: Subir versiones corregidas del documento TFG

$base_path = realpath(__DIR__ . '/../../../');

// IMPORTANTE: Guardar variables de BD ANTES de include check.php
// porque check.php sobrescribe $usuario con el usuario de sesión
require_once($base_path . '/inc/db/bdcommon.inc');
$db_user = $usuario;  // Guardar 'root'
$db_pass = $clave;    // Guardar ''
$db_name = $db;       // Guardar 'base_db'

include("../../login/check.php");

// Obtener variables de sesión
$current_user_id = $mySessionController->getVar("usuario");
$current_user_rol = $mySessionController->getVar("rol");
$cds_domain = $mySessionController->getVar("cds_domain");
$cds_locate = $mySessionController->getVar("cds_locate");
$base_url = $cds_domain . $cds_locate;

// Verificar que sea estudiante (rol 4)
if ($current_user_rol != 4) {
    header('Location: ' . $base_url . 'dashboard.php');
    exit;
}

// Obtener document_id de la URL
$document_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($document_id <= 0) {
    header('Location: ' . $base_url . 'panel_estudiante.php');
    exit;
}

// Obtener información del documento y las observaciones
try {
    // Incluir variables de BD para evitar conflictos con $usuario de sesión
    include($base_path . '/inc/db/bdcommon.inc');
    
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }
    $conn->set_charset("utf8");
    
    // Verificar que el documento pertenece al estudiante y está rechazado
    $sql = "SELECT fd.id, fd.proposal_id, fd.status, tp.title, 
                   (SELECT observations FROM tfg_document_reviews 
                    WHERE document_id = fd.id AND review_type = 'Revision CTFG' 
                    ORDER BY review_date DESC LIMIT 1) as observations,
                   (SELECT COUNT(*) FROM tfg_document_reviews 
                    WHERE document_id = fd.id AND review_type = 'Correccion Estudiante') as corrections_count
            FROM tfg_final_documents fd
            INNER JOIN tfg_proposals tp ON fd.proposal_id = tp.id
            WHERE fd.id = ? AND fd.submitted_by = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $document_id, $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('Location: ' . $base_url . 'panel_estudiante.php');
        exit;
    }
    
    $document = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    // Verificar que el documento esté rechazado
    if ($document['status'] !== 'Rechazado') {
        header('Location: ' . $base_url . 'panel_estudiante.php');
        exit;
    }
    
} catch (Exception $e) {
    die("Error: " . htmlspecialchars($e->getMessage()));
}

$additional_css = ['inc/css/tfg_upload.css'];
$page_title = "Subir Correcciones - TFG";
?>
<!DOCTYPE html>
<html lang="es">
<?php include $base_path . '/head.php'; ?>
<script>
    window.CURRENT_USER_ID = '<?= htmlspecialchars($current_user_id) ?>';
    window.DOCUMENT_ID = <?= $document_id ?>;
    window.MAX_WORDS = 500;
</script>
<body class="d-flex flex-column min-vh-100 fondo-una">
    <?php include $base_path . '/header.php'; ?>

    <main class="flex-fill">
        <div class="container my-5">
            <div class="dashboard-header text-center mb-4">
                <h1><i class="bi bi-file-earmark-arrow-up-fill"></i> Subir Correcciones del TFG</h1>
                <p class="lead">Documento: <strong><?= htmlspecialchars($document['title']) ?></strong></p>
                <p class="text-muted">Corrección número: <strong><?= ($document['corrections_count'] + 1) ?></strong></p>
            </div>

            <!-- Observaciones del CTFG -->
            <div class="section-card mb-4">
                <div class="section-header">
                    <h3><i class="bi bi-chat-left-text-fill"></i> Observaciones del CTFG</h3>
                </div>
                <div class="section-body">
                    <div class="alert alert-warning">
                        <strong><i class="bi bi-exclamation-triangle-fill"></i> Correcciones Requeridas:</strong>
                        <p class="mt-2" style="white-space: pre-wrap;"><?= htmlspecialchars($document['observations'] ?? 'No se encontraron observaciones.') ?></p>
                    </div>
                </div>
            </div>

            <form id="correctionForm" action="tfg_upload_correction_process.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="document_id" value="<?= $document_id ?>">
                
                <!-- Documento Corregido -->
                <div class="section-card">
                    <div class="section-header">
                        <h3><i class="bi bi-file-pdf-fill"></i> Documento Corregido</h3>
                    </div>
                    <div class="section-body">
                        <div class="form-group-tfg">
                            <label for="inp-document" class="form-label-tfg">
                                <i class="bi bi-upload"></i> Subir Documento Corregido (PDF) *
                            </label>
                            <input type="file" 
                                   class="form-control-tfg" 
                                   id="inp-document" 
                                   name="document" 
                                   accept=".pdf" 
                                   required>
                            <small class="text-muted">Solo PDF | Tamaño máximo: 10 MB</small>
                        </div>

                        <div class="form-group-tfg">
                            <label for="txt-corrections-summary" class="form-label-tfg">
                                <i class="bi bi-list-check"></i> Resumen de Cambios Realizados *
                            </label>
                            <textarea class="form-control-tfg" 
                                      id="txt-corrections-summary" 
                                      name="corrections_summary" 
                                      rows="6" 
                                      required
                                      placeholder="Describa los cambios realizados para atender las observaciones del CTFG (máximo 500 palabras)"></textarea>
                            <small class="text-muted">Palabras: <span id="word-count">0</span> / 500</small>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="chk-all-addressed" 
                                   name="all_addressed" 
                                   required>
                            <label class="form-check-label" for="chk-all-addressed">
                                <strong>Confirmo que he atendido TODAS las observaciones del CTFG *</strong>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Botón de envío -->
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-lg btn-primary px-5">
                        <i class="bi bi-send-fill"></i> Enviar Correcciones
                    </button>
                    <a href="<?= $base_url ?>panel_estudiante.php" class="btn btn-lg btn-secondary px-5 ms-3">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </main>

    <?php include $base_path . '/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    // Contador de palabras
    const textarea = document.getElementById('txt-corrections-summary');
    const wordCountSpan = document.getElementById('word-count');
    
    textarea.addEventListener('input', function() {
        const text = this.value.trim();
        const words = text ? text.split(/\s+/).length : 0;
        wordCountSpan.textContent = words;
        
        if (words > window.MAX_WORDS) {
            wordCountSpan.classList.add('text-danger');
            wordCountSpan.classList.remove('text-muted');
        } else {
            wordCountSpan.classList.add('text-muted');
            wordCountSpan.classList.remove('text-danger');
        }
    });

    // Validación del formulario
    document.getElementById('correctionForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const text = textarea.value.trim();
        const words = text ? text.split(/\s+/).length : 0;
        
        if (words > window.MAX_WORDS) {
            Swal.fire({
                icon: 'error',
                title: 'Resumen muy largo',
                text: `El resumen excede el límite de ${window.MAX_WORDS} palabras (actualmente: ${words} palabras).`
            });
            return false;
        }

        const fileInput = document.getElementById('inp-document');
        if (fileInput.files.length === 0) {
            Swal.fire({
                icon: 'error',
                title: 'Archivo requerido',
                text: 'Debe seleccionar un archivo PDF con las correcciones.'
            });
            return false;
        }
        
        const file = fileInput.files[0];
        const maxSize = 8 * 1024 * 1024; // 8 MB
        
        if (file.size > maxSize) {
            Swal.fire({
                icon: 'error',
                title: 'Archivo muy grande',
                text: 'El archivo PDF no debe superar los 8 MB.'
            });
            return false;
        }
        
        // Enviar formulario con AJAX
        const formData = new FormData(this);
        
        Swal.fire({
            title: 'Enviando correcciones...',
            text: 'Por favor espere',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        fetch('tfg_upload_correction_process.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Correcciones Enviadas!',
                    html: `${data.message}<br><br><strong>Versión:</strong> ${data.version}<br><strong>Corrección #:</strong> ${data.corrections_count}`,
                    confirmButtonText: 'Ir al Panel'
                }).then(() => {
                    window.location.href = '<?= $base_url ?>Panel_SubirTFG.php';
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo enviar las correcciones. Intente nuevamente.'
            });
        });
    });
    </script>
</body>
</html>
