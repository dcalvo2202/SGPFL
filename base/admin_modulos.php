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

function redirectWithFlashModulo($type, $message, $editingId = 0, $findKey = '', $showSearch = 0) {
    $params = [
        'flash_type' => $type,
        'flash_msg' => $message
    ];

    if ((int)$editingId > 0) {
        $params['edit'] = (int)$editingId;
    }

    if (trim((string)$findKey) !== '') {
        $params['find_key'] = trim((string)$findKey);
    }

    if ((int)$showSearch === 1) {
        $params['show_search'] = 1;
    }

    header('Location: admin_modulos.php?' . http_build_query($params));
    exit;
}

$editing_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$show_search = isset($_GET['show_search']) ? (int)$_GET['show_search'] : 0;
$find_key = isset($_GET['find_key']) ? trim((string)$_GET['find_key']) : '';
$form_mod_name = '';
$form_mod_desc = '';

if (isset($_GET['flash_msg'])) {
    $flash_message = (string)$_GET['flash_msg'];
    $flash_type = isset($_GET['flash_type']) ? (string)$_GET['flash_type'] : 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? trim((string)$_POST['action']) : 'save';
    $id_mod = isset($_POST['id_mod']) ? (int)$_POST['id_mod'] : 0;
    $mod_name = isset($_POST['mod_name']) ? trim((string)$_POST['mod_name']) : '';
    $mod_desc = isset($_POST['mod_desc']) ? trim((string)$_POST['mod_desc']) : '';

    $show_search = isset($_POST['show_search']) ? (int)$_POST['show_search'] : 0;
    $find_key = isset($_POST['find_key']) ? trim((string)$_POST['find_key']) : '';

    if ($action === 'edit') {
        if ($id_mod <= 0) {
            $flash_message = 'Módulo inválido para modificar.';
            $flash_type = 'danger';
            $editing_id = 0;
        } else {
            $editing_id = $id_mod;
        }
    } elseif ($action === 'delete') {
        if ($id_mod <= 0) {
            $flash_message = 'Módulo inválido para eliminar.';
            $flash_type = 'danger';
        } else {
            $delete = ejecutar_query("UPDATE sis_mod SET active = '0' WHERE id_mod = ?", [$id_mod]);
            if (isset($delete['success']) && $delete['success']) {
                redirectWithFlashModulo('success', 'Módulo eliminado correctamente.', 0, $find_key, $show_search);
            } else {
                $flash_message = 'No fue posible eliminar el módulo.';
                $flash_type = 'danger';
            }
        }
    } else {
        if ($mod_name === '') {
            $flash_message = 'El nombre del módulo es obligatorio.';
            $flash_type = 'danger';
            $editing_id = $id_mod;
            $form_mod_name = $mod_name;
            $form_mod_desc = $mod_desc;
        } else {
            if ($id_mod > 0) {
                $duplicated = seleccion_segura(
                    "SELECT id_mod FROM sis_mod WHERE mod_name = ? AND id_mod <> ? AND active = '1' LIMIT 1",
                    [$mod_name, $id_mod]
                );

                if ($duplicated && isset($duplicated[0]['id_mod'])) {
                    $flash_message = 'Ya existe otro módulo con ese nombre.';
                    $flash_type = 'danger';
                    $editing_id = $id_mod;
                    $form_mod_name = $mod_name;
                    $form_mod_desc = $mod_desc;
                } else {
                    $update = ejecutar_query(
                        "UPDATE sis_mod SET mod_name = ?, mod_desc = ? WHERE id_mod = ? AND active = '1'",
                        [$mod_name, $mod_desc, $id_mod]
                    );

                    if (isset($update['success']) && $update['success']) {
                        redirectWithFlashModulo('success', 'Módulo actualizado correctamente.', 0, $find_key, $show_search);
                    } else {
                        $flash_message = 'No fue posible actualizar el módulo.';
                        $flash_type = 'danger';
                        $editing_id = $id_mod;
                        $form_mod_name = $mod_name;
                        $form_mod_desc = $mod_desc;
                    }
                }
            } else {
                $existing = seleccion_segura(
                    "SELECT id_mod FROM sis_mod WHERE mod_name = ? AND active = '1' LIMIT 1",
                    [$mod_name]
                );

                if ($existing && isset($existing[0]['id_mod'])) {
                    $flash_message = 'Ese módulo ya existe. Usa la opción Modificar.';
                    $flash_type = 'danger';
                    $form_mod_name = $mod_name;
                    $form_mod_desc = $mod_desc;
                } else {
                    $insert = ejecutar_query(
                        "INSERT INTO sis_mod (mod_name, mod_desc, active) VALUES (?, ?, '1')",
                        [$mod_name, $mod_desc]
                    );

                    if (isset($insert['success']) && $insert['success']) {
                        redirectWithFlashModulo('success', 'Módulo creado correctamente.', 0, $find_key, $show_search);
                    } else {
                        $flash_message = 'No fue posible crear el módulo.';
                        $flash_type = 'danger';
                        $form_mod_name = $mod_name;
                        $form_mod_desc = $mod_desc;
                    }
                }
            }
        }
    }
}

