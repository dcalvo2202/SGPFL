<?php
include("mod/login/check.php");
include('lang/lang.es');

require_once __DIR__ . '/inc/db/db.php';

$page_title = 'Aprobar Prórroga';

// Obtener solicitudes de prórroga pendientes
$solicitudes = [];
$conn = new mysqli($db_host, $usuario, $clave, $db);
if (!$conn->connect_error) {
    $conn->set_charset("utf8");
    $sql = "SELECT er.id as request_id, er.proposal_id, er.extension_number, er.reason, 
                   er.request_date, tp.title as proyecto_titulo, tp.user_id as estudiante_id
            FROM tfg_extension_requests er
            JOIN tfg_proposals tp ON er.proposal_id = tp.id
            WHERE er.status = 'pendiente'
            ORDER BY er.request_date ASC";
    
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $solicitudes[] = $row;
        }
    }
    $conn->close();
}

$inlineStyles = <<<'CSS'
.prorroga-card {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-left: 4px solid #034991;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    transition: all 0.3s ease;
    cursor: pointer;
    text-decoration: none;
    display: block;
    color: inherit;
}
.prorroga-card:hover {
    transform: scale(1.02);
    box-shadow: 0 8px 25px rgba(3, 73, 145, 0.15);
    border-left-color: #28a745;
    text-decoration: none;
    color: inherit;
}
.prorroga-card .titulo {
    font-size: 1.1rem;
    font-weight: 600;
    color: #034991;
    margin-bottom: 8px;
}
.prorroga-card .fecha {
    font-size: 0.9rem;
    color: #6c757d;
}
.prorroga-card .fecha i {
    margin-right: 5px;
}
.prorroga-card .badge-prorroga {
    font-size: 0.75rem;
    padding: 4px 10px;
    border-radius: 12px;
}
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
}
.empty-state i {
    font-size: 4rem;
    margin-bottom: 20px;
    opacity: 0.5;
}
CSS;
?>

<!doctype html>
<html lang="es">
<?php include __DIR__ . '/head.php'; ?>
<body class="fondo-una d-flex flex-column min-vh-100">
  <?php include 'header.php'; ?>
  <main class="flex-fill">
    <div class="container my-5">
      <div class="card shadow-sm" style="max-width: 900px; margin: 0 auto;">
        <div class="card-header text-center" style="background:#f8f9fa;font-weight:600;color:#034991;">
          <i class="fa fa-clock-o"></i> Solicitudes de Prórroga Pendientes
        </div>
        <div class="card-body" style="padding:24px;">
          
          <?php if (empty($solicitudes)): ?>
          <!-- Estado vacío -->
          <div class="empty-state">
            <i class="fa fa-inbox"></i>
            <h5>No hay solicitudes pendientes</h5>
            <p>Las solicitudes de prórroga aparecerán aquí cuando los estudiantes las envíen.</p>
          </div>
          
          <?php else: ?>
          <!-- Lista de solicitudes -->
          <p class="text-muted mb-4">
            <i class="fa fa-info-circle"></i> 
            Haz clic en una solicitud para ver los detalles y aprobar o rechazar.
          </p>
          
          <?php foreach ($solicitudes as $solicitud): ?>
          <a href="detalle_prorroga.php?id=<?php echo $solicitud['request_id']; ?>" class="prorroga-card">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <div class="titulo">
                  <?php echo htmlspecialchars($solicitud['proyecto_titulo']); ?>
                </div>
                <div class="fecha">
                  <i class="fa fa-calendar"></i>
                  Solicitado: <?php echo date('d/m/Y H:i', strtotime($solicitud['request_date'])); ?>
                </div>
              </div>
              <div>
                <span class="badge badge-prorroga" style="background-color:<?php echo ($solicitud['extension_number'] == 1) ? '#17a2b8' : '#ffc107'; ?>; color:<?php echo ($solicitud['extension_number'] == 1) ? '#fff' : '#212529'; ?>;">
                  <?php echo ($solicitud['extension_number'] == 1) ? '1ra Prórroga (1 año)' : '2da Prórroga (6 meses)'; ?>
                </span>
              </div>
            </div>
          </a>
          <?php endforeach; ?>
          
          <?php endif; ?>
          
        </div>
      </div>
    </div>
  </main>
  <?php include 'footer.php'; ?>
</body>
</html>
