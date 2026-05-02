<?php
include("mod/login/check.php");
include('lang/lang.es');
include_once(__DIR__ . "/inc/db/bdcommon.inc");
include_once(__DIR__ . "/inc/db/db.php");

$current_user_rol = (int)$mySessionController->getVar("rol");
$base_url = $mySessionController->getVar("cds_domain") . $mySessionController->getVar("cds_locate");

if ($current_user_rol !== 1) {
    header('Location: dashboard.php');
    exit;
}

$flash_message = '';
$flash_type = 'success';

function redirectWithFlash($type, $message, $editingId = 0) {
    $params = [
        'flash_type' => $type,
        'flash_msg' => $message
    ];

    if ((int)$editingId > 0) {
        $params['edit'] = (int)$editingId;
    }

    header('Location: admin_parametros.php?' . http_build_query($params));
    exit;
}

$editing_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$form_parametro = '';
$form_valor = '';
$form_descripcion = '';

if (isset($_GET['flash_msg'])) {
    $flash_message = (string)$_GET['flash_msg'];
    $flash_type = isset($_GET['flash_type']) ? (string)$_GET['flash_type'] : 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? trim($_POST['action']) : 'save';
    $id_pv = isset($_POST['id_pv']) ? (int)$_POST['id_pv'] : 0;
    $parametro = isset($_POST['parametro']) ? trim($_POST['parametro']) : '';
    $valor = isset($_POST['valor']) ? trim($_POST['valor']) : '';
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';

    if ($action === 'edit') {
        if ($id_pv <= 0) {
            $flash_message = 'Parámetro inválido para modificar.';
            $flash_type = 'danger';
            $editing_id = 0;
        } else {
            $editing_id = $id_pv;
        }
    } elseif ($action === 'delete') {
        if ($id_pv <= 0) {
            $flash_message = 'Parámetro inválido para eliminar.';
            $flash_type = 'danger';
        } else {
            $delete = ejecutar_query("DELETE FROM sis_parametros_varios WHERE id_pv = ?", [$id_pv]);
            if (isset($delete['success']) && $delete['success']) {
                redirectWithFlash('success', 'Parámetro eliminado correctamente.');
            } else {
                $flash_message = 'No fue posible eliminar el parámetro.';
                $flash_type = 'danger';
            }
        }
    } else {
        if ($parametro === '') {
            $flash_message = 'El nombre del parámetro es obligatorio.';
            $flash_type = 'danger';
            $editing_id = $id_pv;
            $form_parametro = $parametro;
            $form_valor = $valor;
            $form_descripcion = $descripcion;
        } else {
            if ($id_pv > 0) {
                $duplicated = seleccion_segura(
                    "SELECT id_pv FROM sis_parametros_varios WHERE parametro = ? AND id_pv <> ? LIMIT 1",
                    [$parametro, $id_pv]
                );

                if ($duplicated && isset($duplicated[0]['id_pv'])) {
                    $flash_message = 'Ya existe otro parámetro con ese nombre.';
                    $flash_type = 'danger';
                    $editing_id = $id_pv;
                    $form_parametro = $parametro;
                    $form_valor = $valor;
                    $form_descripcion = $descripcion;
                } else {
                    $update = ejecutar_query(
                        "UPDATE sis_parametros_varios SET parametro = ?, valor = ?, descripcion = ? WHERE id_pv = ?",
                        [$parametro, $valor, $descripcion, $id_pv]
                    );
                    if (isset($update['success']) && $update['success']) {
                        redirectWithFlash('success', 'Parámetro actualizado correctamente.');
                    } else {
                        $flash_message = 'No fue posible actualizar el parámetro.';
                        $flash_type = 'danger';
                        $editing_id = $id_pv;
                        $form_parametro = $parametro;
                        $form_valor = $valor;
                        $form_descripcion = $descripcion;
                    }
                }
            } else {
                $existing = seleccion_segura("SELECT id_pv FROM sis_parametros_varios WHERE parametro = ? LIMIT 1", [$parametro]);
                if ($existing && isset($existing[0]['id_pv'])) {
                    $flash_message = 'Ese parámetro ya existe. Usa la opción Modificar.';
                    $flash_type = 'danger';
                    $form_parametro = $parametro;
                    $form_valor = $valor;
                    $form_descripcion = $descripcion;
                } else {
                    $insert = ejecutar_query(
                        "INSERT INTO sis_parametros_varios (parametro, valor, descripcion) VALUES (?, ?, ?)",
                        [$parametro, $valor, $descripcion]
                    );
                    if (isset($insert['success']) && $insert['success']) {
                        redirectWithFlash('success', 'Parámetro creado correctamente.');
                    } else {
                        $flash_message = 'No fue posible crear el parámetro.';
                        $flash_type = 'danger';
                        $form_parametro = $parametro;
                        $form_valor = $valor;
                        $form_descripcion = $descripcion;
                    }
                }
            }
        }
    }
}

