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
                        <a href="https://recuperacion.una.ac.cr/" target="_blank" class="btn btn-link w-100 mt-2" tabindex="-1">
                        ¿Olvidó su nombre de usuario o contraseña?
                        </a>   
                    </div>
                    <div id="loading_container"></div>
                    <button id="saveForm" class="btn btn-danger btn-lg login-btn" type="submit" name="submit">
                        <?= $vocab["login_but_start"] ?>
                    </button>
                    <!-- Apartado de registro: prototipo, redirige a una vista no implementada -->
                    
                    <div class="text-center p-0">
                        <a href="registro.php" class="btn btn-outline-secondary w-100" tabindex="-1">
                            <!--¿No tienes cuenta? Regístrate aquí -->
                        </a>
                        <!-- Implementar la vista registro.php -->
                    </div>
                </form>

                <img src="img/bottom.png" alt="" class="login-img" />
            </div>
        </div>
        <footer class="footer">
            <div class="container-footer">
                <p class="text-muted text-center"><?= $footer_title ?></p>
            </div>
        </footer>
    </body>
</html>