<?php
include("mod/login/check.php");
include('lang/lang.es');

require_once __DIR__ . '/inc/db/db.php';
require_once __DIR__ . '/lib/mysession/mySession.conf.php';
require_once __DIR__ . '/lib/mysession/mySession.class.php';
$mySessionController = mySession::getIstance($_MYSESSION_CONF);

// Obtener variables de sesión
$current_user_id = $mySessionController->getVar("usuario");
$current_user_name = $mySessionController->getVar("nombre");
$current_user_rol = $mySessionController->getVar("rol");
$base_url = $mySessionController->getVar("cds_domain") . $mySessionController->getVar("cds_locate");

$page_title = 'Solicitud de Prórroga';
$inlineStyles = <<<'CSS'
/* Estilos unificados (UNA) */
body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;}
.dashboard-header h1{font-size:2.5rem;font-weight:700;color:#034991;margin-bottom:.5rem;}
.dashboard-header .lead{color:#6c757d;}
CSS;
?>
<!doctype html>
<html lang="es">
<?php include __DIR__ . '/head.php'; ?>
<body class="fondo-una d-flex flex-column min-vh-100">
  <?php include 'header.php'; ?>
  <main class="flex-fill">
    <div class="container my-5">
      <div class="card form-card shadow-sm mx-auto" style="max-width: 800px;">
        <div class="card-header text-center" style="background:#f8f9fa;font-weight:600;color:#034991;">
          Solicitud de Prórroga Proyecto
        </div>
        <div class="card-body" style="padding:24px;">
          <!-- Contenido del formulario aquí -->
        </div>
      </div>
    </div>
  </main>
  <?php include 'footer.php'; ?>
</body>
</html>
