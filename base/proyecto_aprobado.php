<?php
//session_start();
require_once 'inc/db/db.php';

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
while ($result && $row = mysqli_fetch_assoc($result)) {
    $estudiantes[] = $row;
}

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
$sql_prop = "SELECT p.title
             FROM tfg_proposals p
             WHERE NOT EXISTS (
               SELECT 1 FROM proyecto_aprobado pa WHERE pa.nombre = p.title
             )
             ORDER BY p.title";
$res_prop = mysqli_query($id_con, $sql_prop);
while ($res_prop && $r = mysqli_fetch_assoc($res_prop)) { $propuestas[] = $r; }

$mensaje = '';
if (isset($_GET['ok']))  $mensaje = '<div style="color:green;">exitoso</div>';
if (isset($_GET['err'])) $mensaje = '<div style="color:red;">fallido Intente nuevamente</div>';

$fecha_actual = date('Y-m-d');

// Configura el head estandarizado
$page_title = 'Registrar proyecto aprobado';
$inlineStyles = <<<'CSS'
:root {
  --white:#fcfdfd; --navy-dark:#092567; --navy-mid:#0e4d93; --sky:#aacef5;
  --red:#bd1016; --red-mid:#a80f10; --red-dark:#8f0b0b; --red-light:#f4c4c4;
}
body{margin:0;min-height:100vh;background:var(--white);font-family:sans-serif;}
.hero{height:220px;background:linear-gradient(180deg,var(--red),var(--red-dark) 60%);position:relative;}
.hero svg{position:absolute;bottom:0;width:100%;height:70%;}
form{width:80%;max-width:500px;margin:0 auto 60px;display:flex;flex-direction:column;gap:15px;background:#fff;padding:30px 40px;border-radius:10px;box-shadow:0 2px 12px rgba(9,37,103,.08);}
label{font-weight:bold;color:var(--navy-dark);}
input,select,button{padding:8px;font-size:1rem;border-radius:4px;border:1px solid #ccc;}
input[type="file"]{border:none;}
button{background:var(--navy-mid);color:#fff;border:none;cursor:pointer;transition:background .3s;margin-top:10px;}
button:hover{background:var(--navy-dark);}
.btn-tfg{display:inline-block;background:#1e73be;color:#fff;padding:10px 18px;border-radius:6px;font-size:14px;font-family:Arial,sans-serif;cursor:pointer;text-align:center;box-shadow:0 2px 4px rgba(0,0,0,.2);transition:background-color .3s;}
.btn-tfg:hover{background:#155a92;}
.hero svg polygon:nth-of-type(1){fill:var(--red-mid);opacity:.95;}
.hero svg polygon:nth-of-type(2){fill:var(--red-dark);opacity:.85;}
.hero svg polygon:nth-of-type(3){fill:var(--red-light);opacity:.18;}
CSS;
?>
<!doctype html>
<html lang="es">
<?php include __DIR__ . '/head.php'; ?>
<body>

  <?php include __DIR__ . '/header.php'; ?>

  <!-- Ribbon eliminado -->

  <!-- Ajuste de margen superior tras eliminar la cinta -->
  <div style="height:35px;"></div>

  <div style="width:80%;margin:0 auto 30px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
    <?php if ($mensaje) echo $mensaje; ?>
    <a href="panel_ctfg.php" style="text-decoration:none;">
      <button type="button" style="background:#555;margin:0;">Regresar al panel comité</button>
    </a>
  </div>

  <form action="mod/admin/users/tfg_aprobado_update.php" method="post" enctype="multipart/form-data">
    <!-- Panel de identificador solo lectura -->
    <label>Código identificador (solo lectura):</label>
    <input type="text" value="<?php echo htmlspecialchars($identificador_preview); ?>" readonly>
    <input type="hidden" name="identificador" value="<?php echo htmlspecialchars($identificador_preview); ?>">

    <!-- MOVIDO: Tipo de trabajo antes del nombre del proyecto -->
    <label for="tipo_proyecto">Tipo de trabajo:</label>
    <select id="tipo_proyecto" name="tipo_proyecto" required>
      <option value="">Seleccione tipo</option>
      <option value="tesis">Tesis (máx 2)</option>
      <option value="proyecto">Proyecto de Graduación (máx 3)</option>
      <option value="seminario">Seminario (máx 8)</option>
    </select>
    <small id="limitHint" style="color:#555;">Seleccione entre 1 y 8.</small>

    <label for="nombre">Nombre del proyecto (desde propuestas):</label>
    <select id="nombre" name="nombre" required>
      <option value="">Seleccione un título</option>
      <?php foreach ($propuestas as $p): ?>
        <option value="<?php echo htmlspecialchars($p['title']); ?>">
          <?php echo htmlspecialchars($p['title']); ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php if (empty($propuestas)): ?>
      <small style="color:#b00;">No hay propuestas disponibles (todas ya registradas o ninguna cargada).</small>
    <?php endif; ?>

    <label for="estudiante">Estudiante:</label>
    <!--
    <select id="estudiante" name="estudiante" required>
      <option value="">Selecciona un estudiante</option>
      <?php foreach ($estudiantes as $est) : ?>
        <option value="<?php echo $est['id']; ?>"><?php echo htmlspecialchars($est['nombre']); ?></option>
      <?php endforeach; ?>
    </select>
    -->
    <div style="max-height:240px;overflow:auto;border:1px solid #ccc;padding:8px;border-radius:6px;" id="chkBoxWrap">
      <?php foreach ($estudiantes as $est): ?>
        <label style="display:block;font-size:13px;">
          <input type="checkbox" name="estudiantes[]" value="<?php echo $est['id']; ?>"> 
          <?php echo htmlspecialchars($est['nombre']); ?>
        </label>
      <?php endforeach; ?>
    </div>
    <div id="estCount" style="font-size:12px;color:#092567;margin-top:4px;">0 seleccionados</div>

    <label for="comite">Comité asesor:</label>
    <select name="comite" id="comite" required>
      <option value="">Selecciona un comité</option>
      <?php foreach ($comites as $r): ?>
        <option value="<?php echo $r['Id']; ?>">
          Comité #<?php echo $r['Id']; ?> - T: <?php echo htmlspecialchars($r['tutor_nombre']); ?> / A1: <?php echo htmlspecialchars($r['asesor1_nombre']); ?> / A2: <?php echo htmlspecialchars($r['asesor2_nombre']); ?>
        </option>
      <?php endforeach; ?>
    </select>

    <!-- Nuevo: Estado de aprobación -->
    <label for="aprobado">Estado de aprobación:</label>
    <select id="aprobado" name="aprobado" required>
      <option value="">Seleccione estado</option>
      <option value="1">Aprobado</option>
      <option value="2">Sin aprobar</option>
      <option value="3">Esperando correcciones</option>
    </select>
    <small style="color:#555;">Se enviará como tinyint (1–3) a la base de datos.</small>

    <label for="documento">Documento (Word, PDF, Excel):</label>
    <label for="documento" class="btn-tfg">Subir documento</label>
    <input type="file" id="documento" name="documento" accept=".pdf,.doc,.docx,.xls,.xlsx" required hidden>

    <!-- Miniatura del documento -->
    <div id="previewBox" style="display:none;align-items:center;gap:10px;margin:10px 0;">
      <img id="previewIcon" src="" alt="icono" style="width:40px;height:40px;">
      <span id="previewName" style="font-size:0.95rem;color:#092567;"></span>
    </div>

    <label for="fecha_aprobacion">Fecha de aprobación:</label>
    <input type="date" id="fecha_aprobacion" name="fecha_aprobacion" value="<?php echo $fecha_actual; ?>" required>
    <small style="color:#555;">Seleccione la fecha exacta de aprobación.</small>

    <!-- Solo visual: fecha final (+1 año) -->
    <label>Fecha finalizacion:</label>
    <div id="fecha_finalizacion_view" style="padding:8px;border:1px solid #ccc;border-radius:4px;background:#f5f7fa;color:#092567;font-size:.95rem;">
      <!-- se llena vía JS -->
    </div>

    <button type="submit">Registrar proyecto</button>
    <a href="panel_ctfg.php" style="text-align:center;text-decoration:none;">
      <button type="button" style="width:100%;background:#6c757d;">Regresar al panel comité</button>
    </a>
  </form>

  <script>
    // Icono único de aceptación
    const ICON_URL = 'https://www.pngfind.com/pngs/m/56-561014_doble-check-azul-png-check-de-whatsapp-transparent.png';

    // Mostrar el input file al hacer click en el label
    document.querySelector('.btn-tfg').onclick = function(e) {
      e.preventDefault();
      document.getElementById('documento').click();
    };

    // Mostrar miniatura al seleccionar archivo (siempre el mismo ícono)
    document.getElementById('documento').addEventListener('change', function(e) {
      const file = e.target.files[0];
      const box  = document.getElementById('previewBox');
      if (!file) {
        box.style.display = 'none';
        return;
      }
      document.getElementById('previewIcon').src = ICON_URL;
      document.getElementById('previewName').textContent = file.name;
      box.style.display = 'flex';
    });

    // Mostrar fecha final (+1 año) solo en front-end
    function calcularFechaFinal() {
      const f = document.getElementById('fecha_aprobacion').value;
      const box = document.getElementById('fecha_finalizacion_view');
      if (!f) { box.textContent = '—'; return; }
      const dt = new Date(f + 'T00:00:00');
      dt.setFullYear(dt.getFullYear() + 1);
      // Formato DD/mm/yyyy
      const dd = String(dt.getDate()).padStart(2,'0');
      const mm = String(dt.getMonth() + 1).padStart(2,'0');
      const yyyy = dt.getFullYear();
      box.textContent = `${dd}/${mm}/${yyyy}`;
    }
    document.getElementById('fecha_aprobacion').addEventListener('change', calcularFechaFinal);
    calcularFechaFinal();

    // Contador de estudiantes seleccionados
    (function(){
      const wrap      = document.getElementById('chkBoxWrap');
      const counter   = document.getElementById('estCount');
      const tipoSel   = document.getElementById('tipo_proyecto');
      const hint      = document.getElementById('limitHint');
      const limits    = { tesis:2, proyecto:3, seminario:8 };
      function maxAllowed(){
        return limits[tipoSel.value] || 8; // default 8 si no seleccionado
      }
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
          // desmarca sobrantes (mantén los primeros max)
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
  </script>
</body>
</html>

