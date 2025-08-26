<?php
/**
 * Formulario para editar un rol
 */
include("../../login/check.php");
include("../../../functions.php");
$vocab = $mySessionController->getVar("vocab");
$user_rol = $mySessionController->getVar("rol");
$id_roll = $_GET['id_roll'];
$view_mode = $_GET['view_mode'];
$sql = "SELECT roll_name, roll_desc FROM sis_rolls WHERE id_roll=" . $id_roll;
$res = seleccion($sql);

function check_modulo($mod, $rol) {
    $sql1 = "SELECT COUNT(id_mod) AS cant
             FROM sis_permits 
             WHERE id_mod = " . $mod . "
              AND id_roll = " . $rol . ";";
    $res1 = seleccion($sql1);
    if ($res1[0]['cant'] > 0) {
        return 1;
    } else {
        return 0;
    }
}
?>
<div class="container">
    <div class="well well-sm">
        <h2>
            <?php
            echo ($view_mode == 1) ? $vocab["symbol_edit"] : $vocab["symbol_view"];
            echo " " . $vocab["rols_title"];
            ?>
        </h2>
        <p><?= $vocab["rols_title_desc"] ?></p>
    </div>
    <form method="post" action="">
        <div class="row">
            <div class="col-lg-5 col-md-5 col-sm-8 col-xs-12">
                <input type="hidden" id="id_roll" name="id_roll" value="<?= $id_roll ?>"/>
                <div class="from-group">
                    <label for="name_roll"><?= $vocab["symbol_name"] ?> </label>
                    <input <?= ($view_mode == 0) ? "readonly" : ""; ?> id="name_roll" name="name_roll" class="form-control" type="text" maxlength="255" value="<?= $res[0]['roll_name'] ?>"/> 
                    <p class="help-block"><small><?= $vocab["rols_name_desc"] ?></small></p> 
                </div>		
                <div class="from-group">
                    <label for="desc_roll"><?= $vocab["symbol_desc"] ?></label>
                    <textarea <?= ($view_mode == 0) ? "readonly" : ""; ?> id="desc_roll" name="desc_roll" class="form-control"><?= $res[0]['roll_desc'] ?></textarea> 
                    <p class="help-block"><small><?= $vocab["rols_desc_desc"] ?></small></p> 
                </div>
                <?php
                $sql = "SELECT id_mod, mod_name, mod_desc FROM sis_mod WHERE active = '1';";
                $res2 = seleccion($sql);
                $i = 0;
                ?>
                <div class="dataTables_wrapper form-inline dt-bootstrap center-block">
                    <table class="table table-striped table-bordered dataTable">
                        <thead>
                            <tr>
                                <th><?= $vocab["permits_title"] ?></th>
                                <th><div class="text-center"><i class="fa fa-eye fa-2x text-info" title="<?= $vocab["symbol_view"] ?>"></i></div></th>
                                <th><div class="text-center"><i class="fa fa-list fa-2x text-primary" title="<?= $vocab["menu_list"] ?>"></i></div></th>
                                <th><div class="text-center"><i class="fa fa-save fa-2x text-success" title="<?= $vocab["menu_add"] ?>"></i></div></th>
                                <th><div class="text-center"><i class="fa fa-pencil fa-2x text-success" title="<?= $vocab["symbol_edit"] ?>"></i></div></th>
                                <th><div class="text-center"><i class="fa fa-close fa-2x text-danger" title="<?= $vocab["symbol_delete"] ?>"></i></div></th>
                                <th><div class="text-center"><i class="fa fa-print fa-2x text-primary" title="<?= $vocab["symbol_print"] ?>"></i></div></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($res2 as $row) { ?>
                                <tr>
                                    <td><input <?= ($view_mode == 0) ? "disabled='disabled'" : ""; ?>type="checkbox" id="mod<?= $i ?>" name="mod<?= $i ?>" value="<?= $row['id_mod'] ?>" <?php
                                        if (check_modulo($row['id_mod'], $id_roll)) {
                                            echo "checked='checked'";
                                        }
                                        ?>/> <?= $row['mod_name'] ?></td>
                                    <td><div class="text-center"><input <?= ($view_mode == 0) ? "disabled='disabled'" : ""; ?>type="checkbox" id="act1<?= $i ?>" name="act1<?= $i ?>" value="1" <?php
                                        if (check_permiso($row['id_mod'], $act1, $id_roll)) {
                                            echo "checked='checked'";
                                        }
                                        ?> /></div></td>
                                    <td><div class="text-center"><input <?= ($view_mode == 0) ? "disabled='disabled'" : ""; ?>type="checkbox" id="act2<?= $i ?>" name="act2<?= $i ?>" value="2" <?php
                                        if (check_permiso($row['id_mod'], $act2, $id_roll)) {
                                            echo "checked='checked'";
                                        }
                                        ?>/></div></td>
                                    <td><div class="text-center"><input <?= ($view_mode == 0) ? "disabled='disabled'" : ""; ?>type="checkbox" id="act3<?= $i ?>" name="act3<?= $i ?>" value="3" <?php
                                        if (check_permiso($row['id_mod'], $act3, $id_roll)) {
                                            echo "checked='checked'";
                                        }
                                        ?>/></div></td>
                                    <td><div class="text-center"><input <?= ($view_mode == 0) ? "disabled='disabled'" : ""; ?>type="checkbox" id="act4<?= $i ?>" name="act4<?= $i ?>" value="4" <?php
                                        if (check_permiso($row['id_mod'], $act4, $id_roll)) {
                                            echo "checked='checked'";
                                        }
                                        ?>/></div></td>
                                    <td><div class="text-center"><input <?= ($view_mode == 0) ? "disabled='disabled'" : ""; ?>type="checkbox" id="act5<?= $i ?>" name="act5<?= $i ?>" value="5" <?php
                                        if (check_permiso($row['id_mod'], $act5, $id_roll)) {
                                            echo "checked='checked'";
                                        }
                                        ?>/></div></td>
                                    <td><div class="text-center"><input <?= ($view_mode == 0) ? "disabled='disabled'" : ""; ?>type="checkbox" id="act6<?= $i ?>" name="act6<?= $i ?>" value="6" <?php
                                        if (check_permiso($row['id_mod'], $act6, $id_roll)) {
                                            echo "checked='checked'";
                                        }
                                        ?>/></div></td>
                                </tr>
                                <?php
                                $i++;
                            }
                            ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th><?= $vocab["permits_title"] ?></th>
                                <th><div class="text-center"><i class="fa fa-eye fa-2x text-info" title="<?= $vocab["symbol_view"] ?>"></i></div></th>
                                <th><div class="text-center"><i class="fa fa-list fa-2x text-primary" title="<?= $vocab["menu_list"] ?>"></i></div></th>
                                <th><div class="text-center"><i class="fa fa-save fa-2x text-success" title="<?= $vocab["menu_add"] ?>"></i></div></th>
                                <th><div class="text-center"><i class="fa fa-pencil fa-2x text-success" title="<?= $vocab["symbol_edit"] ?>"></i></div></th>
                                <th><div class="text-center"><i class="fa fa-close fa-2x text-danger" title="<?= $vocab["symbol_delete"] ?>"></i></div></th>
                                <th><div class="text-center"><i class="fa fa-print fa-2x text-primary" title="<?= $vocab["symbol_print"] ?>"></i></div></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <input type="hidden" id="counter" id="counter" value="<?= $i ?>"/>
                <?php
                if ($view_mode == 1) {
                    if (check_permiso($mod2, $act4, $user_rol)) {
                        ?>
                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                            <a <?= ($view_mode == 0) ? "readonly" : ""; ?> class="btn btn-success btn-group-justified"  name="submit" onclick="update_roll();"><i class="fa fa-save fa-inverse"></i> <?= $vocab["symbol_update"] . " " . $vocab["rols"] ?></a>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                            <a class="btn btn-warning btn-group-justified"  name="submit" onclick="javascript:OpcionMenu('mod/admin/rolls/list_roll.php?', '');"><i class="fa fa-rotate-left"></i> <?= $vocab["symbol_return"] ?></a>
                        </div>
                        <?php
                    }
                } else {
                    ?>
                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12"></div>
                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                        <a class="btn btn-warning btn-group-justified"  name="submit" onclick="javascript:OpcionMenu('mod/admin/rolls/list_roll.php?', '');"><i class="fa fa-rotate-left"></i> <?= $vocab["symbol_return"] ?></a>
                    </div>
                <?php } ?>
            </div>
        </div>
    </form>
</div>