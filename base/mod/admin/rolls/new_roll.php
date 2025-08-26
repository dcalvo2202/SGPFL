<?php
/**
 * Formulario para un nuevo rol
 */
include("../../login/check.php");
include("../../../functions.php");
$vocab = $mySessionController->getVar("vocab");
$user_rol = $mySessionController->getVar("rol");
?>
<div class="container">
    <div class="well well-sm">
        <h2><?= $vocab["rols_title"] ?></h2>
        <p><?= $vocab["rols_title_desc"] ?></p>
    </div>

    <form method="post" action="">
        <div class="row">
            <div class="col-lg-5 col-md-5 col-sm-8 col-xs-12">
                <div class="form-group">
                    <label for="name_roll"><?= $vocab["symbol_name"] ?> </label>
                    <input id="name_roll" name="name_roll" class="form-control" type="text" maxlength="255" value=""/> 
                    <p class="help-block"><small><?= $vocab["rols_name_desc"] ?></small></p> 
                </div>		
                <div class="form-group">
                    <label for="desc_roll"><?= $vocab["symbol_desc"] ?></label>

                    <textarea id="desc_roll" name="desc_roll" class="form-control" rows="2"></textarea> 
                    <p class="help-block"><small><?= $vocab["rols_desc_desc"] ?></small></p> 
                </div>
                <div class="dataTables_wrapper form-inline dt-bootstrap center-block">
                    <?php
                    $sql = "SELECT id_mod, mod_name, mod_desc FROM sis_mod WHERE active = '1';";
                    $res = seleccion($sql);
                    $i = 0;
                    ?>
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
                            <?php foreach ($res as $row) { //id="mod<?=$i" ?>
                                <tr>
                                    <td><input type="checkbox" id="mod<?= $i ?>" name="mod<?= $i ?>" value="<?= $row['id_mod'] ?>" /> <?= $row['mod_name'] ?></td>
                                    <td><div class="text-center"><input type="checkbox" id="act1<?= $i ?>" name="act1<?= $i ?>" value="1" /></div></td>
                                    <td><div class="text-center"><input type="checkbox" id="act2<?= $i ?>" name="act2<?= $i ?>" value="2" /></div></td>
                                    <td><div class="text-center"><input type="checkbox" id="act3<?= $i ?>" name="act3<?= $i ?>" value="3" /></div></td>
                                    <td><div class="text-center"><input type="checkbox" id="act4<?= $i ?>" name="act4<?= $i ?>" value="4" /></div></td>
                                    <td><div class="text-center"><input type="checkbox" id="act5<?= $i ?>" name="act5<?= $i ?>" value="5" /></div></td>
                                    <td><div class="text-center"><input type="checkbox" id="act6<?= $i ?>" name="act6<?= $i ?>" value="6" /></div></td>
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
                    <input type="hidden" id="counter" id="counter" value="<?= $i ?>"/>
                    <?php if (check_permiso($mod2, $act3, $user_rol)) { ?>

                    <div class="text-center"><a class="btn btn-success" onclick="new_roll();"><i class="fa fa-save fa-inverse"></i> <?= $vocab["symbol_save"] . " " . $vocab["rols"] ?> </a></div>
                    <?php } ?>
                </div>
            </div>
        </div>
        </ul>
    </form>
</div>