<?php
/**
 * HU-037: Historial de Alertas
 * Panel para visualizar las alertas/notificaciones del usuario
 */

// Verificar autenticación
include("mod/login/check.php");

// Incluir archivos necesarios
include('includes.php');
include('lang/lang.es');
require_once 'inc/alert_functions.php';

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

// Conexión a base de datos
include 'inc/db/bdcommon.inc';
$conn = new mysqli($db_host, $usuario, $clave, $db);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// Determinar qué usuario ver (solo admin puede ver otros usuarios)
$view_user_id = $current_user_id;
if (isset($_GET['user_id']) && $current_user_rol == 1) {
    $view_user_id = $_GET['user_id'];
}

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'mark_read':
                if (isset($_POST['alert_id'])) {
                    markAlertAsRead($conn, (int)$_POST['alert_id'], $view_user_id);
                }
                break;
            case 'mark_all_read':
                markAllAlertsAsRead($conn, $view_user_id);
                break;
        }
        // Redirigir para evitar reenvío del formulario
        header('Location: historial_alertas.php');
        exit;
    }
}

// Obtener alertas del usuario
$alerts = getUserAlerts($conn, $view_user_id, 100);
$unread_count = getUnreadAlertCount($conn, $view_user_id);

// Función para obtener el ícono según el tipo de alerta
function getAlertIcon($type) {
    $icons = [
        'Nueva Propuesta' => 'bi-file-earmark-plus-fill',
        'Propuesta Aprobada' => 'bi-check-circle-fill',
        'Propuesta Rechazada' => 'bi-x-circle-fill',
        'Documento Final' => 'bi-file-earmark-text-fill',
        'Correccion Solicitada' => 'bi-pencil-square',
        'Asesor Aprobado' => 'bi-person-check-fill',
        'Asesor Rechazado' => 'bi-person-x-fill',
        'Prorroga' => 'bi-calendar-plus-fill',
        'Informativa' => 'bi-info-circle-fill',
        'Sistema' => 'bi-gear-fill'
    ];
    return $icons[$type] ?? 'bi-bell-fill';
}

// Función para obtener el color según el tipo de alerta
function getAlertColor($type) {
    $colors = [
        'Nueva Propuesta' => ['bg' => '#e3f2fd', 'icon' => '#1976d2', 'border' => '#1976d2'],
        'Propuesta Aprobada' => ['bg' => '#e8f5e9', 'icon' => '#2e7d32', 'border' => '#2e7d32'],
        'Propuesta Rechazada' => ['bg' => '#ffebee', 'icon' => '#c62828', 'border' => '#c62828'],
        'Documento Final' => ['bg' => '#e0f7fa', 'icon' => '#00838f', 'border' => '#00838f'],
        'Correccion Solicitada' => ['bg' => '#fff3e0', 'icon' => '#ef6c00', 'border' => '#ef6c00'],
        'Asesor Aprobado' => ['bg' => '#e8f5e9', 'icon' => '#2e7d32', 'border' => '#2e7d32'],
        'Asesor Rechazado' => ['bg' => '#fff3e0', 'icon' => '#e65100', 'border' => '#e65100'],
        'Prorroga' => ['bg' => '#fce4ec', 'icon' => '#ad1457', 'border' => '#ad1457'],
        'Informativa' => ['bg' => '#e3f2fd', 'icon' => '#1565c0', 'border' => '#1565c0'],
        'Sistema' => ['bg' => '#eceff1', 'icon' => '#455a64', 'border' => '#455a64']
    ];
    return $colors[$type] ?? ['bg' => '#f5f5f5', 'icon' => '#757575', 'border' => '#757575'];
}

// Agrupar alertas por fecha
function groupAlertsByDate($alerts) {
    $grouped = [];
    foreach ($alerts as $alert) {
        $date = date('Y-m-d', strtotime($alert['sent_at']));
        if (!isset($grouped[$date])) {
            $grouped[$date] = [];
        }
        $grouped[$date][] = $alert;
    }
    return $grouped;
}

