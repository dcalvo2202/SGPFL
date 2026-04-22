<?php
/**
 * HU-012 / HU-041: Panel de revisión de solicitudes de comité asesor
 * 
 * Permite a Gestor Académico y CTFG revisar, aprobar o rechazar
 * solicitudes de integración de comité (asesor externo/interno/tutor).
 * 
 * Usa header.php e includes.php como el resto del sistema.
 * Usa modales HTML personalizados (igual que panel_ctfg_review_final_documents.php)
 */

// 1. VERIFICAR AUTENTICACIÓN Y PERMISOS
include("mod/login/check.php");

// 2. INCLUIR ARCHIVOS NECESARIOS
include('includes.php');
include('lang/lang.es');
include('inc/db/db.php');

// 3. OBTENER VARIABLES DE SESIÓN
$current_user_id = $mySessionController->getVar("usuario");
$current_user_name = $mySessionController->getVar("nombre");
$current_user_rol = $mySessionController->getVar("rol");
$base_url = $mySessionController->getVar("cds_domain") . $mySessionController->getVar("cds_locate");

// 4. CONTROL DE ACCESO POR ROL (Gestor 2, CTFG 3 o Administrador 1)
if ($current_user_rol != 2 && $current_user_rol != 3 && $current_user_rol != 1) {
    header('Location: dashboard.php');
    exit;
}

// 5. Obtener solicitudes de la BD
$solicitudes = [];
$filtro_status = isset($_GET['status']) ? $_GET['status'] : 'Pendiente';
$allowed_filters = ['Pendiente', 'Aprobado', 'Rechazado', 'Todos'];
if (!in_array($filtro_status, $allowed_filters)) {
    $filtro_status = 'Pendiente';
}

try {
    // $id_con viene de inc/db/db.php
    $sql = "SELECT ear.id, ear.applicant_id, ear.full_name, ear.email, ear.telefono, ear.institution, ear.specialization,
                   ear.postulation_type, ear.committee_subrole, ear.committee_role,
                   ear.cv_file_name, ear.cv_file_size, ear.id_copy_file_name, ear.id_copy_file_size,
                   ear.cover_letter_file_name, ear.cover_letter_file_size,
                   ear.status, ear.rejection_count, ear.approval_expires_at, ear.admin_comments, 
                   ear.reviewed_by, ear.reviewed_at, ear.created_at, ear.updated_at,
                   ear.linked_student_id, u.nombre AS linked_student_name
            FROM external_advisor_profile_requests ear
            LEFT JOIN sis_user u ON ear.linked_student_id = u.id";
    
    if ($filtro_status === 'Pendiente') {
        // Buscar "En Revision" o "En Revisión" (con o sin tilde)
        $sql .= " WHERE (ear.status = 'En Revision' OR ear.status = 'En Revisión')";
    } elseif ($filtro_status !== 'Todos') {
        $sql .= " WHERE ear.status = '" . mysqli_real_escape_string($id_con, $filtro_status) . "'";
    }
    $sql .= " ORDER BY ear.created_at DESC";

    $result = mysqli_query($id_con, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $solicitudes[] = $row;
        }
    }
} catch (Exception $e) {
    error_log('Error cargando solicitudes: ' . $e->getMessage());
}

