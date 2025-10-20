<?php
require_once __DIR__ . '/inc/db/db.php';
require_once __DIR__ . '/lib/mysession/mySession.conf.php';
require_once __DIR__ . '/lib/mysession/mySession.class.php';
$mySessionController = mySession::getIstance($_MYSESSION_CONF);

// Generar Identificador unico 
if (empty($_SESSION['identificador_preview'])) {
  $_SESSION['identificador_preview'] = 'UNA-TFG-' . str_pad((string)rand(0,9999), 4, '0', STR_PAD_LEFT) . '-' . date('Y');
}
$identificador_preview = $_SESSION['identificador_preview'];

//Contenedor de solo estudiantes
$estudiantes = [];
$sql = "SELECT u.id, u.nombre
        FROM sis_user u
        INNER JOIN sis_login l ON l.id = u.id
        WHERE l.id_roll = 4
        ORDER BY u.nombre";
$result = mysqli_query($id_con, $sql);
while ($result && $row = mysqli_fetch_assoc($result)) { $estudiantes[] = $row; }

// Contenedor de comités
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
// Valor inicial de finalización (+1 año) calculado en servidor
$fecha_finalizacion_ini = date('Y-m-d', strtotime($fecha_actual . ' +1 year'));

// Head/estilos
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
      <h1 class="page-title mb-3">Registrar proyecto aprobado</h1>

      <?php if ($mensaje) echo $mensaje; ?>

      <div class="mb-3">
        <a href="panel_ctfg.php" class="btn btn-secondary">Regresar al panel comité</a>
      </div>

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
              value="<?php echo (int)$p['proposal_id']; ?>"
              data-registered-id="<?php echo (int)$p['registered_id']; ?>">
              <?php echo htmlspecialchars($p['title']); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (empty($propuestas)): ?>
          <small style="color:#b00;">No hay propuestas disponibles.</small>
        <?php endif; ?>

        <!-- Estudiantes: ahora se llenan dinámicamente desde project_members -->
        <div id="estudiantesSection" style="display:none;">
          <label for="estudiante">Estudiantes:</label>
          <div id="chkBoxWrap" style="max-height:240px;overflow:auto;border:1px solid #ccc;padding:8px;border-radius:6px;">
            <!-- Se inyectan los checkboxes aquí -->
          </div>
          <div id="estCount" style="font-size:12px;color:#092567;margin-top:4px;">0 seleccionados</div>
        </div>

        <!-- Panel Estudiante (tabla informativa) -->
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
                <!-- Filas dinámicas -->
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
        <small style="color:#555;">Se enviará como tinyint (1–3) a la base de datos.</small>

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
        <a href="panel_ctfg.php" class="btn btn-secondary mt-2">Regresar al panel comité</a>
      </form>
    </div>
  </main>
  <?php include 'footer.php'; ?>

  <script>
    // Icono único de aceptación
    const ICON_URL = 'https://w1.pngwing.com/pngs/341/112/png-transparent-green-grass-symbol-logo-dialog-box-accept-yellow-circle.png';

    // Mostrar el input file al hacer click en el label
    document.querySelector('.btn-tfg').onclick = function(e) {
      e.preventDefault();
      document.getElementById('documento').click();
    };

    // Mostrar miniatura al seleccionar archivo (siempre el mismo ícono)
    document.getElementById('documento').addEventListener('change', function(e) {
      const file = e.target.files[0];
      const box  = document.getElementById('previewBox');
      if (!file) { box.style.display = 'none'; return; }
      document.getElementById('previewIcon').src = ICON_URL;
      document.getElementById('previewName').textContent = file.name;
      box.style.display = 'flex';
    });

    // Utilidades de fecha robustas
    function toYMD(d){
      const dd = String(d.getDate()).padStart(2,'0');
      const mm = String(d.getMonth()+1).padStart(2,'0');
      const yyyy = d.getFullYear();
      return `${yyyy}-${mm}-${dd}`;
    }
    function parseYMD(s){
      const parts = (s || '').split('-');
      if (parts.length !== 3) return null;
      const y = Number(parts[0]), m = Number(parts[1]), d = Number(parts[2]);
      if (!y || !m || !d) return null;
      return { y, m, d };
    }
    // Suma 1 año preservando el día; si no existe (p. ej. 29/02), usa el último día del mes
    function addOneYearYMD(ymd){
      const p = parseYMD(ymd);
      if (!p) return '';
      const targetYear = p.y + 1;
      const daysInTargetMonth = new Date(targetYear, p.m, 0).getDate(); // día 0 => último del mes
      const day = Math.min(p.d, daysInTargetMonth);
      return toYMD(new Date(targetYear, p.m - 1, day));
    }

    function calcularFechaFinal(){
      const fa = document.getElementById('fecha_aprobacion');
      const finHidden = document.getElementById('fecha_finalizacion');
      const finView   = document.getElementById('fecha_finalizacion_view');
      if (!fa || !finHidden || !finView) return;

      const ymd = addOneYearYMD(fa.value || '');
      finHidden.value = ymd || '';
      finView.value   = ymd || '';
    }

    document.addEventListener('DOMContentLoaded', () => {
      const fa  = document.getElementById('fecha_aprobacion');

      calcularFechaFinal();              // inicial

      if (fa){
        fa.addEventListener('input',  calcularFechaFinal);
        fa.addEventListener('change', calcularFechaFinal);
        fa.addEventListener('blur',   calcularFechaFinal);
      }

      // Al cambiar la propuesta, buscar autor(es) y preseleccionar estudiantes relacionados
      const nombreSel = document.getElementById('nombre');
      const wrap      = document.getElementById('chkBoxWrap');
      const section   = document.getElementById('estudiantesSection');
      const tipoSel   = document.getElementById('tipo_proyecto');
      const panel     = document.getElementById('panelEstudiante');
      const panelBody = document.getElementById('panelEstudianteBody');
      const panelCount= document.getElementById('panelEstudianteCount');

      function maxAllowed(){
        const v = (tipoSel && tipoSel.value) || '';
        if (v === 'tesis') return 2;
        if (v === 'proyecto') return 3;
        return 8; // seminario u otros
      }

      // Reemplaza toArraySafe por una normalización estricta
      function toArrayStrict(v){
        if (Array.isArray(v)) return v;                 // Array real
        if (v && typeof v === 'object' && 'length' in v) return Array.from(v); // NodeList, jQuery, etc.
        if (v && typeof v === 'object') return Object.values(v);               // {0:...,1:...}
        return [];
      }
      function escapeHtml(s){ return String(s||'').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }

      // Reemplaza toda la función por esta versión sin map/join
      function renderMembers(list){
        const arr = toArrayStrict(list);
        let html = '';
        for (let i = 0; i < arr.length; i++){
          const m = arr[i] || {};
          html += `<label style="display:block;font-size:13px;">
            <input type="checkbox" name="estudiantes[]" value="${escapeHtml(m.id||'')}" checked>
            ${escapeHtml(m.nombre||'')}
          </label>`;
        }
        const el = document.getElementById('chkBoxWrap');
        if (!el) return;
        el.innerHTML = html || '<small style="color:#555;">Sin miembros registrados para esta propuesta.</small>';
        el.dispatchEvent(new Event('change', { bubbles:true }));
      }

      // Reemplaza toda la función por esta versión sin map/join
      function renderStudentPanel(list){
        const arr = toArrayStrict(list);
        const panel     = document.getElementById('panelEstudiante');
        const panelBody = document.getElementById('panelEstudianteBody');
        const panelCount= document.getElementById('panelEstudianteCount');
        if (!panel || !panelBody || !panelCount) return;

        if (!arr.length){
          panelBody.innerHTML = '<tr><td colspan="2" class="text-muted">Sin estudiantes.</td></tr>';
          panelCount.textContent = '0 estudiante(s) encontrado(s).';
          panel.style.display = '';
          return;
        }
        let rows = '';
        for (let i = 0; i < arr.length; i++){
          const m = arr[i] || {};
          rows += `<tr><td><code>${escapeHtml(m.id||'')}</code></td><td>${escapeHtml(m.nombre||'')}</td></tr>`;
        }
        panelBody.innerHTML = rows;
        panelCount.textContent = `${arr.length} estudiante(s) encontrado(s).`;
        panel.style.display = '';
      }

      async function cargarMiembrosPorPropuesta(){
        const nombreSel = document.getElementById('nombre');
        const section   = document.getElementById('estudiantesSection');
        const wrap      = document.getElementById('chkBoxWrap');
        const tipoSel   = document.getElementById('tipo_proyecto');
        const panel     = document.getElementById('panelEstudiante');
        const panelBody = document.getElementById('panelEstudianteBody');
        const panelCount= document.getElementById('panelEstudianteCount');

        if (!nombreSel || !wrap) return;
        const opt = nombreSel.options[nombreSel.selectedIndex];
        const proposalId   = nombreSel.value;
        const registeredId = opt ? (opt.dataset.registeredId || '') : '';
        if (!proposalId && !registeredId) {
          if (section) section.style.display = 'none';
          wrap.innerHTML = '';
          if (panel){ panel.style.display = 'none'; panelBody.innerHTML=''; panelCount.textContent=''; }
          return;
        }

        // Construir body
        const params = new URLSearchParams();
        if (proposalId)   params.append('proposal_id', proposalId);
        if (registeredId) params.append('registered_id', registeredId);
        params.append('debug', '1');

        // URL del endpoint
        const url = new URL('mod/admin/users/project_members_by_proposal.php', window.location.href).toString();

        let raw = '';
        let data = null;

        // 1) Solo la petición/red en este try
        try {
          const resp = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString(),
            credentials: 'same-origin'
          });
          raw = await resp.text();
          try { data = JSON.parse(raw); } catch {_=>{}}

          if (!resp.ok || !data || (data._meta && data._meta.ok === false)) {
            if (section) section.style.display = '';
            wrap.innerHTML = '<small style="color:#b00;">Error: '+escapeHtml(data && data._meta && data._meta.msg ? data._meta.msg : ('HTTP '+resp.status))+'</small>'
                           + (raw ? '<pre style="white-space:pre-wrap;margin:6px 0 0;color:#900;background:#fee;padding:6px;border-radius:4px;">'+escapeHtml(raw)+'</pre>' : '');
            if (panel){ panel.style.display=''; panelBody.innerHTML='<tr><td colspan="2" class="text-danger">Error.</td></tr>'; panelCount.textContent=''; }
            console.error('Endpoint error/JSON:', { url, status: resp.status, raw, data });
            return;
          }
        } catch (e) {
          if (section) section.style.display = '';
          wrap.innerHTML = '<small style="color:#b00;">Error de red: '+escapeHtml(e && e.message)+'</small>';
          if (panel){ panel.style.display=''; panelBody.innerHTML='<tr><td colspan="2" class="text-danger">Error de red.</td></tr>'; panelCount.textContent=''; }
          console.error('Fetch error:', e);
          return;
        }

        // 2) Render en bloque separado (si algo falla veremos el mensaje real)
        try {
          const members = toArrayStrict(data && data.members);
          renderMembers(members);
          renderStudentPanel(members);
          if (section) section.style.display = '';

          const limite = (function(){
            const v = (tipoSel && tipoSel.value) || '';
            if (v === 'tesis') return 2;
            if (v === 'proyecto') return 3;
            return 8;
          })();

          const wrapEl = document.getElementById('chkBoxWrap');
          if (wrapEl){
            const checks = Array.from(wrapEl.querySelectorAll('input[type=checkbox]'));
            if (checks.filter(c => c.checked).length > limite){
              checks.forEach((c, i) => { c.checked = i < limite; });
              wrapEl.dispatchEvent(new Event('change', { bubbles:true }));
              alert('Se ajustó la selección al máximo permitido: ' + limite);
            }
          }
          console.info('Miembros recibidos:', data && data._meta, members);
        } catch (e) {
          if (section) section.style.display = '';
          wrap.innerHTML = '<small style="color:#b00;">Error en render: '+escapeHtml(e && e.message)+'</small>';
          if (panel){ panel.style.display=''; panelBody.innerHTML='<tr><td colspan="2" class="text-danger">Error de render.</td></tr>'; panelCount.textContent=''; }
          console.error('Render error:', e, { raw, data });
        }
      }

      if (nombreSel) {
        if (nombreSel.value) { cargarMiembrosPorPropuesta(); } else { section.style.display = 'none'; if (panel) panel.style.display='none'; }
        nombreSel.addEventListener('change', cargarMiembrosPorPropuesta);
      }
    });

    // Contador de estudiantes seleccionados + límites por tipo (se mantiene)
    (function(){
      const wrap      = document.getElementById('chkBoxWrap');
      const counter   = document.getElementById('estCount');
      const tipoSel   = document.getElementById('tipo_proyecto');
      const hint      = document.getElementById('limitHint');
      const limits    = { tesis:2, proyecto:3, seminario:8 };
      function maxAllowed(){ return limits[tipoSel.value] || 8; }
      function update(){
        const n = wrap.querySelectorAll('input[type=checkbox]:checked').length;
        counter.textContent = n + ' seleccionados';
        if (hint) hint.textContent = 'Seleccione entre 1 y ' + maxAllowed() + '.';
      }
      wrap.addEventListener('change', function(e){
        if (e.target.type === 'checkbox'){
          const max = maxAllowed();
          const checked = wrap.querySelectorAll('input[type=checkbox]:checked');
          if (checked.length > max){
            e.target.checked = false;
            alert('Máximo permitido: ' + max);
          }
          update();
        }
      });
      if (tipoSel) {
        tipoSel.addEventListener('change', function(){
          const max = maxAllowed();
          const checked = [...wrap.querySelectorAll('input[type=checkbox]:checked')];
          if (checked.length > max){
            checked.slice(max).forEach(c => c.checked = false);
            alert('Se ajustó la selección al nuevo máximo: ' + max);
          }
          update();
        });
      }
      update();
    })();
  </script>
<?php if (ob_get_level()) { ob_end_flush(); } ?>
</body>
</html>

