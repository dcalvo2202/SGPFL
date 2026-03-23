<?php
include("mod/login/check.php");
include('lang/lang.es');
include_once(__DIR__ . "/inc/db/bdcommon.inc");
include_once(__DIR__ . "/inc/db/db.php");

$current_user_id = (string)$mySessionController->getVar("usuario");
$current_user_rol = (int)$mySessionController->getVar("rol");
$base_url = $mySessionController->getVar("cds_domain") . $mySessionController->getVar("cds_locate");
$is_ldap_enabled = isset($ldap_status) && (int)$ldap_status === 1;

if ($current_user_rol !== 1) {
    header('Location: dashboard.php');
    exit;
}

$flash_message = '';
$flash_type = 'success';

function redirectWithFlashUsuario($type, $message, $editingId = '', $findKey = '', $showSearch = 0, $page = 1) {
    $params = [
        'flash_type' => $type,
        'flash_msg' => $message
    ];

    if (trim((string)$editingId) !== '') {
        $params['edit'] = trim((string)$editingId);
    }

    if (trim((string)$findKey) !== '') {
        $params['find_key'] = trim((string)$findKey);
    }

    if ((int)$showSearch === 1) {
        $params['show_search'] = 1;
    }

    if ((int)$page > 1) {
        $params['page'] = (int)$page;
    }

    header('Location: admin_usuarios.php?' . http_build_query($params));
    exit;
}

$editing_id = isset($_GET['edit']) ? trim((string)$_GET['edit']) : '';
$show_search = isset($_GET['show_search']) ? (int)$_GET['show_search'] : 0;
$find_key = isset($_GET['find_key']) ? trim((string)$_GET['find_key']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}
$per_page = 15;

$form_id = '';
$form_nombre = '';
$form_email = '';
$form_telefono = '';
$form_id_tipo_tel = 'M';
$form_id_roll = 0;

