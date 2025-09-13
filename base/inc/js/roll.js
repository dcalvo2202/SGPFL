/**
 * Valida que la informacion necesaria para crear un rol en el sistema,
 * sea correcta
 * @returns {Boolean}
 */
function validate_roll(){
    if(document.getElementById('name_roll').value == ""){
        jAlert("Ingrese el nombre del roll","Dato Requerido");
        document.getElementById('name_roll').focus();
        return false;
    }
    return true;
}

/**
 * Genera un arreglo de numeros y letras que representan los permisos
 * que tiene el usuario en los diferentes modulos que pósee el sistema
 * esta cadena sera procesada por una funcion de la base de datos que asignara
 * los permisos correspondientes al rol en edicion la cadena luce asi:
 *      
 *      1m1a2a3a2m1a2a3a
 * 
 * @returns {String} cadena de valor
 */
function get_modules_values(){
    var count = document.getElementById('counter').value;
    var chain = "";
    for(var i=0; i< count; i++){
        if(document.getElementById('mod'+i).checked){
            chain+=document.getElementById('mod'+i).value+"m";
            for(var j=1; j<=6; j++ ){
                if(document.getElementById('act'+j+i).checked){
                    chain+=document.getElementById('act'+j+i).value+"a";
                }
            }
        }
    }
    return chain;
}

/**
 * Envia la información necesecaria para incluir el rol en la base de datos
 * y procesa la salida
 * @returns {undefined} Redirecciona o muestra un mensaje de error.
 */
function new_roll(){
    var loading = document.getElementById('loading_container');
    loading.innerHTML=cargando_bar;
    if(validate_roll()){
        //Obtener Valores
        var roll_name = document.getElementById('name_roll').value;
        var roll_desc = document.getElementById('desc_roll').value;
        var permisos = get_modules_values();
        var page = document.getElementById('container');
        //Preparacion  llamada AJAX 
        var ajax=NuevoAjax();
        var _values_send =
        'roll_name='+roll_name+
        '&roll_desc='+roll_desc+
        '&permisos='+permisos;
        var _URL_="mod/admin/rolls/ajax_new_roll.php?";
        //alert(_URL_+_values_send); //DEBUG
        ajax.open("GET",_URL_+_values_send,true); 
        ajax.onreadystatechange=function() {
            if (ajax.readyState==1){
            //Nada
            }else if (ajax.readyState==4){
                var response = ajax.responseText;
                //alert(response); //DEBUG
                if(response=="OK"){
                    jAlert('El rol se guardo de forma satisfactoria!','Exito');
                    page.innerHTML = '';
                    OpcionMenu('mod/admin/rolls/list_roll.php?','');
                }else{
                    jAlert('Ha ocurrido un error inesperado intentelo más tarde!','Error');
                }
            }
        };
        ajax.send(null);
    }
    loading.innerHTML="";
}

/**
 * Envia la información necesaria para actualizar el rol en la base de datos
 * y procesa la salida.
 * @returns {undefined} Redirecciona o muestra un error
 */
function update_roll(){
    var loading = document.getElementById('loading_container');
    loading.innerHTML=cargando_bar;
    if(validate_roll()){
        //Obtener Valores
        var id_roll = document.getElementById('id_roll').value;
        var roll_name = document.getElementById('name_roll').value;
        var roll_desc = document.getElementById('desc_roll').value;
        var permisos = get_modules_values();
        var page = document.getElementById('container');
        //Preparacion  llamada AJAX 
        var ajax=NuevoAjax();
        var _values_send =
        'id_roll='+id_roll+
        '&roll_name='+roll_name+
        '&roll_desc='+roll_desc+
        '&permisos='+permisos;
        var _URL_="mod/admin/rolls/ajax_upd_roll.php?";
        //alert(_URL_+_values_send); //DEBUG
        ajax.open("GET",_URL_+_values_send,true); 
        ajax.onreadystatechange=function() {
            if (ajax.readyState==1){
            //Nada
            }else if (ajax.readyState==4){
                var response = ajax.responseText;
                //alert(response); //DEBUG
                if(response=="OK"){
                    jAlert('El rol se guardo de forma satisfactoria!','Exito');
                    page.innerHTML = '';
                    OpcionMenu('mod/admin/rolls/edit_roll.php?','id_roll='+id_roll+'&view_mode=1');
                }else{
                    jAlert('Ha ocurrido un error inesperado intentelo más tarde!','Error');
                }
            }
        };
        ajax.send(null);
    }
    loading.innerHTML="";
}

/**
 * envia la información necesaria para elminar el rol de la base de datos y 
 * procesa la salida
 * @param {type} id_roll identificador unico del rol
 * @returns {undefined} Redirecciona o muestra un error
 */
function delete_roll_action(id_roll){
    var page = document.getElementById('container');
    page.innerHTML=cargando;
    var ajax=NuevoAjax();
    //Preparacion  llamada AJAX
    var _values_send ='id_roll='+id_roll;		
    var _URL_="mod/admin/rolls/ajax_del_roll.php?";
    //alert(_URL_+_values_send); //DEBUG
    ajax.open("GET",_URL_+"&"+_values_send,true);
    ajax.onreadystatechange=function() {
        if (ajax.readyState==1){
            page.innerHTML= cargando;
        }else if (ajax.readyState==4){
            var response = ajax.responseText;
            //alert(response); //DEBUG
            if(response =="OK"){
                jAlert('El rol se a eliminado correctamente!','Exito');
                page.innerHTML = '';
                OpcionMenu('mod/admin/rolls/list_roll.php?','');
            }else {
                jAlert('Ha ocurrido un error inesperado intentelo más tarde!','Error');
                page.innerHTML = '';
                OpcionMenu('mod/admin/rolls/list_roll.php?','');
            }
        }
    };
    ajax.send(null);
}

/**
 * Muestra un cuadro para que el usuario confirme que desea eliminar el rol
 * @param {type} id_roll identificador del rol
 * @returns {undefined} Redirecciona o no hace nada
 */
function delete_roll(id_roll){
    jConfirm("Desea eliminar el rol","Eliminar Rol",function(r){
        if(r){
            delete_roll_action(id_roll);
        }
    });
}