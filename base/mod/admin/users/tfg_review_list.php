<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
include __DIR__ . '/../../../inc/db/bdcommon.inc';

$conn = new mysqli($db_host, $usuario, $clave, $db);
if ($conn->connect_error) die("Conexión fallida: " . $conn->connect_error);

$sql = "SELECT p.*, u.nombre as estudiante 
        FROM tfg_proposals p 
        JOIN sis_user u ON p.user_id = u.id 
        WHERE p.status = 'Pendiente de Revisión'
        ORDER BY p.created_at DESC";

$result = $conn->query($sql);
?>

<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Estudiante</th>
                <th>Título</th>
                <th>Fecha</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['estudiante']); ?></td>
                <td><?php echo htmlspecialchars($row['title']); ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                <td>
                    <div class="btn-group">
                        <?php $download_url = (isset($base_url) ? $base_url : '') . 'mod/admin/users/tfg_download.php?id=' . $row['id']; ?>
                        <a href="<?= htmlspecialchars($download_url) ?>" 
                           class="btn btn-info btn-sm">
                            <i class="fa fa-download"></i> Descargar
                        </a>
                        <button class="btn btn-success btn-sm" 
                                onclick="updateStatus(<?php echo $row['id']; ?>, 'Cumple requisitos')">
                            <i class="fa fa-check"></i> Cumple Requisitos
                        </button>
                        <button class="btn btn-danger btn-sm"
                                onclick="updateStatus(<?php echo $row['id']; ?>, 'No cumple requisitos')">
                            <i class="fa fa-times"></i> No Cumple Requisitos
                        </button>
                    </div>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
function updateStatus(id, status) {
    const comments = prompt('Ingrese comentarios sobre la revisión:');
    if (comments === null) return; // User cancelled

    fetch('mod/admin/users/tfg_update_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${id}&status=${encodeURIComponent(status)}&comments=${encodeURIComponent(comments)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Estado actualizado correctamente');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => alert('Error al actualizar el estado'));
}
</script>