if ($editing_id > 0 && $form_mod_name === '' && $form_mod_desc === '') {
    $editRows = seleccion_segura(
        "SELECT id_mod, mod_name, mod_desc FROM sis_mod WHERE id_mod = ? AND active = '1' LIMIT 1",
        [$editing_id]
    );

    if ($editRows && isset($editRows[0]['id_mod'])) {
        $form_mod_name = (string)$editRows[0]['mod_name'];
        $form_mod_desc = (string)$editRows[0]['mod_desc'];
    } else {
        $editing_id = 0;
    }
}

$sql = "SELECT id_mod, mod_name, mod_desc FROM sis_mod WHERE active = '1'";
$params = [];
if ($find_key !== '') {
    $sql .= " AND mod_name LIKE ?";
    $params[] = '%' . $find_key . '%';
}
$sql .= " ORDER BY id_mod ASC";

$modulos = seleccion_segura($sql, $params);
if (!is_array($modulos)) {
    $modulos = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<?php include('head.php'); ?>
<link rel="stylesheet" href="inc/css/admin_panels_responsive.css">
<body class="fondo-una d-flex flex-column min-vh-100">

    <?php include 'header.php'; ?>

    <main class="flex-fill">
        <div class="container my-4">
            <div class="dashboard-header text-center mb-4">
                <h1>Gestión de Módulos</h1>
                <p class="lead">Administración de módulos del sistema</p>
            </div>

            <div class="alert alert-info" role="alert">
                Los módulos representan áreas funcionales del sistema y se utilizan para organizar permisos y accesos.
            </div>

            <?php if ($flash_message): ?>
                <div class="alert alert-<?= htmlspecialchars($flash_type) ?>" role="alert">
                    <?= htmlspecialchars($flash_message) ?>
                </div>
            <?php endif; ?>

            <?php if ($editing_id > 0): ?>
                <div class="alert alert-warning border border-2 border-warning" role="alert">
                    <strong>Modo edición activo:</strong> estás modificando el módulo #<?= (int)$editing_id ?>.
                    Guarda los cambios o presiona <strong>Cancelar</strong> para salir del modo edición.
                </div>
            <?php endif; ?>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <h5 class="mb-0">Búsqueda</h5>
                        <button type="button" id="toggleSearchBtn" class="btn btn-primary">
                            <i class="fa fa-search"></i> <?= $show_search === 1 ? 'Ocultar búsqueda' : 'Mostrar búsqueda' ?>
                        </button>
                    </div>

                    <div id="searchContainer" style="display: <?= $show_search === 1 ? 'block' : 'none' ?>;">
                        <form method="get" class="row g-3 align-items-end">
                            <input type="hidden" name="show_search" value="1">
                            <?php if ($editing_id > 0): ?>
                                <input type="hidden" name="edit" value="<?= (int)$editing_id ?>">
                            <?php endif; ?>
                            <div class="col-md-8">
                                <label class="form-label">Nombre del módulo</label>
                                <input type="text" name="find_key" class="form-control" value="<?= htmlspecialchars($find_key) ?>" placeholder="Digite un nombre para filtrar">
                            </div>
                            <div class="col-md-4 text-end">
                                <button type="submit" class="btn btn-info btn-spacing-right">
                                    <i class="fa fa-search"></i> Buscar
                                </button>
                                <a href="admin_modulos.php" class="btn btn-secondary">Limpiar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="mb-3"><?= $editing_id > 0 ? 'Modificar módulo (Modo edición)' : 'Crear módulo' ?></h5>
                    <form method="post" class="row g-3">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="id_mod" value="<?= (int)$editing_id ?>">
                        <input type="hidden" name="show_search" value="<?= (int)$show_search ?>">
                        <input type="hidden" name="find_key" value="<?= htmlspecialchars($find_key) ?>">

                        <div class="col-md-4">
                            <label class="form-label">Nombre <span class="required-indicator">*</span></label>
                            <input type="text" name="mod_name" class="form-control" required value="<?= htmlspecialchars($form_mod_name) ?>">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Descripción</label>
                            <input type="text" name="mod_desc" class="form-control" value="<?= htmlspecialchars($form_mod_desc) ?>">
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary btn-spacing-right">
                                <i class="fa fa-save"></i> <?= $editing_id > 0 ? 'Actualizar' : 'Guardar' ?>
                            </button>
                            <?php if ($editing_id > 0): ?>
                                <a href="admin_modulos.php?<?= http_build_query(['show_search' => $show_search, 'find_key' => $find_key]) ?>" class="btn btn-secondary btn-spacing-right">
                                    Cancelar
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3">Listado de módulos</h5>
                    <div class="modulos-table-wrapper">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($modulos) === 0): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No hay módulos registrados.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($modulos as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string)$item['id_mod']) ?></td>
                                            <td><strong><?= htmlspecialchars((string)$item['mod_name']) ?></strong></td>
                                            <td><?= htmlspecialchars((string)$item['mod_desc']) ?></td>
                                            <td>
                                                <form method="post" style="display:inline; margin-right: 6px;">
                                                    <input type="hidden" name="action" value="edit">
                                                    <input type="hidden" name="id_mod" value="<?= (int)$item['id_mod'] ?>">
                                                    <input type="hidden" name="show_search" value="<?= (int)$show_search ?>">
                                                    <input type="hidden" name="find_key" value="<?= htmlspecialchars($find_key) ?>">
                                                    <button type="submit" class="btn btn-primary btn-sm">
                                                        Modificar
                                                    </button>
                                                </form>
                                                <form method="post" style="display:inline;" class="js-delete-mod-form" data-modulo="<?= htmlspecialchars((string)$item['mod_name']) ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id_mod" value="<?= (int)$item['id_mod'] ?>">
                                                    <input type="hidden" name="show_search" value="<?= (int)$show_search ?>">
                                                    <input type="hidden" name="find_key" value="<?= htmlspecialchars($find_key) ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" <?= $editing_id > 0 ? 'disabled title="No disponible mientras editas un módulo"' : '' ?>>
                                                        Eliminar
                                                    </button>
                                                </form>
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
            if (flashAlert) {
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
            }
        })();

        (function () {
            var toggleBtn = document.getElementById('toggleSearchBtn');
            var container = document.getElementById('searchContainer');

            if (!toggleBtn || !container) {
                return;
            }

            toggleBtn.addEventListener('click', function () {
                var isHidden = container.style.display === 'none';
                container.style.display = isHidden ? 'block' : 'none';
                toggleBtn.innerHTML = isHidden
                    ? '<i class="fa fa-search-minus"></i> Ocultar búsqueda'
                    : '<i class="fa fa-search"></i> Mostrar búsqueda';
            });
        })();

        document.querySelectorAll('.js-delete-mod-form').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();

                var modulo = form.getAttribute('data-modulo') || 'este módulo';

                if (typeof Swal === 'undefined') {
                    if (confirm('¿Desea eliminar "' + modulo + '"?')) {
                        form.submit();
                    }
                    return;
                }

                Swal.fire({
                    title: '¿Eliminar módulo?',
                    text: 'Se eliminará "' + modulo + '" de forma permanente.',
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
