/**
 *  Valida la información del usuario
 * @param {int} status verifica si se usa LDAP 0 = No, 1 =Si
 * @returns {boolean}
 */
function validate_user(status) {
    var id_user = document.getElementById('id_user');
    if (id_user.value == "") {
        jAlert("Ingrese el identificador del usuario", "Dato Requerido");
        id_user.setAttribute("style", "background-color:#EDF0FF");
        id_user.focus();
        return false;
    }
    if (status == 0) {
        var nombre = document.getElementById('nombre_txt');
        if (nombre.value == "") {
            jAlert("Ingrese el nombre del usuario", "Dato Requerido");
            nombre.setAttribute("style", "background-color:#EDF0FF");
            nombre.focus();
            return false;
        }
    }
    var mail = document.getElementById('correo_txt');
    if (!check_email(mail)) {
        mail.setAttribute("style", "background-color:#EDF0FF");
        mail.focus();
        return false;
    }
    var telefono_txt = document.getElementById('telefono_txt');
    if (telefono_txt.value == "" || telefono_txt.value.length != 8) {
        jAlert("Ingrese un teléfono valido para el usuario", "Dato Requerido");
        telefono_txt.setAttribute("style", "background-color:#EDF0FF");
        telefono_txt.focus();
        return false;
    }
    var tipo_tel = document.getElementById('tipo_tel');
    if (tipo_tel.value == "0") {
        jAlert("Seleccione el tipo de telefono", "Dato Requerido");
        tipo_tel.setAttribute("style", "background-color:#EDF0FF");
        tipo_tel.focus();
        return false;
    }
    var rol_slc = document.getElementById('rol_slc');
    if (rol_slc.value == 0) {
        jAlert("Seleccione el rol del usuario", "Dato Requerido");
        rol_slc.setAttribute("style", "background-color:#EDF0FF");
        rol_slc.focus();
        return false;
    }
    if (status == 0) {
        var pass_user = document.getElementById('pass_user');
        var pass_user_c = document.getElementById('pass_user_c');
        if (pass_user.value == "") {
            jAlert("Ingrese contraseña del usuario", "Dato Requerido");
            pass_user.setAttribute("style", "background-color:#EDF0FF");
            pass_user.focus();
            return false;
        }
        if (pass_user_c.value == "") {
            jAlert("Ingrese nuevamente la contraseña del usuario", "Dato Requerido");
            pass_user_c.setAttribute("style", "background-color:#EDF0FF");
            pass_user_c.focus();
            return false;
        }
        if (pass_user.value != pass_user_c.value) {
            jAlert("Las contraseñas no son iguales", "Dato Requerido");
            pass_user.clear();
            pass_user.setAttribute("style", "background-color:#EDF0FF");
            pass_user_c.clear();
            pass_user_c.setAttribute("style", "background-color:#EDF0FF");
            pass_user.focus();
            return false;
        }
        if (scorePassword(pass_user.value) < 50) {
            jAlert("El password no es seguro", "Dato Requerido");
            pass_user.clear();
            pass_user.setAttribute("style", "background-color:#EDF0FF");
            pass_user_c.clear();
            pass_user_c.setAttribute("style", "background-color:#EDF0FF");
            pass_user.focus();
            return false;
        }
    }
    return true;
}
/**
 *  Valida la información del usuario
 * @param {int} status verifica si se usa LDAP 0 = No, 1 =Si
 * @returns {boolean}
 */
function validate_user_perfil(status) {
    var mail = document.getElementById('correo_txt');
    if (!check_email(mail)) {
        mail.setAttribute("style", "background-color:#EDF0FF");
        mail.focus();
        return false;
    }
    var telefono_txt = document.getElementById('telefono_txt');
    if (telefono_txt.value == "" || telefono_txt.value.length != 8) {
        jAlert("Ingrese un teléfono valido para el usuario", "Dato Requerido");
        telefono_txt.setAttribute("style", "background-color:#EDF0FF");
        telefono_txt.focus();
        return false;
    }
    var tipo_tel = document.getElementById('tipo_tel');
    if (tipo_tel.value == "0") {
        jAlert("Seleccione el tipo de telefono", "Dato Requerido");
        tipo_tel.setAttribute("style", "background-color:#EDF0FF");
        tipo_tel.focus();
        return false;
    }
    if (status == 0) {
        var pass_user = document.getElementById('pass_user');
        var pass_user_c = document.getElementById('pass_user_c');
        if (pass_user.value == "") {
            jAlert("Ingrese contraseña del usuario", "Dato Requerido");
            pass_user.setAttribute("style", "background-color:#EDF0FF");
            pass_user.focus();
            return false;
        }
        if (pass_user_c.value == "") {
            jAlert("Ingrese nuevamente la contraseña del usuario", "Dato Requerido");
            pass_user_c.setAttribute("style", "background-color:#EDF0FF");
            pass_user_c.focus();
            return false;
        }
        if (pass_user.value != pass_user_c.value) {
            jAlert("Las contraseñas no son iguales", "Dato Requerido");
            pass_user.clear();
            pass_user.setAttribute("style", "background-color:#EDF0FF");
            pass_user_c.clear();
            pass_user_c.setAttribute("style", "background-color:#EDF0FF");
            pass_user.focus();
            return false;
        }
        if (scorePassword(pass_user.value) < 50) {
            jAlert("El password no es seguro", "Dato Requerido");
            pass_user.clear();
            pass_user.setAttribute("style", "background-color:#EDF0FF");
            pass_user_c.clear();
            pass_user_c.setAttribute("style", "background-color:#EDF0FF");
            pass_user.focus();
            return false;
        }
    }
    return true;
}
/**
 * Añade el nuevo usuario a la base de datos.
 * @param {int} status verifica si se usa LDAP 0 = No, 1 =Si
 * @returns {undefined} redirecciona a la lista de usuarios
 */
