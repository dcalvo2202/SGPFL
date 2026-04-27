<?php
// VERIFICAR AUTENTICACIÓN USANDO EL SISTEMA ESTÁNDAR
include("mod/login/check.php");
include('lang/lang.es');

// Obtener variables de sesión
$current_user_id = $mySessionController->getVar("usuario");
$current_user_name = $mySessionController->getVar("nombre");
$current_user_rol = $mySessionController->getVar("rol");

// Obtener base_url de la sesión
$cds_domain = $mySessionController->getVar("cds_domain");
$cds_locate = $mySessionController->getVar("cds_locate");
$base_url = $cds_domain . $cds_locate;

// INCLUIR ARCHIVOS NECESARIOS
include_once(__DIR__ . "/inc/db/bdcommon.inc");
include_once(__DIR__ . "/inc/db/db.php");

// Variables para mensajes
$message = null;
$message_type = null;
$user_info = null;
$role_info = null;
$is_external_advisor = false;
$google_calendar_connected = false;

// Mensajes flash desde callbacks (Google auth/disconnect)
$_flash_success = $mySessionController->getVar('success');
$_flash_error   = $mySessionController->getVar('error');
if (!empty($_flash_success)) {
    $message = $_flash_success;
    $message_type = "success";
    $mySessionController->save('success', '');
} elseif (!empty($_flash_error)) {
    $message = $_flash_error;
    $message_type = "danger";
    $mySessionController->save('error', '');
}

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cambiar_contrasena') {
    try {
        $conn = new mysqli($db_host, $usuario, $clave, $db);
        if ($conn->connect_error) {
            throw new Exception("Error de conexión: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8");
        
        // Validar que sea asesor externo aprobado
        $query_check = "SELECT id FROM external_advisor_profile_requests 
                        WHERE applicant_id = ? AND status = 'Aprobado'";
        $stmt_check = $conn->prepare($query_check);
        if (!$stmt_check) {
            throw new Exception("Error en la preparación: " . $conn->error);
        }
        
        $stmt_check->bind_param("s", $current_user_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows === 0) {
            throw new Exception("No tiene permisos para cambiar la contraseña.");
        }
        
        $stmt_check->close();
        
        // Validar campos
        $contrasena_actual = trim($_POST['contrasena_actual'] ?? '');
        $contrasena_nueva = trim($_POST['contrasena_nueva'] ?? '');
        $contrasena_confirmar = trim($_POST['contrasena_confirmar'] ?? '');
        
        if (empty($contrasena_actual)) {
            throw new Exception("Debe ingresar su contraseña actual.");
        }
        
        if (empty($contrasena_nueva)) {
            throw new Exception("Debe ingresar una nueva contraseña.");
        }
        
        if (strlen($contrasena_nueva) < 6) {
            throw new Exception("La nueva contraseña debe tener al menos 6 caracteres.");
        }
        
        if ($contrasena_nueva !== $contrasena_confirmar) {
            throw new Exception("Las contraseñas no coinciden.");
        }
        
        if ($contrasena_nueva === $contrasena_actual) {
            throw new Exception("La nueva contraseña debe ser diferente a la actual.");
        }
        
        // Verificar contraseña actual
        $query_verify = "SELECT pass FROM sis_login WHERE id = ?";
        $stmt_verify = $conn->prepare($query_verify);
        if (!$stmt_verify) {
            throw new Exception("Error en la preparación: " . $conn->error);
        }
        
        $stmt_verify->bind_param("s", $current_user_id);
        $stmt_verify->execute();
        $result_verify = $stmt_verify->get_result();
        
        if ($result_verify->num_rows === 0) {
            throw new Exception("Usuario no encontrado.");
        }
        
        $row = $result_verify->fetch_assoc();
        
        // Verificar contraseña actual usando password_verify()
        if (!password_verify($contrasena_actual, $row['pass'])) {
            throw new Exception("La contraseña actual es incorrecta.");
        }
        
        $stmt_verify->close();
        
        // Actualizar contraseña con password_hash()
        $pass_hash_nueva = password_hash($contrasena_nueva, PASSWORD_DEFAULT);
        $query_update = "UPDATE sis_login SET pass = ? WHERE id = ?";
        $stmt_update = $conn->prepare($query_update);
        
        if (!$stmt_update) {
            throw new Exception("Error en la preparación: " . $conn->error);
        }
        
        $stmt_update->bind_param("ss", $pass_hash_nueva, $current_user_id);
        
        if ($stmt_update->execute()) {
            $message = "Contraseña actualizada exitosamente.";
            $message_type = "success";
        } else {
            throw new Exception("Error al actualizar la contraseña: " . $stmt_update->error);
        }
        
        $stmt_update->close();
        $conn->close();
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = "danger";
        error_log("Error al cambiar contraseña: " . $e->getMessage());
    }
}

