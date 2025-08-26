<?php
include("../../login/check.php");
include("../../../functions.php");
$vocab = $mySessionController->getVar("vocab");
$user_rol = $mySessionController->getVar("rol");
?>
<div class="container">
    <div class="well well-sm">
        <h2><?= $vocab["user_title"] ?></h2>
        <p><?= $vocab["user_title_desc"] ?></p>
    </div>
    <div class="col-lg-5 col-md-5 col-sm-8 col-xs-12">
        <form method="post" action="">
            <div class="form-group">
                <label  for="id_user"><?= $vocab["user_id"] ?> </label>
                <input id="id_user" name="id_user" class="form-control" type="text" placeholder="123456789" onchange="javascrip:onchange_cedula();" onblur="javascrip:onchange_cedula();" /> 
                <p class="help-block"><small><?= $vocab["user_id_desc"] ?></small></p> 
            </div>
            <div class="form-group">
                <label  for="nombre_txt"><?= $vocab["symbol_name"] ?></label>
                <input id="nombre_txt" name="nombre_txt" class="form-control" type="text" value="" <?= ($ldap_status == 1) ? "readonly" : "" ?>/> 
                <p class="help-block"><small><?= $vocab["user_name_desc"] ?></small></p> 
            </div>
            <div class="form-group">
                <label  for="correo_txt"><?= $vocab["user_mail"] ?> </label>
                <input id="correo_txt" name="correo_txt" class="form-control" type="text" value=""/> 
                <p class="help-block"><small><?= $vocab["user_mail_desc"] ?></small></p> 
            </div>
            <div class="form-group">
                <label  for="telefono_txt"><?= $vocab["user_tel"] ?> </label>
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                        <input id="telefono_txt" name="telefono_txt" class="form-control" type="text" value="" onkeypress="return onlyNumbers(event, 0)"/>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                        <select name="tipo_tel" id="tipo_tel" class="form-control" >
                            <option value="0" value="" selected="selected">[<?= $vocab["symbol_select"] ?>]</option>
                            <?php
                            $sql1 = "SELECT id_tipo_tel, desc_tipo_tel FROM sis_tipo_tel ORDER BY desc_tipo_tel";
                            $res1 = seleccion($sql1);
                            foreach ($res1 as $row) {
                                echo("<option value='" . $row["id_tipo_tel"] . "'>" . $row["desc_tipo_tel"] . "</option>");
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <p class="help-block"><small><?= $vocab["user_tel_desc"] ?></small></p> 
            </div>
            <div class="form-group">
                <label  for="telefono_txt"><?= $vocab["rols"] ?> </label>
                <select name="rol_slc" id="rol_slc" class="form-control" >
                    <option value="0" value="" selected="selected">[<?= $vocab["symbol_select"] ?>]</option>
                    <?php
                    $sql = "SELECT id_roll, roll_name FROM sis_rolls WHERE id_roll !=1 ORDER BY roll_name";
                    $res = seleccion($sql);
                    foreach ($res as $row) {
                        echo("<option value='" . $row["id_roll"] . "'>" . $row["roll_name"] . "</option>");
                    }
                    ?>
                </select>
                <p class="help-block"><small><?= $vocab["rols_for_user"] ?></small></p> 
            </div>
            <?php
            if ($ldap_status == 0) {
                ?>
                <div class="form-group">
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                            <label  for="pass_user"><?= $vocab["login_pass"] ?> </label>
                            <input id="pass_user" name="pass_user" class="form-control" type="password" maxlength="15" value="" onkeyup="CheckPasswordStrength(this.value)"/>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                            <label><?= $vocab["login_pass_conf"] ?></label>
                            <input id="pass_user_c" name="pass_user_c" class="form-control" type="password" maxlength="15" value="" />
                        </div>
                    </div>
                    <p class="help-block"><small><?= $vocab["login_pass_desc"] ?></small> <label id="pass_strength"><font style='color:red'>&nbsp;&nbsp;*Inseguro</font></label> </p> 
                </div>
                <?php if (check_permiso($mod3, $act3, $user_rol)) { ?>
                    <div class="text-center"><a class="btn btn-success"  name="submit" onclick="new_user(0);"><i class="fa fa-save fa-inverse"></i> <?= $vocab["symbol_save"] . " " . $vocab["login_user"] ?>  </a></div>
                <?php } ?>

            <?php } else { ?>
                <?php if (check_permiso($mod3, $act3, $user_rol)) { ?>
                    <div class="text-center"><a class="btn btn-success"  name="submit" onclick="new_user(1);"><i class="fa fa-save fa-inverse"></i> <?= $vocab["symbol_save"] . " " . $vocab["login_user"] ?>  </a></div>
                <?php } ?>

            <?php } ?>
        </form>
    </div>
</div>