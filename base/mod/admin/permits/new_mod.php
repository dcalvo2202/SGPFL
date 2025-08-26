<?php
/**
 * Formulario para un nuevo modulo
 */
include("../../login/check.php");
include("../../../functions.php");
$vocab = $mySessionController->getVar("vocab");
$user_rol = $mySessionController->getVar("rol");
?>
<div class="container">
    <div class="well well-sm">
        <h2><?= $vocab["symbol_add"] . " " . $vocab["permits_title"] ?></h2>
        <p><?= $vocab["permits_title_desc"] ?></p>
    </div>
    <div class="col-lg-5 col-md-5 col-sm-8 col-xs-12">
        <form method="post" action="">
            <div class="form-group">
                <label for="name_mod"><?= $vocab["symbol_name"] ?> </label>
                <input id="name_mod" name="name_mod1" class="form-control" type="text" placeholder="Nombre del nuevo modulo"/>
                <p class="help-block"><small><?= $vocab["permits_name_desc"] ?></small></p>
            </div>
            <div class="form-group">
                <label for="desc_mod"><?= $vocab["symbol_desc"] ?> </label>
                <textarea id="desc_mod" name="desc_mod" class="form-control" rows="2"></textarea> 
                <p class="help-block"><small><?= $vocab["permits_desc_desc"] ?></small></p> 
            </div>
            <?php if (check_permiso($mod1, $act3, $user_rol)) { ?>
            <div class="text-center"><a class="btn btn-success" onclick="new_mod();"><i class="fa fa-save fa-inverse"></i> <?= $vocab["symbol_save"] . " " . $vocab["permits_title"] ?></a></div>
            <?php } ?>
        </form>	
    </div>
</div>