// Procesar actualización del perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'actualizar_perfil') {
    try {
        $conn = new mysqli($db_host, $usuario, $clave, $db);
        if ($conn->connect_error) {
            throw new Exception("Error de conexión: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8");
        
        // Validar y sanitizar datos
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        
        // Validaciones
        if (empty($nombre)) {
            throw new Exception("El nombre no puede estar vacío.");
        }
        
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("El formato del email no es válido.");
        }
        
        if (!empty($telefono) && !preg_match('/^[0-9\-\+\(\)\s]+$/', $telefono)) {
            throw new Exception("El formato del teléfono no es válido.");
        }
        
        // Actualizar en la base de datos
        $query_update = "UPDATE sis_user SET nombre = ?, email = ?, telefono = ? WHERE id = ?";
        $stmt = $conn->prepare($query_update);
        
        if (!$stmt) {
            throw new Exception("Error en la preparación: " . $conn->error);
        }
        
        $stmt->bind_param("ssss", $nombre, $email, $telefono, $current_user_id);
        
        if ($stmt->execute()) {
            // Actualizar el nombre en la sesión
            $mySessionController->save("nombre", $nombre);
            $message = "Perfil actualizado exitosamente.";
            $message_type = "success";
        } else {
            throw new Exception("Error al actualizar el perfil: " . $stmt->error);
        }
        
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = "danger";
        error_log("Error al actualizar perfil: " . $e->getMessage());
    }
}

// Conectar a la base de datos y obtener información del usuario
try {
    $conn = new mysqli($db_host, $usuario, $clave, $db);
    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8");
    
    // Verificar si es asesor externo aprobado
    $query_advisor = "SELECT id FROM external_advisor_profile_requests 
                      WHERE applicant_id = ? AND status = 'Aprobado'";
    $stmt_advisor = $conn->prepare($query_advisor);
    if (!$stmt_advisor) {
        throw new Exception("Error en la preparación: " . $conn->error);
    }
    
    $stmt_advisor->bind_param("s", $current_user_id);
    $stmt_advisor->execute();
    $result_advisor = $stmt_advisor->get_result();
    $is_external_advisor = ($result_advisor->num_rows > 0);
    $stmt_advisor->close();

    // Verificar si el usuario tiene Google Calendar conectado
    $query_google = "SELECT sync_enabled FROM google_calendar_tokens WHERE id_user = ? LIMIT 1";
    $stmt_google = $conn->prepare($query_google);
    if (!$stmt_google) {
        throw new Exception("Error en la preparación: " . $conn->error);
    }

    $stmt_google->bind_param("s", $current_user_id);
    $stmt_google->execute();
    $result_google = $stmt_google->get_result();

    if ($result_google->num_rows > 0) {
        $google_data = $result_google->fetch_assoc();
        $google_calendar_connected = ((int) ($google_data['sync_enabled'] ?? 0) === 1);
    }

    $stmt_google->close();
    
    // Obtener información del usuario de sis_user
    $query_user = "SELECT su.*, sr.roll_name, sr.roll_desc 
                   FROM sis_user su
                   JOIN sis_login sl ON su.id = sl.id
                   JOIN sis_rolls sr ON sl.id_roll = sr.id_roll
                   WHERE su.id = ?";
    
    $stmt = $conn->prepare($query_user);
    if (!$stmt) {
        throw new Exception("Error en la preparación: " . $conn->error);
    }
    
    $stmt->bind_param("s", $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user_info = $result->fetch_assoc();
        // Actualizar nombre desde BD si cambió
        $current_user_name = $user_info['nombre'];
        $role_info = array(
            'roll_name' => $user_info['roll_name'],
            'roll_desc' => $user_info['roll_desc']
        );
    }
    
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    $message = "Error al cargar el perfil: " . $e->getMessage();
    $message_type = "danger";
    error_log("Error al obtener perfil del usuario: " . $e->getMessage());
}

// Determinar si se debe mostrar pop-up (después de todas las operaciones)
$show_popup = false;
$popup_type = '';
$popup_message = '';

if (isset($message) && !empty($message)) {
    $show_popup = true;
    $popup_type = $message_type;
    $popup_message = $message;
    $message = null; // Limpiar para no mostrar duplicado
}

