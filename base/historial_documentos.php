<?php
// =============================== INICIALIZACIÓN Y SEGURIDAD ===============================

// Incluir archivos de configuración y utilidades
include("mod/login/check.php");
include('includes.php');
include('lang/lang.es');
include('inc/db/db.php');

// Obtener variables de sesión del usuario autenticado
$current_user_id = $mySessionController->getVar("usuario");
$current_user_name = $mySessionController->getVar("nombre");
$current_user_rol = $mySessionController->getVar("rol");
$cds_domain = $mySessionController->getVar("cds_domain");
$cds_locate = $mySessionController->getVar("cds_locate");
$base_url = $cds_domain . $cds_locate;

// Restringir acceso solo a estudiantes (rol 4)
if ($current_user_rol != 4) {
    header('Location: dashboard.php');
    exit;
}

function formatoLegible($mime_type) {
    // Normalizar a minúsculas para comparaciones más fáciles
    $mime = strtolower($mime_type);
    
    // Extraer la parte después del slash si existe
    $parts = explode('/', $mime);
    $mime = isset($parts[1]) ? $parts[1] : $mime;
    
    // Mapeo de tipos comunes
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
    
    // Comprobar si existe en el mapeo, o devolver formato original en mayúsculas
    return isset($tipos[$mime]) ? $tipos[$mime] : strtoupper($mime);
}

// =============================== OBTENER ID DE PROYECTO ===============================

// Buscar el project_id asociado al usuario autenticado
$project_id = 0;
try {
    $conn = new mysqli($db_host, $usuario, $clave, $db);
    $conn->set_charset("utf8");
    $sql = "SELECT rp.id
            FROM registered_projects rp
            INNER JOIN tfg_proposals tp ON rp.tfg_proposal_id = tp.id
            WHERE tp.user_id = ?
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $current_user_id);
    $stmt->execute();
    $stmt->bind_result($project_id);
    $stmt->fetch();
    $stmt->close();
} catch (Exception $e) {
    error_log("Error obteniendo project_id: " . $e->getMessage());
}

// =============================== OBTENER DOCUMENTOS PRINCIPALES ===============================
// Array para almacenar los documentos y sus versiones
$documentos = [];

// --- Propuesta TFG principal ---
try {
    $conn = new mysqli($db_host, $usuario, $clave, $db);
    $conn->set_charset("utf8");
    $sql = "SELECT tp.id, tp.title, tp.file_name, tp.mime_type, tp.file_size, tp.status, tp.created_at
            FROM tfg_proposals tp 
            WHERE tp.user_id = ?
            ORDER BY tp.created_at DESC
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $row['tipo'] = 'Propuesta TFG'; // Identificador de tipo de documento
        $row['version'] = 1; // Versión inicial
        $documentos[] = $row;
    }
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    error_log("Error obteniendo propuesta TFG: " . $e->getMessage());
}

// Guardar el ID de la propuesta principal para excluirla de las versiones
$propuesta_principal_id = $documentos[0]['id'] ?? 0;
// =============================== OBTENER VERSIONES DE PROPUESTA TFG ===============================

// Versiones históricas de la propuesta TFG (excluyendo la principal)
$propuestas_vers = [];
try {
    $conn = new mysqli($db_host, $usuario, $clave, $db);
    $conn->set_charset("utf8");
    
    // Consulta para ordenar de más antigua a más reciente
    $sql = "SELECT tp.id id_pro,tph.id, tph.proposal_id, tph.file_name, tph.mime_type, tph.file_size, 
                   tph.status, tph.comments, tph.created_at, tp.title
        FROM tfg_proposal_history tph
        INNER JOIN tfg_proposals tp ON tph.proposal_id = tp.id
        WHERE tp.user_id = ?
        AND tp.id != ?  /* Excluir la propuesta principal */
        ORDER BY tp.created_at DESC /* Orden de más antigua a más reciente */
        LIMIT 4"; // Limitamos a 5 versiones
        
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $current_user_id, $propuesta_principal_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $version = 1; // Contador de versión
    while ($row = $result->fetch_assoc()) {
        $propuestas_vers[] = $row;
    }
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    error_log("Error obteniendo versiones de propuestas: " . $e->getMessage());
}

