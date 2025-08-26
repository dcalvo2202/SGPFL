<?php
include("../../login/check.php");
include("../../../functions.php");
$vocab = $mySessionController->getVar("vocab");
$user_rol = $mySessionController->getVar("rol");
$id_user = $mySessionController->getVar("usuario");
$sql = "SELECT id, nombre, email, telefono, id_tipo_tel FROM sis_user WHERE id='$id_user';";
$res = seleccion($sql);
?>
<div class="container">
    <div class="well well-sm">
        <h2><?= $vocab["symbol_edit"] . " " . $vocab["menu_perfil"] ?></h2>
        <p><?= $vocab["user_perfil_desc"] ?></p>
    </div>
    <div class="col-lg-5 col-md-5 col-sm-8 col-xs-12">
        <form method="post" action="">
            <div class="form-group">
                <label for="id_user"><?= $vocab["user_id"] ?> </label>
                <input readonly id="id_user" name="id_user" class="form-control" type="text" maxlength="255" onkeyup="javascrip:onchange_cedula();" onblur="javascrip:onchange_cedula();" value="<?= $res[0]['id'] ?>"/> 
                <p class="guidelines" id="guide_1"><small><?= $vocab["user_id_desc"] ?></small></p> 
            </div>	
            <div class="form-group">
                <label for="nombre_txt"><?= $vocab["symbol_name"] ?></label>
                <input readonly id="nombre_txt" name="nombre_txt" class="form-control" type="text" maxlength="255" value="<?= $res[0]['nombre'] ?>"/> 
                <p class="guidelines" id="guide_2"><small><?= $vocab["user_name_desc"] ?></small></p> 
            </div>	
            <div class="form-group">
                <label for="correo_txt"><?= $vocab["user_mail"] ?> </label>
                <input id="correo_txt" name="correo_txt" class="form-control" type="text" maxlength="255" value="<?= $res[0]['email'] ?>"/> 
                <p class="guidelines" id="guide_3"><small><?= $vocab["user_mail_desc"] ?></small></p> 
            </div>
            <div class="form-group">
                <label for="telefono_txt"><?= $vocab["user_tel"] ?> </label>
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                        <input id="telefono_txt" name="telefono_txt" class="form-control" type="text" maxlength="255" value="<?= $res[0]['telefono'] ?>" onkeypress="return onlyNumbers(event, 0)"/>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                        <select name="tipo_tel" id="tipo_tel" class="form-control" >
                            <?php
                            $sqlb = "SELECT id_tipo_tel, desc_tipo_tel FROM sis_tipo_tel ORDER BY desc_tipo_tel;";
                            // echo $sqlb; //DEBUG
                            $resb = seleccion($sqlb);
                            foreach ($resb as $row) {
                                $selected = ($res[0]['id_tipo_tel'] == $row["id_tipo_tel"]) ? "selected='selected'" : " ";
                                echo("<option " . $selected . " value='" . $row["id_tipo_tel"] . "'>" . $row["desc_tipo_tel"] . "</option>");
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <p class="guidelines" id="guide_1"><small><?= $vocab["user_tel_desc"] ?></small></p> 
            </div>
            <?php
            if ($ldap_status == 0) {
                ?>
                <div class="form-group">
                    <label for="pass_user"><?= $vocab["login_pass"] ?> </label>

                    <input id="pass_user" name="pass_user" class="form-control" type="password" maxlength="15" value="" onkeyup="CheckPasswordStrength(this.value)"/>
                    <label id="pass_strength"><font style='color:red'>&nbsp;&nbsp;*Inseguro</font></label><br>
                    <input id="pass_user_c" name="pass_user_c" class="form-control" type="password" maxlength="15" value="" /><br>
                    <small><p>&nbsp;&nbsp;*<?= $vocab["login_pass_conf"] ?></p></small>
                    <p class="guidelines" id="guide_1"><small><?= $vocab["login_pass_desc"] ?></small></p> 
                </div>
            <?php } ?>
            <div class="text-center"><a class="btn btn-success" name="submit" onclick="update_perfil('<?= $id_user ?>');"><i class="fa fa-save fa-inverse"></i> <?= $vocab["symbol_update"] . " " . $vocab["menu_perfil"] ?></a></div>
        </form>
    </div>
</div>