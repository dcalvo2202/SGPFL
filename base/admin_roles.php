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

function redirectWithFlashRol($type, $message, $editingId = 0, $findKey = '', $showSearch = 0) {
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

    header('Location: admin_roles.php?' . http_build_query($params));
    exit;
}

$editing_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$show_search = isset($_GET['show_search']) ? (int)$_GET['show_search'] : 0;
$find_key = isset($_GET['find_key']) ? trim((string)$_GET['find_key']) : '';
$form_roll_name = '';
$form_roll_desc = '';

if (isset($_GET['flash_msg'])) {
    $flash_message = (string)$_GET['flash_msg'];
    $flash_type = isset($_GET['flash_type']) ? (string)$_GET['flash_type'] : 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? trim((string)$_POST['action']) : 'save';
    $id_roll = isset($_POST['id_roll']) ? (int)$_POST['id_roll'] : 0;
    $roll_name = isset($_POST['roll_name']) ? trim((string)$_POST['roll_name']) : '';
    $roll_desc = isset($_POST['roll_desc']) ? trim((string)$_POST['roll_desc']) : '';

    $show_search = isset($_POST['show_search']) ? (int)$_POST['show_search'] : 0;
    $find_key = isset($_POST['find_key']) ? trim((string)$_POST['find_key']) : '';

    if ($action === 'edit') {
        if ($id_roll <= 0) {
            $flash_message = 'Rol inválido para modificar.';
            $flash_type = 'danger';
            $editing_id = 0;
        } else {
            $editing_id = $id_roll;
        }
    } elseif ($action === 'delete') {
        if ($id_roll <= 0) {
            $flash_message = 'Rol inválido para eliminar.';
            $flash_type = 'danger';
        } elseif ($id_roll === 1) {
            $flash_message = 'El rol Administrador no se puede eliminar.';
            $flash_type = 'danger';
        } else {
            $assignedUsers = seleccion_segura(
                "SELECT COUNT(*) AS total FROM sis_login WHERE id_roll = ?",
                [$id_roll]
            );
            $usersCount = ($assignedUsers && isset($assignedUsers[0]['total'])) ? (int)$assignedUsers[0]['total'] : 0;

            if ($usersCount > 0) {
                $flash_message = 'No se puede eliminar el rol porque tiene usuarios asignados.';
                $flash_type = 'danger';
            } else {
                $deletePermits = ejecutar_query("DELETE FROM sis_permits WHERE id_roll = ?", [$id_roll]);
                $deleteRole = ejecutar_query("DELETE FROM sis_rolls WHERE id_roll = ?", [$id_roll]);

                if (
                    isset($deletePermits['success']) && $deletePermits['success'] &&
                    isset($deleteRole['success']) && $deleteRole['success']
                ) {
                    redirectWithFlashRol('success', 'Rol eliminado correctamente.', 0, $find_key, $show_search);
                } else {
                    $flash_message = 'No fue posible eliminar el rol.';
                    $flash_type = 'danger';
                }
            }
        }
    } else {
        if ($roll_name === '') {
            $flash_message = 'El nombre del rol es obligatorio.';
            $flash_type = 'danger';
            $editing_id = $id_roll;
            $form_roll_name = $roll_name;
            $form_roll_desc = $roll_desc;
        } else {
            if ($id_roll > 0) {
                $duplicated = seleccion_segura(
                    "SELECT id_roll FROM sis_rolls WHERE roll_name = ? AND id_roll <> ? LIMIT 1",
                    [$roll_name, $id_roll]
                );

                if ($duplicated && isset($duplicated[0]['id_roll'])) {
                    $flash_message = 'Ya existe otro rol con ese nombre.';
                    $flash_type = 'danger';
                    $editing_id = $id_roll;
                    $form_roll_name = $roll_name;
                    $form_roll_desc = $roll_desc;
                } else {
                    $update = ejecutar_query(
                        "UPDATE sis_rolls SET roll_name = ?, roll_desc = ? WHERE id_roll = ?",
                        [$roll_name, $roll_desc, $id_roll]
                    );

                    if (isset($update['success']) && $update['success']) {
                        redirectWithFlashRol('success', 'Rol actualizado correctamente.', 0, $find_key, $show_search);
                    } else {
                        $flash_message = 'No fue posible actualizar el rol.';
                        $flash_type = 'danger';
                        $editing_id = $id_roll;
                        $form_roll_name = $roll_name;
                        $form_roll_desc = $roll_desc;
                    }
                }
            } else {
                $existing = seleccion_segura(
                    "SELECT id_roll FROM sis_rolls WHERE roll_name = ? LIMIT 1",
                    [$roll_name]
                );

                if ($existing && isset($existing[0]['id_roll'])) {
                    $flash_message = 'Ese rol ya existe. Usa la opción Modificar.';
                    $flash_type = 'danger';
                    $form_roll_name = $roll_name;
                    $form_roll_desc = $roll_desc;
                } else {
                    $insert = ejecutar_query(
                        "INSERT INTO sis_rolls (roll_name, roll_desc) VALUES (?, ?)",
                        [$roll_name, $roll_desc]
                    );

                    if (isset($insert['success']) && $insert['success']) {
                        redirectWithFlashRol('success', 'Rol creado correctamente.', 0, $find_key, $show_search);
                    } else {
                        $flash_message = 'No fue posible crear el rol.';
                        $flash_type = 'danger';
                        $form_roll_name = $roll_name;
                        $form_roll_desc = $roll_desc;
                    }
                }
            }
        }
    }
}

