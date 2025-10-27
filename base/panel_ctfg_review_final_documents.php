<?php
// VERIFICAR AUTENTICACIÓN Y PERMISOS
include("mod/login/check.php");

// 1. INCLUIR ARCHIVOS NECESARIOS
include('includes.php');
include('lang/lang.es');
include('inc/db/db.php');

// 2. OBTENER VARIABLES DE SESIÓN
$current_user_id = $mySessionController->getVar("usuario");
$current_user_name = $mySessionController->getVar("nombre");
$current_user_rol = $mySessionController->getVar("rol");
$base_url = $mySessionController->getVar("cds_domain") . $mySessionController->getVar("cds_locate");

// 3. CONTROL DE ACCESO POR ROL (Solo CTFG - rol 3)
if ($current_user_rol != 3) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<!-- =============================== HEAD =============================== -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revisión Documentos Finales - CTFG</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/webp" href="<?= htmlspecialchars($favicon_url) ?>">

    <!-- Bootstrap CSS y Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Estilos personalizados -->
    <link href="<?= htmlspecialchars($base_url . 'inc/css/estilo.css') ?>" rel="stylesheet">
    <link href="<?= htmlspecialchars($base_url . 'inc/css/panel_estudiante.css') ?>" rel="stylesheet">
    
    <style>
        /* Tipografía consistente con todo el sistema */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .dashboard-header h1 {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: #034991;
            margin-bottom: 0.5rem;
        }
        
        .dashboard-header .lead {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #6c757d;
        }
        
        .document-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
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
        
        .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 600;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .status-pendiente {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffc107;
        }
        
        .btn-download {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-weight: 600;
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
        
        .empty-state h3 {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #034991;
            font-weight: 700;
        }
        
        .empty-state p {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #6c757d;
        }

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
            z-index: 1060;
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
    </style>
</head>
<body class="fondo-una d-flex flex-column min-vh-100">

    <!-- =============================== HEADER =============================== -->
    <header class="navbar-una" style="background: linear-gradient(135deg, #CD1719, #A01215) !important;">
        <div class="container-fluid px-4">
            <div class="header-left d-flex align-items-center">
                <img src="<?= htmlspecialchars($base_url) ?>img/logo.webp" alt="Logo UNA" class="logo-una">
                <div class="header-text ms-3">
                    <h5 class="mb-0 text-white fw-bold">Universidad Nacional de Costa Rica</h5>
                    <small class="text-light opacity-85">Escuela de Informática</small>
                </div>
            </div>
            <div class="header-right text-end">
                <div class="user-info text-white mb-2">
                    <i class="bi bi-person-circle fs-5"></i>
                    <span class="ms-2 fw-semibold"><?= htmlspecialchars($current_user_name) ?></span>
                </div>
                <div class="user-details">
                    <small class="text-light opacity-75">ID: <?= htmlspecialchars($current_user_id) ?></small>
                    <a href="<?= htmlspecialchars($base_url) ?>dashboard.php" class="btn btn-outline-light btn-sm ms-2" style="font-size: 1.05rem; padding: 0.55rem 1.1rem;">
                        <i class="bi bi-house-fill"></i> Inicio
                    </a>
                    <a href="<?= htmlspecialchars($base_url) ?>mod/login/logout.php" 
                       class="btn btn-outline-light btn-sm ms-2" 
                       style="font-size: 1.05rem; padding: 0.55rem 1.1rem;"
                       onclick="return confirmarCierreSesion(event, '<?= htmlspecialchars($base_url) ?>')">
                        <i class="bi bi-box-arrow-right"></i> Salir
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- =============================== CONTENIDO PRINCIPAL =============================== -->
    <main class="flex-fill">
        <div class="container my-5">
            
            <div class="dashboard-header text-center mb-5">
                <h1 style="font-size: 2.5rem; font-weight: 700;">Revisión de Documentos Finales de TFG</h1>
                <p class="lead">Documentos finales pendientes de revisión y aprobación.</p>
            </div>

            <div class="card document-table">
                <div class="card-body">
                    <?php
                    // Obtener documentos finales pendientes de revisión
                    try {
                        $conn = new mysqli($db_host, $usuario, $clave, $db);
                        if ($conn->connect_error) {
                            throw new Exception("Error de conexión: " . $conn->connect_error);
                        }
                        
                        $conn->set_charset("utf8");
                        
                        $sql = "SELECT 
                                    fd.id,
                                    fd.proposal_id,
                                    fd.status,
                                    fd.submitted_at,
                                    fd.project_status,
                                    tp.title as proposal_title,
                                    u.nombre as student_name,
                                    u.id as student_id,
                                    f.file_name,
                                    f.file_size
                                FROM tfg_final_documents fd
                                INNER JOIN tfg_proposals tp ON fd.proposal_id = tp.id
                                INNER JOIN sis_user u ON fd.submitted_by = u.id
                                INNER JOIN tfg_files f ON fd.file_id = f.id
                                WHERE fd.status = 'Pendiente de Revision'
                                ORDER BY fd.submitted_at DESC";
                        
                        $result = $conn->query($sql);
                        
                        if ($result && $result->num_rows > 0):
                    ?>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Estudiante</th>
                                    <th>Título del Documento</th>
                                    <th>Fecha de Subida</th>
                                    <th>Estado del Proyecto</th>
                                    <th>Estado</th>
                                    <th class="text-center" style="width: 420px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($row['student_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($row['student_id']) ?></small>
                                    </td>
                                    <td>
                                        <div><?= htmlspecialchars($row['proposal_title']) ?></div>
                                        <small class="text-muted">
                                            <i class="bi bi-file-pdf"></i> <?= htmlspecialchars($row['file_name']) ?>
                                            (<?= number_format($row['file_size'] / (1024 * 1024), 2) ?> MB)
                                        </small>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($row['submitted_at'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $row['project_status'] === 'Vigente' ? 'success' : 'warning' ?>">
                                            <?= htmlspecialchars($row['project_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-pendiente">
                                            <?= htmlspecialchars($row['status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                        <?php $download_url = $base_url . 'mod/admin/users/tfg_final_download.php?id=' . $row['id']; ?>
                                        <a href="<?= htmlspecialchars($download_url) ?>" 
                                           class="btn btn-sm btn-primary text-white"
                                           target="_blank"
                                           title="Descargar y revisar documento PDF">
                                           <style>
                                               .btn-primary {
                                                   background-color: #007bff;
                                                   border-color: #007bff;
                                               }
                                               .btn-primary:hover {
                                                   background-color: #0056b3;
                                                   border-color: #0056b3;
                                               }
                                            </style>
                                            <i class="bi bi-download me-1"></i><span> Descargar y revisar documento PDF</span>
                                        </a>
                                        <button onclick="openReviewModal(<?= $row['id'] ?>, 'Aprobado para Defensa')" class="btn btn-sm btn-success" title="Aprobar para Defensa">
                                            <style>
                                                .btn-success {
                                                    background-color: #28a745;
                                                    border-color: #28a745;
                                                }
                                                .btn-success:hover {
                                                    background-color: #1e7e34;
                                                    border-color: #1e7e34;
                                                }
                                            </style>
                                            <i class="bi bi-check-circle"></i><span> Aprobar para Defensa</span>
                                        </button>
                                        <button onclick="openReviewModal(<?= $row['id'] ?>, 'Correcciones Requeridas')" class="btn btn-sm btn-danger" title="Correcciones Requeridas">
                                            <style>
                                                .btn-danger {
                                                    background-color: #dc3545;
                                                    border-color: #dc3545;
                                                }
                                                .btn-danger:hover {
                                                    background-color: #bd2130;
                                                    border-color: #bd2130;
                                                }
                                            </style>
                                            <i class="bi bi-x-circle"></i><span> Correcciones Requeridas</span>
                                        </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php 
                        else: 
                    ?>
                    
                    <div class="empty-state">
                        <i class="bi bi-check2-circle"></i>
                        <h3>¡Todo al día!</h3>
                        <p class="mb-0">No hay documentos finales pendientes de revisión en este momento.</p>
                    </div>
                    
                    <?php 
                        endif;
                        $conn->close();
                    } catch (Exception $e) {
                        echo '<div class="alert alert-danger m-4">';
                        echo '<i class="bi bi-exclamation-triangle"></i> ';
                        echo '<strong>Error:</strong> ' . htmlspecialchars($e->getMessage());
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <a href="panel_ctfg.php" class="btn btn-lg btn-secondary px-5">
                    <i class="bi bi-arrow-left-circle me-2"></i> Volver al Panel Principal
                </a>
            </div>
        </div>
    </main>

    <!-- =============================== FOOTER =============================== -->
    <footer class="footer-una mt-auto">
        <div class="container">
            <p class="mb-1">&copy; <?= date('Y') ?> Universidad Nacional de Costa Rica</p>
            <small>Escuela de Informática - Proyecto SGPFL v3.0</small>
        </div>
    </footer>

    <!-- =============================== MODALS =============================== -->
    <!-- Modal para Comentarios -->
    <div id="commentModal" class="custom-modal-overlay" style="display:none;">
        <div class="custom-modal-content">
            <h4 id="modalTitle">Revisión de Documento Final</h4>
            <p id="modalText"></p>
            <textarea id="modalComments" class="form-control" placeholder="Escriba aquí sus observaciones..." rows="4"></textarea>
            <small id="modalError" class="text-danger" style="display:none;">Los comentarios son obligatorios para esta acción.</small>
            <div class="custom-modal-actions">
                <button id="modalCancel" class="btn btn-secondary">Cancelar</button>
                <button id="modalConfirm" class="btn btn-primary">Enviar Revisión</button>
            </div>
        </div>
    </div>

    <!-- Modal de Alerta -->
    <div id="alertModal" class="custom-modal-overlay" style="display:none;">
        <div class="custom-modal-content">
            <h4 id="alertTitle"></h4>
            <p id="alertMessage"></p>
            <div class="custom-modal-actions">
                <button id="alertOk" class="btn btn-primary">Aceptar</button>
            </div>
        </div>
    </div>

    <!-- =============================== SCRIPTS =============================== -->
    <script>
    // --- State and Elements ---
    let currentReview = { document_id: null, status: null };

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
    function openReviewModal(document_id, status) {
        currentReview = { document_id, status };
        const actionText = status === 'Aprobado para Defensa' ? 'Aprobar para Defensa' : 'Correcciones Requeridas';
        
        // modalText.textContent = `¿Desea ${actionText} este documento?`;
        modalComments.value = '';
        modalError.style.display = 'none';
        commentModal.style.display = 'flex';
        
        if (status === 'Aprobado para Defensa') {
            modalComments.placeholder = "Comentarios opcionales para la aprobación...";
        } else {
            modalComments.placeholder = "Escriba aquí las correcciones requeridas (obligatorio)...";
        }
        modalComments.focus();
    }

    // --- Event Listeners ---
    modalCancel.addEventListener('click', () => {
        commentModal.style.display = 'none';
    });

    modalConfirm.addEventListener('click', () => {
        const comments = modalComments.value.trim();

        if (currentReview.status === 'Correcciones Requeridas' && comments === '') {
            modalError.style.display = 'block';
            return;
        }
        
        modalError.style.display = 'none';
        commentModal.style.display = 'none';

        // --- Step 1: Process review in DB ---
        const dbBody = `document_id=${currentReview.document_id}&status=${encodeURIComponent(currentReview.status)}&comments=${encodeURIComponent(comments)}`;
        
        fetch('mod/admin/users/process_final_document_review.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: dbBody
        })
        .then(response => {
            if (!response.ok) {
                // Si la respuesta del servidor no es 2xx, intenta leer el JSON de error
                return response.json().then(errorData => {
                    throw new Error(errorData.message || 'Error en el servidor');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // --- Step 2: Send notification email ---
                const emailBody = `status=${encodeURIComponent(data.status)}&comments=${encodeURIComponent(data.comments)}&tipo=Documento Final TFG`;

                fetch('mod/admin/users/send_tfg_mail.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: emailBody
                })
                .then(emailResponse => emailResponse.json().catch(() => ({}))) // Evita error si la respuesta no es JSON
                .then(emailData => {
                    console.log('Respuesta del script de correo:', emailData);
                    showCustomAlert('Operación Exitosa', data.message, true);
                })
                .catch(emailError => {
                    console.error('Error al enviar el correo:', emailError);
                    // Muestra éxito aunque el correo falle, porque la operación principal (BD) fue exitosa
                    showCustomAlert('Operación Exitosa', `${data.message} (Pero hubo un problema al enviar la notificación por correo.)`, true);
                });
            } else {
                // El script de BD devolvió success: false
                throw new Error(data.message);
            }
        })
        .catch(error => {
            console.error('Error processing review:', error);
            showCustomAlert('Error en la Operación', 'Ocurrió un error inesperado. Revise la consola para más detalles.', false);
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

    <script src="<?= $base_url ?>inc/js/login.js" type="text/javascript"></script>

</body>
</html>
