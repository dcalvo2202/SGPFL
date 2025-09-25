<?php
session_start();
require_once 'inc/db/db.php';

// Generar identificador (una vez por carga) y guardarlo en sesión
if (empty($_SESSION['identificador_preview'])) {
  $_SESSION['identificador_preview'] = 'UNA-TFG-' . str_pad((string)rand(0,9999), 4, '0', STR_PAD_LEFT) . '-' . date('Y');
}
$identificador_preview = $_SESSION['identificador_preview'];

// Carga de combos
$estudiantes = [];
$sql = "SELECT id, nombre FROM sis_user";
$result = mysqli_query($id_con, $sql);
while ($result && $row = mysqli_fetch_assoc($result)) { $estudiantes[] = $row; }

$comites = [];
$sql_comite = "SELECT Id AS id, integrantes FROM comite"; // OJO: columna es 'Id' en BD
$result_comite = mysqli_query($id_con, $sql_comite);
while ($result_comite && $row = mysqli_fetch_assoc($result_comite)) { $comites[] = $row; }

$categorias = []; 
$sql_categorias = "SELECT idCategoria, nombre, categoria FROM categorias"; // verifica que 'nombre' exista en tu tabla
$result_categorias = mysqli_query($id_con, $sql_categorias);
while ($result_categorias && $row = mysqli_fetch_assoc($result_categorias)) { $categorias[] = $row; }

// Mensajes
$mensaje = '';
if (isset($_GET['ok']))  $mensaje = '<div style="color:green;">exitoso</div>';
if (isset($_GET['err'])) $mensaje = '<div style="color:red;">fallido Intente nuevamente</div>';

// fecha actual
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

  <div style="width:80%;margin:0 auto 30px;">
    <?php if ($mensaje) echo $mensaje; ?>
  </div>

  <form action="mod/admin/users/tfg_aprobado_update.php" method="post" enctype="multipart/form-data">
    <!-- Panel de identificador solo lectura -->
    <label>Código identificador (solo lectura):</label>
    <input type="text" value="<?php echo htmlspecialchars($identificador_preview); ?>" readonly>
    <!-- Campo oculto opcional (el servidor usará el de sesión igualmente) -->
    <input type="hidden" name="identificador" value="<?php echo htmlspecialchars($identificador_preview); ?>">

    <label for="nombre">Nombre del proyecto:</label>
    <input type="text" id="nombre" name="nombre" required>

    <label for="estudiante">Estudiante:</label>
    <select id="estudiante" name="estudiante" required>
      <option value="">Selecciona un estudiante</option>
      <?php foreach ($estudiantes as $est) : ?>
        <option value="<?php echo $est['id']; ?>"><?php echo htmlspecialchars($est['nombre']); ?></option>
      <?php endforeach; ?>
    </select>

    <label for="comite">Comité asesor:</label>
    <select id="comite" name="comite" required>
      <option value="">Selecciona un comité</option>
      <?php foreach ($comites as $com) : ?>
        <option value="<?php echo $com['id']; ?>">
          Comité #<?php echo $com['id']; ?> (Integrantes: <?php echo $com['integrantes']; ?>)
        </option>
      <?php endforeach; ?>
    </select>


    <label for="Categora">Categoría:</label>
   <select id="categoria" name="categoria" required>
  <option value="">Selecciona una categoría</option>
  <?php foreach ($categorias as $cat) : ?>
    <option value="<?php echo $cat['idCategoria']; ?>">
      <?php
        $tipo = ($cat['categoria'] == 1) ? 'multi' : 'inter';
        echo htmlspecialchars($cat['nombre'] . ' (' . $tipo . ')');
      ?>
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
    <input type="date" id="fecha_aprobacion" name="fecha_aprobacion" value="<?php echo $fecha_actual; ?>" readonly>

    <button type="submit">Registrar proyecto</button>
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
  </script>
</body>
</html>