if (isset($_GET['flash_msg'])) {
    $flash_message = (string)$_GET['flash_msg'];
    $flash_type = isset($_GET['flash_type']) ? (string)$_GET['flash_type'] : 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? trim((string)$_POST['action']) : 'save';
    $editing_id_post = isset($_POST['editing_id']) ? trim((string)$_POST['editing_id']) : '';

    $id = isset($_POST['id']) ? trim((string)$_POST['id']) : '';
    $nombre = isset($_POST['nombre']) ? trim((string)$_POST['nombre']) : '';
    $email = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
    $telefono = isset($_POST['telefono']) ? trim((string)$_POST['telefono']) : '';
    $id_tipo_tel = isset($_POST['id_tipo_tel']) ? trim((string)$_POST['id_tipo_tel']) : 'M';
    $id_roll = isset($_POST['id_roll']) ? (int)$_POST['id_roll'] : 0;
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';

    $show_search = isset($_POST['show_search']) ? (int)$_POST['show_search'] : 0;
    $find_key = isset($_POST['find_key']) ? trim((string)$_POST['find_key']) : '';
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    if ($page < 1) {
        $page = 1;
    }

    if ($action === 'edit') {
        if ($id === '') {
            $flash_message = 'Usuario inválido para modificar.';
            $flash_type = 'danger';
            $editing_id = '';
        } else {
            $editing_id = $id;
        }
    } elseif ($action === 'delete') {
        if ($id === '') {
            $flash_message = 'Usuario inválido para eliminar.';
            $flash_type = 'danger';
        } elseif ($id === $current_user_id) {
            $flash_message = 'No puedes eliminar tu propio usuario.';
            $flash_type = 'danger';
        } else {
            $ownerRow = seleccion_segura("SELECT id_roll FROM sis_login WHERE id = ? LIMIT 1", [$id]);
            $targetRole = ($ownerRow && isset($ownerRow[0]['id_roll'])) ? (int)$ownerRow[0]['id_roll'] : 0;
            if ($targetRole === 1) {
                $flash_message = 'No se pueden eliminar usuarios con rol Administrador.';
                $flash_type = 'danger';
            } else {
                $delete = ejecutar_query("DELETE FROM sis_login WHERE id = ?", [$id]);
                if (isset($delete['success']) && $delete['success']) {
                    redirectWithFlashUsuario('success', 'Usuario eliminado correctamente.', '', $find_key, $show_search, $page);
                } else {
                    $flash_message = 'No fue posible eliminar el usuario (puede tener datos relacionados).';
                    $flash_type = 'danger';
                }
            }
        }
    } else {
        if ($id === '' || $nombre === '' || $id_roll <= 0) {
            $flash_message = 'ID, nombre y rol son obligatorios.';
            $flash_type = 'danger';
            $editing_id = $editing_id_post;
            $form_id = $id;
            $form_nombre = $nombre;
            $form_email = $email;
            $form_telefono = $telefono;
            $form_id_tipo_tel = $id_tipo_tel;
            $form_id_roll = $id_roll;
        } else {
            if ($editing_id_post !== '') {
                $editing_id = $editing_id_post;
                $current = seleccion_segura("SELECT id FROM sis_login WHERE id = ? LIMIT 1", [$editing_id_post]);
                if (!$current || !isset($current[0]['id'])) {
                    $flash_message = 'El usuario a modificar no existe.';
                    $flash_type = 'danger';
                    $editing_id = '';
                } else {
                    if ($id !== $editing_id_post) {
                        $flash_message = 'El identificador del usuario no se puede cambiar en modo edición.';
                        $flash_type = 'danger';
                        $id = $editing_id_post;
                    }

                    $existsDuplicate = seleccion_segura("SELECT id FROM sis_login WHERE id = ? LIMIT 1", [$id]);
                    if (!$existsDuplicate || !isset($existsDuplicate[0]['id'])) {
                        $flash_message = 'El usuario no existe en login.';
                        $flash_type = 'danger';
                    } else {
                        $updateLogin = ejecutar_query(
                            "UPDATE sis_login SET id_roll = ? WHERE id = ?",
                            [$id_roll, $id]
                        );

                        $updateUser = ejecutar_query(
                            "UPDATE sis_user SET nombre = ?, email = ?, telefono = ?, id_tipo_tel = ? WHERE id = ?",
                            [$nombre, $email, $telefono, $id_tipo_tel, $id]
                        );

                        $updatePassOk = true;
                        if (trim($password) !== '') {
                            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                            $updatePass = ejecutar_query(
                                "UPDATE sis_login SET pass = ? WHERE id = ?",
                                [$passwordHash, $id]
                            );
                            $updatePassOk = isset($updatePass['success']) && $updatePass['success'];
                        }

                        if (
                            isset($updateLogin['success']) && $updateLogin['success'] &&
                            isset($updateUser['success']) && $updateUser['success'] &&
                            $updatePassOk
                        ) {
                            redirectWithFlashUsuario('success', 'Usuario actualizado correctamente.', '', $find_key, $show_search, $page);
                        } else {
                            $flash_message = 'No fue posible actualizar el usuario.';
                            $flash_type = 'danger';
                            $form_id = $id;
                            $form_nombre = $nombre;
                            $form_email = $email;
                            $form_telefono = $telefono;
                            $form_id_tipo_tel = $id_tipo_tel;
                            $form_id_roll = $id_roll;
                        }
                    }
                }
            } else {
                $exists = seleccion_segura("SELECT id FROM sis_login WHERE id = ? LIMIT 1", [$id]);
                if ($exists && isset($exists[0]['id'])) {
                    $flash_message = 'Ese usuario ya existe. Usa la opción Modificar.';
                    $flash_type = 'danger';
                    $form_id = $id;
                    $form_nombre = $nombre;
                    $form_email = $email;
                    $form_telefono = $telefono;
                    $form_id_tipo_tel = $id_tipo_tel;
                    $form_id_roll = $id_roll;
                } else {
                    if (!$is_ldap_enabled && trim($password) === '') {
                        $flash_message = 'La contraseña es obligatoria al crear usuarios cuando LDAP está deshabilitado.';
                        $flash_type = 'danger';
                        $form_id = $id;
                        $form_nombre = $nombre;
                        $form_email = $email;
                        $form_telefono = $telefono;
                        $form_id_tipo_tel = $id_tipo_tel;
                        $form_id_roll = $id_roll;
                    } else {
                        $passToSave = trim($password) !== '' ? $password : bin2hex(random_bytes(8));
                        $passwordHash = password_hash($passToSave, PASSWORD_DEFAULT);

                        $insertLogin = ejecutar_query(
                            "INSERT INTO sis_login (id, pass, id_roll) VALUES (?, ?, ?)",
                            [$id, $passwordHash, $id_roll]
                        );

                        if (!(isset($insertLogin['success']) && $insertLogin['success'])) {
                            $flash_message = 'No fue posible crear el acceso del usuario.';
                            $flash_type = 'danger';
                        } else {
                            $insertUser = ejecutar_query(
                                "INSERT INTO sis_user (id, nombre, email, telefono, id_tipo_tel) VALUES (?, ?, ?, ?, ?)",
                                [$id, $nombre, $email, $telefono, $id_tipo_tel]
                            );

                            if (isset($insertUser['success']) && $insertUser['success']) {
                                if ($is_ldap_enabled && trim($password) === '') {
                                    redirectWithFlashUsuario('success', 'Usuario creado correctamente. Se generó una contraseña local temporal (LDAP activo).', '', $find_key, $show_search, $page);
                                } else {
                                    redirectWithFlashUsuario('success', 'Usuario creado correctamente.', '', $find_key, $show_search, $page);
                                }
                            } else {
                                ejecutar_query("DELETE FROM sis_login WHERE id = ?", [$id]);
                                $flash_message = 'No fue posible crear el perfil del usuario.';
                                $flash_type = 'danger';
                            }
                        }
                    }
                }
            }
        }
    }
}

