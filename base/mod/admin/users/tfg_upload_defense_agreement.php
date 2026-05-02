<?php
include_once __DIR__ . '/../../login/check.php';

$base_path = realpath(__DIR__ . '/../../../');

include_once $base_path . '/lang/lang.es';
require_once $base_path . '/inc/db/db.php';

$current_user_id   = $mySessionController->getVar("usuario");
$current_user_name = $mySessionController->getVar("nombre");
$current_user_rol  = (int)$mySessionController->getVar("rol");
$cds_domain        = $mySessionController->getVar("cds_domain");
$cds_locate        = $mySessionController->getVar("cds_locate");
$base_url          = $cds_domain . $cds_locate;

if ($current_user_rol !== 3) {
    header('Location: ' . $base_url . 'dashboard.php');
    exit;
}

$proyecto_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($proyecto_id <= 0) {
    header('Location: ' . $base_url . 'ProyectosRegistrados.php');
    exit;
}

$sql = "SELECT p.id_aprobado,
               p.proposal_id,
               p.nombre,
               DATE(p.fecha_finalizacion) AS fecha_defensa
        FROM proyecto_aprobado p
        WHERE p.id_aprobado = ?
        LIMIT 1";

$stmt = mysqli_prepare($id_con, $sql);
mysqli_stmt_bind_param($stmt, "i", $proyecto_id);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$proyecto = mysqli_fetch_assoc($rs);
mysqli_stmt_close($stmt);

if (!$proyecto) {
    header('Location: ' . $base_url . 'ProyectosRegistrados.php');
    exit;
}

$page_title = 'Adjuntar acuerdo de defensa';

$inlineStyles = <<<'CSS'
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
.dashboard-header h1 { font-size: 2.5rem; font-weight: 700; color: #034991; margin-bottom: .5rem; }
.dashboard-header .lead { color: #6c757d; }
.form-card { background:#fff; border-radius:12px; box-shadow:0 4px 6px rgba(0,0,0,.08); }
.form-card .card-header { background:#f8f9fa; font-weight:600; color:#034991; }
.form-card .card-body { padding:24px; }
label { font-weight:600; color:#092567; }
CSS;
?>
<!doctype html>
<html lang="es">
<?php include $base_path . '/head.php'; ?>
<body class="fondo-una d-flex flex-column min-vh-100">
<?php include $base_path . '/header.php'; ?>

<main class="flex-fill">
  <div class="container my-5">
    <div class="dashboard-header text-center mb-5">
      <h1>Adjuntar acuerdo de defensa pública</h1>
      <p class="lead"><?= htmlspecialchars($proyecto['nombre']) ?></p>
    </div>

    <div id="msgBox"></div>

    <div class="card form-card shadow-sm">
      <div class="card-header">Registro del acuerdo de defensa</div>
      <div class="card-body">
        <form id="frmAcuerdo"
          method="post"
          action="tfg_upload_defense_agreement_process.php"
          enctype="multipart/form-data">
          <input type="hidden" name="proyecto_id" value="<?= (int)$proyecto['id_aprobado'] ?>">
          <input type="hidden" name="proposal_id" value="<?= (int)$proyecto['proposal_id'] ?>">

          <div class="mb-3">
            <label class="form-label">Código del acuerdo</label>
            <input type="text"
                   name="codigo_acuerdo"
                   class="form-control"
                   placeholder="UNA-CTFG-EI-ACUE-001-<?= date('Y') ?>"
                   required>
          </div>

          <div class="mb-3">
            <label class="form-label">Fecha de aprobación del documento final</label>
            <input type="date"
                   name="fecha_aprobacion_documento_final"
                   class="form-control"
                   required>
          </div>

          <div class="mb-3">
            <label class="form-label">Fecha de defensa</label>
            <input type="date"
                   name="fecha_defensa"
                   class="form-control"
                   value="<?= htmlspecialchars($proyecto['fecha_defensa'] ?? '') ?>"
                   required>
          </div>

          <div class="mb-3">
            <label class="form-label">Correo destino</label>
            <input type="email"
                   name="correo_destino"
                   class="form-control"
                   value="malcolm.chaves.obando@est.una.ac.cr"
                   required>
          </div>

          <div class="mb-3">
            <label class="form-label">Documento PDF</label>
            <input type="file"
                   name="documento"
                   class="form-control"
                   accept="application/pdf,.pdf"
                   required>
            <small class="text-muted">Solo PDF. Tamaño máximo: 10 MB.</small>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-save"></i> Guardar acuerdo
            </button>
            <a href="<?= htmlspecialchars($base_url) ?>ProyectosRegistrados.php" class="btn btn-secondary">
              <i class="bi bi-arrow-left-circle"></i> Volver
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</main>

<?php include $base_path . '/footer.php'; ?>

<script>
document.getElementById('frmAcuerdo').addEventListener('submit', async function (e) {
    e.preventDefault();

    const msgBox = document.getElementById('msgBox');
    msgBox.innerHTML = '';

    const formData = new FormData(this);

    const saveResponse = await fetch('tfg_upload_defense_agreement_process.php', {
        method: 'POST',
        body: formData
    });

    const saveText = await saveResponse.text();
    let saveData;

    try {
        saveData = JSON.parse(saveText);
    } catch (e) {
        msgBox.innerHTML = `
          <div class="alert alert-danger">
            El servidor no devolvió JSON válido al guardar el acuerdo.
            <pre style="white-space: pre-wrap;">${saveText}</pre>
          </div>
        `;
        return;
    }

    msgBox.innerHTML = `
      <div class="alert alert-${saveData.success ? 'success' : 'danger'}">
        ${saveData.message}
      </div>
    `;

    if (!saveData.success) return;

    const mailBody = new URLSearchParams({
        proyecto_id: formData.get('proyecto_id')
    });

    const mailResponse = await fetch('send_defense_agreement_mail.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: mailBody.toString()
    });

    const mailText = await mailResponse.text();
    let mailData;

    try {
        mailData = JSON.parse(mailText);
    } catch (e) {
        msgBox.innerHTML += `
          <div class="alert alert-warning mt-3">
            El acuerdo se guardó, pero el servidor no devolvió JSON válido al intentar enviar el correo.
            <pre style="white-space: pre-wrap;">${mailText}</pre>
          </div>
        `;
        return;
    }

    msgBox.innerHTML += `
      <div class="alert alert-${mailData.success ? 'success' : 'warning'} mt-3">
        ${mailData.message}
      </div>
    `;

    window.scrollTo({ top: 0, behavior: 'smooth' });
});
</script>
</body>
</html>