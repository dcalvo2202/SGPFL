/**
 * Captura las teclas ingresadas en el formulario de ingreso
 * si la tecla es "Intro" entonces procede al ingreso
 * @param {event} keytxt
 * @returns Nada/Redirecciona al ingreso
 */
function onEnterLogin(keytxt) {
    if (keytxt.keyCode == 13){
        Do_Login();
    }
}

/**
 * Valida que la información requerida para el ingreso sea digitada completamente
 * alerta al usuario en caso de no cumplir los requisitos
 * @returns {Boolean} Verdadero todo bien / Flaso Falta algun dato
 */
function Validate_Login() {
    if(document.getElementById('user').value == ""){
        jAlert("Ingrese su id de usuario","Dato Requerido");
        document.getElementById('user').focus();
        return false;
    }
    if(document.getElementById('pass').value == ""){
        jAlert("Ingrese su clave de usuario","Dato Requerido");
        document.getElementById('pass').focus();
        return false;
    }
    return true;
}

/**
 * Revisa que la infroamción del usuario sea valida y el mismo tenga permisos
 * para ingresar en este sistema.
 * @returns Redirecciona hacia la pagina principal del sistema
 */
function Do_Login(){
    //AJAX Cargando
    var page = document.getElementById('loading_container');
    page.innerHTML = cargando_bar;
    if(Validate_Login()){
        //Obtener variables
        var user = document.getElementById('user').value;
        var pass = document.getElementById('pass').value;
        //Preparacion  llamada AJAX
        var ajax=NuevoAjax();
        var _values_send ='user='+user+'&pass='+pass;
        var _URL_="mod/login/ajax_login.php?";
        //AJAX Insercion
        ajax.open("POST",_URL_,true);
        ajax.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        ajax.setRequestHeader("Content-length", _values_send.length);
        ajax.setRequestHeader("Connection", "close");
        ajax.send(_values_send);
        ajax.onreadystatechange = function() {//Call a function when the state changes.
            if(ajax.readyState == 4 && ajax.status == 200) {
                var response = ajax.responseText;
                if(response== "0"){
                    window.location=document.getElementById("cds_domain_locate").value+"main.php";
                }else if ( response== "1"){
                    jAlert('Clave invalida','Error');
                    page.innerHTML="";
                //setTimeout("".value,15000);
                }else if(response== "2"){
                    jAlert('Este usuario no existe\n\tComuniquese con el administrador','Error');
                    page.innerHTML="";
                //setTimeout("".value,15000);
                }else{
                    jAlert('Sucedio un error inesperado','Error');
                    
                //setTimeout("".value,15000);
                }
            }
        }
    }
    page.innerHTML="";
}