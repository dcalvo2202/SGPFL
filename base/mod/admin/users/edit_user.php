<?php
include("../../login/check.php");
include("../../../functions.php");
$vocab = $mySessionController->getVar("vocab");
$user_rol = $mySessionController->getVar("rol");
$id_user = $_GET['id_user'];
$view_mode = $_GET['view_mode'];
$sql = "SELECT a.id, a.nombre, a.email, a.telefono, a.id_tipo_tel, b.id_roll FROM sis_user a, sis_login b WHERE a.id='$id_user' AND b.id='$id_user';";
$res = seleccion($sql);
?>
<div class="container">
    <div class="well well-sm">
        <h2>
            <?php
            echo ($view_mode == 1) ? $vocab["symbol_edit"] : $vocab["symbol_view"];
            echo " " . $vocab["user_title"];
            ?>
        </h2>
        <p><?= $vocab["user_title_desc"] ?></p>
    </div>
    <div class="col-lg-5 col-md-5 col-sm-8 col-xs-12">
        <form method="post" action="">
            <div class="form-group">
                <label for="id_user"><?= $vocab["user_id"] ?> </label>
                <input <?= ($view_mode == 0) ? "readonly" : ""; ?> id="id_user" name="id_user" class="form-control" type="text" onkeyup="javascrip:onchange_cedula();" onblur="javascrip:onchange_cedula();" value="<?= $res[0]['id'] ?>"/> 
                <p class="help-block"><small><?= $vocab["user_id_desc"] ?></small></p> 
            </div>
            <div class="form-group">
                <label for="nombre_txt"><?= $vocab["symbol_name"] ?></label>
                <input id="nombre_txt" name="nombre_txt" class="form-control" type="text" value="<?= $res[0]['nombre'] ?>" <?= ($ldap_status == 1 || $view_mode == 0) ? "readonly" : "" ?>/> 
                <p class="help-block"><small><?= $vocab["user_name_desc"] ?></small></p> 
            </div>	
            <div class="form-group">
                <label for="correo_txt"><?= $vocab["user_mail"] ?> </label>
                <input <?= ($view_mode == 0) ? "readonly" : ""; ?> id="correo_txt" name="correo_txt" class="form-control" type="text" value="<?= $res[0]['email'] ?>"/> 
                <p class="help-block"><small><?= $vocab["user_mail_desc"] ?></small></p> 
            </div>
            <div class="form-group">
                <label for="telefono_txt"><?= $vocab["user_tel"] ?> </label>
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                        <input <?= ($view_mode == 0) ? "readonly" : ""; ?> id="telefono_txt" name="telefono_txt" class="form-control" type="text" value="<?= $res[0]['telefono'] ?>" onkeypress="return onlyNumbers(event, 0);"/>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                        <select <?= ($view_mode == 0) ? "disabled" : ""; ?> name="tipo_tel" id="tipo_tel" class="form-control" >
                            <?php
                            $sqlb = "SELECT id_tipo_tel, desc_tipo_tel FROM sis_tipo_tel ORDER BY desc_tipo_tel;";
                            echo $sqlb;
                            $resb = seleccion($sqlb);
                            foreach ($resb as $row) {
                                $selected = ($res[0]['id_tipo_tel'] == $row["id_tipo_tel"]) ? "selected='selected'" : " ";
                                echo("<option " . $selected . " value='" . $row["id_tipo_tel"] . "'>" . $row["desc_tipo_tel"] . "</option>");
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <p class="help-block"><small><?= $vocab["user_tel_desc"] ?></small></p> 
            </div>
            <div class="form-group">
                <label for="rol_slc"><?= $vocab["rols"] ?> </label>
                <select <?= ($view_mode == 0) ? "disabled" : ""; ?> name="rol_slc" id="rol_slc" class="form-control" >
                    <?php
                    if ($view_mode == 0) {
                        $sqlc = "SELECT id_roll, roll_name FROM sis_rolls ORDER BY roll_name;";
                    } else {
                        $sqlc = "SELECT id_roll, roll_name FROM sis_rolls WHERE id_roll !=1 ORDER BY roll_name;";
                    }
                    $resc = seleccion($sqlc);
                    foreach ($resc as $row) {
                        $selected = ($res[0]['id_roll'] == $row["id_roll"]) ? "selected='selected'" : " ";
                        echo("<option " . $selected . " value='" . $row["id_roll"] . "'>" . $row["roll_name"] . "</option>");
                    }
                    ?>
                </select>
                <p class="help-block"><small><?= $vocab["rols_for_user"] ?></small></p> 
            </div>
            <div class="row">
                <?php if (check_permiso($mod3, $act4, $user_rol) && $view_mode == 1) { ?>
                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                        <a <?= ($view_mode == 0) ? "readonly" : ""; ?> class="btn btn-success btn-group-justified" name="submit" onclick="update_user('<?= $id_user ?>');"><i class="fa fa-save fa-inverse"></i> <?= $vocab["symbol_update"] . " " . $vocab["login_user"] ?></a>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                        <a class="btn btn-warning btn-group-justified" name="submit" onclick="javascript:OpcionMenu('mod/admin/users/list_user.php?', '');"><i class="fa fa-rotate-left"></i> <?= $vocab["symbol_return"] ?></a>
                    </div>
                <?php } else { ?>
                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12"></div>
                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                        <a class="btn btn-warning btn-group-justified" name="submit" onclick="javascript:OpcionMenu('mod/admin/users/list_user.php?', '');"><i class="fa fa-rotate-left"></i> <?= $vocab["symbol_return"] ?></a>
                    </div>
                <?php } ?>
            </div>
        </form>
    </div>
</div>