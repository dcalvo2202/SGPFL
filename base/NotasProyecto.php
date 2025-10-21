<?php

require_once __DIR__ . '/inc/db/db.php';

// Parámetros
$proyecto_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$proyecto_nombre = isset($_GET['nombre']) ? trim($_GET['nombre']) : '';

if ($proyecto_id <= 0) {
    http_response_code(400);
    echo 'ID de proyecto no válido.';
    exit;
}

// Título/estilos para head.php
$page_title = 'Notas del proyecto';
$inlineStyles = <<<'CSS'
main { padding: 24px 0; }
.page-title { font-weight: 700; color: #092567; margin-bottom: 14px; }
CSS;

// Manejo de POST (agregar nota)
$flash_ok = false;
$flash_err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $notas  = trim($_POST['notas'] ?? '');
    $pid    = (int)($_POST['proyecto_id'] ?? 0);

    if ($pid !== $proyecto_id) {
        $flash_err = 'Proyecto inválido.';
    } elseif ($titulo === '' || $notas === '') {
        $flash_err = 'Título y notas son obligatorios.';
    } else {
        // Obtener siguiente id_nota (la tabla no tiene AUTO_INCREMENT)
        $next_id = null;
        $sqlNext = "SELECT COALESCE(MAX(id_nota), 0) + 1 AS next_id FROM proyecto_notas";
        if ($resNext = mysqli_query($id_con, $sqlNext)) {
            $row = mysqli_fetch_assoc($resNext);
            $next_id = (int)$row['next_id'];
            mysqli_free_result($resNext);
        }

        if ($next_id === null) {
            $flash_err = 'No se pudo generar el identificador de la nota.';
        } else {
            // Usa el id de usuario de la sesión, sin forzar session_start()
            $creado_por = (string)($_SESSION['id'] ?? $_SESSION['user_id'] ?? '');

            $sqlIns = "INSERT INTO proyecto_notas (id_nota, proyecto_id, titulo, notas, creado_por)
                       VALUES (?, ?, ?, ?, ?)";
            if ($stmt = mysqli_prepare($id_con, $sqlIns)) {
                mysqli_stmt_bind_param($stmt, 'iisss', $next_id, $proyecto_id, $titulo, $notas, $creado_por);
                $ok = mysqli_stmt_execute($stmt);
                $err = mysqli_stmt_error($stmt);
                mysqli_stmt_close($stmt);

                if ($ok) {
                    // Evitar reenvío de formulario
                    header("Location: NotasProyecto.php?id={$proyecto_id}&nombre=" . rawurlencode($proyecto_nombre) . "&ok=1");
                    exit;
                } else {
                    $flash_err = 'Error al guardar la nota: ' . htmlspecialchars($err, ENT_QUOTES, 'UTF-8');
                }
            } else {
                $flash_err = 'No se pudo preparar la inserción.';
            }
        }
    }
}

$flash_ok = isset($_GET['ok']) && $_GET['ok'] == '1';

// Cargar lista de notas previas (solo títulos)
$notas_previas = [];
$sqlSel = "SELECT id_nota, titulo, creado_por, creado_en
           FROM proyecto_notas
           WHERE proyecto_id = ?
           ORDER BY creado_en DESC, id_nota DESC";
if ($stmt = mysqli_prepare($id_con, $sqlSel)) {
    mysqli_stmt_bind_param($stmt, 'i', $proyecto_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($res && $r = mysqli_fetch_assoc($res)) $notas_previas[] = $r;
    mysqli_stmt_close($stmt);
}
?>
<!doctype html>
<html lang="es">
<?php include __DIR__ . '/head.php'; ?>
<body class="fondo-una d-flex flex-column min-vh-100">
  <?php include 'header.php'; ?>
  <main class="flex-fill">
    <div class="container my-5">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="page-title">
          Notas del proyecto<?php echo $proyecto_nombre !== '' ? ': ' . htmlspecialchars($proyecto_nombre, ENT_QUOTES, 'UTF-8') : ''; ?>
        </h1>
        <a class="btn btn-outline-secondary" href="ProyectosRegistrados.php">
          <i class="bi bi-arrow-left"></i> Volver
        </a>
      </div>

      <?php if ($flash_ok): ?>
        <div class="alert alert-success">Nota agregada correctamente.</div>
      <?php elseif ($flash_err !== ''): ?>
        <div class="alert alert-danger"><?php echo $flash_err; ?></div>
      <?php endif; ?>

      <!-- Panel centrado para agregar nueva nota -->
      <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
          <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
              <i class="bi bi-journal-plus"></i> Agregar nueva nota
            </div>
            <div class="card-body">
              <form method="post" action="">
                <input type="hidden" name="proyecto_id" value="<?php echo (int)$proyecto_id; ?>">
                <div class="mb-3">
                  <label class="form-label">Título</label>
                  <input type="text" name="titulo" class="form-control" maxlength="200" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">Notas</label>
                  <textarea name="notas" class="form-control" rows="6" required></textarea>
                </div>
                <div class="d-flex gap-2">
                  <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Guardar
                  </button>
                  <button type="reset" class="btn btn-outline-secondary">Limpiar</button>
                </div>
              </form>
            </div>
          </div>

          <!-- Lista de títulos de notas previas -->
          <div class="card">
            <div class="card-header">
              <i class="bi bi-card-list"></i> Notas previas
            </div>
            <div class="card-body p-0">
              <?php if (empty($notas_previas)): ?>
                <div class="p-3 text-muted">No hay notas registradas para este proyecto.</div>
              <?php else: ?>
                <ul class="list-group list-group-flush">
                  <?php foreach ($notas_previas as $n): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                      <div>
                        <div class="fw-semibold">
                          <?php echo htmlspecialchars($n['titulo'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <small class="text-muted">
                          <?php
                            $f = $n['creado_en'] ?? '';
                            $autor = $n['creado_por'] ?? '-';
                            echo 'Creado: ' . htmlspecialchars($f, ENT_QUOTES, 'UTF-8') . ' · Por: ' . htmlspecialchars($autor, ENT_QUOTES, 'UTF-8');
                          ?>
                        </small>
                      </div>
                      <span class="badge bg-light text-dark">#<?php echo (int)$n['id_nota']; ?></span>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>
          </div>

        </div>
      </div>
    </div>
  </main>
  <?php include 'footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>