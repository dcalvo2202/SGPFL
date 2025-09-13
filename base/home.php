<?php
include("mod/login/check.php");
$vocab = $mySessionController->getVar("vocab");
?>
<div style="padding-left: 25px;">
    <h2><?= $vocab["home_title"] ?></h2>
    <p> <?= $vocab['home_title_desc'] . " " . $mySessionController->getVar("page_title"); ?></p>
    <ul type="square">
        <li>
            <i class="fa fa-plus fa-2x text-success" title="<?= $vocab["menu_add"] ?>"></i> <?= $vocab["menu_add"] ?>
        </li>
        <li>
            <i class="fa fa-pencil fa-2x text-success" title="<?= $vocab["symbol_edit"] ?>"></i> <?= $vocab["symbol_edit"] ?>
        </li>
        <li>
            <i class="fa fa-save fa-2x text-success" title="<?= $vocab["symbol_save"] ?>"></i> <?= $vocab["symbol_save"] ?>
        </li>
        <li>
            <i class="fa fa-close fa-2x text-danger" title="<?= $vocab["symbol_delete"] ?>"></i> <?= $vocab["symbol_delete"] ?>
        </li>
        <li>
            <i class="fa fa-eye fa-2x text-info" title="<?= $vocab["symbol_view"] ?>"></i> <?= $vocab["symbol_view"] ?>
        </li>        
        <li>
            <i class="fa fa-print fa-2x text-primary" title="<?= $vocab["symbol_print"] ?>"></i> <?= $vocab["symbol_print"] ?>
        </li>
        <li>
            <i class="fa fa-list fa-2x text-primary" title="<?= $vocab["menu_list"] ?>"></i> <?= $vocab["menu_list"] ?>
        </li>

        <li>
            <i class="fa fa-rotate-left fa-2x text-warning" title="<?= $vocab["symbol_return"] ?>"></i> <?= $vocab["symbol_return"] ?>
        </li>
    </ul>
