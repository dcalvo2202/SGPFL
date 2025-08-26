<?php
error_reporting(0);
include("mod/login/check.php");
$vocab = $mySessionController->getVar("vocab");
$user_rol = $mySessionController->getVar("rol");
?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <title><?php echo $mySessionController->getVar("page_title") ?></title>
        <?php
        include('includes.php');
        ?>
    </head>
    <body onload="javascript:OpcionMenu('home.php?', '');">
        <div style="height: 60px;">
            <?php include('menu.php'); ?>
        </div>
        <!-- Variables Ocultas -->
        <input type="hidden" id="cds_domain_locate" value="<?php echo $mySessionController->getVar('cds_domain') . $mySessionController->getVar('cds_locate'); ?>"/>
        <!-- ----------------- -->
        <img id="top" src="img/top.png" style="margin-bottom: 10px;" class="img_fix_full" alt="">
        <div class="container">           
            <div id="form_container">
                <div id="loading_container"></div>
                <div id="container"></div>
            </div>
        </div>
        <img id="bottom" src="img/bottom.png" style="margin-top: 10px;" class="img_fix_full" alt="">
        <footer class="footer">
            <div class="container">
                <p class="text-muted text-center"><?php echo $footer_title; ?></p>
            </div>
        </footer>
    </body>
</html>