function formatBytes($bytes) {
    if ($bytes <= 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}
?>
<!DOCTYPE html>
<html lang="es">
<!-- =============================== HEAD =============================== -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revisar Solicitudes Comité Asesor - SGPFL UNA</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/webp" href="<?= htmlspecialchars($base_url) ?>img/logo.webp">

    <!-- Bootstrap CSS y Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Estilos personalizados -->
    <link href="<?= htmlspecialchars($base_url . 'inc/css/estilo.css') ?>" rel="stylesheet">
    <link href="<?= htmlspecialchars($base_url . 'inc/css/panel_estudiante.css') ?>" rel="stylesheet">
    
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 1.3rem; }
        
        .dashboard-header h1 {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: #034991;
        }
        
        .dashboard-header .lead {
            font-size: 1.5rem;
        }
        
        .status-badge { 
            padding: 0.35em 0.65em; 
            font-size: 1.2rem; 
            border-radius: 0.25rem; 
            font-weight: 600;
        }
        .status-pending { background-color: #ffc107; color: #212529; }
        .status-approved { background-color: #198754; color: #fff; }
        .status-rejected { background-color: #dc3545; color: #fff; }
        
        .action-btn { margin: 0 2px; }
        .filter-bar { margin-bottom: 1.5rem; }
        .card-solicitud { border-left: 4px solid #034991; margin-bottom: 1rem; }
        .info-label { font-weight: 600; color: #555; font-size: 1.3rem; }
        .doc-link { text-decoration: none; }
        .doc-link:hover { text-decoration: underline; }
        
        /* === MODALES PERSONALIZADOS (mismo estilo que panel_ctfg) === */
        .custom-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            display: none; /* hidden by default */
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
            color: #034991;
            font-weight: 700;
        }
        
        .custom-modal-content textarea {
            width: 100%;
            min-height: 100px;
            margin: 15px 0;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-family: inherit;
        }
        
        .custom-modal-content .btn-group-modal {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .modal-error {
            color: #dc3545;
            font-size: 1.2rem;
            display: none;
            margin-bottom: 10px;
        }
        
        .modal-icon {
            font-size: 3.5rem;
            margin-bottom: 15px;
        }
        .modal-icon.success { color: #198754; }
        .modal-icon.error { color: #dc3545; }
        .modal-icon.warning { color: #ffc107; }
        .modal-icon.question { color: #034991; }
    </style>
</head>
<body class="fondo-una d-flex flex-column min-vh-100">

    <!-- =============================== HEADER =============================== -->
    <?php include('header.php'); ?>

    <!-- =============================== CONTENIDO PRINCIPAL =============================== -->
    <main class="flex-fill">
        <div class="container my-5">
            
            <div class="dashboard-header text-center mb-4">
                <h1>
                    <i class="bi bi-person-badge-fill"></i> Revisión de Solicitudes - Comité Asesor
                </h1>
                <p class="lead">Validar y aprobar solicitudes de tutor, asesor interno y asesor externo</p>
            </div>

            <!-- Filtros -->
            <div class="filter-bar">
                <div class="btn-group" role="group">
                    <a href="?status=Pendiente" class="btn <?= $filtro_status === 'Pendiente' ? 'btn-primary' : 'btn-outline-primary' ?>">
                        <i class="bi bi-hourglass-split"></i> Pendientes
                    </a>
                    <a href="?status=Aprobado" class="btn <?= $filtro_status === 'Aprobado' ? 'btn-success' : 'btn-outline-success' ?>">
                        <i class="bi bi-check-circle"></i> Aprobados
                    </a>
                    <a href="?status=Rechazado" class="btn <?= $filtro_status === 'Rechazado' ? 'btn-danger' : 'btn-outline-danger' ?>">
                        <i class="bi bi-x-circle"></i> Rechazados
                    </a>
                    <a href="?status=Todos" class="btn <?= $filtro_status === 'Todos' ? 'btn-secondary' : 'btn-outline-secondary' ?>">
                        <i class="bi bi-list"></i> Todos
                    </a>
                </div>

                <a href="dashboard.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left-circle"></i> Volver al panel principal
                </a>

            </div>

            <!-- Listado de Solicitudes -->
            <?php if (empty($solicitudes)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> No hay solicitudes con estado "<?= htmlspecialchars($filtro_status) ?>".
                </div>
            <?php else: ?>
                <?php foreach ($solicitudes as $sol): ?>
                    <div class="card card-solicitud">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?= htmlspecialchars($sol['full_name']) ?></strong>
                                <span class="text-muted ms-2">(<?= htmlspecialchars($sol['applicant_id']) ?>)</span>
                            </div>
                            <span class="status-badge <?= 
                                ($sol['status'] === 'En Revision' || $sol['status'] === 'En Revisión') ? 'status-pending' : 
                                ($sol['status'] === 'Aprobado' ? 'status-approved' : 'status-rejected') 
                            ?>">
                                <?= ($sol['status'] === 'En Revision' || $sol['status'] === 'En Revisión') ? 'Pendiente' : htmlspecialchars($sol['status']) ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><span class="info-label">Email:</span> <?= htmlspecialchars($sol['email']) ?></p>
                                    <p><span class="info-label">Teléfono:</span> <?= htmlspecialchars($sol['telefono'] ?: 'No especificado') ?></p>
                                    <p><span class="info-label">Institución:</span> <?= htmlspecialchars($sol['institution']) ?></p>
                                    <p><span class="info-label">Especialización:</span> <?= htmlspecialchars($sol['specialization']) ?></p>
                                    <p><span class="info-label">Tipo de postulación:</span> <?= htmlspecialchars($sol['postulation_type'] ?: 'Asesor Externo') ?></p>
                                    <p><span class="info-label">Rol solicitado:</span> <?= htmlspecialchars($sol['committee_role'] ?: '-') ?></p>
                                    <p>
                                        <span class="info-label"><i class="bi bi-mortarboard-fill text-primary"></i> Estudiante a asesorar:</span>
                                        <?php if (!empty($sol['linked_student_name'])): ?>
                                            <span class="badge bg-info"><?= htmlspecialchars($sol['linked_student_name']) ?></span>
                                            <small class="text-muted">(ID: <?= htmlspecialchars($sol['linked_student_id']) ?>)</small>
                                        <?php else: ?>
                                            <span class="text-muted">No especificado</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p>
                                        <span class="info-label">Currículum:</span>
                                        <a href="descargar_documento_asesor.php?id=<?= $sol['id'] ?>&type=cv" class="doc-link" target="_blank">
                                            <i class="bi bi-file-earmark-pdf text-danger"></i> <?= htmlspecialchars($sol['cv_file_name']) ?>
                                            (<?= formatBytes($sol['cv_file_size']) ?>)
                                        </a>
                                    </p>
                                    <p>
                                        <span class="info-label">Cédula:</span>
                                        <a href="descargar_documento_asesor.php?id=<?= $sol['id'] ?>&type=id_copy" class="doc-link" target="_blank">
                                            <i class="bi bi-file-earmark-image text-primary"></i> <?= htmlspecialchars($sol['id_copy_file_name']) ?>
                                            (<?= formatBytes($sol['id_copy_file_size']) ?>)
                                        </a>
                                    </p>
                                    <p>
                                        <span class="info-label">Carta:</span>
                                        <a href="descargar_documento_asesor.php?id=<?= $sol['id'] ?>&type=cover_letter" class="doc-link" target="_blank">
                                            <i class="bi bi-file-earmark-pdf text-danger"></i> <?= htmlspecialchars($sol['cover_letter_file_name']) ?>
                                            (<?= formatBytes($sol['cover_letter_file_size']) ?>)
                                        </a>
                                    </p>
                                    <p><span class="info-label">Fecha solicitud:</span> <?= date('d/m/Y H:i', strtotime($sol['created_at'])) ?></p>
                                    <?php if ($sol['reviewed_at']): ?>
                                        <p><span class="info-label">Revisado:</span> <?= date('d/m/Y H:i', strtotime($sol['reviewed_at'])) ?></p>
                                    <?php endif; ?>
                                    <?php if ($sol['status'] === 'Aprobado' && $sol['approval_expires_at']): ?>
                                        <p><span class="info-label">Vigencia hasta:</span> 
                                            <span class="badge bg-info"><?= date('d/m/Y', strtotime($sol['approval_expires_at'])) ?></span>
                                        </p>
                                    <?php endif; ?>
                                    <?php if ($sol['rejection_count'] > 0): ?>
                                        <p><span class="info-label">Rechazos previos:</span> 
                                            <span class="badge bg-warning text-dark"><?= $sol['rejection_count'] ?></span>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($sol['admin_comments']): ?>
                                <div class="alert alert-secondary mt-2">
                                    <strong>Comentarios:</strong> <?= nl2br(htmlspecialchars($sol['admin_comments'])) ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($sol['status'] === 'En Revision' || $sol['status'] === 'En Revisión'): ?>
                                <div class="mt-3">
                                    <button type="button" class="btn btn-success action-btn btn-aprobar"
                                            data-id="<?= $sol['id'] ?>" 
                                            data-nombre="<?= htmlspecialchars($sol['full_name'], ENT_QUOTES) ?>">
                                        <i class="bi bi-check-lg"></i> Aprobar
                                    </button>
                                    <button type="button" class="btn btn-danger action-btn btn-rechazar"
                                            data-id="<?= $sol['id'] ?>" 
                                            data-nombre="<?= htmlspecialchars($sol['full_name'], ENT_QUOTES) ?>">
                                        <i class="bi bi-x-lg"></i> Rechazar
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </main>

    <!-- =============================== FOOTER =============================== -->
    <?php include('footer.php'); ?>

    <!-- ============================================================== -->
    <!-- MODAL DE CONFIRMACIÓN DE APROBACIÓN -->
    <!-- ============================================================== -->
    <div id="modalAprobar" class="custom-modal-overlay">
        <div class="custom-modal-content text-center">
            <i class="bi bi-check-circle-fill modal-icon question"></i>
            <h4>Aprobar Solicitud de Comité</h4>
            <p id="modalAprobarTexto">¿Está seguro de aprobar esta solicitud?</p>
            <p class="text-muted small">La solicitud quedará disponible para crear o editar comités asesores.</p>
            <div class="btn-group-modal">
                <button type="button" class="btn btn-secondary" id="btnCancelarAprobar">Cancelar</button>
                <button type="button" class="btn btn-success" id="btnConfirmarAprobar">
                    <i class="bi bi-check-lg"></i> Sí, Aprobar
                </button>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL DE RECHAZO CON MOTIVO -->
    <!-- ============================================================== -->
    <div id="modalRechazar" class="custom-modal-overlay">
        <div class="custom-modal-content">
            <h4><i class="bi bi-x-circle text-danger"></i> Rechazar Solicitud</h4>
            <p id="modalRechazarTexto">¿Por qué rechaza esta solicitud?</p>
            <p class="text-muted small">El asesor podrá corregir y reenviar (máximo 2 intentos).</p>
            <textarea id="motivoRechazo" placeholder="Ej: CV incompleto, cédula ilegible, falta experiencia..." maxlength="500"></textarea>
            <div id="errorRechazo" class="modal-error">Debe especificar un motivo detallado (mínimo 10 caracteres).</div>
            <div class="btn-group-modal">
                <button type="button" class="btn btn-secondary" id="btnCancelarRechazar">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmarRechazar">
                    <i class="bi bi-x-lg"></i> Rechazar
                </button>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL DE ALERTA (ÉXITO/ERROR) -->
    <!-- ============================================================== -->
    <div id="modalAlerta" class="custom-modal-overlay">
        <div class="custom-modal-content text-center">
            <i id="alertaIcono" class="bi modal-icon"></i>
            <h4 id="alertaTitulo">Título</h4>
            <p id="alertaMensaje">Mensaje</p>
            <div class="btn-group-modal justify-content-center">
                <button type="button" class="btn btn-primary" id="btnAlertaOk">Aceptar</button>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL DE CARGANDO -->
    <!-- ============================================================== -->
    <div id="modalCargando" class="custom-modal-overlay">
        <div class="custom-modal-content text-center">
            <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <h4>Procesando...</h4>
            <p>Por favor espere.</p>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Lógica de botones usando modales HTML personalizados -->
    <script>
    (function() {
        'use strict';
        
        // Variables globales para el modal
        var currentRequest = { id: null, nombre: '' };
        var isSuccess = false;
        
        // Referencias a elementos DOM
        var modalAprobar = document.getElementById('modalAprobar');
        var modalAprobarTexto = document.getElementById('modalAprobarTexto');
        var btnCancelarAprobar = document.getElementById('btnCancelarAprobar');
        var btnConfirmarAprobar = document.getElementById('btnConfirmarAprobar');
        
        var modalRechazar = document.getElementById('modalRechazar');
        var modalRechazarTexto = document.getElementById('modalRechazarTexto');
        var motivoRechazo = document.getElementById('motivoRechazo');
        var errorRechazo = document.getElementById('errorRechazo');
        var btnCancelarRechazar = document.getElementById('btnCancelarRechazar');
        var btnConfirmarRechazar = document.getElementById('btnConfirmarRechazar');
        
        var modalAlerta = document.getElementById('modalAlerta');
        var alertaIcono = document.getElementById('alertaIcono');
        var alertaTitulo = document.getElementById('alertaTitulo');
        var alertaMensaje = document.getElementById('alertaMensaje');
        var btnAlertaOk = document.getElementById('btnAlertaOk');
        
        var modalCargando = document.getElementById('modalCargando');
        
        // Funciones para mostrar/ocultar modales
        function showModal(modal) {
            modal.style.display = 'flex';
        }
        
        function hideModal(modal) {
            modal.style.display = 'none';
        }
        
        function showLoading() {
            showModal(modalCargando);
        }
        
        function hideLoading() {
            hideModal(modalCargando);
        }
        
        function showAlert(titulo, mensaje, esExito) {
            isSuccess = esExito;
            alertaTitulo.textContent = titulo;
            alertaMensaje.textContent = mensaje;
            
            // Cambiar icono según éxito/error
            alertaIcono.className = 'bi modal-icon';
            if (esExito) {
                alertaIcono.classList.add('bi-check-circle-fill', 'success');
            } else {
                alertaIcono.classList.add('bi-x-circle-fill', 'error');
            }
            
            showModal(modalAlerta);
        }
        
        // === BOTONES APROBAR ===
        var botonesAprobar = document.querySelectorAll('.btn-aprobar');
        botonesAprobar.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                currentRequest.id = this.getAttribute('data-id');
                currentRequest.nombre = this.getAttribute('data-nombre');
                
                modalAprobarTexto.innerHTML = '¿Está seguro de aprobar la solicitud de <strong>' + currentRequest.nombre + '</strong>?';
                showModal(modalAprobar);
            });
        });
        
        btnCancelarAprobar.addEventListener('click', function() {
            hideModal(modalAprobar);
        });
        
        btnConfirmarAprobar.addEventListener('click', function() {
            hideModal(modalAprobar);
            enviarDecision(currentRequest.id, 'Aprobado', '');
        });
        
        // === BOTONES RECHAZAR ===
        var botonesRechazar = document.querySelectorAll('.btn-rechazar');
        botonesRechazar.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                currentRequest.id = this.getAttribute('data-id');
                currentRequest.nombre = this.getAttribute('data-nombre');
                modalRechazarTexto.innerHTML = '¿Por qué rechaza la solicitud de <strong>' + currentRequest.nombre + '</strong>?';
                motivoRechazo.value = '';
                errorRechazo.style.display = 'none';
                showModal(modalRechazar);
                motivoRechazo.focus();
            });
        });
        
        btnCancelarRechazar.addEventListener('click', function() {
            hideModal(modalRechazar);
        });
        
        btnConfirmarRechazar.addEventListener('click', function() {
            var motivo = motivoRechazo.value.trim();
            if (motivo.length < 10) {
                errorRechazo.style.display = 'block';
                motivoRechazo.focus();
                return;
            }
            
            hideModal(modalRechazar);
            enviarDecision(currentRequest.id, 'Rechazado', motivo);
        });
        
        // === MODAL ALERTA OK ===
        btnAlertaOk.addEventListener('click', function() {
            hideModal(modalAlerta);
            if (isSuccess) {
                window.location.reload();
            }
        });
        
        // === CERRAR MODALES AL HACER CLIC FUERA ===
        window.addEventListener('click', function(event) {
            if (event.target === modalAprobar) hideModal(modalAprobar);
            if (event.target === modalRechazar) hideModal(modalRechazar);
            if (event.target === modalAlerta) {
                hideModal(modalAlerta);
                if (isSuccess) window.location.reload();
            }
        });
        
        // === FUNCIÓN PARA ENVIAR DECISIÓN AL SERVIDOR ===
        function enviarDecision(id, decision, comentarios) {
            showLoading();
            
            var formData = new FormData();
            formData.append('id', id);
            formData.append('decision', decision);
            formData.append('comentarios', comentarios);
            
            fetch('procesar_decision_asesor.php', {
                method: 'POST',
                body: formData
            })
            .then(function(response) {
                if (!response.ok) {
                    return response.json().then(function(data) {
                        throw new Error(data.message || 'Error en el servidor');
                    });
                }
                return response.json();
            })
            .then(function(data) {
                hideLoading();
                if (data.success) {
                    var titulo = decision === 'Aprobado' ? '¡Aprobado!' : 'Solicitud Rechazada';
                    showAlert(titulo, data.message, true);
                } else {
                    showAlert('Error', data.message || 'Error desconocido', false);
                }
            })
            .catch(function(error) {
                hideLoading();
                console.error('Error:', error);
                showAlert('Error de Conexión', error.message || 'No se pudo conectar con el servidor.', false);
            });
        }
        
    })();
    </script>
</body>
</html>
