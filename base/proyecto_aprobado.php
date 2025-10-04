<?php
session_start();
require_once 'inc/db/db.php';

// Generar identificador (una vez por carga) y guardarlo en sesión
if (empty($_SESSION['identificador_preview'])) {
  $_SESSION['identificador_preview'] = 'UNA-TFG-' . str_pad((string)rand(0,9999), 4, '0', STR_PAD_LEFT) . '-' . date('Y');
}
$identificador_preview = $_SESSION['identificador_preview'];

// Cargar estudiantes (rol = 4)
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

// Cargar comités (nuevo modelo con tutor / asesores)
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

// NUEVO: cargar títulos de propuestas (tfg_proposals)
// Evitar duplicar las que ya están en proyecto_aprobado (opcional)
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
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"> 
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Registrar proyecto aprobado</title>
  <style>
  :root {
    --white: #fcfdfd;
    --navy-dark: #092567;
    --navy-mid: #0e4d93;
    --sky: #aacef5;
    --red: #bd1016;
  }
  body {
    margin: 0;
    min-height: 100vh;
    background: var(--white);
    font-family: sans-serif;
  }
  /* Header azul */
  .hero {
    height: 220px;
    background: linear-gradient(180deg, var(--navy-dark), #0b2b61 60%);
    position: relative;
  }
  .hero svg {
    position: absolute;
    bottom: 0;
    width: 100%;
    height: 70%;
  }
  /* Franja roja */
  .ribbon {
    background: linear-gradient(90deg, var(--red), #a80f10 80%);
    height: 70px;
    margin: 40px auto;
    width: 80%;
    position: relative;
  }
  .ribbon::after {
    content: "";
    position: absolute;
    right: -38px;
    top: 0;
    width: 60px;
    height: 100%;
    transform: skewX(-30deg);
    background: linear-gradient(90deg, #a80f10, #8f0b0b);
    clip-path: polygon(0 0, 100% 50%, 0 100%);
  }
  /* Formulario */
  form {
    width: 80%;
    max-width: 500px;
    margin: 0 auto 60px;
    display: flex;
    flex-direction: column;
    gap: 15px;
    background: #fff;
    padding: 30px 40px;
    border-radius: 10px;
    box-shadow: 0 2px 12px rgba(9,37,103,0.08);
  }
  label {
    font-weight: bold;
    color: var(--navy-dark);
  }
  input, select, button {
    padding: 8px;
    font-size: 1rem;
    border-radius: 4px;
    border: 1px solid #ccc;
  }
  input[type="file"] {
    border: none;
  }
  button {
    background: var(--navy-mid);
    color: white;
    border: none;
    cursor: pointer;
    transition: background .3s;
    margin-top: 10px;
  }
  button:hover {
    background: var(--navy-dark);
  }
   .btn-tfg {
    display: inline-block;
    background-color: #1e73be;
    color: #fff;
    padding: 10px 18px;
    border-radius: 6px;
    font-size: 14px;
    font-family: Arial, sans-serif;
    cursor: pointer;
    text-align: center;
    box-shadow: 0px 2px 4px rgba(0,0,0,0.2);
    transition: background-color 0.3s;
  }
  .btn-tfg:hover {
    background-color: #155a92;
  }

</style>
</head>
<body>
  <header class="hero">
    <svg viewBox="0 0 1200 200" preserveAspectRatio="none" aria-hidden="true">
      <polygon points="0,140 120,110 220,120 360,90 520,120 680,100 840,135 1000,110 1200,140 1200,200 0,200"
               fill="#0e4d93" opacity="0.95"/>
      <polygon points="0,160 100,140 260,150 420,130 620,150 820,140 1000,160 1200,150 1200,200 0,200"
               fill="#0b3b75" opacity="0.85"/>
      <polygon points="0,180 200,170 400,175 600,170 800,175 1000,180 1200,175 1200,200 0,200"
               fill="#aacef5" opacity="0.12"/>
    </svg>
  </header>

  <div class="ribbon"></div>


  <div style="width:80%;margin:0 auto 10px;">
    <?php if ($mensaje) echo $mensaje; ?>
  </div>
  <div style="width:80%;margin:0 auto 10px;">
    <?php
      if (!empty($_SESSION['last_sql_error'])) {
        echo '<pre style="background:#fee;border:1px solid #e99;padding:8px;color:#900;font-size:12px;">'
             . htmlspecialchars($_SESSION['last_sql_error'])
             . '</pre>';
        unset($_SESSION['last_sql_error']);
      }
    ?>
  </div>

  <form action="mod/admin/users/tfg_aprobado_update.php" method="post" enctype="multipart/form-data">
    <!-- Panel de identificador solo lectura -->
    <label>Código identificador (solo lectura):</label>
    <input type="text" value="<?php echo htmlspecialchars($identificador_preview); ?>" readonly>
    <!-- Campo oculto opcional (el servidor usará el de sesión igualmente) -->
    <input type="hidden" name="identificador" value="<?php echo htmlspecialchars($identificador_preview); ?>">

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
    <select id="estudiante" name="estudiante" required>
      <option value="">Selecciona un estudiante</option>
      <?php foreach ($estudiantes as $est) : ?>
        <option value="<?php echo $est['id']; ?>"><?php echo htmlspecialchars($est['nombre']); ?></option>
      <?php endforeach; ?>
    </select>

    <label for="comite">Comité asesor:</label>
    <select name="comite" id="comite" required>
      <option value="">Selecciona un comité</option>
      <?php foreach ($comites as $r): ?>
        <option value="<?php echo $r['Id']; ?>">
          Comité #<?php echo $r['Id']; ?> - T: <?php echo htmlspecialchars($r['tutor_nombre']); ?> / A1: <?php echo htmlspecialchars($r['asesor1_nombre']); ?> / A2: <?php echo htmlspecialchars($r['asesor2_nombre']); ?>
        </option>
      <?php endforeach; ?>
    </select>

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

    <!-- Panel de visualización: Fecha de expiración (1 año después) -->
    <label>Fecha de expiración:</label>
    <div id="expPanel" style="padding:10px;border:1px solid #ccc;border-radius:6px;background:#f5f8fc;font-size:0.95rem;color:#092567;">
      <strong id="fecha_expiracion">
        <?php echo date('Y-m-d', strtotime($fecha_actual . ' +1 year')); ?>
      </strong>
      <span style="display:block;font-size:11px;color:#555;margin-top:4px;">(Generada automáticamente +1 año)</span>
    </div>
    <input type="hidden" id="fecha_expiracion_hidden" name="fecha_expiracion" value="<?php echo date('Y-m-d', strtotime($fecha_actual . ' +1 year')); ?>">

    <button type="submit">Registrar proyecto</button>
  </form>

  <script>
    // Icono único de aceptación
    const ICON_URL = 'data:image/png;base64,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';

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

    function recalcularExpiracion() {
      const f = document.getElementById('fecha_aprobacion').value;
      if (!f) return;
      const parts = f.split('-');
      if (parts.length !== 3) return;
      const d = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
      d.setFullYear(d.getFullYear() + 1);
      // Ajuste si resulta inválido (ej: 29 Feb)
      if (isNaN(d.getTime())) return;
      const yyyy = d.getFullYear();
      const mm = String(d.getMonth() + 1).padStart(2,'0');
      const dd = String(d.getDate()).padStart(2,'0');
      const exp = `${yyyy}-${mm}-${dd}`;
      document.getElementById('fecha_expiracion').textContent = exp;
      document.getElementById('fecha_expiracion_hidden').value = exp;
    }
    document.getElementById('fecha_aprobacion').addEventListener('change', recalcularExpiracion);
    // Inicial
    recalcularExpiracion();
  </script>
</body>
</html>

