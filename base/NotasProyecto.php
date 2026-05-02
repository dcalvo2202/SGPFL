<?php
include("mod/login/check.php");
include('includes.php');
include('lang/lang.es');

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
    $etapa  = trim($_POST['etapa_proyecto'] ?? ''); // NUEVO

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
            // Usa el id de usuario desde mySession si está disponible
            $creado_por = (string)($mySessionController->getVar('usuario') ?? $_SESSION['id'] ?? $_SESSION['user_id'] ?? '');

            $sqlIns = "INSERT INTO proyecto_notas (id_nota, proyecto_id, titulo, notas, creado_por, etapa_proyecto)
                       VALUES (?, ?, ?, ?, ?, NULLIF(?, ''))"; // guarda NULL si viene vacío
            if ($stmt = mysqli_prepare($id_con, $sqlIns)) {
                mysqli_stmt_bind_param($stmt, 'iissss', $next_id, $proyecto_id, $titulo, $notas, $creado_por, $etapa);
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
$sqlSel = "SELECT id_nota, titulo, notas, creado_por, creado_en, etapa_proyecto
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

      <div class="dashboard-header text-center mb-5">
        <h1>
          Notas del proyecto<?php echo $proyecto_nombre !== '' ? ': ' . htmlspecialchars($proyecto_nombre, ENT_QUOTES, 'UTF-8') : ''; ?>
        </h1>
        <p class="lead">Agregue nuevas notas y consulte el historial asociado al proyecto aprobado.</p>
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
                  <label class="form-label">Etapa de proyecto</label> <!-- NUEVO -->
                  <input type="text" name="etapa_proyecto" class="form-control" maxlength="100"
                         placeholder="p. ej., Anteproyecto, Borrador final, Defensa">
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

          <!-- Visor de nota seleccionada -->
          <div id="notaViewer" class="card shadow-sm mb-4" style="display:none;">
            <div class="card-header bg-light">
              <strong id="notaViewerTitulo"></strong>
            </div>
            <div class="card-body">
              <div id="notaViewerEtapa" class="mb-2 text-primary fw-semibold" style="display:none;"></div> <!-- NUEVO -->
              <div id="notaViewerTexto" style="white-space:pre-wrap;"></div>
              <div id="notaViewerMeta" class="text-muted small mt-2"></div>
            </div>
          </div>

          <!-- Lista de títulos de notas previas -->
          <div class="card shadow-sm">
            <div class="card-header">
              <i class="bi bi-card-list"></i> Notas previas
            </div>
            <div class="card-body p-0">
              <?php if (empty($notas_previas)): ?>
                <div class="p-3 text-muted">No hay notas registradas para este proyecto.</div>
              <?php else: ?>
                <ul id="notasPreviasList" class="list-group list-group-flush">
                  <?php foreach ($notas_previas as $n): ?>
                    <li
                      class="list-group-item d-flex justify-content-between align-items-start"
                      data-titulo="<?php echo htmlspecialchars($n['titulo'], ENT_QUOTES, 'UTF-8'); ?>"
                      data-creado="<?php echo htmlspecialchars($n['creado_en'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                      data-autor="<?php echo htmlspecialchars($n['creado_por'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>"
                      data-etapa="<?php echo htmlspecialchars($n['etapa_proyecto'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                      role="button"
                      tabindex="0"
                    >
                      <div>
                        <div class="fw-semibold"><?php echo htmlspecialchars($n['titulo'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <small class="text-muted">
                          <?php
                            $f = $n['creado_en'] ?? '';
                            $autor = $n['creado_por'] ?? '-';
                            echo 'Creado: ' . htmlspecialchars($f, ENT_QUOTES, 'UTF-8') . ' · Por: ' . htmlspecialchars($autor, ENT_QUOTES, 'UTF-8');
                          ?>
                        </small>
                      </div>
                      <span class="badge bg-light text-dark">#<?php echo (int)$n['id_nota']; ?></span>

                      <!-- Contenido completo oculto para el visor -->
                      <div class="note-content d-none"><?php echo htmlspecialchars($n['notas'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>
          </div>

          <div class="text-center mt-4">
            <a href="ProyectosRegistrados.php" class="btn btn-secondary">
              <i class="bi bi-arrow-left-circle"></i> Volver a Proyectos
            </a>
          </div>

        </div>
      </div>
    </div>
  </main>
  <?php include 'footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    (function(){
      const list = document.getElementById('notasPreviasList');
      const viewer = document.getElementById('notaViewer');
      const vTitle = document.getElementById('notaViewerTitulo');
      const vText  = document.getElementById('notaViewerTexto');
      const vMeta  = document.getElementById('notaViewerMeta');
      const vStage = document.getElementById('notaViewerEtapa'); // NUEVO
      if (!list || !viewer) return;

      function showFromItem(li){
        const titulo = li.getAttribute('data-titulo') || '';
        const creado = li.getAttribute('data-creado') || '';
        const autor  = li.getAttribute('data-autor') || '';
        const etapa  = li.getAttribute('data-etapa') || '';
        const contentEl = li.querySelector('.note-content');
        const texto = contentEl ? contentEl.textContent : '';
        vTitle.textContent = titulo;
        vText.textContent = texto;
        vMeta.textContent = (creado || autor) ? `Creado: ${creado} · Por: ${autor}` : '';
        if (etapa) {
          vStage.textContent = `Etapa: ${etapa}`;
          vStage.style.display = '';
        } else {
          vStage.textContent = '';
          vStage.style.display = 'none';
        }
        viewer.style.display = '';
        list.querySelectorAll('.list-group-item').forEach(el => el.classList.remove('active'));
        li.classList.add('active');
      }

      list.addEventListener('click', (e) => {
        const li = e.target.closest('.list-group-item');
        if (li) showFromItem(li);
      });

      list.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          const li = e.target.closest('.list-group-item');
          if (li) { e.preventDefault(); showFromItem(li); }
        }
      });

      // Mostrar la primera al cargar (opcional)
      const first = list.querySelector('.list-group-item');
      if (first) showFromItem(first);
    })();
  </script>
</body>
</html>