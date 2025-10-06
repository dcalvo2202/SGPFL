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
    </head>
    <body>
        <div class="page-container">
            <h1 class="mb-4">Comision</h1>
            <main class="flex-grow-1 container py-4">
                <a href="index.php">Inicio</a>
                <a href="/">Envio documentos</a>
                <a href="proyecto_aprobado.php">Aprobar Proyecto</a>
                <a href="/base/mod/login/logout.php">Logout</a>
            </main>
            <footer class="bg-dark text-white py-4 text-center">
                <p class="text-muted text-center"><?= $footer_title ?></p>
            </footer>
        </div>
    </body>
</html>
