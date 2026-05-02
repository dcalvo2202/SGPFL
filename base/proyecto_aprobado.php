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

// Helper: validar/generar identificador único contra DB
function pa_ident_exists(mysqli $db, string $ident): bool {
  if ($ident === '') return false;
  $stmt = mysqli_prepare($db, "SELECT 1 FROM proyecto_aprobado WHERE identificador = ? LIMIT 1");
  if (!$stmt) return false;
  mysqli_stmt_bind_param($stmt, "s", $ident);
  mysqli_stmt_execute($stmt);
  $rs = mysqli_stmt_get_result($stmt);
  $exists = $rs && mysqli_fetch_row($rs);
  mysqli_stmt_close($stmt);
  return (bool)$exists;
}
function pa_generar_ident_unico(mysqli $db): string {
  for ($i=0; $i<30; $i++) {
    $ident = 'UNA-TFG-' . str_pad((string)random_int(0,9999), 4, '0', STR_PAD_LEFT) . '-' . date('Y');
    if (!pa_ident_exists($db, $ident)) return $ident;
  }
  return 'UNA-TFG-' . strtoupper(bin2hex(random_bytes(2))) . '-' . date('Y');
}

// Generar Identificador unico 
if (!isset($_SESSION['identificador_preview'])) {
  $_SESSION['identificador_preview'] = pa_generar_ident_unico($id_con);
} else {

  if (pa_ident_exists($id_con, $_SESSION['identificador_preview'])) {
    $_SESSION['identificador_preview'] = pa_generar_ident_unico($id_con);
  }
}
$identificador_preview = $_SESSION['identificador_preview'];

$estudiantes = [];
$sql = "SELECT u.id, u.nombre
        FROM sis_user u
        INNER JOIN sis_login l ON l.id = u.id
        WHERE l.id_roll = 4
        ORDER BY u.nombre";
$result = mysqli_query($id_con, $sql);
while ($result && $row = mysqli_fetch_assoc($result)) { $estudiantes[] = $row; }

$comites = [];
$sql_comite = "SELECT c.Id,
                      t.nombre  AS tutor_nombre,
                      a1.nombre AS asesor1_nombre,
                      a2.nombre AS asesor2_nombre
                FROM comite c
                JOIN sis_user t  ON t.id  = c.tutor
                JOIN sis_user a1 ON a1.id = c.asesor_1
                JOIN sis_user a2 ON a2.id = c.asesor_2
                ORDER BY c.Id";
$result_comite = mysqli_query($id_con, $sql_comite);
while ($result_comite && $row = mysqli_fetch_assoc($result_comite)) { $comites[] = $row; }

$propuestas = [];
$sql_prop = "SELECT 
               rp.id               AS registered_id,
               rp.tfg_proposal_id  AS proposal_id,
               tp.title
             FROM registered_projects rp
             JOIN tfg_proposals tp ON tp.id = rp.tfg_proposal_id
             WHERE tp.status = 'Cumple requisitos'
               AND NOT EXISTS (
               SELECT 1 FROM proyecto_aprobado pa WHERE pa.nombre = tp.title
             )
             ORDER BY tp.title";
$res_prop = mysqli_query($id_con, $sql_prop);
while ($res_prop && $r = mysqli_fetch_assoc($res_prop)) { $propuestas[] = $r; }

$mensaje = '';
if (isset($_GET['ok']))  $mensaje = '<div class="alert alert-success py-2 mb-3">Registro exitoso.</div>';
if (isset($_GET['err'])) $mensaje = '<div class="alert alert-danger py-2 mb-3">Registro fallido. Intente nuevamente.</div>';

$fecha_actual = date('Y-m-d');

$fecha_finalizacion_ini = date('Y-m-d', strtotime($fecha_actual . ' +1 year'));

