<?php
/**
 * Formulario para editar el modulo
 */
include("../../login/check.php");
include("../../../functions.php");
$vocab = $mySessionController->getVar("vocab");
$user_rol = $mySessionController->getVar("rol");
$id_mod = $_GET['id_mod'];
$view_mode = $_GET['view_mode'];
$sql = "SELECT mod_name, mod_desc FROM sis_mod WHERE id_mod=" . $id_mod;
$res = seleccion($sql);
?>
<div class="container">
    <div class="well well-sm">
        <h2>
            <?php
            echo ($view_mode == 1) ? $vocab["symbol_edit"] : $vocab["symbol_view"];
            echo " " . $vocab["permits_title"];
            ?>
        </h2>
        <p><?= $vocab["permits_title_desc"] ?></p>
    </div>
    <div class="col-lg-5 col-md-5 col-sm-8 col-xs-12">
        <form method="post" action="">
            <input type="hidden" id="id_mod" name="id_mod" value="<?= $id_mod ?>"/>
            <div class="form-group">
                <label for="name_mod"><?= $vocab["symbol_name"] ?> </label>
                <input <?= ($view_mode == 0) ? "readonly" : ""; ?> id="name_mod" name="name_mod" class="form-control" type="text" maxlength="255" value="<?= $res[0]['mod_name'] ?>"/> 
                <p class="help-block"><small><?= $vocab["permits_name_desc"] ?></small></p> 
            </div>
            <div class="form-group">
                <label for="desc_mod"><?= $vocab["symbol_desc"] ?> </label>
                <textarea <?= ($view_mode == 0) ? "readonly" : ""; ?> id="desc_mod" name="desc_mod" class="form-control" rows="2"><?= $res[0]['mod_desc'] ?></textarea> 
                <p class="help-block"><small><?= $vocab["permits_desc_desc"] ?></small></p> 
            </div>
            <?php
            if ($view_mode == 1) {
                if (check_permiso($mod1, $act4, $user_rol)) {
                    ?>
                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                        <a <?= ($view_mode == 0) ? "readonly" : ""; ?> class="btn btn-success btn-group-justified"  name="submit" onclick="update_mod();"><i class="fa fa-save fa-inverse"></i> <?= $vocab["symbol_update"] . " " . $vocab["permits_title"] ?></a>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                        <a class="btn btn-warning btn-group-justified"  name="submit" onclick="javascript:OpcionMenu('mod/admin/permits/list_mod.php?', '');"><i class="fa fa-rotate-left"></i> <?= $vocab["symbol_return"] ?></a>
                    </div>
                    <?php
                }
            } else {
                ?>
                <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12"></div>
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                    <a class="btn btn-warning btn-group-justified"  name="submit" onclick="javascript:OpcionMenu('mod/admin/permits/list_mod.php?', '');"><i class="fa fa-rotate-left"></i> <?= $vocab["symbol_return"] ?></a>
                </div>
            <?php } ?>
        </form>	
    </div>
</div>