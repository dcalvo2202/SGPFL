<?php

require_once __DIR__ . '/inc/db/db.php';

// Título y opciones para el head.php
$page_title   = 'Proyectos registrados';
$inlineStyles = <<<'CSS'
main { padding: 24px 0; }
.page-title { font-weight: 700; color: #092567; margin-bottom: 14px; }
CSS;

// Filtros (GET ?q=&estado=&comite=&f_ini=&f_fin=)
$q = trim($_GET['q'] ?? '');
$estado = isset($_GET['estado']) && in_array((int)$_GET['estado'], [1,2,3], true) ? (int)$_GET['estado'] : 1;
$comite_id = isset($_GET['comite']) ? (int)$_GET['comite'] : 0;
$f_ini = trim($_GET['f_ini'] ?? '');
$f_fin = trim($_GET['f_fin'] ?? '');

$validDate = fn($d) => $d !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
if (!$validDate($f_ini)) $f_ini = '';
if (!$validDate($f_fin)) $f_fin = '';
if ($f_ini && $f_fin && $f_ini > $f_fin) { $tmp = $f_ini; $f_ini = $f_fin; $f_fin = $tmp; }

// Cargar comités para el selector
$comites = [];
$sqlCom = "SELECT c.Id,
                  COALESCE(t.nombre,'-') AS tutor_nombre,
                  COALESCE(a1.nombre,'-') AS asesor1_nombre,
                  COALESCE(a2.nombre,'-') AS asesor2_nombre
           FROM comite c
           LEFT JOIN sis_user t  ON t.id  = c.tutor
           LEFT JOIN sis_user a1 ON a1.id = c.asesor_1
           LEFT JOIN sis_user a2 ON a2.id = c.asesor_2
           ORDER BY c.Id";
if ($resCom = mysqli_query($id_con, $sqlCom)) {
    while ($r = mysqli_fetch_assoc($resCom)) $comites[] = $r;
    mysqli_free_result($resCom);
}

// Etiquetas/estilos para el estado
function estadoInfo(int $v): array {
    switch ($v) {
        case 1: return ['Aprobado', 'success'];
        case 2: return ['Sin aprobar', 'secondary'];
        case 3: return ['Esperando correcciones', 'warning'];
        default: return ['Desconocido', 'light'];
    }
}
list($estadoLabel, $estadoBadge) = estadoInfo($estado);

// Construcción de consulta con condiciones dinámicas
$proyectos_aprobados = [];
$sql = "SELECT p.id_aprobado, p.nombre, p.aprobado, p.fecha_creacion, p.comite_id,
               COALESCE(t.nombre,'-')  AS tutor_nombre,
               COALESCE(a1.nombre,'-') AS asesor1_nombre,
               COALESCE(a2.nombre,'-') AS asesor2_nombre
        FROM proyecto_aprobado p
        LEFT JOIN comite c ON c.Id = p.comite_id
        LEFT JOIN sis_user t  ON t.id  = c.tutor
        LEFT JOIN sis_user a1 ON a1.id = c.asesor_1
        LEFT JOIN sis_user a2 ON a2.id = c.asesor_2
        WHERE ";
$conds  = ["p.aprobado = ?"];
$types  = "i";
$params = [$estado];

if ($q !== '') {
    $conds[] = "p.nombre LIKE ?";
    $types  .= "s";
    $params[] = "%{$q}%";
}
if ($comite_id > 0) {
    $conds[] = "p.comite_id = ?";
    $types  .= "i";
    $params[] = $comite_id;
}
if ($f_ini !== '') {
    $conds[] = "DATE(p.fecha_creacion) >= ?";
    $types  .= "s";
    $params[] = $f_ini;
}
if ($f_fin !== '') {
    $conds[] = "DATE(p.fecha_creacion) <= ?";
    $types  .= "s";
    $params[] = $f_fin;
}
$sql .= implode(' AND ', $conds) . " ORDER BY p.fecha_creacion DESC, p.id_aprobado DESC";

if ($stmt = mysqli_prepare($id_con, $sql)) {
    // bind dinámico
    $bind = [$stmt, $types];
    foreach ($params as $k => $v) { $bind[] = &$params[$k]; }
    call_user_func_array('mysqli_stmt_bind_param', $bind);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($res && $row = mysqli_fetch_assoc($res)) $proyectos_aprobados[] = $row;
    mysqli_stmt_close($stmt);
}
?>
<!doctype html>
<html lang="es">
<?php include __DIR__ . '/head.php'; ?>
<body class="fondo-una d-flex flex-column min-vh-100">
  <?php include 'header.php'; ?>
  <main class="flex-fill">
    <div class="container my-5">
      <h1 class="page-title">
        Proyectos <span class="badge bg-<?php echo $estadoBadge; ?>"><?php echo $estadoLabel; ?></span>
      </h1>

      <!-- Filtros -->
      <form class="row g-2 mb-4" method="get" action="">
        <div class="col-sm-6 col-md-4 col-lg-4">
          <input type="search" class="form-control" name="q" placeholder="Buscar por nombre..."
                 value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off">
        </div>
        <div class="col-sm-6 col-md-3 col-lg-2">
          <select name="estado" class="form-select">
            <option value="1" <?php echo $estado===1?'selected':''; ?>>Aprobado</option>
            <option value="2" <?php echo $estado===2?'selected':''; ?>>Sin aprobar</option>
            <option value="3" <?php echo $estado===3?'selected':''; ?>>Esperando correcciones</option>
          </select>
        </div>
        <div class="col-sm-6 col-md-5 col-lg-4">
          <select name="comite" class="form-select">
            <option value="0">Todos los comités</option>
            <?php foreach ($comites as $c): ?>
              <option value="<?php echo (int)$c['Id']; ?>" <?php echo $comite_id===(int)$c['Id']?'selected':''; ?>>
                Comité #<?php echo (int)$c['Id']; ?> — T: <?php echo htmlspecialchars($c['tutor_nombre']); ?> / A1: <?php echo htmlspecialchars($c['asesor1_nombre']); ?> / A2: <?php echo htmlspecialchars($c['asesor2_nombre']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="w-100 d-none d-md-block"></div>
        <div class="col-sm-6 col-md-3 col-lg-2">
          <input type="date" class="form-control" name="f_ini" value="<?php echo htmlspecialchars($f_ini); ?>" placeholder="Desde">
        </div>
        <div class="col-sm-6 col-md-3 col-lg-2">
          <input type="date" class="form-control" name="f_fin" value="<?php echo htmlspecialchars($f_fin); ?>" placeholder="Hasta">
        </div>
        <div class="col-auto">
          <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Buscar</button>
          <?php if ($q !== '' || $comite_id>0 || $f_ini!=='' || $f_fin!=='' || isset($_GET['estado'])): ?>
            <a class="btn btn-outline-secondary" href="ProyectosRegistrados.php">Limpiar</a>
          <?php endif; ?>
        </div>
      </form>

      <?php if (empty($proyectos_aprobados)): ?>
        <div class="alert alert-warning">No hay proyectos que coincidan con los filtros.</div>
      <?php else: ?>
        <ul class="list-group">
          <?php foreach ($proyectos_aprobados as $p): ?>
            <?php list($lbl,$bdg) = estadoInfo((int)$p['aprobado']); ?>
            <li class="list-group-item"
                data-proyecto-id="<?php echo (int)$p['id_aprobado']; ?>"
                data-proyecto-nombre="<?php echo htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8'); ?>">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <div class="fw-semibold"><?php echo htmlspecialchars($p['nombre']); ?></div>
                  <small class="text-muted">
                    Comité #<?php echo (int)$p['comite_id']; ?> —
                    T: <?php echo htmlspecialchars($p['tutor_nombre']); ?> /
                    A1: <?php echo htmlspecialchars($p['asesor1_nombre']); ?> /
                    A2: <?php echo htmlspecialchars($p['asesor2_nombre']); ?> ·
                    Fecha: <?php echo htmlspecialchars(substr($p['fecha_creacion'],0,10)); ?>
                  </small>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <!-- Enlace directo (sin popup) -->
                  <a class="btn btn-sm btn-outline-info"
                     href="NotasProyecto.php?id=<?php echo (int)$p['id_aprobado']; ?>&nombre=<?php echo rawurlencode($p['nombre']); ?>">
                    <i class="bi bi-journal-text"></i> Notas
                  </a>
                  <span class="badge bg-<?php echo $bdg; ?>"><?php echo $lbl; ?></span>
                </div>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </main>

  <?php include 'footer.php'; ?>

   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
 </body>
 </html>