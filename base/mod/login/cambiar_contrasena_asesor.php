<?php
/**
 * Página de recuperación de contraseña para Asesores Externos
 * Presenta un formulario para solicitar recuperación de contraseña
 */

include('../../includes.php');
include('../../lang/lang.es');
require_once __DIR__ . '/../../config.inc';

// Si llegamos aquí con un token válido en GET, mostramos el formulario de cambio de contraseña
$token = isset($_GET['token']) ? sanitize_input($_GET['token']) : '';
$token_valid = false;
$token_user = null;

if (!empty($token)) {
    // Validar token
    require_once __DIR__ . '/../../inc/db/db.php';
    
    $sql = "SELECT user_id, email, expires_at FROM password_recovery_tokens 
            WHERE token = ? AND type = 'external_advisor' AND used = 0 AND expires_at > NOW()";
    $result = seleccion_segura($sql, [$token]);
    
    if (!empty($result)) {
        $token_valid = true;
        $token_user = $result[0]['user_id'];
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <?php
        require_once __DIR__ . '/../../config.inc';
    ?>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?= $page_title ?> - Recuperar Contraseña</title>
    <link rel="icon" type="image/webp" href="<?= $favicon_url ?>">
</head>

<body class="fondo-una">
    <div class="login-container">
        <div class="login-card mx-auto">
            <img src="../../img/top.png" alt="" class="login-img" />
            
            <?php if ($token_valid): ?>
                <!-- Formulario para cambiar contraseña -->
                <div class="text-center mb-4">
                    <span class="fa-stack fa-2x mb-2">
                        <i class="fa fa-circle fa-stack-2x text-primary"></i>
                        <i class="fa fa-lock fa-stack-1x fa-inverse"></i>
                    </span>
                    <h2 class="mb-1" style="font-weight:700;">Nueva Contraseña</h2>
                    <p class="text-muted mb-3">Ingresa tu nueva contraseña</p>
                </div>

                <form id="resetPasswordForm" onsubmit="handleResetPassword(event)">
                    <input type="hidden" id="token" name="token" value="<?= htmlspecialchars($token) ?>">
                    
                    <div class="form-group">
                        <label for="new_password" class="fw-bold">Nueva Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-addon bg-white"><i class="fa fa-lock"></i></span>
                            <input id="new_password" name="new_password" type="password" 
                                   class="form-control form-control-lg" 
                                   placeholder="Ingresa tu nueva contraseña" 
                                   required minlength="8">
                            <span class="input-group-addon puntero bg-white" onclick="togglePassword('new_password')">
                                <i class="fa fa-eye" style="opacity:0.5;transition:opacity 0.2s;"></i>
                            </span>
                        </div>
                        <small class="form-text text-muted">Mínimo 8 caracteres</small>
                    </div>

                    <div class="form-group mt-3">
                        <label for="confirm_password" class="fw-bold">Confirmar Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-addon bg-white"><i class="fa fa-lock"></i></span>
                            <input id="confirm_password" name="confirm_password" type="password" 
                                   class="form-control form-control-lg" 
                                   placeholder="Confirma tu nueva contraseña" 
                                   required minlength="8">
                            <span class="input-group-addon puntero bg-white" onclick="togglePassword('confirm_password')">
                                <i class="fa fa-eye" style="opacity:0.5;transition:opacity 0.2s;"></i>
                            </span>
                        </div>
                    </div>

                    <div id="loading_container_reset" style="margin-top: 15px;"></div>
                    
                    <button id="submitReset" class="btn btn-danger btn-lg login-btn" type="submit">
                        Cambiar Contraseña
                    </button>

                    <div class="text-center p-0 mt-3">
                        <a href="../../login.php" class="btn btn-outline-secondary w-100">
                            Volver al Login
                        </a>
                    </div>
                </form>
            <?php else: ?>
                <!-- Formulario de solicitud inicial -->
                <div class="text-center mb-4">
                    <span class="fa-stack fa-2x mb-2">
                        <i class="fa fa-circle fa-stack-2x text-primary"></i>
                        <i class="fa fa-envelope fa-stack-1x fa-inverse"></i>
                    </span>
                    <h2 class="mb-1" style="font-weight:700;">Recuperar Contraseña</h2>
                    <p class="text-muted mb-3">Ingresa tu correo electrónico para resetear tu contraseña</p>
                </div>

                <form id="requestPasswordRecoveryForm" onsubmit="handlePasswordRecoveryRequest(event)">
                    <div class="form-group">
                        <label for="email" class="fw-bold">Correo Electrónico</label>
                        <div class="input-group">
                            <span class="input-group-addon bg-white"><i class="fa fa-envelope"></i></span>
                            <input id="email" name="email" type="email" class="form-control form-control-lg" 
                                   placeholder="tu@email.com" required>
                        </div>
                    </div>

                    <div id="loading_container" style="margin-top: 15px;"></div>
                    
                    <button id="submitEmail" class="btn btn-danger btn-lg login-btn" type="submit">
                        Enviar Link de Recuperación
                    </button>

                    <div class="text-center p-0 mt-3">
                        <a href="../../login.php" class="btn btn-outline-secondary w-100">
                            Volver al Login
                        </a>
                    </div>
                </form>

                <div id="recoveryMessage" style="margin-top: 20px; display: none;"></div>
            <?php endif; ?>

            <img src="../../img/bottom.png" alt="" class="login-img" />
        </div>
    </div>

    <footer class="footer mt-auto">
        <div class="container-footer">
            <p class="text-muted text-center"><?= $footer_title ?></p>
        </div>
    </footer>
</body>
</html>

<script>
// Función auxiliar para mostrar loading
function showLoading(containerId, show = true) {
    const container = document.getElementById(containerId);
    if (show) {
        container.innerHTML = '<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Procesando...</div>';
    } else {
        container.innerHTML = '';
    }
}

// Toggle de visibilidad de contraseña
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    if (field.type === 'password') {
        field.type = 'text';
    } else {
        field.type = 'password';
    }
}

// Manejar solicitud de recuperación
async function handlePasswordRecoveryRequest(event) {
    event.preventDefault();
    
    const email = document.getElementById('email').value.trim();
    const submitBtn = document.getElementById('submitEmail');
    const messageDiv = document.getElementById('recoveryMessage');
    
    if (!email) {
        Swal.fire({
            icon: 'warning',
            title: 'Correo requerido',
            text: 'Por favor ingresa un correo electrónico válido',
            confirmButtonColor: '#dc3545'
        });
        return;
    }

    submitBtn.disabled = true;
    showLoading('loading_container', true);

    try {
        const response = await fetch('./send_password_recovery_email.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                email: email,
                user_type: 'external_advisor'
            })
        });

        const data = await response.json();
        showLoading('loading_container', false);

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Correo Enviado!',
                html: `<p>Se ha enviado un link de recuperación a:</p>
                       <p style="font-weight: bold; margin: 15px 0;">${email}</p>
                       <p style="font-size: 0.85em;">Por favor revisa tu correo (incluyendo la carpeta de spam) y haz clic en el link dentro de <strong>1 hora</strong>.</p>`,
                confirmButtonColor: '#28a745',
                confirmButtonText: 'Entendido'
            });
            document.getElementById('requestPasswordRecoveryForm').style.display = 'none';
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'No se pudo procesar tu solicitud',
                confirmButtonColor: '#dc3545'
            });
        }
    } catch (error) {
        console.error('Error:', error);
        showLoading('loading_container', false);
        Swal.fire({
            icon: 'error',
            title: 'Error de Conexión',
            text: 'Ocurrió un error. Por favor intenta de nuevo.',
            confirmButtonColor: '#dc3545'
        });
    } finally {
        submitBtn.disabled = false;
    }
}

