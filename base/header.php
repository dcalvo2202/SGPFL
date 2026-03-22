<?php 
include("mod/login/check.php");
require_once('includes.php');
include('lang/lang.es');

// Normaliza $base_url si no viene definido por la página
if (!isset($base_url) || !$base_url) {
    $cds_domain = isset($mySessionController) ? ($mySessionController->getVar("cds_domain") ?? '') : '';
    $cds_locate = isset($mySessionController) ? ($mySessionController->getVar("cds_locate") ?? '/base/') : '/base/';
    $base_url = rtrim($cds_domain, '/') . '/' . trim($cds_locate, '/') . '/';
}

// Obtener variables de sesión
$current_user_id = $mySessionController->getVar("usuario");
$current_user_name = $mySessionController->getVar("nombre");
$current_user_rol = $mySessionController->getVar("rol");
$cds_domain = $mySessionController->getVar("cds_domain");
$cds_locate = $mySessionController->getVar("cds_locate");
$base_url = $cds_domain . $cds_locate;

// HU-037: Obtener conteo de notificaciones sin leer
// HU-029: Obtener conteo de mensajes sin leer
$unread_notifications = 0;
$unread_messages = 0;
try {
    require_once __DIR__ . '/inc/alert_functions.php';
    require_once __DIR__ . '/inc/chat_functions.php';
    require_once __DIR__ . '/inc/db/bdcommon.inc';
    $conn_header = new mysqli($db_host, $usuario, $clave, $db);
    if (!$conn_header->connect_error) {
        $conn_header->set_charset("utf8");
        $unread_notifications = getUnreadAlertCount($conn_header, $current_user_id);
        $unread_messages = getTotalUnreadMessages($conn_header, $current_user_id);
        $conn_header->close();
    }
} catch (Exception $e) {
    // Silenciar errores de notificaciones/chat
}

?>
 <!-- =============================== HEADER =============================== -->
    <style>
        .notification-bell {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border-radius: 8px;
            background: rgba(255,255,255,0.15);
            color: white;
            text-decoration: none;
            transition: all 0.2s ease;
            margin-right: 0.5rem;
        }
        .notification-bell:hover {
            background: rgba(255,255,255,0.25);
            color: white;
            transform: scale(1.05);
        }
        .notification-bell i {
            font-size: 1.3rem;
        }
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            min-width: 20px;
            height: 20px;
            padding: 0 5px;
            font-size: 0.7rem;
            font-weight: 700;
            color: #fff;
            background: #ffc107;
            border: 2px solid #CD1719;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse-badge 2s infinite;
        }
        @keyframes pulse-badge {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        .header-actions {
            display: flex;
            align-items: center;
        }
        .user-profile-link {
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            padding: 0.65rem 1.2rem;
            border-radius: 12px;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.3);
            font-weight: 600;
            cursor: pointer;
        }
        .user-profile-link:hover {
            background: rgba(255,255,255,0.35);
            border-color: rgba(255,255,255,0.6);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }
        .user-profile-link:active {
            transform: translateY(0);
        }
        .user-profile-link i {
            font-size: 1.4rem;
            margin-right: 0.5rem;
        }
        .user-profile-link::after {
            font-size: 1.1rem;
            margin-left: 0.5rem;
            opacity: 0.7;
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        .user-profile-link:hover::after {
            opacity: 1;
            transform: translateX(3px);
        }
        /* HU-029: Chat bell */
        .chat-bell {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border-radius: 8px;
            background: rgba(255,255,255,0.15);
            color: white;
            text-decoration: none;
            transition: all 0.2s ease;
            margin-right: 0.5rem;
        }
        .chat-bell:hover {
            background: rgba(255,255,255,0.25);
            color: white;
            transform: scale(1.05);
        }
        .chat-bell i {
            font-size: 1.3rem;
        }
        .chat-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            min-width: 20px;
            height: 20px;
            padding: 0 5px;
            font-size: 0.7rem;
            font-weight: 700;
            color: #fff;
            background: #6f42c1;
            border: 2px solid #CD1719;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse-badge 2s infinite;
        }
    </style>
    <header class="navbar-una" style="background: linear-gradient(135deg, #CD1719, #A01215) !important; padding: 1.25rem 0;">
        <div class="container-fluid px-4">
            <div class="header-left d-flex align-items-center">
                <img src="<?= htmlspecialchars($base_url) ?>img/logo.webp" alt="Logo UNA" class="logo-una" style="height: 70px;">
                <div class="header-text ms-3">
                    <h5 class="mb-0 text-white fw-bold">Universidad Nacional de Costa Rica</h5>
                    <small class="text-light opacity-85">Escuela de Informática</small>
                </div>
            </div>
            <div class="header-right text-end">
                <div class="user-info text-white mb-2">
                    <a href="<?= htmlspecialchars($base_url) ?>perfil.php" class="user-profile-link" title="Haz clic para ver tu perfil">
                        <i class="bi bi-person-badge"></i>
                        <span><?= htmlspecialchars($current_user_name) ?></span>
                    </a>
                </div>
                <div class="header-actions user-details">
                    <small class="text-light opacity-75 me-2">ID: <?= htmlspecialchars($current_user_id) ?></small>
                    
                    <!-- HU-037: Campanita de notificaciones -->
                    <a href="<?= htmlspecialchars($base_url) ?>historial_alertas.php" class="notification-bell" title="Mis Notificaciones">
                        <i class="bi bi-bell-fill"></i>
                        <?php if ($unread_notifications > 0): ?>
                            <span class="notification-badge"><?= $unread_notifications > 99 ? '99+' : $unread_notifications ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <!-- HU-029: Chat de comunicación interna -->
                    <a href="<?= htmlspecialchars($base_url) ?>chat.php" class="chat-bell" title="Mensajes">
                        <i class="bi bi-chat-dots-fill"></i>
                        <?php if ($unread_messages > 0): ?>
                            <span class="chat-badge" id="chatHeaderBadge"><?= $unread_messages > 99 ? '99+' : $unread_messages ?></span>
                        <?php else: ?>
                            <span class="chat-badge" id="chatHeaderBadge" style="display:none;"></span>
                        <?php endif; ?>
                    </a>
                    
                    <a href="<?= htmlspecialchars($base_url) ?>dashboard.php" class="btn btn-outline-light btn-sm ms-1" style="font-size: 1.05rem; padding: 0.55rem 1.1rem;">
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