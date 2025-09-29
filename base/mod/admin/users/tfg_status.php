<?php
// tfg_status.php

// Asegura que una sesión esté iniciada sin crear una nueva si ya existe.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Se usa include_once para evitar re-declaraciones si el panel ya lo incluyó.
include_once __DIR__ . '/../../../inc/db/bdcommon.inc';

$conn = new mysqli($db_host, $usuario, $clave, $db);
if ($conn->connect_error) {
    echo '<div class="alert alert-danger">Error de conexión con la base de datos.</div>';
} else {
    // Si la sesión no está definida, se usa un ID de prueba.
    // COMENTAR O ELIMINAR ESTA LÍNEA EN PRODUCCIÓN.
    $user_id = $_SESSION['id'] ?? 'estudiante001'; 

    $sql = "SELECT id, title, status, created_at FROM tfg_proposals WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $status = htmlspecialchars($row['status'] ?? '');
            $badge_color = 'bg-secondary';

            // Reemplazo de str_contains por strpos para compatibilidad con PHP 7+.
            $s = strtolower($status);
            if (strpos($s, 'aprobado') !== false) $badge_color = 'bg-success';
            if (strpos($s, 'rechazado') !== false) $badge_color = 'bg-danger';
            if (strpos($s, 'revisión') !== false || strpos($s, 'revision') !== false || strpos($s, 'pendiente') !== false) $badge_color = 'bg-info';
            
            // Construcción de la URL de descarga usando $base_url si está disponible.
            $download_url = 'mod/admin/users/tfg_download.php?id=' . (int)$row['id'];
            
            // Manejo seguro de la fecha de creación en caso de que sea nula.
            $created = $row['created_at'] ?? null;
            $created_fmt = $created ? htmlspecialchars(date("d/m/Y H:i", strtotime($created))) : '—';

            echo '
            <h3 class="h5 mb-3">Estado de tu última propuesta</h3>
            <div class="list-group">
                <div class="list-group-item"><strong>Título:</strong> ' . htmlspecialchars($row['title'] ?? '') . '</div>
                <div class="list-group-item"><strong>Estado:</strong> <span class="badge ' . $badge_color . ' fs-6">' . $status . '</span></div>
                <div class="list-group-item"><strong>Fecha de envío:</strong> ' . $created_fmt . '</div>
                <div class="list-group-item text-center">
                    <a class="btn btn-success" href="'.htmlspecialchars($download_url).'" target="_blank">
                        <i class="bi bi-file-earmark-arrow-down-fill me-2"></i>Ver/Descargar Documento
                    </a>
                </div>
            </div>';
        } else {
            echo '
            <div class="alert alert-secondary text-center">
                <i class="bi bi-info-circle-fill me-2"></i> Aún no has subido ninguna propuesta.
            </div>';
        }
        $stmt->close();
    } else {
        echo '<div class="alert alert-danger">Error al preparar la consulta.</div>';
    }
    $conn->close();
}
?>