function new_user(status) {
    if (validate_user(status)) {
        var loading = document.getElementById('loading_container');
        loading.innerHTML = cargando_bar;
        //Obtener Valores
        var id = document.getElementById('id_user').value;
        var nombre = document.getElementById('nombre_txt').value;
        var email = document.getElementById('correo_txt').value;
        var telefono = document.getElementById('telefono_txt').value;
        var id_tipo_tel = document.getElementById('tipo_tel').value;
        var id_roll = document.getElementById('rol_slc').value;
        if (status == 0){
            var pass = document.getElementById('pass_user').value;
        }else{
            var pass = "";
        }
        //Preparacion  llamada AJAX 
        var ajax = NuevoAjax();
        var _values_send =
                'id=' + id +
                '&nombre=' + nombre +
                '&email=' + email +
                '&telefono=' + telefono +
                '&id_tipo_tel=' + id_tipo_tel +
                '&id_roll=' + id_roll +
                '&pass=' + pass;
        var _URL_ = "mod/admin/users/ajax_new_user.php?";
        //alert(_URL_ + _values_send); //DEBUG
        ajax.open("GET", _URL_ + _values_send, true);
        ajax.onreadystatechange = function() {
            if (ajax.readyState == 1) {
                //Nada
            } else if (ajax.readyState == 4) {
                var response = ajax.responseText;
                //alert(response); //DEBUG
                if (response == 0) {
                    jAlert("Usuario añadido con exito", "Exito");
                    OpcionMenu('mod/admin/users/list_user.php?', '');
                } else if (response == 1 || response == 2) {
                    jAlert("Error en la Base de Datos, intente nuevamente.\n Si persiste informe a la USTDS", "Error");
                } else if (response == 3) {
                    jAlert("El usuario ya existe.\n Consulte a la USTDS", "Usuario ya existe");
                } else {
                    jAlert("Ocurrio un error inesperado.\n Consulte a la USTDS", "Error inesperado");
                }
            }
        };
        ajax.send(null);
        loading.innerHTML = "";
    }
}
/**
 * Actualiza la informacion del usuario
 * @param {string} id_user identificador del usuario que se actualiza
lñp * @returns {undefined} N/A
 */
function update_user(id_user){
    if (validate_user(1)) {
        var loading = document.getElementById('loading_container');
        loading.innerHTML = cargando_bar;
        //Obtener Valores
        var id = id_user;
        var nombre = document.getElementById('nombre_txt').value;
        var email = document.getElementById('correo_txt').value;
        var telefono = document.getElementById('telefono_txt').value;
        var id_tipo_tel = document.getElementById('tipo_tel').value;
        var id_roll = document.getElementById('rol_slc').value;
        //Preparacion  llamada AJAX 
        var ajax = NuevoAjax();
        var _values_send =
                'id=' + id +
                '&nombre=' + nombre +
                '&email=' + email +
                '&telefono=' + telefono +
                '&id_tipo_tel=' + id_tipo_tel +
                '&id_roll=' + id_roll;
        var _URL_ = "mod/admin/users/ajax_upd_user.php?";
        //alert(_URL_ + _values_send); //DEBUG
        ajax.open("GET", _URL_ + _values_send, true);
        ajax.onreadystatechange = function() {
            if (ajax.readyState == 1) {
                //Nada
            } else if (ajax.readyState == 4) {
                var response = ajax.responseText;
                //alert(response); //DEBUG
                if (response == 0) {
                    jAlert("Usuario actualizado con exito", "Exito");
                    OpcionMenu('mod/admin/users/list_user.php?', '');
                } else if (response == 1 || response == 2) {
                    jAlert("Error en la Base de Datos, intente nuevamente.\n Si persiste informe a la USTDS");
                } else {
                    jAlert("Ocurrio un error inesperado.\n Consulte a la USTDS", "Error inesperado");
                }
            }
        };
        ajax.send(null);
        loading.innerHTML = "";
    }
}
/**
 * Actualiza la informacion del usuario personal
 * @param {string} id_user identificador del usuario que se actualiza
 * @param {int} status verifica si se usa LDAP 0 = No, 1 =Si
 * @returns {undefined} N/A
 */