if ($editing_id > 0 && $form_parametro === '' && $form_valor === '' && $form_descripcion === '') {
    $editRows = seleccion_segura(
        "SELECT id_pv, parametro, valor, descripcion FROM sis_parametros_varios WHERE id_pv = ? LIMIT 1",
        [$editing_id]
    );

    if ($editRows && isset($editRows[0]['id_pv'])) {
        $form_parametro = (string)$editRows[0]['parametro'];
        $form_valor = (string)$editRows[0]['valor'];
        $form_descripcion = (string)$editRows[0]['descripcion'];
    } else {
        $editing_id = 0;
    }
}

$parametros = seleccion("SELECT id_pv, parametro, valor, descripcion FROM sis_parametros_varios ORDER BY parametro ASC");
if (!is_array($parametros)) {
    $parametros = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<?php include('head.php'); ?>
<link rel="stylesheet" href="inc/css/admin_panels_responsive.css">
<style>
    /* Estilos específicos de admin_parametros */
    @media (max-width: 576px) {
        .parametros-table-wrapper .table td:nth-child(1)::before { content: "ID"; }
        .parametros-table-wrapper .table td:nth-child(2)::before { content: "Parámetro"; }
        .parametros-table-wrapper .table td:nth-child(3)::before { content: "Valor"; }
        .parametros-table-wrapper .table td:nth-child(4)::before { content: "Descripción"; }
        .parametros-table-wrapper .table td:nth-child(5)::before { content: "Acciones"; }
    }
</style>
<body class="fondo-una d-flex flex-column min-vh-100">

    <?php include 'header.php'; ?>

    <main class="flex-fill">
        <div class="container my-4">
            <div class="dashboard-header text-center mb-4">
                <h1>Parámetros del Sistema</h1>
                <p class="lead">Administración de parámetros</p>
            </div>

            <div class="alert alert-info" role="alert">
                Los parámetros del sistema permiten centralizar configuraciones generales que afectan el comportamiento de distintos módulos.
                Se utilizan para ajustar valores operativos sin necesidad de modificar código.
            </div>

            <?php if ($flash_message): ?>
                <div class="alert alert-<?= htmlspecialchars($flash_type) ?>" role="alert">
                    <?= htmlspecialchars($flash_message) ?>
                </div>
            <?php endif; ?>

            <?php if ($editing_id > 0): ?>
                <div class="alert alert-warning border border-2 border-warning" role="alert">
                    <strong>Modo edición activo:</strong> estás modificando el parámetro #<?= (int)$editing_id ?>.
                    Guarda los cambios o presiona <strong>Cancelar</strong> para salir del modo edición.
                </div>
            <?php endif; ?>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="mb-3"><?= $editing_id > 0 ? 'Modificar parámetro (Modo edición)' : 'Crear parámetro' ?></h5>
                    <form method="post" class="row g-3">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="id_pv" value="<?= (int)$editing_id ?>">
                        <div class="col-md-4">
                            <label class="form-label">Parámetro <span class="required-indicator">*</span></label>
                            <input type="text" name="parametro" class="form-control" required value="<?= htmlspecialchars($form_parametro) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Valor</label>
                            <input type="text" name="valor" class="form-control" value="<?= htmlspecialchars($form_valor) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Descripción</label>
                            <input type="text" name="descripcion" class="form-control" value="<?= htmlspecialchars($form_descripcion) ?>">
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary btn-spacing-right">
                                <i class="fa fa-save"></i> <?= $editing_id > 0 ? 'Actualizar' : 'Guardar' ?>
                            </button>
                            <?php if ($editing_id > 0): ?>
                                <a href="admin_parametros.php" class="btn btn-secondary btn-spacing-right">
                                    Cancelar
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3">Listado de parámetros</h5>
                    <div class="parametros-table-wrapper">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Parámetro</th>
                                    <th>Valor</th>
                                    <th>Descripción</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($parametros) === 0): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No hay parámetros registrados.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($parametros as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string)$item['id_pv']) ?></td>
                                            <td><strong><?= htmlspecialchars((string)$item['parametro']) ?></strong></td>
                                            <td><?= htmlspecialchars((string)$item['valor']) ?></td>
                                            <td><?= htmlspecialchars((string)$item['descripcion']) ?></td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2 flex-nowrap">
                                                    <form method="post" style="display:inline; margin-right: 6px;">
                                                        <input type="hidden" name="action" value="edit">
                                                        <input type="hidden" name="id_pv" value="<?= (int)$item['id_pv'] ?>">
                                                        <button type="submit" class="btn btn-primary btn-sm">
                                                            Modificar
                                                        </button>
                                                    </form>
                                                    <form method="post" style="display:inline;" class="js-delete-param-form" data-parametro="<?= htmlspecialchars((string)$item['parametro']) ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id_pv" value="<?= (int)$item['id_pv'] ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm" <?= $editing_id > 0 ? 'disabled title="No disponible mientras editas un parámetro"' : '' ?>>
                                                            Eliminar
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 text-center">
                <a href="<?= htmlspecialchars($base_url) ?>dashboard.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left-circle"></i> Volver al Panel Principal
                </a>
            </div>
        </div>
    </main>

    <footer class="footer-una mt-auto">
        <div class="container">
            <p class="mb-1">&copy; <?= date('Y') ?> Universidad Nacional de Costa Rica</p>
            <small>Escuela de Informática - Proyecto SGPFL v3.0</small>
        </div>
    </footer>

    <script>
        (function () {
            var flashAlert = document.querySelector('.container .alert.alert-success, .container .alert.alert-danger');
            if (!flashAlert) {
                return;
            }

            setTimeout(function () {
                flashAlert.style.transition = 'opacity 0.35s ease';
                flashAlert.style.opacity = '0';
                setTimeout(function () {
                    if (flashAlert.parentNode) {
                        flashAlert.parentNode.removeChild(flashAlert);
                    }
                }, 350);
            }, 3500);

            if (window.history && window.history.replaceState) {
                var url = new URL(window.location.href);
                url.searchParams.delete('flash_msg');
                url.searchParams.delete('flash_type');
                window.history.replaceState({}, document.title, url.toString());
            }
        })();

        document.querySelectorAll('.js-delete-param-form').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();

                var parametro = form.getAttribute('data-parametro') || 'este parámetro';

                if (typeof Swal === 'undefined') {
                    if (confirm('¿Desea eliminar "' + parametro + '"?')) {
                        form.submit();
                    }
                    return;
                }

                Swal.fire({
                    title: '¿Eliminar parámetro?',
                    text: 'Se eliminará "' + parametro + '" de forma permanente.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true
                }).then(function (result) {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
</body>
</html>