if ($editing_id !== '' && $form_id === '' && $form_nombre === '') {
    $editRows = seleccion_segura(
        "SELECT u.id, u.nombre, u.email, u.telefono, u.id_tipo_tel, l.id_roll
         FROM sis_user u
         INNER JOIN sis_login l ON l.id = u.id
         WHERE u.id = ?
         LIMIT 1",
        [$editing_id]
    );

    if ($editRows && isset($editRows[0]['id'])) {
        $form_id = (string)$editRows[0]['id'];
        $form_nombre = (string)$editRows[0]['nombre'];
        $form_email = (string)$editRows[0]['email'];
        $form_telefono = (string)$editRows[0]['telefono'];
        $form_id_tipo_tel = (string)$editRows[0]['id_tipo_tel'];
        $form_id_roll = (int)$editRows[0]['id_roll'];
    } else {
        $editing_id = '';
    }
}

$roles = seleccion_segura("SELECT id_roll, roll_name FROM sis_rolls ORDER BY roll_name ASC");
if (!is_array($roles)) {
    $roles = [];
}

$tiposTel = seleccion_segura("SELECT id_tipo_tel, desc_tipo_tel FROM sis_tipo_tel ORDER BY desc_tipo_tel ASC");
if (!is_array($tiposTel)) {
    $tiposTel = [];
}

$sql = "SELECT u.id, u.nombre, u.email, u.telefono, u.id_tipo_tel, l.id_roll, r.roll_name
        FROM sis_user u
        INNER JOIN sis_login l ON l.id = u.id
        LEFT JOIN sis_rolls r ON r.id_roll = l.id_roll
        WHERE 1=1";
$params = [];