// Formatear fecha para mostrar
function formatDateHeader($date) {
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    if ($date === $today) {
        return 'Hoy';
    } elseif ($date === $yesterday) {
        return 'Ayer';
    } else {
        $timestamp = strtotime($date);
        $dayNames = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        $monthNames = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        
        return $dayNames[date('w', $timestamp)] . ', ' . date('j', $timestamp) . ' de ' . $monthNames[date('n', $timestamp)] . ' de ' . date('Y', $timestamp);
    }
}

$grouped_alerts = groupAlertsByDate($alerts);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include 'head.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --una-primary: #003DA5;
            --una-secondary: #002B75;
        }
        
        .notifications-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--una-primary), var(--una-secondary));
            color: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 61, 165, 0.3);
        }
        
        .page-header h1 {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            color: white;
        }
        
        .page-header p {
            opacity: 0.9;
            margin-bottom: 0;
        }
        
        .unread-badge {
            display: inline-flex;
            align-items: center;
            background: rgba(255,255,255,0.2);
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 1rem;
            margin-top: 0.75rem;
        }
        
        .unread-badge i {
            margin-right: 0.4rem;
        }
        
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
            font-size: 1.15rem;
        }
        
        .btn-back {
            background: white;
            border: 1px solid #dee2e6;
            color: #495057;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s;
            text-decoration: none;
        }
        
        .btn-back:hover {
            background: #f8f9fa;
            border-color: #adb5bd;
            color: #212529;
        }
        
        .btn-mark-all {
            background: var(--una-primary);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-mark-all:hover {
            background: var(--una-secondary);
            color: white;
        }
        
        .date-header {
            font-size: 1rem;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 1.5rem 0 0.75rem 0;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .date-header:first-child {
            margin-top: 0;
        }
        
        .notification-card {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border-left: 4px solid;
            transition: all 0.2s ease;
            position: relative;
        }
        
        .notification-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .notification-card.unread {
            background: linear-gradient(135deg, #ffffff 0%, #f8faff 100%);
        }
        
        .notification-card.unread::before {
            content: '';
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 10px;
            height: 10px;
            background: #dc3545;
            border-radius: 50%;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.2);
        }
        
        .notification-content {
            display: flex;
            gap: 1rem;
        }
        
        .notification-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }
        
        .notification-body {
            flex: 1;
            min-width: 0;
        }
        
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
            gap: 0.5rem;
        }
        
        .notification-title {
            font-weight: 600;
            font-size: 1.5rem;
            color: #212529;
            margin: 0;
            line-height: 1.4;
        }
        
        .notification-time {
            font-size: 1.15rem;
            color: #6c757d;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .notification-type {
            display: inline-block;
            font-size: 1rem;
            font-weight: 600;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .notification-message {
            color: #495057;
            font-size: 1.30rem;
            line-height: 1.6;
            margin: 0 0 0.75rem 0;
            white-space: pre-line;
        }
        
        .notification-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid #f0f0f0;
        }
        
        .btn-mark-read {
            background: transparent;
            border: 1px solid #dee2e6;
            color: #6c757d;
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            font-size: 1.15rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .btn-mark-read:hover {
            background: #f8f9fa;
            border-color: #adb5bd;
            color: #495057;
        }
        
        .read-status {
            font-size: 1.15rem;
            color: #28a745;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        
        .empty-state-icon {
            width: 80px;
            height: 80px;
            background: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        
        .empty-state-icon i {
            font-size: 2.5rem;
            color: #adb5bd;
        }
        
        .empty-state h3 {
            color: #495057;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .empty-state p {
            color: #6c757d;
            margin-bottom: 0;
        }
        
        .footer-info {
            text-align: center;
            padding: 1.5rem;
            color: #6c757d;
            font-size: 1.30rem;
        }
        
        .footer-info i {
            margin-right: 0.25rem;
        }

        @media (max-width: 576px) {
            .page-header {
                padding: 1.5rem;
            }
            
            .page-header h1 {
                font-size: 1.4rem;
            }
            
            .action-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .notification-content {
                flex-direction: column;
                gap: 1.15rem;
            }
            
            .notification-icon {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
            }
            
            .notification-header {
                flex-direction: column;
            }
            
            .notification-time {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body class="fondo-una d-flex flex-column min-vh-100">

    <?php include 'header.php'; ?>

    <main class="flex-fill">
        <div class="container my-4">
            <div class="notifications-container">
                
                <!-- Encabezado -->
                <div class="page-header">
                    <h1><i class="bi bi-bell-fill me-2"></i>Mis Notificaciones</h1>
                    <p>Centro de alertas y notificaciones del sistema</p>
                    <?php if ($unread_count > 0): ?>
                        <div class="unread-badge">
                            <i class="bi bi-envelope-fill"></i>
                            <?= $unread_count ?> notificación<?= $unread_count > 1 ? 'es' : '' ?> sin leer
                        </div>
                    <?php else: ?>
                        <div class="unread-badge">
                            <i class="bi bi-check-all"></i>
                            Todas las notificaciones leídas
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Barra de acciones -->
                <div class="action-bar">
                    <a href="dashboard.php" class="btn-back">
                        <i class="bi bi-arrow-left me-1"></i> Volver al Panel Principal
                    </a>
                    <?php if ($unread_count > 0): ?>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="mark_all_read">
                            <button type="submit" class="btn btn-mark-all">
                                <i class="bi bi-check-all me-1"></i> Marcar todas como leídas
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Lista de alertas -->
                <?php if (empty($alerts)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="bi bi-bell-slash"></i>
                        </div>
                        <h3>No tienes notificaciones</h3>
                        <p>Cuando recibas alertas del sistema, aparecerán aquí.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($grouped_alerts as $date => $date_alerts): ?>
                        <div class="date-header">
                            <i class="bi bi-calendar3 me-1"></i>
                            <?= formatDateHeader($date) ?>
                        </div>
                        
                        <?php foreach ($date_alerts as $alert): ?>
                            <?php 
                                $is_unread = is_null($alert['read_at']);
                                $icon = getAlertIcon($alert['alert_type']);
                                $colors = getAlertColor($alert['alert_type']);
                            ?>
                            <div class="notification-card <?= $is_unread ? 'unread' : '' ?>" 
                                 style="border-left-color: <?= $colors['border'] ?>;">
                                <div class="notification-content">
                                    <div class="notification-icon" 
                                         style="background: <?= $colors['bg'] ?>; color: <?= $colors['icon'] ?>;">
                                        <i class="bi <?= $icon ?>"></i>
                                    </div>
                                    
                                    <div class="notification-body">
                                        <div class="notification-header">
                                            <h6 class="notification-title">
                                                <?= htmlspecialchars($alert['subject']) ?>
                                            </h6>
                                            <span class="notification-time">
                                                <i class="bi bi-clock"></i>
                                                <?= date('H:i', strtotime($alert['sent_at'])) ?>
                                            </span>
                                        </div>
                                        
                                        <span class="notification-type" 
                                              style="background: <?= $colors['bg'] ?>; color: <?= $colors['icon'] ?>;">
                                            <?= htmlspecialchars($alert['alert_type']) ?>
                                        </span>
                                        
                                        <p class="notification-message">
                                            <?= nl2br(htmlspecialchars($alert['message'])) ?>
                                        </p>
                                        
                                        <div class="notification-actions">
                                            <?php if ($is_unread): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="mark_read">
                                                    <input type="hidden" name="alert_id" value="<?= $alert['id'] ?>">
                                                    <button type="submit" class="btn-mark-read">
                                                        <i class="bi bi-check"></i> Marcar como leída
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="read-status">
                                                    <i class="bi bi-check-circle-fill"></i>
                                                    Leída el <?= date('d/m/Y H:i', strtotime($alert['read_at'])) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Información del pie -->
                <div class="footer-info">
                    <i class="bi bi-info-circle"></i>
                    Las notificaciones se conservan en el sistema para tu referencia.
                </div>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>

</body>
</html>
<?php
$conn->close();
?>