function update_perfil(id_user,status){
    if (validate_user_perfil(status)) {
        var loading = document.getElementById('loading_container');
        loading.innerHTML = cargando_bar;
        //Obtener Valores
        var id = id_user;
        var email = document.getElementById('correo_txt').value;
        var telefono = document.getElementById('telefono_txt').value;
        var id_tipo_tel = document.getElementById('tipo_tel').value;
        if (status == 0){
            var pass = document.getElementById('pass_user').value;
        }else{
            var pass = "";
        }
        //Preparacion  llamada AJAX 
        var ajax = NuevoAjax();
        var _values_send =
                'id=' + id +
                '&email=' + email +
                '&telefono=' + telefono +
                '&id_tipo_tel=' + id_tipo_tel +
                '&pass=' + pass;
        var _URL_ = "mod/admin/users/ajax_upd_perfil.php?";
        //alert(_URL_ + _values_send); //DEBUG
        ajax.open("GET", _URL_ + _values_send, true);
        ajax.onreadystatechange = function() {
            if (ajax.readyState == 1) {
                //Nada
            } else if (ajax.readyState == 4) {
                var response = ajax.responseText;
                //alert(response); //DEBUG
                if (response == 0) {
                    jAlert("Perfil actualizado con exito", "Exito");
                    OpcionMenu('mod/admin/users/list_user.php?', '');
                } else if (response == 1 || response == 2) {
                    jAlert("Error en la Base de Datos, intente nuevamente.\n Si persiste informe a la USTDS", "errboxid");
                } else {
                    jAlert("Ocurrio un error inesperado.\n Consulte a la USTDS", "Error inesperado");
                }
            }
        };
        ajax.send(null);
        loading.innerHTML = "";
    }
}
/**
 * Redirecciona a la pantalla editar usuario
 * @param {string} id cédula del usuario
 */
function view_user(id) {
    OpcionMenu('mod/admin/users/edit_user.php?', 'id_user=' + id + '&view_mode=0');
}
/**
 * Redirecciona a la pantalla editar usuario
 * @param {string} id cédula del usuario
 */
function edit_user(id) {
    OpcionMenu('mod/admin/users/edit_user.php?', 'id_user=' + id + '&view_mode=1');
}
/**
 * 
 * @param {type} id_user
 * @returns {undefined}
 */
function delete_user_action(id_user) {
    var page = document.getElementById('container');
    page.innerHTML = cargando;
    var ajax = NuevoAjax();
    //Preparacion  llamada AJAX
    var _values_send = 'id_user=' + id_user;
    var _URL_ = "mod/admin/users/ajax_del_user.php?";
    //alert(_URL_ + _values_send); //DEBUG
    ajax.open("GET", _URL_ + "&" + _values_send, true);
    ajax.onreadystatechange = function() {
        if (ajax.readyState == 1) {
            page.innerHTML = cargando;
        } else if (ajax.readyState == 4) {
            var response = ajax.responseText;
            //alert(response); //DEBUG
            if (response == 0) {
                jAlert('El usuario se a eliminado correctamente!', 'Exito');
                OpcionMenu('mod/admin/users/list_user.php?', '');
            } else if (response == 1 || response == 2) {
                jAlert('Ha ocurrido un error en la Base de Datos Intentelo Nuevamente\n Si el problema continua comuniquese con la USTDS', 'Error');
                OpcionMenu('mod/admin/users/list_user.php?', '');
            } else {
                jAlert('Ha ocurrido un error inesperado intentelo más tarde!', 'Error');
                OpcionMenu('mod/admin/users/list_user.php?', '');
            }
        }
    };
    page.innerHTML = '';
    ajax.send(null);
}
/**
 * 
 * @param {type} id_user
 * @returns {undefined}
 */
function delete_user(id_user) {
    jConfirm("Desea eliminar el usuario cédula:" + id_user, "Eliminar Usuario", function(r) {
        if (r) {
            delete_user_action(id_user);
        }
    });
}
/**
 * Consulta el nombre de usuario en servidor LDAP,
 * lo inserta en el campo nombre_txt de formulario
 */
function onchange_cedula() {
    var loading = document.getElementById('loading_container');
    loading.innerHTML = cargando_bar;
    //Obtener Valores
    var id = document.getElementById('id_user').value;
    //Preparacion  llamada AJAX 
    var ajax = NuevoAjax();
    var _values_send = 'id=' + id;
    var _URL_ = "mod/admin/users/ajax_get_name.php?";
    //alert(_URL_ + _values_send); //DEBUG
    ajax.open("GET", _URL_ + _values_send, true);
    ajax.onreadystatechange = function() {
        if (ajax.readyState == 1) {
            //Nada
        } else if (ajax.readyState == 4) {
            var response = ajax.responseText;
            //alert(response); //DEBUG
            if (response != "") {
                document.getElementById('nombre_txt').value = response;
                create_mail(response);
            }
        }
    };
    ajax.send(null);
    loading.innerHTML = "";
}

function create_mail(nombre){
    nombre = nombre.toLowerCase();
    nombre= nombre.replace(" ",".");
    nombre= nombre.replace(" ",".");
    var correo = nombre + "@una.cr";
    document.getElementById('correo_txt').value = correo;    
}