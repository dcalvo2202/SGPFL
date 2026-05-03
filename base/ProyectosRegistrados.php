<?php
include("mod/login/check.php");
include('lang/lang.es');

require_once __DIR__ . '/inc/db/db.php';

require_once __DIR__ . '/lib/mysession/mySession.conf.php';
require_once __DIR__ . '/lib/mysession/mySession.class.php';
$mySessionController = mySession::getIstance($_MYSESSION_CONF);

// 2. OBTENER VARIABLES DE SESIÓN
$current_user_id = $mySessionController->getVar("usuario");
$current_user_name = $mySessionController->getVar("nombre");
$current_user_rol = $mySessionController->getVar("rol");
$base_url = $mySessionController->getVar("cds_domain") . $mySessionController->getVar("cds_locate");

// 3. CONTROL DE ACCESO POR ROL (Solo CTFG, Gestor academico, admin - rol 1, 2,3)
if ($current_user_rol != 2 && $current_user_rol != 1 && $current_user_rol != 3) {
    header('Location: dashboard.php');
    exit;
}

// Título y opciones para el head.php
$page_title   = 'Proyectos registrados';
$inlineStyles = <<<'CSS'
/* Estilos unificados (UNA) */
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
main { padding: 24px 0; }
.dashboard-header h1 { font-weight: 700; color: var(--azul-una); margin-bottom: .5rem; }
.dashboard-header .lead { color: #6c757d; }
.page-title { font-weight: 700; color: var(--azul-una); margin-bottom: .5rem; }

/* Card/table estilo panel_ctfg_review_final_documents */
.document-table { background:#fff; border-radius:12px; box-shadow:0 4px 6px rgba(0,0,0,.1); overflow:hidden; }
.table thead th { font-weight:600; font-size:.95rem; color:#034991; border-bottom:2px solid #dee2e6; }
.table tbody td { vertical-align:middle; }

/* Adaptación para listas (list-group) dentro de document-table */
.document-table .list-group { border-radius:0; }
.document-table .list-group-item { border:0; border-bottom:1px solid #dee2e6; padding:12px 16px; min-height:58px; }
.document-table .list-group-item:last-child { border-bottom:0; }
.document-table .fw-semibold { color:#034991; }

/* Alineación fija de acciones (Notas + estado) */
.list-row { display:flex; align-items:center; gap:12px; width:100%; }
.list-row .content { flex: 1 1 auto; min-width:0; }
.item-actions { margin-left:auto; display:flex; gap:8px; align-items:center; white-space:nowrap; flex-shrink:0; align-self:flex-start; }

/* Badges/empty state */
.status-badge { padding:.35rem .75rem; border-radius:6px; font-size:.875rem; font-weight:600; }
.empty-state { padding: 2.5rem 1.25rem; text-align:center; }
.empty-state i { font-size: 3rem; color: #28a745; margin-bottom: 1rem; }
.empty-state h3 { color:#034991; font-weight:700; }
.empty-state p { color:#6c757d; }

/* Responsive: pila acciones bajo el texto en pantallas pequeñas */
@media (max-width: 576px) {
  .list-row { flex-direction: column; gap: 8px; }
  .item-actions { 
    margin-left: 0;
    margin-top: 8px;
    width: 100%;
    flex-wrap: wrap;
    justify-content: flex-start;
  }
  .document-table .list-group-item { 
    padding: 12px;
    min-height: auto;
  }
  .document-table .fw-semibold {
    font-size: 0.95rem;
    word-break: break-word;
  }
  .document-table .list-group-item small {
    font-size: 0.85rem;
    display: block;
    margin-top: 4px;
  }
}

/* Extra responsive para pantallas muy pequeñas (420px) */
@media (max-width: 420px) {
  .document-table .list-group-item { 
    padding: 10px 8px;
    min-height: auto;
  }
  .document-table .fw-semibold {
    font-size: 0.9rem;
  }
  .item-actions {
    gap: 6px;
  }
  .item-actions button,
  .item-actions a,
  .item-actions form {
    flex: 1 1 auto;
    min-width: 0;
  }
  .item-actions button,
  .item-actions a {
    padding: 0.4rem 0.5rem !important;
    font-size: 0.8rem !important;
  }
  .status-badge {
    padding: 0.25rem 0.5rem !important;
    font-size: 0.75rem !important;
  }
}

/* Extra responsive para pantallas ultra pequeñas (360px) */
@media (max-width: 360px) {
  .document-table .list-group-item { 
    padding: 8px 6px;
  }
  .document-table .fw-semibold {
    font-size: 0.85rem;
  }
  .item-actions {
    gap: 4px;
  }
  .item-actions button,
  .item-actions a {
    padding: 0.35rem 0.4rem !important;
    font-size: 0.75rem !important;
  }
}

/* Filtros responsive */
.form-filters { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 1rem; }
.form-filters input,
.form-filters select {
  flex: 1 1 auto;
  min-width: 150px;
}
.form-filters .btn-group { display: flex; gap: 6px; flex-wrap: wrap; }

@media (max-width: 754px) {
  .form-filters {
    gap: 6px;
  }
  .form-filters input,
  .form-filters select {
    min-width: 0;
    width: 100%;
  }
  .form-filters .btn-group {
    width: 100%;
    justify-content: stretch;
  }
  .form-filters .btn-group button,
  .form-filters .btn-group a {
    flex: 1 1 auto;
    min-width: 0;
  }
}

@media (max-width: 576px) {
  .form-filters {
    gap: 6px;
    margin-bottom: 0.75rem;
  }
  .form-filters input,
  .form-filters select {
    width: 100%;
    min-width: 0;
    font-size: 0.95rem;
    padding: 0.5rem 0.75rem;
  }
  .form-filters .btn-group {
    width: 100%;
    flex-direction: column;
    gap: 6px;
  }
  .form-filters .btn-group button,
  .form-filters .btn-group a {
    width: 100%;
    padding: 0.6rem 1rem;
    font-size: 0.95rem;
  }
  .form-filters .w-100 {
    display: none;
  }
}

@media (max-width: 420px) {
  .form-filters {
    gap: 5px;
  }
  .form-filters input,
  .form-filters select {
    width: 100%;
    font-size: 0.9rem;
    padding: 0.45rem 0.65rem;
  }
  .form-filters .btn-group button,
  .form-filters .btn-group a {
    width: 100%;
    padding: 0.5rem 0.75rem;
    font-size: 0.9rem;
  }
}

@media (max-width: 360px) {
  .form-filters {
    gap: 4px;
  }
  .form-filters input,
  .form-filters select {
    font-size: 0.85rem;
    padding: 0.4rem 0.55rem;
  }
  .form-filters .btn-group button,
  .form-filters .btn-group a {
    padding: 0.45rem 0.6rem;
    font-size: 0.85rem;
  }
}
CSS;

// Filtros (GET ?q=&estado=&comite=&f_ini=&f_fin=&prof=)
$q = trim($_GET['q'] ?? '');
// Estado: 0 = sin filtro (Todos)
$estado = (isset($_GET['estado']) && $_GET['estado'] !== '' && in_array((int)$_GET['estado'], [1,2,3,4], true))
  ? (int)$_GET['estado']
  : 0;

$comite_id = isset($_GET['comite']) ? (int)$_GET['comite'] : 0;
$f_ini = trim($_GET['f_ini'] ?? '');
$f_fin = trim($_GET['f_fin'] ?? '');

// filtro por miembro del comité (ID o nombre)
$prof = trim($_GET['prof'] ?? '');

// filtro por estudiante (ID o nombre)
$estudiante = trim($_GET['estudiante'] ?? '');

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
        case 2: return ['Prorrogado', 'primary'];
        case 3: return ['Vencido', 'danger'];
        case 4: return ['Cancelado', 'secondary'];
        case 0: return ['Todos', 'info'];
        default: return ['Desconocido', 'light'];
    }
}
list($estadoLabel, $estadoBadge) = estadoInfo($estado);

// Construcción de consulta con condiciones dinámicas
$proyectos_aprobados = [];
$sql = "SELECT DISTINCT p.id_aprobado, p.nombre, p.aprobado, p.fecha_creacion, p.comite_id, p.estado,
               COALESCE(t.nombre,'-')  AS tutor_nombre,
               COALESCE(a1.nombre,'-') AS asesor1_nombre,
               COALESCE(a2.nombre,'-') AS asesor2_nombre
        FROM proyecto_aprobado p
        LEFT JOIN comite c ON c.Id = p.comite_id
        LEFT JOIN sis_user t  ON t.id  = c.tutor
        LEFT JOIN sis_user a1 ON a1.id = c.asesor_1
        LEFT JOIN sis_user a2 ON a2.id = c.asesor_2
        LEFT JOIN proyecto_aprobado_estudiantes pae ON pae.id_aprobado = p.id_aprobado
        LEFT JOIN sis_user est ON est.id = pae.estudiante_id";
$conds  = [];
$types  = "";
$params = [];

/**
 * Manejo de "cancelados" (HU)
 * - Si filtro estado=4: mostrar cancelados (HU o el estado viejo)
 * - Si NO es estado=4: excluir cancelados de búsquedas activas
 */
if ($estado === 4) {
  $conds[] = "(UPPER(COALESCE(p.estado,'')) = 'CANCELADO' OR p.aprobado = 4)";
} else {
  $conds[] = "(UPPER(COALESCE(p.estado,'')) <> 'CANCELADO' AND p.aprobado <> 4)";
}

// Agregar condiciones solo si hay filtros
if ($estado > 0 && $estado !== 4) {
    $conds[] = "p.aprobado = ?";
    $types  .= "i";
    $params[] = $estado;
}
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

// Nuevo: condición por profesor (coincide en tutor/asesores por ID o por nombre)
if ($prof !== '') {
    if (preg_match('/^\d+$/', $prof)) {
        $pid = (int)$prof;
        $conds[] = "(c.tutor = ? OR c.asesor_1 = ? OR c.asesor_2 = ?)";
        $types  .= "iii";
        $params[] = $pid;
        $params[] = $pid;
        $params[] = $pid;
    } else {
        $like = "%{$prof}%";
        $conds[] = "(t.nombre LIKE ? OR a1.nombre LIKE ? OR a2.nombre LIKE ?)";
        $types  .= "sss";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
}

// Nuevo: condición por estudiante (coincide por ID o nombre de cualquier estudiante vinculado)
if ($estudiante !== '') {
    if (preg_match('/^\d+$/', $estudiante)) {
        $sid = (int)$estudiante;
        $conds[] = "pae.estudiante_id = ?";
        $types  .= "i";
        $params[] = $sid;
    } else {
        $elike = "%{$estudiante}%";
        $conds[] = "est.nombre LIKE ?";
        $types  .= "s";
        $params[] = $elike;
    }
}

// WHERE solo si hay condiciones
if (!empty($conds)) {
    $sql .= " WHERE " . implode(' AND ', $conds);
}
$sql .= " ORDER BY p.fecha_creacion DESC, p.id_aprobado DESC";

if ($stmt = mysqli_prepare($id_con, $sql)) {
    if (!empty($params)) {
        $bind = [$stmt, $types];
        foreach ($params as $k => $v) { $bind[] = &$params[$k]; }
        call_user_func_array('mysqli_stmt_bind_param', $bind);
    }
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

      <div class="dashboard-header text-center mb-5">
        <h1 class="page-title">Proyectos registrados</h1>
        <p class="lead">Consulte y filtre proyectos Aprobados, Prorrogados, Vencidos o Cancelados.</p>
        <?php if (isset($_GET['ok_cancel'])): ?>
          <div class="alert alert-success py-2 mb-3">Proyecto cancelado correctamente.</div>
        <?php endif; ?>
        <?php if (isset($_GET['err_cancel'])): ?>
          <div class="alert alert-danger py-2 mb-3">
            No se pudo cancelar el proyecto: <?php echo htmlspecialchars($_GET['err_cancel'], ENT_QUOTES, 'UTF-8'); ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Estado seleccionado -->
      <div class="mb-3">
        <span class="badge bg-<?php echo $estadoBadge; ?>"><?php echo $estadoLabel; ?></span>
      </div>

      <!-- Filtros -->
      <form class="form-filters" method="get" action="">
        <input type="search" class="form-control" name="q" placeholder="Buscar por nombre de proyecto..."
               value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off">
        
        <select name="estado" class="form-select">
          <option value="" <?php echo $estado===0?'selected':''; ?>>Todos</option>
          <option value="1" <?php echo $estado===1?'selected':''; ?>>Aprobado</option>
          <option value="2" <?php echo $estado===2?'selected':''; ?>>Prorrogado</option>
          <option value="3" <?php echo $estado===3?'selected':''; ?>>Vencido</option>
          <option value="4" <?php echo $estado===4?'selected':''; ?>>Cancelado</option>
        </select>
        
        <select name="comite" class="form-select">
          <option value="0">Todos los comités</option>
          <?php foreach ($comites as $c): ?>
            <option value="<?php echo (int)$c['Id']; ?>" <?php echo $comite_id===(int)$c['Id']?'selected':''; ?>>
              Comité #<?php echo (int)$c['Id']; ?> — T: <?php echo htmlspecialchars($c['tutor_nombre']); ?> / A1: <?php echo htmlspecialchars($c['asesor1_nombre']); ?> / A2: <?php echo htmlspecialchars($c['asesor2_nombre']); ?>
            </option>
          <?php endforeach; ?>
        </select>
        
        <input type="text" class="form-control" name="prof" placeholder="Miembro del comité (ID o nombre)"
               value="<?php echo htmlspecialchars($prof, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off">

        <input type="text" class="form-control" name="estudiante" placeholder="Estudiante (ID o nombre)"
               value="<?php echo htmlspecialchars($estudiante, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off">
        
        <input type="date" class="form-control" name="f_ini" value="<?php echo htmlspecialchars($f_ini); ?>" placeholder="Desde">
        
        <input type="date" class="form-control" name="f_fin" value="<?php echo htmlspecialchars($f_fin); ?>" placeholder="Hasta">
        
        <div class="btn-group">
          <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Buscar</button>
          <?php if ($q !== '' || $comite_id>0 || $f_ini!=='' || $f_fin!=='' || isset($_GET['estado']) || $prof!=='' || $estudiante!==''): ?>
            <a class="btn btn-outline-secondary" href="ProyectosRegistrados.php">Limpiar</a>
          <?php endif; ?>
        </div>
      </form>

      <?php if (empty($proyectos_aprobados)): ?>
        <div class="card document-table">
          <div class="card-body">
            <div class="empty-state">
              <i class="bi bi-check2-circle"></i>
              <h3>Sin resultados</h3>
              <p class="mb-0">No hay proyectos que coincidan con los filtros actuales.</p>
            </div>
          </div>
        </div>
      <?php else: ?>
        <!-- Listado estandarizado en card -->
        <div class="card document-table">
          <div class="card-body p-0">
            <ul class="list-group list-group-flush">
              <?php foreach ($proyectos_aprobados as $p): ?>
                <?php list($lbl,$bdg) = estadoInfo((int)$p['aprobado']); ?>
                <li class="list-group-item"
                    data-proyecto-id="<?php echo (int)$p['id_aprobado']; ?>"
                    data-proyecto-nombre="<?php echo htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8'); ?>">
                  <div class="list-row">
                    <div class="content">
                      <div class="fw-semibold"><?php echo htmlspecialchars($p['nombre']); ?></div>
                      <small class="text-muted">
                        Comité #<?php echo (int)$p['comite_id']; ?> —
                        T: <?php echo htmlspecialchars($p['tutor_nombre']); ?> /
                        A1: <?php echo htmlspecialchars($p['asesor1_nombre']); ?> /
                        A2: <?php echo htmlspecialchars($p['asesor2_nombre']); ?> ·
                        Fecha: <?php echo htmlspecialchars(substr($p['fecha_creacion'],0,10)); ?>
                      </small>
                    </div>
                    <?php
                      $isCanceladoHU  = (isset($p['estado']) && strtoupper((string)$p['estado']) === 'CANCELADO');
                      $isCanceladoOld = ((int)$p['aprobado'] === 4);
                      $isCancelado    = $isCanceladoHU || $isCanceladoOld;

                      // Badge: si está cancelado por cualquiera, mostrar Cancelado
                      if ($isCancelado) {
                        $lbl = 'Cancelado';
                        $bdg = 'secondary';
                      } else {
                        list($lbl,$bdg) = estadoInfo((int)$p['aprobado']);
                      }
                    ?>

                    <div class="item-actions">
                      <a class="btn btn-sm btn-outline-info"
                        href="NotasProyecto.php?id=<?php echo (int)$p['id_aprobado']; ?>&nombre=<?php echo rawurlencode($p['nombre']); ?>">
                        <i class="bi bi-journal-text"></i> Notas
                      </a>

                      <?php if (((int)$current_user_rol === 3 || (int)$current_user_rol === 2) && !$isCancelado): ?>
                        <a class="btn btn-sm btn-outline-primary"
                          href="mod/admin/users/tfg_upload_defense_agreement.php?id=<?php echo (int)$p['id_aprobado']; ?>">
                          <i class="bi bi-file-earmark-arrow-up"></i> Acuerdo defensa
                        </a>
                      <?php endif; ?>

                      <?php if (!$isCancelado): ?>
                        <form method="post" action="cancelar_proyecto_aprobado.php" class="d-inline" onsubmit="return confirm('¿Cancelar este proyecto?');">
                          <input type="hidden" name="proyecto_id" value="<?php echo (int)$p['id_aprobado']; ?>">
                          <input type="hidden" name="motivo" value="Prueba funcional de cancelación">
                          <input type="hidden" name="observaciones" value="Prueba temporal sin modal">
                          <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-x-octagon"></i> Cancelar
                          </button>
                        </form>
                      <?php endif; ?>

                      <span class="badge bg-<?php echo $bdg; ?>"><?php echo $lbl; ?></span>
                    </div>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      <?php endif; ?>

      <div class="text-center mt-4">
        <a href="panel_ctfg.php" class="btn btn-secondary">
          <i class="bi bi-arrow-left-circle"></i> Volver al panel principal
        </a>
      </div>
    </div>

    <!-- Modal Cancelar -->
    <div class="modal fade" id="modalCancelar" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <form method="post" action="cancelar_proyecto_aprobado.php">
            <div class="modal-header">
              <h5 class="modal-title">Cancelar proyecto</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">
              <input type="hidden" name="proyecto_id" id="cancel_proyecto_id" value="">
              <p class="mb-2">Proyecto: <strong id="cancel_proyecto_nombre"></strong></p>

              <div class="mb-3">
                <label class="form-label">Motivo (requerido)</label>
                <input type="text" name="motivo" class="form-control" maxlength="500" required>
              </div>

              <div class="mb-3">
                <label class="form-label">Observaciones</label>
                <textarea name="observaciones" class="form-control" rows="4"></textarea>
              </div>

              <div class="alert alert-warning mb-0">
                Se validará que el proyecto lleve <strong>≥ 6 meses sin avances</strong> antes de permitir la cancelación (Art. 73 RGPEA).
              </div>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
              <button type="submit" class="btn btn-danger">
                <i class="bi bi-check2-circle"></i> Confirmar cancelación
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </main>

  <?php include 'footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    (function(){
      const modal = document.getElementById('modalCancelar');
      if (!modal) return;

      modal.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        const id = btn.getAttribute('data-id') || '';
        const nombre = btn.getAttribute('data-nombre') || '';
        document.getElementById('cancel_proyecto_id').value = id;
        document.getElementById('cancel_proyecto_nombre').textContent = nombre;
      });
    })();
  </script>
</body>
</html>