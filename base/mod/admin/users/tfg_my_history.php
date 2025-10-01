<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
include __DIR__ . '/../../../inc/db/bdcommon.inc';

$conn = new mysqli($db_host, $usuario, $clave, $db);
if ($conn->connect_error) {
    die('<div class="alert alert-danger">Error de conexión con la base de datos.</div>');
}

// Obtener el ID del usuario (estudiante)
$user_id = $_SESSION['id'] ?? 'estudiante001'; // Cambiar por el ID real de la sesión

// Consulta para obtener historial del estudiante
$sql = "SELECT h.*, u.nombre as reviewer_name 
        FROM tfg_proposal_history h 
        LEFT JOIN sis_user u ON h.reviewed_by = u.id 
        JOIN tfg_proposals p ON h.proposal_id = p.id
        WHERE p.user_id = ? 
        ORDER BY h.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    echo '<div class="card">
            <div class="card-header">
                <h6 class="mb-0">Historial de Versiones</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Revisor</th>
                                <th>Comentarios</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>';
    
    while ($row = $result->fetch_assoc()) {
        $date = date('d/m/Y H:i', strtotime($row['created_at']));
        $badge_class = '';
        switch($row['status']) {
            case 'Cumple requisitos':
                $badge_class = 'bg-success';
                break;
            case 'No cumple requisitos':
                $badge_class = 'bg-danger';
                break;
            default:
                $badge_class = 'bg-warning';
        }
        
        echo '<tr>
                <td>' . $date . '</td>
                <td>
                    <span class="badge ' . $badge_class . '">' . htmlspecialchars($row['status']) . '</span>
                </td>
                <td>' . ($row['reviewer_name'] ? htmlspecialchars($row['reviewer_name']) : 'Sistema') . '</td>
                <td>' . htmlspecialchars($row['comments'] ?? 'Sin comentarios') . '</td>
                <td>';
        
        $dl_url = (isset($base_url) ? $base_url : '') . 'mod/admin/users/tfg_download.php?version=' . $row['id'];
        
        echo '    <a href="' . htmlspecialchars($dl_url) . '" 
                       class="btn btn-sm btn-outline-primary" target="_blank">
                        <i class="bi bi-download"></i> Descargar
                    </a>
                </td>
              </tr>';
    }
    
    echo '</tbody></table></div></div></div>';
} else {
    echo '<div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            No hay historial de versiones disponible.
          </div>';
}
?>