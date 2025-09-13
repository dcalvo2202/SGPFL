<?php
/**
 * Lista los roles del sistema, no utilizar este tipo de tablas para los modulos
 */
include("../../login/check.php");
include("../../../functions.php");
$vocab = $mySessionController->getVar("vocab");
$user_rol = $mySessionController->getVar("rol");

/* * *************************************************************************************** */
//Informacion requerida obtenida de la sesion
$ip = $mySessionController->getVar("cds_domain");
$ip .= $mySessionController->getVar("cds_locate");

$page_cant = $mySessionController->getVar("page_cant");

//
$sql = "SELECT COUNT(id_roll) AS cant
        FROM sis_rolls";

$find_key = (isset($_GET['find_key'])) ? $_GET['find_key'] : '';
if ($find_key != "") {
    $sql.=" WHERE roll_name LIKE  '%" . $find_key . "%'";
}
$sql.=";";
$res_cant = seleccion($sql);

$cant_pagi = ceil((int) $res_cant[0]['cant'] / (int) $page_cant);
$page = (isset($_GET["page"])) ? $_GET["page"] : "1";
if (!$page) {
    $start = 0;
    $page = 1;
} else {
    $start = (isset($_GET["start"])) ? $_GET["start"] : "0";
}

/* * ********************************************************************************************** */
$sql = "SELECT  id_roll, roll_name
        FROM sis_rolls";

$find_key = (isset($_GET['find_key'])) ? $_GET['find_key'] : '';
if ($find_key != "") {
    $sql.="  WHERE roll_name LIKE '%" . $find_key . "%'";
}

$order_key = (isset($_GET['order_key'])) ? $_GET['order_key'] : '';
if ($order_key != "") {
    $sql.=" ORDER BY " . $order_key;
} else {
    $sql.=" ORDER BY id_roll";
}

