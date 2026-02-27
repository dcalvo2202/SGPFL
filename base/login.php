<!DOCTYPE html>
<html>
    <head>
    <?php
        include('includes.php');
        include('lang/lang.es');
        require_once __DIR__ . '/config.inc';
        require_once __DIR__ . '/config.inc';
        require_once __DIR__ . '/lib/mysession/mySession.conf.php';
        // Si existe una cookie de sesión previa, la limpiamos para evitar conflictos
        if (isset($_COOKIE[$_MYSESSION_CONF['SID']])) {
            setcookie($_MYSESSION_CONF['SID'], '', time()-3600, '/base/'); // usa mismo path que en conf
        }
    ?>
    <script>
    window.BASE_URL = '<?php echo rtrim($cds_domain, '/') . '/' . trim($cds_locate, '/') . '/'; ?>';
    </script>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?= $page_title ?></title>
    <!-- Logo de la escuela -->
    <link rel="icon" type="image/webp" href="<?= $favicon_url ?>">
    </head>

    <body class="fondo-una">
        <!-- Valores Ocultos -->
        <input type="hidden" id="cds_domain_locate" value="<?php echo $cds_domain . $cds_locate; ?>"/>
        <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
        <!-- --------------- -->
    
        <div class="login-container">
            <div class="login-card mx-auto">
                <img src="img/top.png" alt="" class="login-img" />
                <div class="text-center mb-4">
                    <span class="fa-stack fa-2x mb-2">
                        <i class="fa fa-circle fa-stack-2x text-primary"></i>
                        <i class="fa fa-key fa-stack-1x fa-inverse"></i>
                    </span>
                    <h2 class="mb-1" style="font-weight:700;"><?= $vocab["login_title"] ?></h2>
                    <p class="text-muted mb-3"><?= $vocab["login_title_desc"] ?></p>
                </div>
                <form method="post" action="mod/login/ajax_login.php" onsubmit="Do_Login(); return false;">
                    <div class="form-group">
                        <label for="user" class="fw-bold"><?= $vocab["login_user"] ?> </label>
                        <div class="input-group">
                            <span class="input-group-addon bg-white"><i class="fa fa-user"></i></span>
                            <input id="user" name="user" class="form-control form-control-lg" type="text" placeholder="<?= $vocab["login_user_desc"] ?>" onkeypress="onEnterLogin(event);"/>
                        </div>
                        <!--<p class="help-block" id="guia_1"><small><?= $vocab["login_user_desc"] ?></small></p> -->
                    </div>
                    <div class="form-group mt-4">
                        <label for="pass" class="fw-bold"><?= $vocab["login_pass"] ?></label>
                        <div class="input-group">
                            <span class="input-group-addon bg-white"><i class="fa fa-lock"></i></span>
                            <input id="pass" name="pass" type="password" class="form-control form-control-lg" placeholder="<?= $vocab["login_pass_desc"] ?>" onkeypress="onEnterLogin(event);" autocomplete="new-password">
                            <span class="input-group-addon puntero bg-white" onclick="togglePassword()">
                                <i class="fa fa-eye" id="togglePasswordIcon" style="opacity:0.5;transition:opacity 0.2s;"></i>
                            </span>
                        </div>
                       <!-- <p class="help-block" id="guia_2"><small><?= $vocab["login_pass_desc"] ?></small></p> -->
                    </div>

                    <div class="text-center mt-2">
                        <button type="button" class="btn btn-link w-100 mt-2" onclick="showPasswordRecoveryOptions()" tabindex="-1">
                            ¿Olvidó su nombre de usuario o contraseña?
                        </button>
                    </div>
                    <div id="loading_container"></div>
                    <button id="saveForm" class="btn btn-danger btn-lg login-btn" type="submit" name="submit">
                        <?= $vocab["login_but_start"] ?>
                    </button>
                    
                    <div class="text-center p-0">
                        <a href="registro.php" class="btn btn-outline-secondary w-100" tabindex="-1">
                            ¿Eres Asesor Externo? <strong>Solicita tu registro aquí</strong>
                        </a>
                    </div>
                </form>

                <img src="img/bottom.png" alt="" class="login-img" />
            </div>
        </div>

        <!-- Modal para recuperación de contraseña -->
        <div class="modal fade" id="passwordRecoveryModal" tabindex="-1" role="dialog" aria-labelledby="passwordRecoveryLabel">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h4 class="modal-title" id="passwordRecoveryLabel">
                            <i class="fa fa-lock"></i> Recuperar Contraseña
                        </h4>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">¿Cuál es tu tipo de usuario?</p>
                        <div class="buttonsRecovery">
                            <button type="button" class="btn btn-primary btn-block" onclick="redirectPasswordRecovery('regular')">
                                <i class="fa fa-user"></i> Usuario Regular
                            </button>
                            <button type="button" class="btn btn-primary btn-block" style="margin-top:10px;" onclick="redirectPasswordRecovery('external_advisor')">
                                <i class="fa fa-graduation-cap"></i> Asesor Externo
                            </button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    </div>
                </div>
            </div>
        </div>

        <footer class="footer mt-auto">
            <div class="container-footer">
                <p class="text-muted text-center"><?= $footer_title ?></p>
            </div>
        </footer>
    </body>
</html>

<style>
/* Centrar modal usando posicionamiento absoluto */
.modal.fade.in {
    padding: 0 !important;
    display: block;
    padding-top: 150px !important;
}

.modal.fade.in .modal-dialog {
    margin: 50px auto;
}
</style>

<script>
// Mostrar modal usando vanilla JavaScript
function showPasswordRecoveryOptions() {
    // Esperar a que jQuery esté listo
    if (typeof jQuery !== 'undefined') {
        jQuery('#passwordRecoveryModal').modal('show');
    } else {
        // Fallback si jQuery no está disponible
        var modal = document.getElementById('passwordRecoveryModal');
        if (modal) {
            modal.style.display = 'block';
            modal.classList.add('in');
        }
    }
}

// Redirigir según el tipo de usuario seleccionado
function redirectPasswordRecovery(userType) {
    if (typeof jQuery !== 'undefined') {
        jQuery('#passwordRecoveryModal').modal('hide');
    } else {
        var modal = document.getElementById('passwordRecoveryModal');
        if (modal) {
            modal.style.display = 'none';
            modal.classList.remove('in');
        }
    }
    
    if (userType === 'regular') {
        // Usuario regular: redirigir al sitio de recuperación estándar
        window.open('https://recuperacion.una.ac.cr/', '_blank');
    } else if (userType === 'external_advisor') {
        // Asesor externo: redirigir a su propio proceso de recuperación
        window.location.href = 'mod/login/cambiar_contrasena_asesor.php';
    }
}
</script>