<?php
/**
 * HU-027: Ver detalle de proyecto archivado
 * 
 * Muestra información completa de un proyecto en el archivo histórico.
 */

// Verificar autenticación
include("mod/login/check.php");
require_once('inc/constants.php');
require_once('inc/archive_functions.php');

// Variables de sesión
$current_user_id = $mySessionController->getVar("usuario");
$current_user_name = $mySessionController->getVar("nombre");
$rol = $mySessionController->getVar("rol");

// URL base
$cds_domain = $mySessionController->getVar("cds_domain");
$cds_locate = $mySessionController->getVar("cds_locate");
$base_url = $cds_domain . $cds_locate;

// Verificar permisos
$allowed_roles = [ROL_ADMIN, ROL_CTFG, ROL_GESTOR];

if (!in_array($rol, $allowed_roles)) {
    header("location: ./index.php");
    exit();
}

// Obtener ID
$archive_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$archive_id) {
    header("location: panel_archivo_historico.php");
    exit();
}

// Conexión (incluir después de variables de sesión)
$conn = new mysqli('localhost', 'root', '', 'base_db');
$conn->set_charset("utf8mb4");

// Registrar acceso
logArchiveAccess($conn, $current_user_id, 'VIEW_DETAIL', $archive_id);

// Obtener propuesta archivada
$stmt = $conn->prepare("
    SELECT tpa.*, 
           u.nombre as user_name,
           u.email as user_email
    FROM tfg_proposals_archive tpa
    LEFT JOIN sis_user u ON tpa.user_id = u.id
    WHERE tpa.id = ?
");
$stmt->bind_param("i", $archive_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    header("location: panel_archivo_historico.php");
    exit();
}

$proposal = $result->fetch_assoc();
$stmt->close();

// Obtener proyecto registrado archivado
$stmt_project = $conn->prepare("
    SELECT * FROM registered_projects_archive WHERE original_proposal_id = ?
");
$stmt_project->bind_param("i", $proposal['original_proposal_id']);
$stmt_project->execute();
$project_result = $stmt_project->get_result();
$project = $project_result->fetch_assoc();
$stmt_project->close();

// Obtener miembros archivados si hay proyecto
$members = [];
if ($project) {
    $stmt_members = $conn->prepare("
        SELECT * FROM project_members_archive WHERE original_project_id = ?
    ");
    $stmt_members->bind_param("i", $project['original_project_id']);
    $stmt_members->execute();
    $members_result = $stmt_members->get_result();
    while ($member = $members_result->fetch_assoc()) {
        $members[] = $member;
    }
    $stmt_members->close();
}

// Obtener archivos adicionales
$stmt_files = $conn->prepare("
    SELECT id, file_name, mime_type, original_size, compressed_size, document_type, original_created_at
    FROM tfg_files_archive WHERE original_proposal_id = ?
");
$stmt_files->bind_param("i", $proposal['original_proposal_id']);
$stmt_files->execute();
$files_result = $stmt_files->get_result();
$additional_files = [];
while ($file = $files_result->fetch_assoc()) {
    $additional_files[] = $file;
}
$stmt_files->close();

// Función para formatear tamaño
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Proyecto Archivado</title>
    <?php include('head.php'); ?>
    <style>
        .detail-label {
            font-weight: 600;
            color: #495057;
        }
        .archive-header {
            background: linear-gradient(135deg, #495057 0%, #343a40 100%);
            color: white;
            padding: 2rem;
            border-radius: 8px 8px 0 0;
        }
        .badge-concluido { background-color: #198754; }
        .badge-cancelado { background-color: #dc3545; }
        .section-card {
            border-left: 4px solid #6c757d;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <?php include('header.php'); ?>
    
    <div class="container-fluid py-4">
        <!-- Navegación -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Inicio</a></li>
                <li class="breadcrumb-item"><a href="panel_archivo_historico.php">Archivo Histórico</a></li>
                <li class="breadcrumb-item active">Detalle</li>
            </ol>
        </nav>
        
        <div class="card shadow">
            <!-- Cabecera -->
            <div class="archive-header">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h3 class="mb-2"><?php echo htmlspecialchars($proposal['title']); ?></h3>
                        <p class="mb-0 opacity-75">
                            <i class="bi bi-archive-fill me-2"></i>
                            Archivado el <?php echo date('d/m/Y \a \l\a\s H:i', strtotime($proposal['archived_at'])); ?>
                        </p>
                    </div>
                    <span class="badge fs-6 <?php echo $proposal['archive_reason'] === 'Concluido' ? 'badge-concluido' : 'badge-cancelado'; ?>">
                        <?php echo htmlspecialchars($proposal['archive_reason']); ?>
                    </span>
                </div>
            </div>
            
            <div class="card-body">
                <!-- Información del Estudiante -->
                <div class="card section-card mb-4">
                    <div class="card-header bg-light">
                        <i class="bi bi-person-fill me-2"></i>Información del Estudiante
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <p class="detail-label mb-1">Nombre</p>
                                <p><?php echo htmlspecialchars($proposal['user_name'] ?? 'No disponible'); ?></p>
                            </div>
                            <div class="col-md-4">
                                <p class="detail-label mb-1">ID de Usuario</p>
                                <p><?php echo htmlspecialchars($proposal['user_id']); ?></p>
                            </div>
                            <div class="col-md-4">
                                <p class="detail-label mb-1">Correo</p>
                                <p><?php echo htmlspecialchars($proposal['user_email'] ?? 'No disponible'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Información de la Propuesta -->
                <div class="card section-card mb-4">
                    <div class="card-header bg-light">
                        <i class="bi bi-file-text-fill me-2"></i>Propuesta TFG
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="detail-label mb-1">ID Original</p>
                                <p>#<?php echo $proposal['original_proposal_id']; ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="detail-label mb-1">Estado Original</p>
                                <p><span class="badge bg-secondary"><?php echo htmlspecialchars($proposal['original_status']); ?></span></p>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="detail-label mb-1">Disciplinas</p>
                                <p><?php echo htmlspecialchars($proposal['disciplines']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="detail-label mb-1">Fechas</p>
                                <p>
                                    <small>Creado: <?php echo date('d/m/Y', strtotime($proposal['original_created_at'])); ?></small><br>
                                    <small>Última actualización: <?php echo date('d/m/Y', strtotime($proposal['original_updated_at'])); ?></small>
                                </p>
                            </div>
                        </div>
                        <div class="mb-3">
                            <p class="detail-label mb-1">Descripción</p>
                            <p class="text-justify"><?php echo nl2br(htmlspecialchars($proposal['project_description'])); ?></p>
                        </div>
                        
                        <?php if ($proposal['admin_comments']): ?>
                        <div class="alert alert-info">
                            <p class="detail-label mb-1"><i class="bi bi-chat-quote-fill me-2"></i>Comentarios del Revisor</p>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($proposal['admin_comments'])); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Documento principal -->
                        <?php if ($proposal['file_size'] > 0): ?>
                        <div class="border rounded p-3 bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-file-pdf-fill text-danger fs-4 me-2"></i>
                                    <span class="fw-semibold"><?php echo htmlspecialchars($proposal['file_name']); ?></span>
                                    <br>
                                    <small class="text-muted">
                                        Original: <?php echo formatFileSize($proposal['file_size']); ?>
                                        <?php if ($proposal['is_compressed']): ?>
                                            | Comprimido: <?php echo formatFileSize($proposal['compressed_size']); ?>
                                            (<?php echo round((1 - ($proposal['compressed_size'] / $proposal['file_size'])) * 100); ?>% reducción)
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <a href="descargar_archivo.php?type=proposal&id=<?php echo $proposal['id']; ?>" 
                                   class="btn btn-success">
                                    <i class="bi bi-download me-1"></i> Descargar
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Proyecto Registrado -->
                <?php if ($project): ?>
                <div class="card section-card mb-4">
                    <div class="card-header bg-light">
                        <i class="bi bi-kanban-fill me-2"></i>Proyecto Registrado
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <p class="detail-label mb-1">Tipo de Proyecto</p>
                                <p><?php echo htmlspecialchars($project['project_type_name'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="col-md-4">
                                <p class="detail-label mb-1">Supervisor</p>
                                <p><?php echo htmlspecialchars($project['supervisor_name'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="col-md-4">
                                <p class="detail-label mb-1">Calificación Final</p>
                                <p><?php echo $project['final_grade'] ? number_format($project['final_grade'], 2) : 'N/A'; ?></p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <p class="detail-label mb-1">Fecha Inicio</p>
                                <p><?php echo $project['start_date'] ? date('d/m/Y', strtotime($project['start_date'])) : 'N/A'; ?></p>
                            </div>
                            <div class="col-md-4">
                                <p class="detail-label mb-1">Fecha Fin</p>
                                <p><?php echo $project['end_date'] ? date('d/m/Y', strtotime($project['end_date'])) : 'N/A'; ?></p>
                            </div>
                            <div class="col-md-4">
                                <p class="detail-label mb-1">Estado Original</p>
                                <p><span class="badge bg-secondary"><?php echo htmlspecialchars($project['original_status']); ?></span></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Miembros del Proyecto -->
                <?php if (!empty($members)): ?>
                <div class="card section-card mb-4">
                    <div class="card-header bg-light">
                        <i class="bi bi-people-fill me-2"></i>Miembros del Proyecto
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nombre</th>
                                        <th>ID Usuario</th>
                                        <th>Rol</th>
                                        <th>Estado</th>
                                        <th>Fecha Unión</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($members as $member): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($member['user_name']); ?></td>
                                        <td><?php echo htmlspecialchars($member['user_id']); ?></td>
                                        <td><span class="badge bg-primary"><?php echo htmlspecialchars($member['role']); ?></span></td>
                                        <td><?php echo htmlspecialchars($member['member_status']); ?></td>
                                        <td><?php echo $member['joined_at'] ? date('d/m/Y', strtotime($member['joined_at'])) : 'N/A'; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Archivos Adicionales -->
                <?php if (!empty($additional_files)): ?>
                <div class="card section-card mb-4">
                    <div class="card-header bg-light">
                        <i class="bi bi-folder-fill me-2"></i>Documentos Adicionales
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <?php foreach ($additional_files as $file): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-file-earmark me-2"></i>
                                    <strong><?php echo htmlspecialchars($file['file_name']); ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($file['document_type'] ?? 'Documento'); ?> |
                                        <?php echo formatFileSize($file['original_size']); ?> |
                                        <?php echo date('d/m/Y', strtotime($file['original_created_at'])); ?>
                                    </small>
                                </div>
                                <a href="descargar_archivo.php?type=file&id=<?php echo $file['id']; ?>" 
                                   class="btn btn-sm btn-outline-success">
                                    <i class="bi bi-download"></i>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Botón de regreso -->
                <div class="mt-4">
                    <a href="panel_archivo_historico.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Volver al Archivo Histórico
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <?php include('footer.php'); ?>
</body>
</html>

<?php $conn->close(); ?>
