<?php
if (!isset($vocab)) {
    $vocab = $mySessionController->getVar("vocab");
}
if (!isset($user_rol)) {
    $user_rol = (int)$mySessionController->getVar("rol");
}
?>

<style>
    .admin-access-bar {
        position: relative;
        z-index: 1100;
    }
    .admin-access-bar .panel {
        overflow: visible;
    }
    .admin-access-bar .panel-body {
        overflow: visible;
    }
    .admin-access-bar .dropdown-menu {
        z-index: 1200;
    }
</style>

<div class="container admin-access-bar" style="margin-top: 12px; margin-bottom: 10px;">
    <div class="panel panel-default" style="margin-bottom: 10px;">
        <div class="panel-body" style="padding: 10px 15px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <a class="btn btn-default btn-sm" onclick="javascript:OpcionMenu('home.php?', '');" style="cursor:pointer;">
                <i class="fa fa-home"></i> <?= $vocab["menu_home"] ?>
            </a>

            <a class="btn btn-default btn-sm" onclick="javascript:OpcionMenu('mod/admin/users/edit_perfil.php?', '');" style="cursor:pointer;">
                <i class="fa fa-user"></i> <?= $vocab["menu_perfil"] ?>
            </a>

            <?php if (check_permiso($mod1, $act1, $user_rol)) { ?>
                <div class="btn-group">
                    <button type="button" class="btn btn-danger btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-gears"></i> <?= $vocab["menu_admin"] ?> <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        <?php if (check_permiso($mod1, $act4, $user_rol)) { ?>
                            <li><a onclick="javascript:OpcionMenu('mod/admin/param/edit_param.php?', '');" style="cursor:pointer;"><i class="fa fa-gg text-warning"></i> <?= $vocab["menu_pram"] ?></a></li>
                        <?php } ?>

                        <?php if (check_permiso($mod1, $act1, $user_rol)) { ?>
                            <li class="dropdown-header"><?= $vocab["menu_mod"] ?></li>
                            <?php if (check_permiso($mod1, $act2, $user_rol)) { ?>
                                <li><a onclick="javascript:OpcionMenu('mod/admin/permits/list_mod.php?', '');" style="cursor:pointer;"><i class="fa fa-list text-primary"></i> <?= $vocab["menu_list"] ?></a></li>
                            <?php } ?>
                            <?php if (check_permiso($mod1, $act3, $user_rol)) { ?>
                                <li><a onclick="javascript:OpcionMenu('mod/admin/permits/new_mod.php?', '');" style="cursor:pointer;"><i class="fa fa-plus text-success"></i> <?= $vocab["menu_add"] ?></a></li>
                            <?php } ?>
                            <li role="separator" class="divider"></li>
                        <?php } ?>

                        <?php if (check_permiso($mod2, $act1, $user_rol)) { ?>
                            <li class="dropdown-header"><?= $vocab["menu_roll"] ?></li>
                            <?php if (check_permiso($mod2, $act2, $user_rol)) { ?>
                                <li><a onclick="javascript:OpcionMenu('mod/admin/rolls/list_roll.php?', '');" style="cursor:pointer;"><i class="fa fa-list text-primary"></i> <?= $vocab["menu_list"] ?></a></li>
                            <?php } ?>
                            <?php if (check_permiso($mod2, $act3, $user_rol)) { ?>
                                <li><a onclick="javascript:OpcionMenu('mod/admin/rolls/new_roll.php?', '');" style="cursor:pointer;"><i class="fa fa-plus text-success"></i> <?= $vocab["menu_add"] ?></a></li>
                            <?php } ?>
                            <li role="separator" class="divider"></li>
                        <?php } ?>

                        <?php if (check_permiso($mod3, $act1, $user_rol)) { ?>
                            <li class="dropdown-header"><?= $vocab["menu_user"] ?></li>
                            <?php if (check_permiso($mod3, $act2, $user_rol)) { ?>
                                <li><a onclick="javascript:OpcionMenu('mod/admin/users/list_user.php?', '');" style="cursor:pointer;"><i class="fa fa-list text-primary"></i> <?= $vocab["menu_list"] ?></a></li>
                            <?php } ?>
                            <?php if (check_permiso($mod3, $act3, $user_rol)) { ?>
                                <li><a onclick="javascript:OpcionMenu('mod/admin/users/new_user.php?', '');" style="cursor:pointer;"><i class="fa fa-plus text-success"></i> <?= $vocab["menu_add"] ?></a></li>
                            <?php } ?>
                        <?php } ?>

                        <?php if (check_permiso($mod3, $act2, $user_rol)) { ?>
                            <li role="separator" class="divider"></li>
                            <li><a onclick="javascript:OpcionMenu('mod/admin/audit/list_access_log.php?', '');" style="cursor:pointer;"><i class="fa fa-shield text-danger"></i> Auditoría de accesos</a></li>
                        <?php } ?>
                    </ul>
                </div>
            <?php } ?>

            <a class="btn btn-info btn-sm" href="https://escinf.una.ac.cr/index.php/contactenos" target="_blank">
                <i class="fa fa-question"></i> <?= $vocab["menu_help"] ?>
            </a>
        </div>
    </div>
</div>