?>
<!DOCTYPE html>
<html lang="es">
<?php include('head.php'); ?>
<body class="fondo-una d-flex flex-column min-vh-100">

    <!-- =============================== HEADER =============================== -->
    <?php include 'header.php'; ?> 

    <!-- =============================== CONTENIDO PRINCIPAL =============================== -->
    <main class="flex-fill">
        <div class="container my-5">
            
            <!-- Alertas de mensaje -->
            <?php if ($message): ?>
                <div class="alert alert-<?= htmlspecialchars($message_type) ?> alert-dismissible fade show" role="alert">
                    <i class="bi bi-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="profile-header text-center mb-5">
                <h1 class="mb-3">Mi Perfil</h1>
            </div>

            <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                <input type="hidden" name="action" value="actualizar_perfil">
                
                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6">
                        
                        <!-- Card de Información Personal (Editable) -->
                        <div class="card shadow-sm mb-4 border-0">
                            <div class="card-header bg-rojo-una text-white d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-person-fill"></i> Información Personal
                                </h5>
                                <button type="button" class="btn btn-light btn-sm" id="btnEditar" onclick="toggleEditMode()">
                                    <i class="bi bi-pencil"></i> Editar
                                </button>
                            </div>
                            <div class="card-body">
                                <!-- ID de Usuario (No editable) -->
                                <div class="row mb-3">
                                    <label class="col-sm-5 fw-bold text-muted">ID de Usuario:</label>
                                    <div class="col-sm-7">
                                        <span class="badge bg-secondary"><?= htmlspecialchars($current_user_id) ?></span>
                                    </div>
                                </div>
                                <hr>

                                <!-- Nombre Completo (Editable) -->
                                <div class="row mb-3">
                                    <label for="nombre" class="col-sm-5 col-form-label fw-bold text-muted">Nombre Completo:</label>
                                    <div class="col-sm-7">
                                        <input type="text" class="form-control edit-field" id="nombre" name="nombre" 
                                               value="<?= htmlspecialchars($user_info['nombre'] ?? $current_user_name) ?>" 
                                               disabled required>
                                    </div>
                                </div>
                                <hr>

                                <!-- Email (Editable) -->
                                <div class="row mb-3">
                                    <label for="email" class="col-sm-5 col-form-label fw-bold text-muted">Email:</label>
                                    <div class="col-sm-7">
                                        <input type="email" class="form-control edit-field" id="email" name="email" 
                                               value="<?= htmlspecialchars($user_info['email'] ?? '') ?>" 
                                               disabled placeholder="No asignado">
                                    </div>
                                </div>
                                <hr>

                                <!-- Teléfono (Editable) -->
                                <div class="row mb-3">
                                    <label for="telefono" class="col-sm-5 col-form-label fw-bold text-muted">Teléfono:</label>
                                    <div class="col-sm-7">
                                        <input type="tel" class="form-control edit-field" id="telefono" name="telefono" 
                                               value="<?= htmlspecialchars($user_info['telefono'] ?? '') ?>" 
                                               disabled placeholder="No asignado">
                                    </div>
                                </div>

                                <!-- Botones de guardado (ocultos inicialmente) -->
                                <div id="botones-guardado" class="row mt-4" style="display: none;">
                                    <div class="col-sm-7 offset-sm-5">
                                        <button type="submit" class="btn btn-success btn-sm me-2">
                                            <i class="bi bi-check-circle"></i> Guardar Cambios
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="toggleEditMode()">
                                            <i class="bi bi-x-circle"></i> Cancelar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card de Rol y Permisos (Solo lectura) -->
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-rojo-una text-white">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-shield-check"></i> Rol y Permisos
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-sm-5 fw-bold text-muted">Rol:</div>
                                    <div class="col-sm-7">
                                        <?php if ($role_info): ?>
                                            <span class="badge bg-success"><?= htmlspecialchars($role_info['roll_name']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted"><em>No disponible</em></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-sm-5 col fw-bold text-muted mb-2">Descripción del Rol:</div>
                                </div>
                                <div class="alert alert-info" role="alert">
                                    <?php if ($role_info && !empty($role_info['roll_desc'])): ?>
                                        <?= htmlspecialchars($role_info['roll_desc']) ?>
                                    <?php else: ?>
                                        <em>No hay descripción disponible</em>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Card de Cambio de Contraseña (Solo para Asesores Externos Aprobados) -->
                        <?php if ($is_external_advisor): ?>
                        <div class="card shadow-sm border-0 mt-4">
                            <div class="card-header bg-rojo-una text-white">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-key"></i> Seguridad - Cambiar Contraseña
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" novalidate>
                                    <input type="hidden" name="action" value="cambiar_contrasena">
                                    
                                    <!-- Contraseña Actual -->
                                    <div class="mb-3">
                                        <label for="contrasena_actual" class="form-label fw-bold">Contraseña Actual:</label>
                                        <input type="password" class="form-control password-field" id="contrasena_actual" name="contrasena_actual" 
                                               required autocomplete="current-password">
                                        <small class="form-text text-muted">Ingresar su contraseña actual para confirmar el cambio</small>
                                    </div>
                                    
                                    <hr>
                                    
                                    <!-- Contraseña Nueva -->
                                    <div class="mb-3">
                                        <label for="contrasena_nueva" class="form-label fw-bold">Nueva Contraseña:</label>
                                        <input type="password" class="form-control password-field" id="contrasena_nueva" name="contrasena_nueva" 
                                               required autocomplete="new-password">
                                        <div class="password-strength"></div>
                                        <small class="form-text text-muted">Mínimo 6 caracteres. Use mayúsculas, números y caracteres especiales para mayor seguridad</small>
                                    </div>
                                    
                                    <!-- Confirmar Nueva Contraseña -->
                                    <div class="mb-3">
                                        <label for="contrasena_confirmar" class="form-label fw-bold">Confirmar Nueva Contraseña:</label>
                                        <input type="password" class="form-control password-field" id="contrasena_confirmar" name="contrasena_confirmar" 
                                               required autocomplete="new-password">
                                        <small class="form-text text-muted">Debe coincidir con la nueva contraseña</small>
                                    </div>
                                    
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-circle"></i> Actualizar Contraseña
                                        </button>
                                        <button type="reset" class="btn btn-secondary">
                                            <i class="bi bi-arrow-clockwise"></i> Limpiar
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>

                    </div>
                </div>
            </form>

            <!-- Sección Google Calendar en el perfil -->
            <div class="row justify-content-center mt-4">
                <div class="col-md-8 col-lg-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-rojo-una text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-calendar-check"></i> Sincronización con Google Calendar
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($google_calendar_connected): ?>
                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle"></i> Google Calendar está conectado
                                </div>
                                <p class="text-muted">Los eventos de tu proyecto se sincronizan automáticamente con tu calendario de Google.</p>

                                <form method="POST" action="auth/disconnect_google.php" style="display: inline;">
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('¿Desconectar Google Calendar?');">
                                        <i class="bi bi-x-circle"></i> Desconectar Google Calendar
                                    </button>
                                </form>
                            <?php else: ?>
                                <p class="text-muted">Conecta tu Google Calendar para sincronizar automáticamente los eventos de tu proyecto.</p>
                                <a href="auth/google_auth.php" class="btn btn-primary">
                                    <i class="bi bi-calendar-plus"></i> Conectar Google Calendar
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="row justify-content-center mt-5">
                <div class="col-md-8 col-lg-6 text-center">
                    <a href="<?= htmlspecialchars($base_url) ?>dashboard.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Volver al Inicio
                    </a>
                </div>
            </div>

        </div>
    </main>

    <!-- =============================== FOOTER =============================== -->
    <footer class="footer-una mt-auto">
        <div class="container">
            <p class="mb-1">&copy; <?= date('Y') ?> Universidad Nacional de Costa Rica</p>
            <small>Escuela de Informática - Proyecto SGPFL v3.0</small>
        </div>
    </footer>

    <style>
        small.form-text {
            font-size: 1.15rem;
        }
        span.badge {
            font-size: 1.30rem;
        }
        .card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
        }
        .bg-rojo-una {
            background: linear-gradient(135deg, #CD1719, #A01215) !important;
        }
        .fondo-una {
            background-color: #f8f9fa;
        }
        .edit-field:disabled {
            background-color: #f8f9fa;
            color: #212529;
            border-color: #dee2e6;
            cursor: not-allowed;
        }
        .edit-field:not(:disabled) {
            background-color: #ffffff;
            border-color: #CD1719;
            border-width: 2px;
        }
        .edit-field:focus:not(:disabled) {
            border-color: #A01215;
            box-shadow: 0 0 0 0.2rem rgba(205, 23, 25, 0.25);
        }
        .form-text {
            font-size: 0.8rem;
            color: #6c757d;
        }
        #btnEditar {
            font-weight: 600;
            transition: all 0.2s ease;
        }
        #btnEditar:hover {
            background-color: #e8e9ea !important;
            transform: scale(1.05);
        }
        
        /* Estilos para el formulario de cambio de contraseña */
        .password-field {
            border-color: #CD1719;
            transition: all 0.3s ease;
        }
        
        .password-field:focus {
            border-color: #A01215;
            box-shadow: 0 0 0 0.2rem rgba(205, 23, 25, 0.25);
        }
        
        .password-field:valid {
            border-color: #198754;
        }
        
        .password-field:invalid:not(:placeholder-shown) {
            border-color: #dc3545;
        }
        
        .password-strength {
            height: 4px;
            border-radius: 2px;
            margin-top: 5px;
            transition: all 0.3s ease;
        }
        
        .password-strength.weak {
            background-color: #dc3545;
            width: 33%;
        }
        
        .password-strength.medium {
            background-color: #ffc107;
            width: 66%;
        }
        
        .password-strength.strong {
            background-color: #198754;
            width: 100%;
        }
    </style>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function toggleEditMode() {
            const editFields = document.querySelectorAll('.edit-field');
            const btnEditar = document.getElementById('btnEditar');
            const botonesGuardado = document.getElementById('botones-guardado');
            
            editFields.forEach(field => {
                field.disabled = !field.disabled;
            });
            
            // Toggle entre botón Editar y Guardar
            const isEditing = !editFields[0].disabled;
            
            if (isEditing) {
                btnEditar.style.display = 'none';
                botonesGuardado.style.display = 'flex';
                // Enfocar el primer campo al activar edición
                editFields[0].focus();
            } else {
                btnEditar.style.display = 'block';
                botonesGuardado.style.display = 'none';
            }
        }
        
        // Validación de contraseñas
        document.addEventListener('DOMContentLoaded', function() {
            // Mostrar notificación con SweetAlert2 (debe ejecutarse primero)
            <?php if ($show_popup): ?>
                Swal.fire({
                    title: '<?= $popup_type === "success" ? "¡Éxito!" : "Error" ?>',
                    text: '<?= addslashes($popup_message) ?>',
                    icon: '<?= $popup_type === "success" ? "success" : "error" ?>',
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '<?= $popup_type === "success" ? "#198754" : "#dc3545" ?>',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then((result) => {
                    if (result.isConfirmed && '<?= $popup_type ?>' === 'success') {
                        // Si fue exitoso cambio de contraseña, limpiar el formulario
                        const contraActual = document.getElementById('contrasena_actual');
                        const contraNueva = document.getElementById('contrasena_nueva');
                        const contraConfirm = document.getElementById('contrasena_confirmar');
                        if (contraActual) contraActual.value = '';
                        if (contraNueva) contraNueva.value = '';
                        if (contraConfirm) contraConfirm.value = '';
                        // Remover indicador de fortaleza
                        const strengthIndicator = document.querySelector('.password-strength');
                        if (strengthIndicator) {
                            strengthIndicator.className = 'password-strength';
                        }
                    }
                });
            <?php endif; ?>
            
            // Validadores de contraseña
            const contrasenaNueva = document.getElementById('contrasena_nueva');
            const contrasenaConfirmar = document.getElementById('contrasena_confirmar');
            
            if (contrasenaNueva) {
                contrasenaNueva.addEventListener('input', function() {
                    validatePasswordStrength(this.value);
                    validatePasswordMatch();
                });
            }
            
            if (contrasenaConfirmar) {
                contrasenaConfirmar.addEventListener('input', function() {
                    validatePasswordMatch();
                });
            }
        });
        
        function validatePasswordStrength(password) {
            const strengthIndicator = document.querySelector('.password-strength');
            if (!strengthIndicator) return;
            
            let strength = 0;
            
            // Longitud
            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            
            // Complejidad
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength++;
            
            strengthIndicator.className = 'password-strength';
            if (strength <= 2) {
                strengthIndicator.classList.add('weak');
            } else if (strength <= 3) {
                strengthIndicator.classList.add('medium');
            } else {
                strengthIndicator.classList.add('strong');
            }
        }
        
        function validatePasswordMatch() {
            const contrasenaNueva = document.getElementById('contrasena_nueva');
            const contrasenaConfirmar = document.getElementById('contrasena_confirmar');
            
            if (!contrasenaConfirmar) return;
            
            if (contrasenaConfirmar.value === '') {
                contrasenaConfirmar.setCustomValidity('');
                contrasenaConfirmar.classList.remove('is-invalid');
            } else if (contrasenaNueva.value !== contrasenaConfirmar.value) {
                contrasenaConfirmar.setCustomValidity('Las contraseñas no coinciden');
                contrasenaConfirmar.classList.add('is-invalid');
            } else {
                contrasenaConfirmar.setCustomValidity('');
                contrasenaConfirmar.classList.remove('is-invalid');
            }
        }
    </script>

</body>
</html>
