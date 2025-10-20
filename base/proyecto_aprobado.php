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

// Contenedor de nombres de propuestas no asignadas
$propuestas = [];
$sql_prop = "SELECT p.title, p.user_id
             FROM tfg_proposals p
             WHERE NOT EXISTS (
               SELECT 1 FROM proyecto_aprobado pa WHERE pa.nombre = p.title
             )
             ORDER BY p.title";
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
              value="<?php echo htmlspecialchars($p['title']); ?>"
              data-user-id="<?php echo htmlspecialchars($p['user_id'] ?? ''); ?>"
            >
              <?php echo htmlspecialchars($p['title']); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (empty($propuestas)): ?>
          <small style="color:#b00;">No hay propuestas disponibles (todas ya registradas o ninguna cargada).</small>
        <?php endif; ?>

        <!-- Estudiantes (checkbox) -->
        <label for="estudiante">Estudiantes:</label>
        <div id="authorInfo" style="font-size:12px;color:#555;margin-bottom:6px;">
         
        </div>
        <div style="max-height:240px;overflow:auto;border:1px solid #ccc;padding:8px;border-radius:6px;" id="chkBoxWrap">
          <?php foreach ($estudiantes as $est): ?>
            <label style="display:block;font-size:13px;">
              <input type="checkbox" name="estudiantes[]" value="<?php echo $est['id']; ?>"> 
              <?php echo htmlspecialchars($est['nombre']); ?>
            </label>
          <?php endforeach; ?>
        </div>
        <div id="estCount" style="font-size:12px;color:#092567;margin-top:4px;">0 seleccionados</div>

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
      const tipoSel   = document.getElementById('tipo_proyecto');

      function maxAllowed(){
        const v = (tipoSel && tipoSel.value) || '';
        if (v === 'tesis') return 2;
        if (v === 'proyecto') return 3;
        return 8; // seminario u otros
      }

      async function sincronizarEstudiantesPorPropuesta(){
        if (!nombreSel || !wrap) return;
        const title = nombreSel.value;
        if (!title) return;

        try {
          const resp = await fetch('mod/admin/users/tfg_proposal_author.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'title=' + encodeURIComponent(title)
          });
          if (!resp.ok) return;
          const data = await resp.json();
          const ids = Array.isArray(data.user_ids) ? data.user_ids.map(String) : [];

          // Desmarcar todo primero
          const allChecks = Array.from(wrap.querySelectorAll('input[type=checkbox]'));
          allChecks.forEach(c => c.checked = false);

          // Marcar coincidentes respetando el máximo permitido
          const limite = maxAllowed();
          let marcados = 0;

          ids.forEach(id => {
            if (marcados >= limite) return;
            const chk = allChecks.find(c => String(c.value) === id);
            if (chk) {
              chk.checked = true;
              marcados++;
              const lab = chk.closest('label');
              if (lab) { lab.style.background = '#fffbd6'; lab.style.outline = '1px dashed #b9a200';
                setTimeout(() => { lab.style.background=''; lab.style.outline=''; }, 1200);
              }
            }
          });

          // Disparar evento para actualizar contador/validaciones visuales
          wrap.dispatchEvent(new Event('change', { bubbles: true }));
        } catch (e) {
          // Silencioso
        }
      }

      if (nombreSel) {
        nombreSel.addEventListener('change', sincronizarEstudiantesPorPropuesta);
        // Inicial si ya hay una opción seleccionada
        if (nombreSel.value) sincronizarEstudiantesPorPropuesta();
      }
    });

    // Contador de estudiantes seleccionados + límites por tipo
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
        hint.textContent = 'Seleccione entre 1 y ' + maxAllowed() + '.';
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
      tipoSel.addEventListener('change', function(){
        const max = maxAllowed();
        const checked = [...wrap.querySelectorAll('input[type=checkbox]:checked')];
        if (checked.length > max){
          checked.slice(max).forEach(c => c.checked = false);
          alert('Se ajustó la selección al nuevo máximo: ' + max);
        }
        update();
      });
      document.querySelector('form').addEventListener('submit', function(e){
        const n = wrap.querySelectorAll('input[type=checkbox]:checked').length;
        const max = maxAllowed();
        if (!tipoSel.value){
          alert('Seleccione el tipo de trabajo.');
          e.preventDefault(); return;
        }
        if (n < 1 || n > max){
          alert('Debe seleccionar entre 1 y ' + max + ' estudiantes.');
          e.preventDefault();
        }
      });
      update();
    })();

    // Mostrar user_id del autor sugerido y preseleccionar su checkbox si existe
    function updateAuthorFromProposal() {
      const sel = document.getElementById('nombre');
      const info = document.getElementById('authorInfo');
      const wrap = document.getElementById('chkBoxWrap');
      if (!sel || !info || !wrap) return;

      const opt = sel.options[sel.selectedIndex] || null;
      const uid = opt && opt.dataset ? (opt.dataset.userId || '') : '';

      if (uid) {
        const chk = wrap.querySelector('input[type=checkbox][value="' + uid + '"]');
        if (chk) {
          // preseleccionar y resaltar
          chk.checked = true;
          const lab = chk.closest('label');
          if (lab) {
            lab.style.background = '#fffbd6';
            lab.style.outline = '1px dashed #b9a200';
            lab.scrollIntoView({ behavior: 'smooth', block: 'center' });
            // limpiar resaltado después de un rato
            setTimeout(() => { lab.style.background=''; lab.style.outline=''; }, 1500);
          }
          // Disparar change para actualizar contador/límites
          chk.dispatchEvent(new Event('change', { bubbles: true }));
        }
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
      // ...existing code...
      const nombreSel = document.getElementById('nombre');
      if (nombreSel) {
        updateAuthorFromProposal();
        nombreSel.addEventListener('change', updateAuthorFromProposal);
      }
    });
  </script>
<?php if (ob_get_level()) { ob_end_flush(); } ?>
</body>
</html>