$sql.=" limit " . (int) $start . "," . (int) $page_cant . ";";
$res = seleccion($sql);
?>
<!--  ****** Titulo ***** -->
<div class="well well-sm"><h1><?= $vocab["rols_list_title"] ?></h1></div>
<form name="con_ct" action="" method="post">
    <!--  ****** Variable Oculta ***** -->
    <input type="hidden" id="order_key" name="order_key" value="<?= $order_key ?>" />
    <!--  ****** Menu de Busqueda ***** -->
    <div class="row">
        <div class="col-lg-2 col-md-2 col-sm-3 col-xs-8">
            <div class="text-left"><a id="Buscar_btn1" style="display:block;" class="btn btn-primary btn-sm"  onclick="show_hide_search()"> <?= $vocab["symbol_show_search"] ?> <i class="fa fa-search-plus fa-inverse"></i></a></div>
            <div class="text-left"><a id="Buscar_btn2" style="display:none;" class="btn btn-primary btn-sm"  onclick="show_hide_search()"> <?= $vocab["symbol_hide_search"] ?> <i class="fa fa-search-minus fa-inverse"></i></a></div>
            <br>
            <div id="Buscar" style="display:none;">
                <div class="form-group">
                    <label for="find_key"><?= $vocab["symbol_name"] ?></label>
                    <input id="find_key" name="find_key" class="element text medium" type="text" value="<?= $find_key ?>"/> 
                    <p class="guidelines" id="guide_1"><small><?= $vocab["rols_list_title_desc"] ?></small></p> 
                </div>
                <div class="text-center"><a class="btn btn-info" onclick="javascript: OpcionMenu('mod/admin/rolls/list_roll.php?', 'find_key=' + document.getElementById('find_key').value + '&order_key=' + document.getElementById('order_key').value + '&page=<?= $page ?>&start=<?= $start ?>');">
                        <?= $vocab["symbol_search"] ?> <i class='fa fa-search fa-inverse'></i>
                    </a></div>
            </div>
            <br>
        </div>
    </div>
    <?php /*     * ************************************************************************************ */ ?>
    <h3 class="text-center">Cantidad Total de Registros: <?= $res_cant[0]['cant'] ?></h3>
    <?php /*     * ************************************************************************************ */ ?>
    <!--  ****** Tabla de contenido ***** -->
    <div class="dataTables_wrapper form-inline dt-bootstrap center-block">
        <table class="table table-striped table-bordered dataTable">
            <!--  ****** Cabecera Tabla ***** -->
            <thead>
                <tr>
                    <th class="puntero"><a onClick="javascript:OpcionMenu('mod/admin/rolls/list_roll.php?', 'find_key1=<?= $find_key ?>&order_key=id_roll&page=<?= $page ?>&start=<?= $start ?>'); window.scrollTo(0, 0);">Id</a></th>
                    <th class="puntero"><a onClick="javascript:OpcionMenu('mod/admin/rolls/list_roll.php?', 'find_key1=<?= $find_key ?>&order_key=roll_name&page=<?= $page ?>&start=<?= $start ?>'); window.scrollTo(0, 0);"><?= $vocab["symbol_name"] ?></a></th>
                    <?php if (check_permiso($mod2, $act1, $user_rol)) { ?>
                        <th style=" width: 5%;"><div class="text-center"><i class="fa fa-eye text-primary fa-2x" title="<?= $vocab["symbol_view"] ?>"></i></div></th>
                    <?php } ?>
                    <?php if (check_permiso($act2, $act4, $user_rol)) { ?>
                        <th width="5%"><div class="text-center"><i class="fa fa-pencil fa-2x text-success puntero" title="<?= $vocab["symbol_edit"] ?>"></i></div></th>
                    <?php } ?>
                    <?php if (check_permiso($act2, $act5, $user_rol)) { ?>
                        <th width="5%"><div class="text-center"><i class="fa fa-close fa-2x text-danger puntero" title="<?= $vocab["symbol_delete"] ?>"></i></div></th>
                    <?php } ?>
                </tr>
            </thead>
            <!--  ****** Contenido Tabla ***** -->
            <tbody>
                <?php
                if (count($res) > 0) {
                    for ($i = 0; $i < count($res); $i++) {
                        ?>
                        <tr id="fila<?= $i ?>"  align='center'>
                            <td><?= $res[$i]['id_roll'] ?></td>
                            <td><?= $res[$i]['roll_name'] ?></td>
                            <?php if (check_permiso($act2, $act1, $user_rol)) { ?>
                                <td>
                                    <?php if ($res[$i]['id_roll'] != 1) { ?>
                                        <a class="puntero" onClick="javascript:OpcionMenu('mod/admin/rolls/edit_roll.php?', 'id_roll=<?= $res[$i]["id_roll"] ?>&view_mode=0');">
                                        <?php } else { ?>
                                            <a style="cursor: not-allowed; ">
                                        <?php }?>
                                        <div class="text-center"><i class="fa fa-eye text-primary" title="<?= $vocab["symbol_view"] ?>"></i></div>
                                        <?php if ($res[$i]['id_roll'] != 1) { ?>
                                        </a>
                                    <?php } ?>
                                </td>
                            <?php } ?>
                            <?php if (check_permiso($act2, $act4, $user_rol)) { ?>
                                <td>
                                    <?php if ($res[$i]['id_roll'] != 1) { ?>
                                        <a class="puntero" " onClick="javascript:OpcionMenu('mod/admin/rolls/edit_roll.php?', 'id_roll=<?= $res[$i]["id_roll"] ?>&view_mode=1');">
                                        <?php } else { ?>
                                            <a style="cursor: not-allowed; ">
                                        <?php }?>
                                        <div class="text-center"><i class="fa fa-pencil text-success" title="<?= $vocab["symbol_edit"] ?>"></i></div>
                                        <?php if ($res[$i]['id_roll'] != 1) { ?>
                                        </a>
                                    <?php } ?>
                                </td>
                            <?php } ?>
                            <?php if (check_permiso($act2, $act5, $user_rol)) { ?>
                                <td>
                                    <?php if ($res[$i]['id_roll'] != 1) { ?>
                                        <a class="puntero" " onClick="javascript:delete_roll(<?= $res[$i]['id_roll'] ?>);">
                                        <?php } else { ?>
                                            <a style="cursor: not-allowed; ">
                                        <?php }?>
                                        <div class="text-center"><i class="fa fa-close text-danger" title="<?= $vocab["symbol_delete"] ?>"></i></div>
                                        <?php if ($res[$i]['id_roll'] != 1) { ?>
                                        </a>
                                    <?php } ?>
                                </td>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr id="fila0" align='center'>
                        <td colspan="4"><?= $vocab["symbol_no_data"] ?></td>
                    </tr>   
                <?php } ?>
            </tbody>
            <tfoot>
                <tr>
                    <th class="puntero"><a onClick="javascript:OpcionMenu('mod/admin/rolls/list_roll.php?', 'find_key1=<?= $find_key ?>&order_key=id_roll&page=<?= $page ?>&start=<?= $start ?>'); window.scrollTo(0, 0);">Id</a></th>
                    <th class="puntero"><a onClick="javascript:OpcionMenu('mod/admin/rolls/list_roll.php?', 'find_key1=<?= $find_key ?>&order_key=roll_name&page=<?= $page ?>&start=<?= $start ?>'); window.scrollTo(0, 0);"><?= $vocab["symbol_name"] ?></a></th>
                    <?php if (check_permiso($mod2, $act1, $user_rol)) { ?>
                        <th style=" width: 5%;"><div class="text-center"><i class="fa fa-eye text-primary fa-2x" title="<?= $vocab["symbol_view"] ?>"></i></div></th>
                    <?php } ?>
                    <?php if (check_permiso($act2, $act4, $user_rol)) { ?>
                        <th width="5%"><div class="text-center"><i class="fa fa-pencil fa-2x text-success puntero" title="<?= $vocab["symbol_edit"] ?>"></i></div></th>
                    <?php } ?>
                    <?php if (check_permiso($act2, $act5, $user_rol)) { ?>
                        <th width="5%"><div class="text-center"><i class="fa fa-close fa-2x text-danger puntero" title="<?= $vocab["symbol_delete"] ?>"></i></div></th>
                    <?php } ?>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php /*     * ***************************************************************************************** */ ?>
    <div class="row">
        <table class="text-muted table_fix_nav">
            <tr valign="center">
                <td>
                    <?php if ($page != 1) { ?>
                        <a class="puntero" onClick="javascript:OpcionMenu('mod/admin/permits/list_mod.php?', 'page=<?= $page - 1 ?>&start=<?= $start - $page_cant ?>&find_key=<?= $find_key ?>&order_key=<?= $order_key ?>'); window.scrollTo(0, 0);"><i class="fa fa-toggle-left text-primary"></i></a>
                    <?php } ?>
                </td>
                <td>
                    &nbsp;&nbsp;&nbsp;<?= $vocab["symbol_page"] ?>: <?= $page . " " ?> <?= $vocab["symbol_page_of"] ?> <?= $cant_pagi ?>&nbsp;&nbsp;&nbsp;
                </td>
                <td>
                    <?php if (($cant_pagi != $page) && ($cant_pagi != 0)) { ?>
                        <a class="puntero" onClick="javascript:OpcionMenu('mod/admin/permits/list_mod.php?', 'page=<?= $page + 1 ?>&start=<?= $start + $page_cant ?>&find_key=<?= $find_key ?>&order_key=<?= $order_key ?>'); window.scrollTo(0, 0);"><i class="fa fa-toggle-right text-primary"></i></a>
                    <?php } ?>
                </td>
            </tr>
        </table>
    </div>
    <span class="text-muted text-primary text-center"><?= $vocab["symbol_order_label"] ?></span>
    <?php if (check_permiso($mod2, $act3, $user_rol)) { ?>
        <div class="row">
            <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12"></div>
            <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                <div class="text-center"><a class="btn btn-success" onclick="javascript:OpcionMenu('mod/admin/rolls/new_roll.php?', '');"> <i class="fa fa-plus fa-inverse"></i> <?= $vocab["symbol_add"] ?> <?= $vocab["rols_title"] ?></a></div>
            </div>
        </div>
    <?php } ?>
</form>