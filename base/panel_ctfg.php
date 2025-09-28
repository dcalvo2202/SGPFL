<<<<<<< HEAD
<?php
// panel_ctfg.php
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Panel de Proyectos</title>
  <style>
    body { font-family: sans-serif; background: #fcfdfd; text-align: center; margin-top: 100px; }
    .btn-main {
      background: #0e4d93;
      color: #fff;
      padding: 18px 36px;
      border-radius: 8px;
      font-size: 1.2rem;
      border: none;
      cursor: pointer;
      box-shadow: 0 2px 8px rgba(9,37,103,0.08);
      transition: background .3s;
    }
    .btn-main:hover { background: #092567; }
  </style>
</head>
<body>
  <a href="proyecto_aprobado.php">
    <button class="btn-main">Agregar proyecto aprobado</button>
  </a>
</body>
</html><?php
=======
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <title><?= $page_title ?></title>
        <?php
        include('includes.php');
        include('lang/lang.es');
        ?>
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-..." crossorigin="anonymous">

        <!-- Bootstrap JS Bundle (incluye Popper) -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-..." crossorigin="anonymous"></script>
    </head>
    <body>
        <div className="page-container">
            <h1 className="mb-4">Comision</h1>
            <main className="flex-grow-1 container py-4">
                <a href="index.php">Inicio</a>
                <a href="/">Envio documentos</a>
                <a href="/base/mod/login/logout.php">Logout</a>
            </main>
            <footer className="bg-dark text-white py-4 text-center">
                <p class="text-muted text-center"><?= $footer_title ?></p>
            </footer>
        </div>
    </body>
</html>
>>>>>>> main
