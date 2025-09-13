<?php
require_once 'config.inc';
?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <title><?= $page_title ?></title>
        <?php
        include('includes.php');
        include('lang/lang.es');
        ?>
    </head>
    <body>
        <!-- Valores Ocultos -->
        <input type="hidden" id="cds_domain_locate" value="<?php echo $cds_domain . $cds_locate; ?>"/>
        <!-- --------------- -->
        <div class="container">
            <div class="col-lg-3 col-md-3 col-sm-2 col-xs-12"></div>
            <div class="col-lg-6 col-md-6 col-sm-8 col-xs-12">
                <img id="top" class="img_fix_full" src="img/top.png" alt="">
                <div class="well well-sm">
                    <h2><?= $vocab["login_title"] ?></h2>
                    <p><?= $vocab["login_title_desc"] ?></p>
                </div>
                <div id="loading_container"></div>
                <div class="row">
                    <div class="col-lg-1 col-md-1 col-sm-1 col-xs-12"></div>
                    <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12 text-center text-danger">
                        <div id="fix_valing_log"></div>
                        <span class="fa-stack fa-3x">
                            <i class="fa fa-circle fa-stack-2x"></i>
                            <i class="fa fa-key fa-stack-1x fa-inverse"></i>
                        </span>
                    </div>
                    <div class="col-lg-1 col-md-1 col-sm-1 col-xs-12"></div>
                    <div class="col-lg-7 col-md-7 col-sm-7 col-xs-12">
                        <form method="post" action="mod/login/ajax_login.php">
                            <div class="form-group">
                                <label for="user"><?= $vocab["login_user"] ?> </label>
                                <input id="user" name="user" class="form-control" type="text" placeholder="<?= $vocab["login_user"] ?>" onkeypress="return onlyNumbers(event, 0);"/>
                                <p class="help-block" id="guia_1"><small><?= $vocab["login_user_desc"] ?></small></p>
                            </div>
                            <div class="form-group">
                                <label for="pass"><?= $vocab["login_pass"] ?></label>
                                <input id="pass" name="pass" type="password" class="form-control" placeholder="<?= $vocab["login_pass"] ?>" onkeypress="onEnterLogin(event);">
                                <p class="help-block" id="guia_2"><small><?= $vocab["login_pass_desc"] ?></small></p> 
                            </div>
                            <input id="saveForm" class="btn btn-group-justified btn-danger" type="button" name="submit" value="<?= $vocab["login_but_start"] ?>" onclick="Do_Login();" />
                        </form>
                    </div>
                </div>
                <br>
                <img id="bottom" class="img_fix_full" src="img/bottom.png" alt="">
            </div>
        </div>
        <footer class="footer">
            <div class="container">
                <p class="text-muted text-center"><?= $footer_title ?></p>
            </div>
        </footer>
    </body>
</html>