</div>
<div class="container">
    <div class="well">
        <h1>Elementos de un formulario en HTML5 y Bootstrap</h1>
        <p>Utilize estos ejemplos para construir su propio formulario</p>
    </div>
    <div class="row">
        <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1"></div>
        <div class="col-lg-3 col-md-4 col-sm-5 col-xs-10">
            <form>
                <div class="form-group">
                    <label for="type-button">Elemento input type button</label>
                    <input id="type-button" name="type-button" class="btn btn-success fa-fa form-control" type="button" value="&#xf0c7;  Button" title="propiedad title" onclick="alert('button');"/>
                    <p class="help-block">Es un boton, en el sistema base no se utiliza preferimos el tag < a > </p>
                </div>
                <div class="form-group">
                    <label for="type-button">Elemento a como un button</label>
                    <div class="text-center"><a class="btn btn-success btn-group-justified"  name="submit" onclick="alert('button');"><i class="fa fa-save fa-inverse"></i> Button < a > </a></div>
                    <p class="help-block">Es un boton, se usa el < a > para controlar el flujo del sistema ya que los botones pueden desencadenar submits</p>
                </div>
                <div class="form-group">
                    <label for="type-checkbox">Elemento input type checkbox</label>
                    <div class="checkbox checkbox_efect">
                        <label><input id="type-checkbox1" name="type-checkbox" class="" type="checkbox" title="propiedad title"/><span class="cr"><i class="cr-icon glyphicon glyphicon-ok"></i></span> Checkbox</label> 
                    </div>
                    <div class="checkbox checkbox_efect">
                        <label><input id="type-checkbox2" name="type-checkbox" class="" type="checkbox" title="propiedad title"/><span class="cr"><i class="cr-icon glyphicon glyphicon-ok"></i></span> Checkbox</label> 
                    </div>
                    <div class="checkbox checkbox_efect">
                        <label class="checkbox-inline">
                            <input id="inlineCheckbox1" name="inlineCheckbox1" type="checkbox" value="1"><span class="cr"><i class="cr-icon glyphicon glyphicon-ok"></i></span> 1 Checkbox
                        </label>
                        <label class="checkbox-inline">
                            <input id="inlineCheckbox2" name="inlineCheckbox2" type="checkbox" value="2"><span class="cr"><i class="cr-icon glyphicon glyphicon-ok"></i></span> 2 Checkbox
                        </label>
                    </div>
                    <p class="help-block">Con bootstrap hay que añadir algunos < div ></p>
                </div>
                <div class="form-group">
                    <label for="type-radio">Elemento input type radio</label>
                    <div class="radio radio_efect">
                        <label><input id="type-radio1" name="type-radio" class="" type="radio" title="propiedad title"/> <span class="cr"><i class="cr-icon glyphicon glyphicon-ok"></i></span>Radio</label> 
                    </div>
                    <div class="radio radio_efect">
                        <label><input id="type-radio2" name="type-radio" class="" type="radio" title="propiedad title"/> <span class="cr"><i class="cr-icon glyphicon glyphicon-ok"></i></span>Radio</label> 
                    </div>
                    <div class="radio radio_efect">
                        <label class="radio-inline">
                            <input id="inlineCheckbox1" name="inlineCheckbox" type="radio" value="1"> <span class="cr"><i class="cr-icon glyphicon glyphicon-ok"></i></span>1 Radio
                        </label>
                        <label class="radio-inline">
                            <input id="inlineCheckbox2" name="inlineCheckbox" type="radio" value="2"> <span class="cr"><i class="cr-icon glyphicon glyphicon-ok"></i></span>2 Radio
                        </label>
                    </div>
                    <p class="help-block">Con bootstrap hay que añadir algunos < div > y un < spam > para el efecto en el icono</p>
                </div>
                <div class="form-group form-inline">
                    <label for="fecha_1">Fecha </label><br>
                    <div>
                        <span>
                            <input id="fecha_1_2" name="fecha_1_2" class="form-control form-inline" size="2" maxlength="2" value="" type="text" placeholder="DD"> /
                        </span>
                        <span>
                            <input id="fecha_1_1" name="fecha_1_1" class="form-control" size="2" maxlength="2" value="" type="text" placeholder="MM"> /
                        </span>
                        <span>
                            <input id="fecha_1_3" name="fecha_1_3" class="form-control" size="4" maxlength="4" value="" type="text" placeholder="AAAA">
                        </span>
                        <span id="calendar_1" style="font-size: 20px;" class="text-primary">
                            <i class="fa fa-calendar datepicker puntero" id="cal_img_1" title="Seleeccione una fecha"></i>
                        </span>
                    </div>
                    <script type="text/javascript">
                        Calendar.setup({
                            inputField: "fecha_1_3",
                            baseField: "fecha_1",
                            displayArea: "calendar_1",
                            button: "cal_img_1",
                            ifFormat: "%B %e, %Y",
                            onSelect: selectDate
                        });
                    </script>
                    <p class="help-block">Este calendario se va a utilizar en vez de las versiones nuevas de input que no son para todos los navegadores.</p>
                </div>
                <div class="form-group">
                    <label for="type_text">Elemento input type text</label>
                    <input id="type-text" name="type-text" class="form-control" type="text" placeholder="propiedad placeholder" title="propiedad title"/>
                    <p class="help-block">ayuda</p>
                </div>
                <div class="form-group">
                    <label for="type-password">Elemento input type password</label>
                    <input id="type-password" name="type-password" class="form-control" type="password" placeholder="propiedad placeholder" title="propiedad title"/>
                    <p class="help-block">Ayuda</p>
                </div>
                <div class="form-group">
                    <label for="">Elemento input type hidden</label>
                    <input id="" name="" class="form-control" type="hidden" placeholder="propiedad placeholder" title="propiedad title"/>
                    <p class="help-block">Ayuda</p>
                </div>
                <div class="form-group">
                    <label for="">Elemento input type submit</label>
                    <input id="" name="" class="form-control" type="submit" placeholder="propiedad placeholder" title="propiedad title" value="Sbumit"/>
                    <p class="help-block">Ayuda</p>
                </div>
                <div class="form-group">
                    <label for="">Elemento input type reset</label>
                    <input id="" name="" class="form-control" type="reset" placeholder="propiedad placeholder" title="propiedad title" value="Reset"/>
                    <p class="help-block">Ayuda</p>
                </div>
                <div class="form-group">
                    <label for="type-file">Elemento input type file</label>
                    <input id="type-file" name="type-file" class="form-control filestyle" type="file" placeholder="propiedad placeholder" title="propiedad title"/>
                    <script type="text/javascript">
                        jQuery(document).ready(function () {
                            jQuery(":file").filestyle();
                        });
                    </script>
                    <p class="help-block">Sirve para seleccionar la ruta a un archivo local</p>
                </div>
                <div class="form-group">
                    <label for="">Elemento input type image</label>
                    <input id="" name="" class="form-control" type="image" src="http://www.escinf.una.ac.cr/templates/zt_zizia/images/logo.png" placeholder="propiedad placeholder" title="propiedad title"/>
                    <p class="help-block">Permite colocar una imagen en el imput, con la propiedad src</p>
                </div>
                <!-- HTML5 -->
                <h2>HTML5</h2>
                <div class="form-group">
                    <label for="type-color">Elemento input type color</label>
                    <input id="type-color" name="type-color" class="form-control" type="color" title="propiedad title" value="#AB3639"/>
                    <p class="help-block">El value soporta colores en notación hexadecimal</p>
                </div>
                <div class="form-group">
                    <label for="">Elemento input type number</label>
                    <input id="" name="" class="form-control" type="number" min="1" max="10" placeholder="Digite un numerio del 1 al 10" title="propiedad title"/>
                    <p class="help-block">Se puede determina el valor minimo y maximo para el scroll, añadir javascript o pattern para solo permitir numeros</p>
                </div>
                <div class="form-group">
                    <label for="">Elemento input type range</label>
                    <input id="" name="" class="form-control" type="range" min="0" max="10" placeholder="propiedad placeholder" title="propiedad title"/>
                    <p class="help-block">Se utiliza un rango de valores con las propiedades min y max</p>
                </div>
                <div class="form-group">
                    <label for="">Elemento input type search</label>
                    <input id="" name="" class="form-control" type="search" placeholder="propiedad placeholder" title="propiedad title"/>
                    <p class="help-block">Es como un text solo que se usa cuando se hace un buscar en el sitio solo</p>
                </div>
            </form>
        </div>
    </div>
</div>