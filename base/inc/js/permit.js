/**
 * Valida que la informacion necesaria para añadir un modulo sea valida
 * @returns {Boolean}
 */
function validate_mod() {
    if (document.getElementById('name_mod').value == "") {
        jAlert("Ingrese el nombre del Modulo", "Dato Requerido");
        document.getElementById('name_mod').focus();
        return false;
    }
    return true;
}

/**
 * Envia los datos para ser incluidos en la base de datos, recibe una respuesta
 * y la interpreta.
 * @returns {undefined} Redirecciona página
 */
function new_mod() {
    var loading = document.getElementById('loading_container');
    loading.innerHTML = cargando_bar;
    if (validate_mod()) {
        //Obtener Valores
        var name_mod = document.getElementById('name_mod').value;
        var desc_mod = document.getElementById('desc_mod').value;
        var page = document.getElementById('container');
        //Preparacion  llamada AJAX 
        var ajax = NuevoAjax();
        var _values_send =
                'name_mod=' + name_mod +
                '&desc_mod=' + desc_mod;
        var _URL_ = "mod/admin/permits/ajax_new_mod.php?";
        //alert(_URL_+_values_send); //DEBUG
        ajax.open("GET", _URL_ + _values_send, true);
        ajax.onreadystatechange = function() {
            if (ajax.readyState == 1) {
                //Nada
            } else if (ajax.readyState == 4) {
                var response = ajax.responseText;
                //alert(response); //DEBUG
                if (response == "OK") {
                    jAlert('El modulo se guardo de forma satisfactoria!', 'Exito');
                    page.innerHTML = '';
                    OpcionMenu('mod/admin/permits/list_mod.php?', '');
                } else {
                    jAlert('Ha ocurrido un error inesperado intentelo más tarde!', 'Error');
                }
            }
        };
        ajax.send(null);
    }
    loading.innerHTML = "";
}

/**
 * Eenvia los datos para actualizar la infromacion de un modulo y procesa la
 * respuesta
 * @returns {undefined} Redirecciona o muestra un mensaje flotante
 */
function update_mod() {
    var loading = document.getElementById('loading_container');
    loading.innerHTML = cargando_bar;
    if (validate_mod()) {
        //Obtener Valores
        var id_mod = document.getElementById('id_mod').value;
        var name_mod = document.getElementById('name_mod').value;
        var desc_mod = document.getElementById('desc_mod').value;
        var page = document.getElementById('container');
        //Preparacion  llamada AJAX 
        var ajax = NuevoAjax();
        var _values_send =
                'id_mod=' + id_mod +
                '&name_mod=' + name_mod +
                '&desc_mod=' + desc_mod;
        var _URL_ = "mod/admin/permits/ajax_upd_mod.php?";
        //alert(_URL_+_values_send); //DEBUG
        ajax.open("GET", _URL_ + _values_send, true);
        ajax.onreadystatechange = function() {
            if (ajax.readyState == 1) {
                //Nada
            } else if (ajax.readyState == 4) {
                var response = ajax.responseText;
                //alert(response); //DEBUG
                if (response == "OK") {
                    jAlert('El modulo se guardo de forma satisfactoria!', 'Exito');
                    page.innerHTML = '';
                    OpcionMenu('mod/admin/permits/list_mod.php?', '');
                } else {
                    jAlert('Ha ocurrido un error inesperado intentelo más tarde!', 'Error');
                }
            }
        };
        ajax.send(null);
    }
    loading.innerHTML = "";
}

/**
 * Envia los datos para eliminar el modulo
 * @param {type} id_mod identificador del elemento a eliminar
 * @returns {undefined} Redirecciona o muestra un mensaje flotante.
 */
function delete_mod_action(id_mod) {
    var page = document.getElementById('container');
    page.innerHTML = cargando;
    var ajax = NuevoAjax();
    //Preparacion  llamada AJAX
    var _values_send = 'id_mod=' + id_mod;
    var _URL_ = "mod/admin/permits/ajax_del_mod.php?";
    //alert(_URL_+_values_send); //DEBUG
    ajax.open("GET", _URL_ + "&" + _values_send, true);
    ajax.onreadystatechange = function() {
        if (ajax.readyState == 1) {
            page.innerHTML = cargando;
        } else if (ajax.readyState == 4) {
            var response = ajax.responseText;
            //alert(response); //DEBUG
            if (response == "OK") {
                jAlert('El modulo se a eliminado correctamente!', 'Exito');
                page.innerHTML = '';
                OpcionMenu('mod/admin/permits/list_mod.php?', '');
            } else {
                jAlert('Ha ocurrido un error inesperado intentelo más tarde!', 'Error');
                page.innerHTML = '';
                OpcionMenu('mod/admin/permits/list_mod.php?', '');
            }
        }
    };
    ajax.send(null);
}

/**
 * Alerta al usuario de que va a realizar una eliminación y solicita una
 * confirmación para proceder.
 * 
 * @param {type} id_mod identificador unico
 * @returns {undefined} Procede a hacer la eliminacion o no hace nada
 */
function delete_mod(id_mod) {
    jConfirm("Desea eliminar el modulo", "Eliminar Modulo", function(r) {
        if (r) {
            delete_mod_action(id_mod);
        }
    });
}