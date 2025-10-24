<?php
include("mod/login/check.php");
include('includes.php');
include('lang/lang.es');

require_once __DIR__ . '/inc/db/db.php';
require_once __DIR__ . '/lib/mysession/mySession.conf.php';
require_once __DIR__ . '/lib/mysession/mySession.class.php';
$mySessionController = mySession::getIstance($_MYSESSION_CONF);

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
  // Si el actual ya fue usado en DB (p.ej., después de guardar + volver atrás), regenerar
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

// Contenedor de nombres de propuestas tomadas desde registered_projects
$propuestas = [];
$sql_prop = "SELECT 
               rp.id               AS registered_id,
               rp.tfg_proposal_id  AS proposal_id,
               tp.title
             FROM registered_projects rp
             JOIN tfg_proposals tp ON tp.id = rp.tfg_proposal_id
             WHERE NOT EXISTS (
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
:root {
  --white:#fcfdfd; --navy-dark:#092567; --navy-mid:#0e4d93; --sky:#aacef5;
  --red:#bd1016; --red-mid:#a80f10; --red-dark:#8f0b0b; --red-light:#f4c4c4;
}
form{width:100%;max-width:600px;margin:0 auto;display:flex;flex-direction:column;gap:15px;background:#fff;padding:30px 40px;border-radius:10px;box-shadow:0 2px 12px rgba(9,37,103,.08);}
label{font-weight:bold;color:var(--navy-dark);}
input,select,button{padding:8px;font-size:1rem;border-radius:4px;border:1px solid #ccc;}
input[type="file"]{border:none;}
button{background:var(--navy-mid);color:#fff;border:none;cursor:pointer;transition:background .3s;margin-top:10px;}
button:hover{background:var(--navy-dark);}
.btn-tfg{display:inline-block;background:#1e73be;color:#fff;padding:10px 18px;border-radius:6px;font-size:14px;font-family:Arial,sans-serif;cursor:pointer;text-align:center;box-shadow:0 2px 4px rgba(0,0,0,0.2);transition:background-color .3s;}
.btn-tfg:hover{background:#155a92;}
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
        <h1 style="font-size: 2.5rem; font-weight: 700;">Registrar proyecto aprobado</h1>
        <p class="lead">Complete los datos para registrar un proyecto aprobado y asociarlo a su comité.</p>
      </div>

      <?php if ($mensaje) echo $mensaje; ?>

      <div class="card shadow-sm">
        <div class="card-body">
          <form action="mod/admin/users/tfg_aprobado_update.php" method="post" enctype="multipart/form-data">
            <!-- Identificador -->
            <label>Código identificador (solo lectura):</label>
            <input type="text" value="<?php echo htmlspecialchars($identificador_preview); ?>" readonly>
            <input type="hidden" name="identificador" value="<?php echo htmlspecialchars($identificador_preview); ?>">

            <!-- Tipo de trabajo -->
            <label for="tipo_proyecto">Tipo de trabajo:</label>
            <select id="tipo_proyecto" name="tipo_proyecto" required>
              <option value="">Seleccione tipo</option>
              <option value="tesis">Tesis (máx 2)</option>
              <option value="proyecto">Proyecto de Graduación (máx 3)</option>
              <option value="seminario">Seminario (máx 8)</option>
            </select>
            <small id="limitHint" style="color:#555;">Seleccione entre 1 y 8.</small>

            <!-- Nombre desde propuestas -->
            <label for="nombre">Nombre del proyecto (desde propuestas):</label>
            <select id="nombre" name="nombre" required>
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

            <div id="estudiantesSection" style="display:none;">
              <label for="estudiante">Estudiantes:</label>
              <div id="chkBoxWrap" style="max-height:240px;overflow:auto;border:1px solid #ccc;padding:8px;border-radius:6px;">
                <!-- Se inyectan los checkboxes aquí -->
              </div>
              <div id="estCount" style="font-size:12px;color:#092567;margin-top:4px;">0 seleccionados</div>
            </div>

            <div id="panelEstudiante" class="card p-3 mt-3" style="display:none;">
              <h5 class="mb-2">Panel Estudiante</h5>
              <div class="table-responsive">
                <table class="table table-striped table-sm align-middle mb-2">
                  <thead>
                    <tr>
                      <th style="width:180px;">user_id</th>
                      <th>Nombre</th>
                    </tr>
                  </thead>
                  <tbody id="panelEstudianteBody">
                  </tbody>
                </table>
              </div>
              <div id="panelEstudianteCount" class="text-muted" style="font-size:.9rem;"></div>
            </div>

            <!-- Comité -->
            <label for="comite">Comité asesor:</label>
            <select name="comite" id="comite" required>
              <option value="">Selecciona un comité</option>
              <?php foreach ($comites as $r): ?>
                <option value="<?php echo $r['Id']; ?>">
                  Comité #<?php echo $r['Id']; ?> - T: <?php echo htmlspecialchars($r['tutor_nombre']); ?> / A1: <?php echo htmlspecialchars($r['asesor1_nombre']); ?> / A2: <?php echo htmlspecialchars($r['asesor2_nombre']); ?>
                </option>
              <?php endforeach; ?>
            </select>

            <!-- Estado aprobado -->
            <label for="aprobado">Estado de aprobación:</label>
            <select id="aprobado" name="aprobado" required>
              <option value="">Seleccione estado</option>
              <option value="1">Aprobado</option>
              <option value="2">Sin aprobar</option>
              <option value="3">Esperando correcciones</option>
            </select>
            

            <!-- Documento -->
            <label for="documento">Documento (Word, PDF):</label>
            <label for="documento" class="btn-tfg">Subir documento</label>
            <input type="file" id="documento" name="documento" accept=".pdf,.doc,.docx,.xls,.xlsx" required hidden>

            <!-- Miniatura del documento -->
            <div id="previewBox" style="display:none;align-items:center;gap:10px;margin:10px 0;">
              <img id="previewIcon" src="" alt="icono" style="width:40px;height:40px;">
              <span id="previewName" style="font-size:0.95rem;color:#092567;"></span>
            </div>

            <!-- Fechas -->
            <label for="fecha_aprobacion">Fecha de aprobación:</label>
            <input type="date" id="fecha_aprobacion" name="fecha_aprobacion" value="<?php echo $fecha_actual; ?>" required>
            <small style="color:#555;">Seleccione la fecha exacta de aprobación.</small>

            <label for="fecha_finalizacion_view" class="mt-2">Fecha finalización:</label>
            <!-- Visible (solo lectura) con valor inicial desde PHP -->
            <input
              type="date"
              id="fecha_finalizacion_view"
              class="form-control"
              value="<?php echo htmlspecialchars($fecha_finalizacion_ini); ?>"
              style="pointer-events:none;background:#f5f7fa;"
              readonly
              onfocus="this.blur()"
              aria-readonly="true"
            >
            <!-- Enviado al backend -->
            <input type="hidden" id="fecha_finalizacion" name="fecha_finalizacion"
                   value="<?php echo htmlspecialchars($fecha_finalizacion_ini); ?>" required>

            <button type="submit">Registrar proyecto</button>
          </form>
        </div>
      </div>

      <div class="text-center mt-4">
        <a href="panel_ctfg.php" class="btn btn-secondary">
          <i class="bi bi-arrow-left-circle"></i> Volver al Panel CTFG
        </a>
      </div>
    </div>
  </main>
  <?php include 'footer.php'; ?>

  <script>
    const ICON_URL = 'https://w1.pngwing.com/pngs/341/112/png-transparent-green-grass-symbol-logo-dialog-box-accept-yellow-circle.png';
    document.querySelector('.btn-tfg').onclick = function(e){ e.preventDefault(); document.getElementById('documento').click(); };
    document.getElementById('documento').addEventListener('change', function(e){
      const file = e.target.files[0], box = document.getElementById('previewBox');
      if (!file) { box.style.display = 'none'; return; }
      document.getElementById('previewIcon').src = ICON_URL;
      document.getElementById('previewName').textContent = file.name;
      box.style.display = 'flex';
    });

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
        el.innerHTML = '<small style="color:#555;">Sin miembros registrados para esta propuesta.</small>';
      } else {
        let html = '';
        for (let i=0; i<arr.length; i++){
          const m = arr[i] || {};
          html += `<label style="display:block;font-size:13px;">
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
          wrap.innerHTML = '<small style="color:#b00;">No se pudo cargar estudiantes (HTTP '+resp.status+').'+escapeHtml(msg)+'</small>';
          if (panel){ panel.style.display=''; panelBody.innerHTML='<tr><td colspan="2" class="text-danger">Error.</td></tr>'; panelCount.textContent=''; }
          console.error('Respuesta inválida:', {status: resp.status, raw});
          return;
        }
      } catch (e) {
        if (section) section.style.display = '';
        wrap.innerHTML = '<small style="color:#b00;">Error de red.</small>';
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
        wrap.innerHTML = '<small style="color:#b00;">Error en render: ' + escapeHtml(e && e.message) + '</small>';
        if (panel){ panel.style.display=''; panelBody.innerHTML='<tr><td colspan="2" class="text-danger">Error.</td></tr>'; panelCount.textContent=''; }
        console.error('Render error:', e, raw);
      }
    }

    function initProyectoAprobado(){
      calcularFechaFinal();
      // Actualizar finalización al seleccionar/cambiar la fecha de aprobación
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
