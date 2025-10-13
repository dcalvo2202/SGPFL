<?php
include("mod/login/check.php");
include('includes.php');
include('lang/lang.es');
include('inc/db/db.php');

// Obtener variables de sesión
$current_user_id = $mySessionController->getVar("usuario");
$current_user_name = $mySessionController->getVar("nombre");
$current_user_rol = $mySessionController->getVar("rol");
$cds_domain = $mySessionController->getVar("cds_domain");
$cds_locate = $mySessionController->getVar("cds_locate");
$base_url = $cds_domain . $cds_locate;

// Restringir acceso solo a estudiantes
if ($current_user_rol != 4) {
    header('Location: dashboard.php');
    exit;
}

// Buscar el project_id asociado al usuario autenticado
$project_id = 0;
try {
    $conn = new mysqli($db_host, $usuario, $clave, $db);
    $conn->set_charset("utf8");
    $sql = "SELECT rp.id
            FROM registered_projects rp
            INNER JOIN tfg_proposals tp ON rp.tfg_proposal_id = tp.id
            WHERE tp.user_id = ?
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $current_user_id);
    $stmt->execute();
    $stmt->bind_result($project_id);
    $stmt->fetch();
    $stmt->close();
} catch (Exception $e) {
    error_log("Error obteniendo project_id: " . $e->getMessage());
}

/* Validar que el proyecto pertenezca al estudiante autenticado
$es_propietario = false;
try {
    $conn = new mysqli($db_host, $usuario, $clave, $db);
    $conn->set_charset("utf8");
    $sql = "SELECT rp.id 
            FROM registered_projects rp
            INNER JOIN tfg_proposals tp ON rp.tfg_proposal_id = tp.id
            WHERE rp.id = ? AND tp.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $project_id, $current_user_id);
    $stmt->execute();
    $stmt->store_result();
    $es_propietario = $stmt->num_rows > 0;
    $stmt->close();
    if (!$es_propietario) {
        header('Location: dashboard.php');
        exit;
    }
} catch (Exception $e) {
    error_log("Error validando propietario: " . $e->getMessage());
    header('Location: dashboard.php');
    exit;
}*/

// Consultar el documento de la propuesta TFG asociada al usuario autenticado y su proyecto
$propuesta_tfg = null;
try {
    $conn = new mysqli($db_host, $usuario, $clave, $db);
    $conn->set_charset("utf8");
    $sql = "SELECT tp.id, tp.title, tp.file_name, tp.mime_type, tp.file_size, tp.status, tp.created_at
            FROM registered_projects rp
            INNER JOIN tfg_proposals tp ON rp.tfg_proposal_id = tp.id
            WHERE tp.user_id = ?
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $propuesta_tfg = $row;
    }
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    error_log("Error obteniendo propuesta TFG: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<!-- =============================== HEAD =============================== -->
<?php include 'head.php'; ?>
<body class="d-flex flex-column min-vh-100 fondo-una">
    <!-- =============================== HEADER =============================== -->
    <?php include 'header.php'; ?> 
    <!-- =============================== CONTENIDO PRINCIPAL =============================== -->
    <main class="flex-fill">
        <div class="container my-4">
            <h1 class="text-center mb-4" style="color: #b00; font-size: 2.5rem;">Historial de documentos</h1>
            <div class="table-responsive">
                <table class="table table-bordered align-middle text-center">
                    <thead class="table-secondary table-responsive">
                        <tr>
                            <th>Documento</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Versión</th>
                            <th>Tamaño</th>
                            <th>Formato</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($propuesta_tfg): ?>
                            <tr>
                                <td>Propuesta TFG: <?= htmlspecialchars($propuesta_tfg['title']) ?></td>
                                <td><?= date('d/m/Y', strtotime($propuesta_tfg['created_at'])) ?></td>
                                <td><?= htmlspecialchars($propuesta_tfg['status']) ?></td>
                                <td>1</td>
                                <td><?= number_format($propuesta_tfg['file_size'] / 1024, 2) ?> KB</td>
                                <td><?= strtoupper(htmlspecialchars($propuesta_tfg['mime_type'])) ?></td>
                                <td>
                                    <a href="<?= $base_url . 'mod/admin/users/tfg_download.php?proposal_id=' . $propuesta_tfg['id'] ?>" class="btn btn-link">Descargar</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">No hay documentos subidos para este proyecto.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-center mt-4">
                <a href="Panel_SubirTFG.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left-circle"></i> Volver al Panel Principal
                </a>
            </div>
        </div>
    </main>
  <!-- =============================== FOOTER =============================== -->
    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>