// =============================== OBTENER DOCUMENTO FINAL DE TFG ===============================

// Documento final de TFG (último entregado)
try {
    $conn = new mysqli($db_host, $usuario, $clave, $db);
    $conn->set_charset("utf8");
    $sql = "SELECT fd.id as doc_id, fd.proposal_id, fd.status, fd.project_status, fd.submitted_at,
                   f.id as file_id, f.file_name, f.mime_type, f.file_size, f.version, f.document_type, f.upload_date
            FROM tfg_final_documents fd
            INNER JOIN tfg_files f ON fd.file_id = f.id
            INNER JOIN tfg_proposals tp ON fd.proposal_id = tp.id
            WHERE tp.user_id = ?
            ORDER BY fd.submitted_at DESC
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $doc = [
            'id' => $row['file_id'],
            'title' => $row['file_name'],
            'file_name' => $row['file_name'],
            'mime_type' => $row['mime_type'],
            'file_size' => $row['file_size'],
            'status' => $row['status'],
            'created_at' => $row['submitted_at'],
            'tipo' => $row['document_type'],
            'document_type' => $row['document_type'],
            'version' => $row['version']
        ];
        $documentos[] = $doc;
        
        // Guardar info para buscar versiones de este documento final
        $doc_final_ids[$row['document_type']] = [
            'file_id' => $row['file_id'],
            'document_type' => $row['document_type']
        ];
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error obteniendo documentos finales: " . $e->getMessage());
}