if ($find_key !== '') {
    $sql .= " AND (u.id LIKE ? OR u.nombre LIKE ? OR u.email LIKE ? OR r.roll_name LIKE ?)";
    $like = '%' . $find_key . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$count_sql = "SELECT COUNT(*) AS total
        FROM sis_user u
        INNER JOIN sis_login l ON l.id = u.id
        LEFT JOIN sis_rolls r ON r.id_roll = l.id_roll
        WHERE 1=1";

$count_params = $params;
$count_sql .= ($find_key !== '') ? " AND (u.id LIKE ? OR u.nombre LIKE ? OR u.email LIKE ? OR r.roll_name LIKE ?)" : "";
$count_rows = seleccion_segura($count_sql, $count_params);
$total_records = ($count_rows && isset($count_rows[0]['total'])) ? (int)$count_rows[0]['total'] : 0;
$total_pages = max(1, (int)ceil($total_records / $per_page));
if ($page > $total_pages) {
    $page = $total_pages;
}
$offset = ($page - 1) * $per_page;

$sql .= " ORDER BY u.nombre ASC LIMIT ?, ?";
$params[] = $offset;
$params[] = $per_page;

$usuarios = seleccion_segura($sql, $params);
if (!is_array($usuarios)) {
    $usuarios = [];
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
                <h1>Gestión de Usuarios</h1>
                <p class="lead">Administración de usuarios del sistema</p>
            </div>

            <div class="alert alert-info" role="alert">
                Esta vista permite registrar, actualizar y eliminar cuentas de acceso del sistema y su rol asociado.
            </div>

            <?php if ($flash_message): ?>
                <div class="alert alert-<?= htmlspecialchars($flash_type) ?>" role="alert">
                    <?= htmlspecialchars($flash_message) ?>
                </div>
            <?php endif; ?>

            <?php if ($editing_id !== ''): ?>
                <div class="alert alert-warning border border-2 border-warning" role="alert">
                    <strong>Modo edición activo:</strong> estás modificando al usuario <?= htmlspecialchars($editing_id) ?>.
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
                            <?php if ($editing_id !== ''): ?>
                                <input type="hidden" name="edit" value="<?= htmlspecialchars($editing_id) ?>">
                            <?php endif; ?>
                            <div class="col-md-8">
                                <label class="form-label">Buscar por ID, nombre, correo o rol</label>
                                <input type="text" name="find_key" class="form-control" value="<?= htmlspecialchars($find_key) ?>" placeholder="Digite un criterio de búsqueda">
                            </div>
                            <div class="col-md-4 text-end">
                                <button type="submit" class="btn btn-info" style="margin-right: 10px;">
                                    <i class="fa fa-search"></i> Buscar
                                </button>
                                <a href="admin_usuarios.php" class="btn btn-secondary">Limpiar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="mb-3"><?= $editing_id !== '' ? 'Modificar usuario (Modo edición)' : 'Crear usuario' ?></h5>
                    <form method="post" class="row g-3">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="editing_id" value="<?= htmlspecialchars($editing_id) ?>">
                        <input type="hidden" name="show_search" value="<?= (int)$show_search ?>">
                        <input type="hidden" name="find_key" value="<?= htmlspecialchars($find_key) ?>">
                        <input type="hidden" name="page" value="<?= (int)$page ?>">

                        <div class="col-md-3">
                            <label class="form-label">ID</label>
                            <input type="text" name="id" class="form-control" required value="<?= htmlspecialchars($form_id) ?>" <?= $editing_id !== '' ? 'readonly' : '' ?>>
                        </div>

                        <div class="col-md-5">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="nombre" class="form-control" required value="<?= htmlspecialchars($form_nombre) ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Correo</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($form_email) ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($form_telefono) ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Tipo teléfono</label>
                            <select name="id_tipo_tel" class="form-select">
                                <?php foreach ($tiposTel as $tipo): ?>
                                    <?php $tipoId = (string)$tipo['id_tipo_tel']; ?>
                                    <option value="<?= htmlspecialchars($tipoId) ?>" <?= $form_id_tipo_tel === $tipoId ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string)$tipo['desc_tipo_tel']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Rol</label>
                            <select name="id_roll" class="form-select" required>
                                <option value="0">[Seleccionar]</option>
                                <?php foreach ($roles as $rol): ?>
                                    <?php $rolId = (int)$rol['id_roll']; ?>
                                    <option value="<?= $rolId ?>" <?= $form_id_roll === $rolId ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string)$rol['roll_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label"><?= $editing_id !== '' ? 'Nueva contraseña (opcional)' : 'Contraseña' ?></label>
                            <input type="password" name="password" class="form-control" <?= (!$is_ldap_enabled && $editing_id === '') ? 'required' : '' ?>>
                        </div>

                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary" style="margin-right: 10px;">
                                <i class="fa fa-save"></i> <?= $editing_id !== '' ? 'Actualizar' : 'Guardar' ?>
                            </button>
                            <?php if ($editing_id !== ''): ?>
                                <a href="admin_usuarios.php?<?= http_build_query(['show_search' => $show_search, 'find_key' => $find_key]) ?>" class="btn btn-secondary" style="margin-right: 10px;">
                                    Cancelar
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3">Listado de usuarios</h5>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Correo</th>
                                    <th>Teléfono</th>
                                    <th>Rol</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($usuarios) === 0): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No hay usuarios registrados.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($usuarios as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string)$item['id']) ?></td>
                                            <td><strong><?= htmlspecialchars((string)$item['nombre']) ?></strong></td>
                                            <td><?= htmlspecialchars((string)$item['email']) ?></td>
                                            <td><?= htmlspecialchars((string)$item['telefono']) ?></td>
                                            <td><?= htmlspecialchars((string)$item['roll_name']) ?></td>
                                            <td class="text-nowrap">
                                                <div class="d-flex align-items-center gap-2 flex-nowrap">
                                                    <form method="post" class="mb-0">
                                                        <input type="hidden" name="action" value="edit">
                                                        <input type="hidden" name="id" value="<?= htmlspecialchars((string)$item['id']) ?>">
                                                        <input type="hidden" name="editing_id" value="">
                                                        <input type="hidden" name="show_search" value="<?= (int)$show_search ?>">
                                                        <input type="hidden" name="find_key" value="<?= htmlspecialchars($find_key) ?>">
                                                        <input type="hidden" name="page" value="<?= (int)$page ?>">
                                                        <button type="submit" class="btn btn-primary btn-sm">Modificar</button>
                                                    </form>
                                                    <form method="post" class="js-delete-user-form mb-0" data-user="<?= htmlspecialchars((string)$item['id']) ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?= htmlspecialchars((string)$item['id']) ?>">
                                                        <input type="hidden" name="editing_id" value="">
                                                        <input type="hidden" name="show_search" value="<?= (int)$show_search ?>">
                                                        <input type="hidden" name="find_key" value="<?= htmlspecialchars($find_key) ?>">
                                                        <input type="hidden" name="page" value="<?= (int)$page ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm" <?= $editing_id !== '' ? 'disabled title="No disponible mientras editas un usuario"' : '' ?>>Eliminar</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                        <small class="text-muted">Mostrando máximo <?= (int)$per_page ?> usuarios por página. Total: <?= (int)$total_records ?></small>
                        <div>
                            <?php
                                $prev_disabled = ($page <= 1) ? 'disabled' : '';
                                $next_disabled = ($page >= $total_pages) ? 'disabled' : '';
                                $prev_page = max(1, $page - 1);
                                $next_page = min($total_pages, $page + 1);
                                $base_qs = ['show_search' => $show_search, 'find_key' => $find_key];
                            ?>
                            <a class="btn btn-outline-secondary btn-sm <?= $prev_disabled ?>" href="admin_usuarios.php?<?= http_build_query(array_merge($base_qs, ['page' => $prev_page])) ?>">Anterior</a>
                            <span class="mx-2">Página <?= (int)$page ?> de <?= (int)$total_pages ?></span>
                            <a class="btn btn-outline-secondary btn-sm <?= $next_disabled ?>" href="admin_usuarios.php?<?= http_build_query(array_merge($base_qs, ['page' => $next_page])) ?>">Siguiente</a>
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

        document.querySelectorAll('.js-delete-user-form').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();

                var userId = form.getAttribute('data-user') || 'este usuario';

                if (typeof Swal === 'undefined') {
                    if (confirm('¿Desea eliminar al usuario "' + userId + '"?')) {
                        form.submit();
                    }
                    return;
                }

                Swal.fire({
                    title: '¿Eliminar usuario?',
                    text: 'Se eliminará el usuario "' + userId + '".',
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
