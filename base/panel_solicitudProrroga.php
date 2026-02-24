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

// Obtener el título del proyecto asignado al estudiante
$titulo_proyecto = '';
$conn = new mysqli($db_host, $usuario, $clave, $db);
if (!$conn->connect_error) {
    $sql = "SELECT title FROM tfg_proposals WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $current_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $titulo_proyecto = htmlspecialchars($row['title']);
        }
        $stmt->close();
    }
    $conn->close();
}

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
          <?php if (!empty($titulo_proyecto)): ?>
          <h4 class="text-center mb-4" style="color:#034991; font-weight:600;">
            <?php echo $titulo_proyecto; ?>
          </h4>
          <?php endif; ?>
          
          <!-- Panel de Motivo -->
          <div class="form-group mb-4">
            <label for="motivo" style="font-weight:600; color:#034991; margin-bottom:8px; display:block;">
              Motivo (menos de 200 palabras)
            </label>
            <textarea 
              id="motivo" 
              name="motivo" 
              class="form-control" 
              rows="5" 
              maxlength="200" 
              placeholder="Escriba el motivo de su solicitud de prórroga..."
              style="resize:vertical; border:1px solid #ced4da; border-radius:4px;"
              required
            ></textarea>
            <small class="text-muted" style="display:block; margin-top:5px;">
              <span id="charCount">0</span>/200 caracteres
            </small>
          </div>
          
          <script>
            document.getElementById('motivo').addEventListener('input', function() {
              document.getElementById('charCount').textContent = this.value.length;
            });
          </script>
          
          <!-- Botón Aceptar -->
          <div class="text-center mt-4">
            <button type="submit" class="btn" style="background-color:#28a745; color:#fff; padding:10px 40px; font-weight:600; border:none; border-radius:4px;">
              Aceptar
            </button>
          </div>
        </div>
      </div>
    </div>
  </main>
  <?php include 'footer.php'; ?>
</body>
</html>
