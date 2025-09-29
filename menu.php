<?php
require 'functions.php';
?>
<nav class="navbar navbar-inverse navbar-fixed-top" style="cursor: pointer;">
    <div class="container">
        <div class="navbar-header">
            <button aria-controls="navbar" aria-expanded="false" data-target="#navbar" data-toggle="collapse" class="navbar-toggle collapsed" type="button">
                <span class="sr-only">Alternar Navegación</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a href="#" class="navbar-brand"><?php echo $mySessionController->getVar("page_title") ?></a>
        </div>
        <div class="navbar-collapse collapse" id="navbar" aria-expanded="false" style="height: 1px;">
            <ul class="nav navbar-nav">
                <!-- INICIO -->
                <li><a onclick="javascript:OpcionMenu('home.php?', '');"><span><i class="fa fa-home fa-inverse"></i> <?= $vocab["menu_home"] ?></span></a></li>
                <!-- MODULOS DEL SISTEMA -->
                <!-- ESPACIO -->
                <li><a href="" style="width: 150px;"></a></li>
                <!-- PERFIL -->
                <li><a href='#' onclick="javascript:OpcionMenu('mod/admin/users/edit_perfil.php?', '');"><span> <i class="fa fa-user fa-inverse"></i> <?= $vocab["menu_perfil"] ?></span></a></li>
                <!-- ADMINISTRACION -->
                <?php if (check_permiso($mod1, $act1, $user_rol)) { ?>
                    <li class="dropdown">
                        <a aria-expanded="false" aria-haspopup="true" role="button" data-toggle="dropdown" class="dropdown-toggle" href="#"><span><i class="fa fa-gears fa-inverse"></i> <?= $vocab["menu_admin"] ?></span> <span class="caret"></span></a>
                        <ul class="dropdown-menu">
                            <?php if (check_permiso($mod1, $act4, $user_rol)) { ?>
                            <li><a onclick="javascript:OpcionMenu('mod/admin/param/edit_param.php?', '');"><span><i class="fa fa-gg text-warning"></i> <?= $vocab["menu_pram"] ?></span></a></li>
                            <?php } ?> 
                            <!-- MODULOS -->
                            <?php if (check_permiso($mod1, $act1, $user_rol)) { ?>
                                <li class="dropdown-submenu">
                                    <a href="#"><span><i class="fa fa-puzzle-piece text-danger"></i> <?= $vocab["menu_mod"] ?></span></a>
                                    <ul class="dropdown-menu">
                                        <?php if (check_permiso($mod1, $act2, $user_rol)) { ?>
                                            <li><a onclick="javascript:OpcionMenu('mod/admin/permits/list_mod.php?', '');"><span> <i class="fa fa-list text-primary"></i> <?= $vocab["menu_list"] ?></span></a></li>
                                        <?php } ?>
                                        <?php if (check_permiso($mod1, $act3, $user_rol)) { ?>
                                            <li><a onclick="javascript:OpcionMenu('mod/admin/permits/new_mod.php?', '');"><span><i class="fa fa-plus text-success"></i> <?= $vocab["menu_add"] ?></span></a></li>
                                        <?php } ?>
                                    </ul>
                                </li>
                            <?php } ?>
                            <!-- ROLES -->
                            <?php if (check_permiso($mod2, $act1, $user_rol)) { ?>
                                <li class="dropdown-submenu">
                                    <a href="#"><span><i class="fa fa-sitemap text-warning"></i> <?= $vocab["menu_roll"] ?></span></a>
                                    <ul class="dropdown-menu">
                                        <?php if (check_permiso($mod2, $act2, $user_rol)) { ?>
                                            <li><a onclick="javascript:OpcionMenu('mod/admin/rolls/list_roll.php?', '');"><span> <i class="fa fa-list text-primary"></i> <?= $vocab["menu_list"] ?></span></a></li>
                                        <?php } ?>
                                        <?php if (check_permiso($mod2, $act3, $user_rol)) { ?>
                                            <li><a onclick="javascript:OpcionMenu('mod/admin/rolls/new_roll.php?', '');"><span><i class="fa fa-plus text-success"></i> <?= $vocab["menu_add"] ?></span></a></li>
                                        <?php } ?>
                                    </ul>
                                </li>
                            <?php } ?>
                            <!-- USUARIOS -->
                            <?php if (check_permiso($mod3, $act1, $user_rol)) { ?>
                                <li class="dropdown-submenu">
                                    <a href="#"><span><i class="fa fa-users text-primary"></i> <?= $vocab["menu_user"] ?></span></span></a>
                                    <ul class="dropdown-menu">
                                        <?php if (check_permiso($mod3, $act2, $user_rol)) { ?>
                                            <li><a onclick="javascript:OpcionMenu('mod/admin/users/list_user.php?', '');"><span> <i class="fa fa-list text-primary"></i>  <?= $vocab["menu_list"] ?></span></a></li>
                                        <?php } ?>
                                        <?php if (check_permiso($mod3, $act3, $user_rol)) { ?>
                                            <li><a onclick="javascript:OpcionMenu('mod/admin/users/new_user.php?', '');"><span><i class="fa fa-plus text-success"></i> <?= $vocab["menu_add"] ?></span></a></li>
                                        <?php } ?>
                                    </ul>
                                <?php } ?>
                        </ul>
                    </li>
                <?php } ?>
                <li><a href="http://www.soporteinf.una.ac.cr" target="_blank"><span><i class="fa fa-question fa-inverse"></i> <?= $vocab["menu_help"] ?></span></a></li>
                <li><a href='#' onclick="window.location = document.getElementById('cds_domain_locate').value + 'mod/login/logout.php'"><span><i class="fa fa-sign-out fa-inverse"></i> <?= $vocab["menu_logout"] ?></span></a></li>
            </ul>
        </div><!--/.nav-collapse -->
    </div>
</nav>