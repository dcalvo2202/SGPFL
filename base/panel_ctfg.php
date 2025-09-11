<?php
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoge los datos del formulario
    $nombre = trim($_POST['nombre'] ?? '');
    $estudiante = trim($_POST['estudiante'] ?? '');
    $comite = trim($_POST['comite'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $fecha_aprobacion = trim($_POST['fecha_aprobacion'] ?? '');

    // Validación básica
    if ($nombre === '' || $estudiante === '' || $comite === '' || $categoria === '' || $fecha_aprobacion === '') {
        $mensaje = '<div style="color:red;">Todos los campos son obligatorios.</div>';
    } else {
        // Aquí puedes agregar la lógica para guardar en la base de datos
        // Ejemplo:
        // require_once 'inc/db/db.php';
        // $sql = "INSERT INTO proyectos (...) VALUES (...)";
        // $result = mysqli_query($conn, $sql);

        $mensaje = '<div style="color:green;">Proyecto validado correctamente. (Falta guardar en la base de datos)</div>';
    }
}
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Colores en Bruto</title>
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
  }
  label {
    font-weight: bold;
  }
  input, select, button {
    padding: 8px;
    font-size: 1rem;
  }
  button {
    background: var(--navy-mid);
    color: white;
    border: none;
    cursor: pointer;
    transition: background .3s;
  }
  button:hover {
    background: var(--navy-dark);
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

  <form action="panel_ctfg.php" method="post">
    <label for="nombre">Nombre del proyecto:</label>
    <input type="text" id="nombre" name="nombre" required>

    <label for="estudiante">Estudiante:</label>
    <select id="estudiante" name="estudiante" required>
      <option value="">Selecciona un estudiante</option>
    </select>

    <label for="comite">Comité asesor:</label>
    <select id="comite" name="comite" required>
      <option value="">Selecciona un comité</option>
    </select>

    <label for="categoria">Categoría:</label>
    <select id="categoria" name="categoria" required>
      <option value="">Selecciona una categoría</option>
    </select>

    <label for="fecha_aprobacion">Fecha de aprobación:</label>
    <input type="date" id="fecha_aprobacion" name="fecha_aprobacion" required>

    <button type="submit">Registrar