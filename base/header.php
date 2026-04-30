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
        /* Menú hamburguesa */
        .hamburger-menu {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem 0.75rem;
            transition: all 0.3s ease;
        }
        .hamburger-menu:hover {
            opacity: 0.8;
        }
        .mobile-menu {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: rgba(13, 30, 50, 0.98);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 0 0 8px 8px;
            width: 100%;
            padding: 1rem 0;
            z-index: 1049;
            flex-direction: column;
            gap: 0;
            left: 0;
        }
        .mobile-menu.active {
            display: flex;
        }
        .mobile-menu a,
        .mobile-menu div {
            padding: 0.75rem 1rem;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            transition: all 0.2s ease;
        }
        .mobile-menu a:hover {
            background: rgba(255,255,255,0.1);
            padding-left: 1.5rem;
        }
        .mobile-menu a:last-child {
            border-bottom: none;
        }
        .mobile-menu-id {
            padding: 0.75rem 1rem;
            color: rgba(255,255,255,0.75);
            font-size: 0.9rem;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
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
        .navbar-una {
            position: sticky;
            top: 0;
            z-index: 1050;
        }
        .navbar-una .logo-una {
            width: auto !important;
            aspect-ratio: 1 / 1;
            object-fit: contain;
        }
        .navbar-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            width: 100%;
        }
        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .header-container-main {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            gap: 1rem;
        }
        @media (max-width: 754px) {
            .navbar-una {
                padding: 0.55rem 0 !important;
            }
            .navbar-una .container-fluid {
                padding-left: 1rem;
                padding-right: 1rem;
                gap: 0.65rem;
            }
            .header-container-main {
                gap: 0.75rem;
            }
            .header-left {
                justify-content: flex-start;
                text-align: left;
                flex-wrap: nowrap;
                gap: 0.75rem;
                flex: 1;
                min-width: 0;
            }
            .logo-una {
                height: 44px !important;
                width: 44px !important;
            }
            .header-text h5 {
                font-size: 1.50rem !important;
                line-height: 1.08;
            }
            .header-text small {
                font-size: 1.30rem !important;
            }
            /* En tablet, mostrar hamburguesa y ocultar menú inline */
            .hamburger-menu {
                display: block !important;
                margin-left: auto;
                flex-shrink: 0;
                font-size: 3rem;
            }
            .header-right {
                display: none !important;
            }
            .mobile-menu {
                position: static;
                background: transparent;
                border: none;
                width: auto;
                padding: 0;
                left: auto;
                right: auto;
                display: none;
            }
            .mobile-menu.active {
                display: flex;
                width: 100%;
                background: rgba(13, 30, 50, 0.98);
                margin-top: 0.5rem;
                border-radius: 8px;
                margin-left: -1rem;
                margin-right: -1rem;
                padding-left: 0;
                padding-right: 0;
            }
            .user-info {
                justify-content: center;
                margin-bottom: 0.5rem;
            }
            .user-profile-link {
                width: 100%;
                justify-content: center;
                padding: 0.56rem 0.8rem;
                font-size: 0.92rem;
            }
            .user-details {
                flex-wrap: wrap;
                justify-content: center;
                gap: 0.5rem;
            }
            .user-details small {
                width: 100%;
                text-align: left;
                white-space: normal;
                font-size: 0.95rem;
            }
            .notification-bell,
            .chat-bell {
                width: 40px;
                height: 40px;
                margin-right: 0;
            }
            .notification-bell i,
            .chat-bell i {
                font-size: 1.15rem;
            }
            .notification-badge,
            .chat-badge {
                top: -4px;
                right: -4px;
                min-width: 18px;
                height: 18px;
                font-size: 0.65rem;
            }
            .navbar-una .btn-outline-light {
                font-size: 0.92rem !important;
                padding: 0.45rem 0.8rem !important;
                margin-left: 0 !important;
                flex: 1 1 120px;
            }
        }

        @media (max-width: 420px) {
            .navbar-una {
                padding: 0.4rem 0 !important;
            }

            .navbar-una .container-fluid {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
                gap: 0.45rem;
                flex-direction: column;
                align-items: stretch;
            }

            .navbar-container {
                flex-direction: row;
                gap: 0.55rem;
            }

            .header-left {
                justify-content: flex-start;
                text-align: left;
                flex-wrap: nowrap;
                gap: 0.55rem;
                flex: 1;
            }

            .logo-una {
                height: 34px !important;
                width: 34px !important;
                flex: 0 0 auto;
            }

            .header-text {
                min-width: 0;
            }

            .header-text h5 {
                font-size: 1.40rem !important;
                line-height: 1.03;
                margin-bottom: 0.1rem !important;
            }

            .header-text small {
                font-size: 1.25rem !important;
                line-height: 1;
            }

            .hamburger-menu {
                display: block !important;
                padding: 0.25rem 0.5rem;
                font-size: 2.5rem;
                margin: 0;
                flex-shrink: 0;
                margin-left: auto;
            }

            .mobile-menu.active {
                margin-left: -0.75rem;
                margin-right: -0.75rem;
                border-radius: 8px;
                margin-top: 0.5rem;
            }

            .mobile-menu a,
            .mobile-menu-id {
                font-size: 0.9rem;
                padding: 0.65rem 1rem;
            }

            .header-right {
                display: none !important;
            }
        }

        @media (max-width: 360px) {
            .header-text h5 {
                font-size: 1.40rem !important;
            }

            .header-text small {
                font-size: 1rem !important;
            }

            .user-profile-link {
                padding: 0.42rem 0.58rem;
                font-size: 1rem !important;
            }

            .navbar-una .btn-outline-light {
                font-size: 0.8rem !important;
                padding: 0.44rem 0.55rem !important;
                min-height: 36px;
            }

            .hamburger-menu {
                display: block !important;
                padding: 0.25rem 0.5rem;
                font-size: 2.5rem;
                margin: 0;
            }

            .notification-bell,
            .chat-bell {
                height: 36px;
            }
        }
    </style>
    <header class="navbar-una" style="background: linear-gradient(135deg, #CD1719, #A01215) !important; padding: 1.25rem 0;">
        <div class="container-fluid px-4">
            <div class="header-container-main">
                <!-- Lado Izquierdo: Logo y Título -->
                <div class="header-left d-flex align-items-center" style="flex: 0 1 auto;">
                    <img src="<?= htmlspecialchars($base_url) ?>img/logo.webp" alt="Logo UNA" class="logo-una" style="height: 70px;">
                    <div class="header-text ms-3">
                        <h5 class="mb-0 text-white fw-bold">Universidad Nacional de Costa Rica</h5>
                        <small class="text-light opacity-85">Escuela de Informática</small>
                    </div>
                </div>
                
                <!-- Lado Derecho: Hamburguesa (móvil) o Menú (desktop) -->
                <button class="hamburger-menu" id="hamburgerMenu" title="Menú">
                    <i class="bi bi-list"></i>
                </button>
            </div>
            
            <!-- Menú desplegable en móvil -->
            <div class="mobile-menu" id="mobileMenu">
                <div class="mobile-menu-id">
                    ID: <?= htmlspecialchars($current_user_id) ?>
                </div>
                
                <a href="<?= htmlspecialchars($base_url) ?>perfil.php" title="Ver perfil">
                    <i class="bi bi-person-badge"></i>
                    <span><?= htmlspecialchars($current_user_name) ?></span>
                </a>
                
                <a href="<?= htmlspecialchars($base_url) ?>historial_alertas.php" title="Mis Notificaciones">
                    <i class="bi bi-bell-fill"></i>
                    <span>Notificaciones 
                    <?php if ($unread_notifications > 0): ?>
                        <span class="badge bg-warning text-dark"><?= $unread_notifications > 99 ? '99+' : $unread_notifications ?></span>
                    <?php endif; ?>
                    </span>
                </a>
                
                <a href="<?= htmlspecialchars($base_url) ?>chat.php" title="Mensajes">
                    <i class="bi bi-chat-dots-fill"></i>
                    <span>Mensajes 
                    <?php if ($unread_messages > 0): ?>
                        <span class="badge bg-info text-dark"><?= $unread_messages > 99 ? '99+' : $unread_messages ?></span>
                    <?php endif; ?>
                    </span>
                </a>
                
                <a href="<?= htmlspecialchars($base_url) ?>dashboard.php">
                    <i class="bi bi-house-fill"></i>
                    <span>Inicio</span>
                </a>
                
                <a href="<?= htmlspecialchars($base_url) ?>mod/login/logout.php" 
                   onclick="return confirmarCierreSesion(event, '<?= htmlspecialchars($base_url) ?>')">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Salir</span>
                </a>
            </div>
            
            <!-- Menú en desktop -->
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
    
    <script>
        // Hamburger menu toggle
        const hamburgerMenu = document.getElementById('hamburgerMenu');
        const mobileMenu = document.getElementById('mobileMenu');
        
        if (hamburgerMenu && mobileMenu) {
            hamburgerMenu.addEventListener('click', function() {
                mobileMenu.classList.toggle('active');
            });
            
            // Cerrar menú cuando se clickea un enlace
            const menuLinks = mobileMenu.querySelectorAll('a');
            menuLinks.forEach(link => {
                link.addEventListener('click', function() {
                    mobileMenu.classList.remove('active');
                });
            });
            
            // Cerrar menú cuando se hace click fuera
            document.addEventListener('click', function(event) {
                if (!event.target.closest('.navbar-una')) {
                    mobileMenu.classList.remove('active');
                }
            });
        }
    </script>