if ($editing_id > 0 && $form_roll_name === '' && $form_roll_desc === '') {
    $editRows = seleccion_segura(
        "SELECT id_roll, roll_name, roll_desc FROM sis_rolls WHERE id_roll = ? LIMIT 1",
        [$editing_id]
    );

    if ($editRows && isset($editRows[0]['id_roll'])) {
        $form_roll_name = (string)$editRows[0]['roll_name'];
        $form_roll_desc = (string)$editRows[0]['roll_desc'];
    } else {
        $editing_id = 0;
    }
}

$sql = "SELECT id_roll, roll_name, roll_desc FROM sis_rolls WHERE 1=1";
$params = [];
if ($find_key !== '') {
    $sql .= " AND roll_name LIKE ?";
    $params[] = '%' . $find_key . '%';
}
$sql .= " ORDER BY id_roll ASC";

$roles = seleccion_segura($sql, $params);
if (!is_array($roles)) {
    $roles = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<?php include('head.php'); ?>
<body class="fondo-una d-flex flex-column min-vh-100">

    <?php include 'header.php'; ?>

    <main class="flex-fill">
        <div class="container my-4">
            <div class="dashboard-header text-center mb-4">
                <h1>Gestión de Roles</h1>
                <p class="lead">Administración de roles del sistema</p>
            </div>

            <div class="alert alert-info" role="alert">
                Los roles agrupan permisos de acceso y acciones disponibles para cada tipo de usuario dentro del sistema.
            </div>

            <?php if ($flash_message): ?>
                <div class="alert alert-<?= htmlspecialchars($flash_type) ?>" role="alert">
                    <?= htmlspecialchars($flash_message) ?>
                </div>
            <?php endif; ?>

            <?php if ($editing_id > 0): ?>
                <div class="alert alert-warning border border-2 border-warning" role="alert">
                    <strong>Modo edición activo:</strong> estás modificando el rol #<?= (int)$editing_id ?>.
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
                                <label class="form-label">Nombre del rol</label>
                                <input type="text" name="find_key" class="form-control" value="<?= htmlspecialchars($find_key) ?>" placeholder="Digite un nombre para filtrar">
                            </div>
                            <div class="col-md-4 text-end">
                                <button type="submit" class="btn btn-info" style="margin-right: 10px;">
                                    <i class="fa fa-search"></i> Buscar
                                </button>
                                <a href="admin_roles.php" class="btn btn-secondary">Limpiar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="mb-3"><?= $editing_id > 0 ? 'Modificar rol (Modo edición)' : 'Crear rol' ?></h5>
                    <form method="post" class="row g-3">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="id_roll" value="<?= (int)$editing_id ?>">
                        <input type="hidden" name="show_search" value="<?= (int)$show_search ?>">
                        <input type="hidden" name="find_key" value="<?= htmlspecialchars($find_key) ?>">

                        <div class="col-md-4">
                            <label class="form-label">Nombre <span style="color: red;">*</span></label>
                            <input type="text" name="roll_name" class="form-control" required value="<?= htmlspecialchars($form_roll_name) ?>">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Descripción</label>
                            <input type="text" name="roll_desc" class="form-control" value="<?= htmlspecialchars($form_roll_desc) ?>">
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary" style="margin-right: 10px;">
                                <i class="fa fa-save"></i> <?= $editing_id > 0 ? 'Actualizar' : 'Guardar' ?>
                            </button>
                            <?php if ($editing_id > 0): ?>
                                <a href="admin_roles.php?<?= http_build_query(['show_search' => $show_search, 'find_key' => $find_key]) ?>" class="btn btn-secondary" style="margin-right: 10px;">
                                    Cancelar
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3">Listado de roles</h5>
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
                                <?php if (count($roles) === 0): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No hay roles registrados.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($roles as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string)$item['id_roll']) ?></td>
                                            <td><strong><?= htmlspecialchars((string)$item['roll_name']) ?></strong></td>
                                            <td><?= htmlspecialchars((string)$item['roll_desc']) ?></td>
                                            <td class="text-nowrap">
                                                <div class="d-flex align-items-center gap-2 flex-nowrap">
                                                    <form method="post" class="mb-0">
                                                        <input type="hidden" name="action" value="edit">
                                                        <input type="hidden" name="id_roll" value="<?= (int)$item['id_roll'] ?>">
                                                        <input type="hidden" name="show_search" value="<?= (int)$show_search ?>">
                                                        <input type="hidden" name="find_key" value="<?= htmlspecialchars($find_key) ?>">
                                                        <button type="submit" class="btn btn-primary btn-sm">Modificar</button>
                                                    </form>
                                                    <form method="post" class="js-delete-roll-form mb-0" data-rol="<?= htmlspecialchars((string)$item['roll_name']) ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id_roll" value="<?= (int)$item['id_roll'] ?>">
                                                        <input type="hidden" name="show_search" value="<?= (int)$show_search ?>">
                                                        <input type="hidden" name="find_key" value="<?= htmlspecialchars($find_key) ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm" <?= $editing_id > 0 ? 'disabled title="No disponible mientras editas un rol"' : '' ?>>Eliminar</button>
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

        document.querySelectorAll('.js-delete-roll-form').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();

                var rol = form.getAttribute('data-rol') || 'este rol';

                if (typeof Swal === 'undefined') {
                    if (confirm('¿Desea eliminar "' + rol + '"?')) {
                        form.submit();
                    }
                    return;
                }

                Swal.fire({
                    title: '¿Eliminar rol?',
                    text: 'Se eliminará "' + rol + '" de forma permanente.',
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