// Manejar cambio de contraseña
async function handleResetPassword(event) {
    event.preventDefault();
    
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const token = document.getElementById('token').value;
    const submitBtn = document.getElementById('submitReset');
    
    if (newPassword !== confirmPassword) {
        Swal.fire({
            icon: 'warning',
            title: 'Las contraseñas no coinciden',
            text: 'Asegúrate de que ambas contraseñas sean idénticas',
            confirmButtonColor: '#ffc107'
        });
        return;
    }

    if (newPassword.length < 8) {
        Swal.fire({
            icon: 'warning',
            title: 'Contraseña débil',
            text: 'La contraseña debe tener mínimo 8 caracteres',
            confirmButtonColor: '#ffc107'
        });
        return;
    }

    submitBtn.disabled = true;
    showLoading('loading_container_reset', true);

    try {
        const response = await fetch('./reset_password_advisor.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                token: token,
                new_password: newPassword
            })
        });

        const data = await response.json();
        showLoading('loading_container_reset', false);

        if (data.success) {
            // Limpiar localStorage para evitar caché
            if (typeof localStorage !== 'undefined') {
                try { localStorage.clear(); } catch(e) {}
            }
            
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                html: '<p style="margin-bottom: 15px;">Tu contraseña ha sido <strong>actualizada correctamente</strong></p><p style="font-size: 0.9em; color: #666;">Redirigiendo al login...</p>',
                allowOutsideClick: false,
                allowEscapeKey: false,
                confirmButtonColor: '#28a745',
                confirmButtonText: 'Ir a Login',
                timer: 3000,
                timerProgressBar: true,
                didOpen: () => {
                    // Después de que se cierre, redirigir
                }
            }).then(() => {
                // Usar replace para no guardar en historial
                window.location.replace('../../login.php');
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Error al actualizar la contraseña',
                confirmButtonColor: '#dc3545'
            });
        }
    } catch (error) {
        console.error('Error:', error);
        showLoading('loading_container_reset', false);
        Swal.fire({
            icon: 'error',
            title: 'Error de Conexión',
            text: 'Ocurrió un error. Por favor intenta de nuevo.',
            confirmButtonColor: '#dc3545'
        });
    } finally {
        submitBtn.disabled = false;
    }
}

// Validar token en URL si existe
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token');
    
    if (token && '<?= $token_valid ? "true" : "false" ?>' === 'false') {
        // Token inválido o expirado
        const messageDiv = document.getElementById('recoveryMessage');
        if (messageDiv) {
            messageDiv.className = 'alert alert-danger';
            messageDiv.innerHTML = `
                <h4><i class="fa fa-exclamation-circle"></i> Token Inválido</h4>
                <p>El link de recuperación es inválido o ha expirado. Por favor solicita un nuevo link.</p>
            `;
            messageDiv.style.display = 'block';
            document.getElementById('requestPasswordRecoveryForm').style.display = 'block';
        }
    }
});
</script>

<style>
.login-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    padding: 20px;
}

.login-card {
    width: 100%;
    max-width: 450px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 30px;
}

.login-img {
    width: 100%;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 15px;
}

.input-group-addon {
    border: 1px solid #ced4da;
    color: #495057;
}

.form-control:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}

.login-btn {
    width: 100%;
    padding: 12px;
    font-size: 16px;
}

.alert {
    padding: 15px;
    border-radius: 4px;
}

.alert-success {
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.alert-danger {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}
</style>

<?php
function sanitize_input($input) {
    return htmlspecialchars(stripslashes(trim($input)));
}
?>