// =============================== OBTENER VERSIONES DE DOCUMENTO FINAL DE TFG ===============================
// Array para almacenar versiones de documentos finales
$versiones_docs_finales = [];
if (!empty($doc_final_ids)) {
    try {
        foreach ($doc_final_ids as $doc_info) {
            $conn = new mysqli($db_host, $usuario, $clave, $db);
            $conn->set_charset("utf8");
            
            // Obtener todas las versiones del mismo tipo de documento excepto la actual
            $sql = "SELECT f.id, fd.project_status as status, f.file_name, f.mime_type, f.file_size, f.version, f.document_type, f.upload_date, f.uploaded_by, fd.status, fd.proposal_id
                    FROM tfg_files f
                    LEFT JOIN tfg_final_documents fd ON f.id = fd.file_id
                    WHERE f.document_type = ? 
                    AND f.uploaded_by = ?
                    AND f.id != ?
                    ORDER BY f.upload_date DESC
                    LIMIT 4"; // Limitamos a 5 versiones
                    
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $doc_info['document_type'], $current_user_id, $doc_info['file_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $versions = [];
            while ($row = $result->fetch_assoc()) {
                $versions[] = [
                    'id' => $row['id'],
                    'title' => $row['file_name'],
                    'file_name' => $row['file_name'],
                    'mime_type' => $row['mime_type'],
                    'file_size' => $row['file_size'],
                    'version' => $row['version'],
                    'document_type' => $row['document_type'],
                    'created_at' => $row['upload_date'],
                    'status' => $row['status'] ? $row['status'] : 'Versión anterior' // Usamos el estado de la tabla si existe
                ];
            }
            
            if (!empty($versions)) {
                $versiones_docs_finales[$doc_info['document_type']] = $versions;
            }
            
            $stmt->close();
            $conn->close();
        }
    } catch (Exception $e) {
        error_log("Error obteniendo versiones de documentos finales: " . $e->getMessage());
    }
}

// =============================== OBTENER OTROS DOCUMENTOS DEL USUARIO ===============================

// Otros archivos subidos por el usuario (no propuestas ni documentos finales)
try {
    $conn = new mysqli($db_host, $usuario, $clave, $db);
    $conn->set_charset("utf8");
    
    // Consulta para obtener otros tipos de documentos
    $sql = "SELECT id, file_name, mime_type, file_size, version, document_type, upload_date, uploaded_by
            FROM tfg_files
            WHERE uploaded_by = ? 
            AND document_type NOT IN ('Propuesta TFG', 'Documento Final TFG')
            ORDER BY upload_date DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $doc = [
            'id' => $row['id'],
            'title' => $row['file_name'],
            'file_name' => $row['file_name'],
            'mime_type' => $row['mime_type'],
            'file_size' => $row['file_size'],
            'status' => '-', // Estado genérico
            'created_at' => $row['upload_date'],
            'tipo' => $row['document_type'],
            'document_type' => $row['document_type'],
            'version' => $row['version'] ?? 1.0
        ];
        $documentos[] = $doc;
    }
    
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    error_log("Error obteniendo otros documentos: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="es">
<!-- =============================== HEAD =============================== -->
<?php include 'head.php'; ?>
<body class="d-flex flex-column min-vh-100 fondo-una">
    <!-- =============================== HEADER =============================== -->
    <?php include 'header.php'; ?> 
    <!-- =============================== CONTENIDO PRINCIPAL =============================== -->
    <main class="flex-fill">
        <div class="container my-4">
            <h1 class="text-center mb-4" style="color: #b00; font-size: 2.5rem;">Historial de documentos</h1>
            <div class="table-responsive">
                <table class="table table-bordered align-middle text-center">
                    <thead class="table-secondary table-responsive">
                        <tr>
                            <th>Documento</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Versión</th>
                            <th>Tamaño</th>
                            <th>Formato</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documentos as $i => $doc): ?>
                            <?php $collapseId = 'versionesCollapse_' . $i; ?>
                            <!-- Fila principal -->
                            <tr>
                                <td data-label="Documento"><?= htmlspecialchars($doc['tipo']) ?>: <?= htmlspecialchars($doc['title'] ?? $doc['file_name']) ?></td>
                                <td data-label="Fecha"><?= date('d/m/Y', strtotime($doc['created_at'])) ?></td>
                                <td data-label="Estado"><?= htmlspecialchars($doc['status']) ?></td>
                                <td data-label="Versión">
                                    <?php if (($doc['tipo'] === 'Propuesta TFG' && !empty($propuestas_vers)) || 
                                            ($doc['tipo'] === 'Documento Final TFG' && !empty($versiones_docs_finales[$doc['document_type']]))): ?>
                                        <button class="btn btn-link-una toggle-collapse" type="button"
                                        data-target="<?= $collapseId ?>">
                                            Versiones
                                        </button>
                                    <?php elseif (isset($doc['version'])): ?>
                                        <?= number_format($doc['version'], 1) ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td data-label="Tamaño"><?= number_format($doc['file_size'] / (1024 * 1024), 2) ?> MB</td>
                                <td data-label="Formato"><?= formatoLegible($doc['mime_type']) ?></td>
                                <td data-label="Acción">
                                    <?php if ($doc['tipo'] === 'Propuesta TFG'): ?>  
                                        <a href="<?= $base_url . 'mod/admin/users/tfg_download.php?id=' . $doc['id'] ?>" class="btn-link-una">
                                            <i class="bi bi-download me-1"></i>
                                            <span>Descargar</span>
                                        </a>
                                    <?php elseif ($doc['tipo'] === 'Documento Final TFG' || $doc['tipo'] !== 'Propuesta TFG'): ?>
                                        <a href="<?= $base_url . 'mod/admin/users/tfg_download_file.php?id=' . $doc['id'] ?>" class="btn-link-una">
                                            <i class="bi bi-download me-1"></i>
                                            <span>Descargar</span>    
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            
                            <!-- Collapse container para mostrar versiones -->
                            <?php if ($doc['tipo'] === 'Propuesta TFG' && !empty($propuestas_vers)): ?>
                            <tr class="collapse-row">
                                <td colspan="7" class="p-0">
                                    <div class="custom-collapse" id="<?= $collapseId ?>" style="display: none;">
                                        <div class="bg-light py-2 px-4">
                                            <table class="table table-bordered mb-0 version-table">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Nombre</th>
                                                        <th>Fecha</th>
                                                        <th>Estado</th>
                                                        <th>Versión</th>
                                                        <th>Tamaño</th>
                                                        <th>Formato</th>
                                                        <th>Acción</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($propuestas_vers as $version): ?>
                                                    <tr>
                                                        <td data-label="Documento"><?= htmlspecialchars($version['title']) ?></td>
                                                        <td data-label="Fecha"><?= date('d/m/Y', strtotime($version['created_at'])) ?></td>
                                                        <td data-label="Estado"><?= htmlspecialchars($version['status']) ?></td>
                                                        <td data-label="Versión" class="text-center">-</td>
                                                        <td data-label="Tamaño"><?= number_format($version['file_size'] / (1024 * 1024), 2) ?> MB</td>
                                                        <td data-label="Formato"><?= formatoLegible($version['mime_type']) ?></td>
                                                        <td data-label="Acción">
                                                            <a href="<?= $base_url . 'mod/admin/users/tfg_download.php?id=' . $version['id_pro'] ?>" class="btn-link-una">
                                                                <i class="bi bi-download me-1"></i>
                                                                <span>Descargar</span>
                                                            </a>    
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php elseif ($doc['tipo'] === 'Documento Final TFG' && !empty($versiones_docs_finales[$doc['document_type']])): ?>
                            <tr class="collapse-row">
                                <td colspan="7" class="p-0">
                                    <div class="custom-collapse" id="<?= $collapseId ?>" style="display: none;">
                                        <div class="bg-light py-2 px-4">
                                            <table class="table table-bordered mb-0 version-table">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Nombre</th>
                                                        <th>Fecha</th>
                                                        <th>Estado</th>
                                                        <th>Versión</th>
                                                        <th>Tamaño</th>
                                                        <th>Formato</th>
                                                        <th>Acción</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($versiones_docs_finales[$doc['document_type']] as $version): ?>
                                                    <tr>
                                                        <td data-label="Documento"><?= htmlspecialchars($version['file_name']) ?></td>
                                                        <td data-label="Fecha"><?= date('d/m/Y', strtotime($version['created_at'])) ?></td>
                                                        <td data-label="Estado"><?= htmlspecialchars($version['status']) ?></td>
                                                        <td data-label="Versión"><?= number_format($version['version'], 1) ?></td>
                                                        <td data-label="Tamaño"><?= number_format($version['file_size'] / (1024 * 1024), 2) ?> MB</td>
                                                        <td data-label="Formato"><?= formatoLegible($version['mime_type']) ?></td>
                                                        <td data-label="Acción">
                                                            <a href="<?= $base_url . 'mod/admin/users/tfg_download_file.php?id=' . $version['id'] ?>" class="btn-link-una">
                                                                <i class="bi bi-download me-1"></i>
                                                                <span>Descargar</span>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if (empty($documentos)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">No se han encontrado documentos.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-center mt-4">
                <a href="Panel_SubirTFG.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left-circle"></i> Volver al Panel Principal
                </a>
            </div>
        </div>
    </main>
  <!-- =============================== FOOTER =============================== -->
    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.toggle-collapse').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var targetId = this.getAttribute('data-target');
                var target = document.getElementById(targetId);
                if (target) {
                    target.style.display = (target.style.display === 'none' || target.style.display === '') ? 'block' : 'none';
                }
            });
        });
    });
    </script>
</body>
</html>