$page_title = 'Registrar proyecto aprobado';
$inlineStyles = <<<'CSS'
/* Estilos unificados (UNA) */
body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;}
.dashboard-header h1{font-weight:700;color:var(--azul-una);margin-bottom:.5rem;}
.dashboard-header .lead{color:#6c757d;}
.form-card{background:#fff;border-radius:12px;box-shadow:0 4px 6px rgba(0,0,0,.08);}
.form-card .card-header{background:#f8f9fa;font-weight:600;color:#034991;}
.form-card .card-body{padding:24px;}
label{font-weight:600;color:#092567;}
.btn-tfg{display:inline-flex;align-items:center;gap:.5rem;background:#1e73be;color:#fff;padding:10px 16px;border-radius:6px;border:none;cursor:pointer;}
.btn-tfg:hover{background:#155a92;}
#previewBox img{width:40px;height:40px;}
.small-hint{color:#6c757d;font-size:1.3rem;}
.form-select{font-size:1.5rem;}
.scrollable-y{max-height:240px;overflow:auto;border:1px solid #ccc;padding:8px;border-radius:6px;}
.text-error{color:#b00;}
.text-success-accent{font-size:1.3rem;color:#09a567;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:420px;}
.bg-validated-light{background:#f5f7fa;}
.text-muted{font-size:.9rem;}
.checkbox-label{display:block;font-size:13px;}
.no-members{color:#555;}
.table-col-id{width:180px;}
CSS;
?>
<!doctype html>
<html lang="es">
<?php include __DIR__ . '/head.php'; ?>
<body class="fondo-una d-flex flex-column min-vh-100">
  <?php include 'header.php'; ?>
  <main class="flex-fill">
    <div class="container my-5">

      <div class="dashboard-header text-center mb-5">
        <h1 class="fw-bold">Registrar proyecto aprobado</h1>
        <p class="lead">Complete los datos para registrar un proyecto aprobado y asociarlo a su comité.</p>
      </div>

      <?php if ($mensaje) echo $mensaje; ?>

      <div class="card form-card shadow-sm">
        <div class="card-header">Registro de proyecto aprobado</div>
        <div class="card-body">
          <form action="mod/admin/users/tfg_aprobado_update.php" method="post" enctype="multipart/form-data">
            <!-- Identificador -->
            <div class="mb-3">
              <label>Código identificador (solo lectura):</label>
              <input type="text" class="form-control" value="<?php echo htmlspecialchars($identificador_preview); ?>" readonly>
              <input type="hidden" name="identificador" value="<?php echo htmlspecialchars($identificador_preview); ?>">
            </div>

            <!-- Tipo de trabajo -->
            <div class="mb-3">
              <label for="tipo_proyecto">Tipo de trabajo:</label>
              <select id="tipo_proyecto" name="tipo_proyecto" class="form-select" required>
                <option value="">Seleccione tipo</option>
                <option value="tesis">Tesis (máx 2)</option>
                <option value="proyecto">Proyecto de Graduación (máx 3)</option>
                <option value="seminario">Seminario (máx 8)</option>
              </select>
              <small id="limitHint" class="small-hint">Seleccione entre 1 y 8.</small>
            </div>

            <!-- Nombre desde propuestas -->
            <div class="mb-3">
              <label for="nombre">Nombre del proyecto (desde propuestas):</label>
              <select id="nombre" name="nombre" class="form-select" required>
                <option value="">Seleccione un título</option>
                <?php foreach ($propuestas as $p): ?>
                  <option
                    value="<?php echo htmlspecialchars($p['title']); ?>"
                    data-proposal-id="<?php echo (int)$p['proposal_id']; ?>"
                    data-registered-id="<?php echo (int)$p['registered_id']; ?>">
                    <?php echo htmlspecialchars($p['title']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <input type="hidden" name="proposal_id" id="proposal_id" value="">
              <input type="hidden" name="registered_id" id="registered_id" value="">
            </div>

            <div id="estudiantesSection" class="mb-3" style="display:none;">
              <label for="estudiante">Estudiantes:</label>
              <div id="chkBoxWrap" class="scrollable-y">
                <!-- Se inyectan los checkboxes aquí -->
              </div>
              <div id="estCount" class="small-hint mt-1">0 seleccionados</div>
            </div>

            <div id="panelEstudiante" class="card p-3 mt-3" style="display:none;">
              <h5 class="mb-2">Panel Estudiante</h5>
              <div class="table-responsive">
                <table class="table table-striped table-sm align-middle mb-2">
                  <thead>
                    <tr>
                      <th style="width:180px;">Cédula</th>
                      <th>Nombre</th>
                    </tr>
                  </thead>
                  <tbody id="panelEstudianteBody">
                  </tbody>
                </table>
              </div>
              <div id="panelEstudianteCount" class="text-muted"></div>
            </div>

            <!-- Comité -->
            <div class="mb-3">
              <label for="comite">Comité asesor:</label>
              <select name="comite" id="comite" class="form-select" required>
                <option value="">Selecciona un comité</option>
                <?php foreach ($comites as $r): ?>
                  <option value="<?php echo $r['Id']; ?>">
                    Comité #<?php echo $r['Id']; ?> - T: <?php echo htmlspecialchars($r['tutor_nombre']); ?> / A1: <?php echo htmlspecialchars($r['asesor1_nombre']); ?> / A2: <?php echo htmlspecialchars($r['asesor2_nombre']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Estado aprobado -->
            <div class="mb-3">
              <label for="aprobado">Estado:</label>
              <select id="aprobado" name="aprobado" class="form-select" required>
                <option value="">Seleccione estado</option>
                <option value="1">Aprobado</option>
                <option value="2">Prorrogado</option>
                <option value="3">Vencido</option>
                <option value="4">Cancelado</option>
              </select>
            </div>

            <!-- Documento -->
            <div class="mb-3">
              <label for="documento">Acuerdo (Word, PDF):</label><br>
              <div class="d-flex align-items-center gap-3 flex-wrap mt-1">
                <label for="documento" class="btn-tfg mb-0"><i class="bi bi-upload"></i> Subir Acuerdo</label>
                <input type="file" id="documento" name="documento" accept=".pdf,.doc,.docx,.xls,.xlsx" required hidden>
<div id="previewBox" class="d-flex align-items-center gap-2" style="display:none;min-width:0;">
                  <img id="previewIcon" alt="Archivo seleccionado" hidden>
                  <span id="previewName" class="text-success-accent"></span>
                </div>
              </div>
            </div>

            <!-- Fechas -->
            <div class="mb-3">
              <label for="fecha_aprobacion">Fecha de aprobación:</label>
              <input type="date" id="fecha_aprobacion" name="fecha_aprobacion" class="form-control" value="<?php echo $fecha_actual; ?>" required>
              <small class="small-hint">Seleccione la fecha exacta de aprobación.</small>
            </div>

            <div class="mb-3">
              <label for="fecha_finalizacion_view" class="mt-2">Fecha finalización:</label>
<input
                type="date"
                id="fecha_finalizacion_view"
                class="form-control bg-validated-light"
                value="<?php echo htmlspecialchars($fecha_finalizacion_ini); ?>"
                style="pointer-events: none;"
                readonly
                onfocus="this.blur()"
                aria-readonly="true"
              >
              <!-- Enviado al backend -->
              <input type="hidden" id="fecha_finalizacion" name="fecha_finalizacion"
                    value="<?php echo htmlspecialchars($fecha_finalizacion_ini); ?>" required>
            </div>

            <div class="mt-3">
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Registrar proyecto
              </button>
            </div>
          </form>
        </div>
      </div>

      <div class="text-center mt-4">
        <a href="panel_ctfg.php" class="btn btn-secondary">
          <i class="bi bi-arrow-left-circle"></i> Volver al panel principal
        </a>
      </div>
    </div>
  </main>
  <?php include 'footer.php'; ?>

  <script>
    // Usa el icono solo cuando haya archivo
    const ICON_URL = 'https://icons.getbootstrap.com/assets/icons/check2-circle.svg';

    // Helper para ocultar el preview cuando no hay archivo
    function hidePreview() {
      const box = document.getElementById('previewBox');
      const img = document.getElementById('previewIcon');
      const name = document.getElementById('previewName');
      if (img) { img.removeAttribute('src'); img.hidden = true; }
      if (name) name.textContent = '';
      if (box) box.style.display = 'none';
    }

    document.querySelector('.btn-tfg').onclick = function(e){
      e.preventDefault();
      document.getElementById('documento').click();
    };

    document.getElementById('documento').addEventListener('change', function(e){
      const file = e.target.files && e.target.files[0];
      const box  = document.getElementById('previewBox');
      const img  = document.getElementById('previewIcon');
      const name = document.getElementById('previewName');

      if (!file) { hidePreview(); return; }

      if (img) {
        img.src = ICON_URL;    // podrías cambiarlo por un icono según extensión
        img.hidden = false;
      }
      if (name) name.textContent = file.name;
      if (box) box.style.display = 'flex';
    });

    // Al cargar, asegúrate de que no se vea nada si no hay archivo
    if (document.readyState !== 'loading') hidePreview();
    else document.addEventListener('DOMContentLoaded', hidePreview);

    function toYMD(d){ const dd=String(d.getDate()).padStart(2,'0'), mm=String(d.getMonth()+1).padStart(2,'0'), yyyy=d.getFullYear(); return `${yyyy}-${mm}-${dd}`; }
    function parseYMD(s){ const p=(s||'').split('-'); if (p.length!==3) return null; const y=+p[0],m=+p[1],d=+p[2]; if(!y||!m||!d)return null; return {y,m,d}; }
    function addOneYearYMD(ymd){ const p=parseYMD(ymd); if(!p)return''; const ty=p.y+1, dim=new Date(ty,p.m,0).getDate(), day=Math.min(p.d,dim); return toYMD(new Date(ty,p.m-1,day)); }
    function calcularFechaFinal(){
      const fa=document.getElementById('fecha_aprobacion'), finH=document.getElementById('fecha_finalizacion'), finV=document.getElementById('fecha_finalizacion_view');
      if(!fa||!finH||!finV) return; const ymd=addOneYearYMD(fa.value||''); finH.value=ymd||''; finV.value=ymd||'';
    }

    function toArr(v){
      if (Array.isArray(v)) return v;
      if (v && typeof v === 'object' && 'length' in v && typeof v.length === 'number') return Array.from(v);
      if (v && typeof v === 'object') return Object.values(v);
      return [];
    }

    function escapeHtml(s){ return String(s||'').replace(/[&<>"']/g, m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }

    function renderMembers(list){
      const arr = toArr(list);
      const el = document.getElementById('chkBoxWrap');
      if (!el) return;

      if (!arr.length){
        el.innerHTML = '<small class="no-members">Sin miembros registrados para esta propuesta.</small>';
      } else {
        let html = '';
        for (let i=0; i<arr.length; i++){
          const m = arr[i] || {};
          html += `<label class="checkbox-label">
            <input type="checkbox" name="estudiantes[]" value="${escapeHtml(m.id||'')}" checked>
            ${escapeHtml(m.nombre||'')}
          </label>`;
        }
        el.innerHTML = html;
      }

      const n = el.querySelectorAll('input[type=checkbox]:checked').length;
      const counter = document.getElementById('estCount');
      if (counter) counter.textContent = n + ' seleccionados';
    }

    function renderStudentPanel(list){
      const arr = toArr(list);
      const panel = document.getElementById('panelEstudiante');
      const panelBody = document.getElementById('panelEstudianteBody');
      const panelCount = document.getElementById('panelEstudianteCount');
      if (!panel || !panelBody || !panelCount) return;

      if (!arr.length){
        panelBody.innerHTML = '<tr><td colspan="2" class="text-muted">Sin estudiantes.</td></tr>';
        panelCount.textContent = '0 estudiante(s) encontrado(s).';
        panel.style.display = '';
        return;
      }

      let rows = '';
      for (let i=0; i<arr.length; i++){
        const m = arr[i] || {};
        rows += `<tr><td><code>${escapeHtml(m.id||'')}</code></td><td>${escapeHtml(m.nombre||'')}</td></tr>`;
      }
      panelBody.innerHTML = rows;
      panelCount.textContent = `${arr.length} estudiante(s) encontrado(s).`;
      panel.style.display = '';
    }

    function getSelectedIds(){
      const sel = document.getElementById('nombre');
      const opt = sel && (sel.selectedOptions && sel.selectedOptions[0] || sel.options[sel.selectedIndex]) || null;
      return {
        pid: opt ? (opt.getAttribute('data-proposal-id')   || opt.dataset.proposalId   || '').trim() : '',
        rid: opt ? (opt.getAttribute('data-registered-id') || opt.dataset.registeredId || '').trim() : ''
      };
    }

    async function cargarMiembrosPorPropuesta(){
      const section = document.getElementById('estudiantesSection');
      const wrap    = document.getElementById('chkBoxWrap');
      const panel   = document.getElementById('panelEstudiante');
      const panelBody  = document.getElementById('panelEstudianteBody');
      const panelCount = document.getElementById('panelEstudianteCount');
      const tipoSel = document.getElementById('tipo_proyecto');

      const ids = getSelectedIds();
      const hidPid = document.getElementById('proposal_id');
      const hidRid = document.getElementById('registered_id');
      if (hidPid) hidPid.value = ids.pid;
      if (hidRid) hidRid.value = ids.rid;

      if (!ids.rid && !ids.pid){
        if (section) section.style.display = 'none';
        if (wrap) wrap.innerHTML = '';
        if (panel){ panel.style.display='none'; panelBody.innerHTML=''; panelCount.textContent=''; }
        return;
      }

      let data = null, raw = '';
      try {
        const body = 'registered_id=' + encodeURIComponent(ids.rid || '') +
                     '&proposal_id='  + encodeURIComponent(ids.pid || '');
        const resp = await fetch('mod/admin/users/project_members_by_proposal.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body,
          credentials: 'same-origin'
        });
        raw = await resp.text();
        try { data = JSON.parse(raw); } catch {}
        if (!data) data = {};
        if (!resp.ok) {
          const msg = (data && data.error) ? (' ' + data.error) : '';
          if (section) section.style.display = '';
          wrap.innerHTML = '<small class="text-error">No se pudo cargar estudiantes (HTTP '+resp.status+').'+escapeHtml(msg)+'</small>';
          if (panel){ panel.style.display=''; panelBody.innerHTML='<tr><td colspan="2" class="text-danger">Error.</td></tr>'; panelCount.textContent=''; }
          console.error('Respuesta inválida:', {status: resp.status, raw});
          return;
        }
      } catch (e) {
        if (section) section.style.display = '';
        wrap.innerHTML = '<small class="text-error">Error de red.</small>';
        if (panel){ panel.style.display=''; panelBody.innerHTML='<tr><td colspan="2" class="text-danger">Error.</td></tr>'; panelCount.textContent=''; }
        console.error('Fetch error:', e);
        return;
      }

      try {
        const members = toArr((data && data.members) || []);
        renderMembers(members);
        renderStudentPanel(members);
        if (section) section.style.display = '';

        const wrapBox = document.getElementById('chkBoxWrap');
        const tipoSel = document.getElementById('tipo_proyecto');
        const max = (tipoSel && tipoSel.value === 'tesis') ? 2 :
                    (tipoSel && tipoSel.value === 'proyecto') ? 3 : 8;
        const checks = Array.from((wrapBox||document).querySelectorAll('input[type=checkbox]'));
        if (checks.filter(c => c.checked).length > max){
          checks.forEach((c, i) => c.checked = i < max);
        }
        console.info('Miembros:', members);
      } catch (e) {
        if (section) section.style.display = '';
        wrap.innerHTML = '<small class="text-error">Error en render: ' + escapeHtml(e && e.message) + '</small>';
        if (panel){ panel.style.display=''; panelBody.innerHTML='<tr><td colspan="2" class="text-danger">Error.</td></tr>'; panelCount.textContent=''; }
        console.error('Render error:', e, raw);
      }
    }

    function initProyectoAprobado(){
      calcularFechaFinal();
      const fa = document.getElementById('fecha_aprobacion');
      if (fa) {
        fa.addEventListener('change', calcularFechaFinal);
        fa.addEventListener('input', calcularFechaFinal);
      }

      const sel = document.getElementById('nombre');
      if (sel){
        if (sel.selectedIndex > 0) cargarMiembrosPorPropuesta();
        sel.addEventListener('change', cargarMiembrosPorPropuesta);
      }
    }

    if (document.readyState !== 'loading') initProyectoAprobado();
    else document.addEventListener('DOMContentLoaded', initProyectoAprobado);
  </script>
</body>
</html>