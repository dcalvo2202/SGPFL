<?php
/**
 * HU-029: Sistema de Chat Interno
 * Comunicación entre Estudiante, Comisión y Comité Asesor
 * Interfaz tipo Microsoft Teams
 */

// Verificar autenticación
include("mod/login/check.php");

// Incluir archivos necesarios
// Usar el mismo header compartido que el resto de vistas.
// Solo desactivar Prototype para evitar conflicto con jQuery en chat.
$disable_prototype_js = true;
include('lang/lang.es');
require_once 'inc/chat_functions.php';
require_once 'inc/constants.php';

// Obtener variables de sesión
$current_user_id = $mySessionController->getVar("usuario");
$current_user_name = $mySessionController->getVar("nombre");
$current_user_rol = $mySessionController->getVar("rol");
$base_url = $mySessionController->getVar("cds_domain") . $mySessionController->getVar("cds_locate");

// Verificar que el usuario esté autenticado
if (!$current_user_id) {
    header('Location: login.php');
    exit;
}

// CSS adicional para head.php
$additional_css = ['inc/css/chat.css'];
$page_title = 'Chat - SGPFL';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include 'head.php'; ?>
</head>
<body class="d-flex flex-column min-vh-100 chat-page-body" style="background: #f0f2f5;">

    <?php include 'header.php'; ?>

    <!-- =============================== CONTENEDOR PRINCIPAL =============================== -->
    <div class="container-fluid flex-grow-1 py-3 px-md-4 chat-page-shell">
        <div class="chat-container">
            
            <!-- ==================== SIDEBAR ==================== -->
            <div class="chat-sidebar">
                <!-- Encabezado del sidebar -->
                <div class="chat-sidebar-header">
                    <h5><i class="bi bi-chat-dots-fill me-2"></i>Mensajes</h5>
                </div>
                
                <!-- Barra de búsqueda -->
                <div class="chat-search-container">
                    <div class="chat-search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" id="chatSearchInput" placeholder="Buscar por nombre, email o ID..." autocomplete="off">
                    </div>
                    <div id="chatSearchResults" class="chat-search-results"></div>
                </div>
                
                <!-- Lista de conversaciones -->
                <div class="chat-conversations-list" id="chatConversationsList">
                    <div class="chat-empty-state" style="padding: 2rem 1rem;">
                        <div class="spinner-border text-secondary" role="status" style="width: 2rem; height: 2rem;">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p style="font-size: 0.85rem; color: #999; margin-top: 1rem;">Cargando conversaciones...</p>
                    </div>
                </div>
            </div>

            <!-- ==================== ÁREA PRINCIPAL DEL CHAT ==================== -->
            <div class="chat-main">
                <!-- Encabezado del chat -->
                <div class="chat-header">
                    <button class="btn btn-sm btn-light d-md-none me-2" id="chatBackBtn" title="Volver a conversaciones">
                        <i class="bi bi-arrow-left"></i>
                    </button>
                    <div class="chat-header-info" id="chatHeaderInfo">
                        <!-- Se llena dinámicamente -->
                    </div>
                    <div class="ms-auto d-flex align-items-center">
                        <button class="btn btn-sm btn-outline-secondary" id="chatGroupMembersBtn" style="display:none;" title="Ver miembros del grupo">
                            <i class="bi bi-people me-1"></i> Miembros
                        </button>
                    </div>
                </div>
                
                <!-- Área de mensajes -->
                <div class="chat-messages-area" id="chatMessagesArea">
                    <div class="chat-empty-state">
                        <i class="bi bi-chat-left-text"></i>
                        <h4>Bienvenido al Chat</h4>
                        <p>Busca a un usuario por su nombre para iniciar una conversación, o selecciona un chat existente.</p>
                    </div>
                </div>
                
                <!-- Área de entrada de mensaje -->
                <div class="chat-input-area" id="chatInputArea" style="display: none;">
                    <textarea id="chatMessageInput" 
                              placeholder="Escribe un mensaje..." 
                              rows="1" 
                              maxlength="5000"
                              disabled></textarea>
                    <button class="chat-send-btn" id="chatSendBtn" disabled title="Enviar mensaje">
                        <i class="bi bi-send-fill"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- =============================== MODAL DE MIEMBROS =============================== -->
    <div class="modal fade" id="chatMembersModal" tabindex="-1" aria-labelledby="chatMembersModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header chat-members-modal-header">
                    <h5 class="modal-title" id="chatMembersModalLabel">
                        <i class="bi bi-people-fill me-2"></i>Miembros del Grupo
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="chatMembersModalBody">
                    <p class="text-muted text-center py-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Los miembros del grupo se actualizan automáticamente según los integrantes del proyecto.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <!-- Chat JavaScript -->
    <script>
        // Pasar la URL base al JavaScript del chat
        window.CHAT_BASE_URL = '<?= rtrim($base_url, "/") . "/" ?>';
    </script>
    <script src="<?= htmlspecialchars($base_url . 'inc/js/chat.js') ?>"></script>

</body>
</html>
