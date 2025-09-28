// Estilos para SweetAlert2, alertas del sistema.
var swal2CustomStyle = document.createElement('style');
swal2CustomStyle.innerHTML = `
.swal2-popup {
    border-radius: 1.5em !important;
}
.swal2-icon {
    margin-top: 2em !important;
    margin-bottom: 0 !important;
    padding: 0 !important;
}
.swal2-title {
    margin-bottom: 0 !important;
    margin-top: 0 !important;
    padding: 0 !important;
    padding-bottom: 0.30em !important;
    padding-top: 0.20em !important;
}
.swal2-html-container {
    margin-bottom: 0.05em !important;
    margin-top: 0 !important;
    padding: 0 !important;
}
.swal2-ok-btn-lg,
.swal2-confirm {
    background-color: #1565c0 !important;
    color: #fff !important;
    border: none !important;
    font-size: 1.4em !important;
    padding: 0.65em 1.5em !important;
    min-width: 3rem !important;
    border-radius: 0.5rem !important;
    margin-top: 0 !important;
}
`;
document.head.appendChild(swal2CustomStyle);

/*
    Opcion para mostrar y ocultar la clave en el formulario de ingreso.
*/
function togglePassword() {
            var input = document.getElementById("pass");
            var icon = document.getElementById("togglePasswordIcon");
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
}

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
 * @returns {Boolean} Verdadero todo bien / Falso Falta algun dato
 */
function Validate_Login() {
    if(document.getElementById('user').value == ""){
        // Cambiado: Usar SweetAlert2 en vez de jAlert
            Swal.fire({
                icon: 'warning',
                title: '<span style="font-size:1.3em;">Dato Requerido</span>',
                html: '<span style="font-size:1.3em;">Ingrese su identificación</span>',
                confirmButtonText: 'Aceptar',
                customClass: {
                    confirmButton: 'swal2-ok-btn-lg'
                }
            });
        document.getElementById('user').focus();
        return false;
    }
    if(document.getElementById('pass').value == ""){
            Swal.fire({
                icon: 'warning',
                title: '<span style="font-size:1.3em;">Dato Requerido</span>',
                html: '<span style="font-size:1.3em;">Ingrese su contraseña</span>',
                confirmButtonText: 'Aceptar',
                customClass: {
                    confirmButton: 'swal2-ok-btn-lg'
                }
            });
        document.getElementById('pass').focus();
        return false;
    }
    return true;
}

/**
 * Revisa que la información del usuario sea valida y el mismo tenga permisos
 * para ingresar en este sistema.
 * @returns Redirecciona hacia la pagina principal del sistema
 */
function Do_Login(){
    //AJAX Cargando
    var page = document.getElementById('loading_container');
    // Deshabilitar el botón de ingreso para evitar múltiples envíos
    var loginBtn = document.getElementById('saveForm');
    if (loginBtn) loginBtn.disabled = true;
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
        //ajax.setRequestHeader("Content-length", _values_send.length);
        //ajax.setRequestHeader("Connection", "close");
        ajax.send(_values_send);
        ajax.onreadystatechange = function() {//Call a function when the state changes.
            if(ajax.readyState == 4 && ajax.status == 200) {
                var response = ajax.responseText;
                console.log("Respuesta del backend:", response);

                //Tuvo éxito el ingreso, entonces redirecciona.
                if(response.trim() == "0"){
                    window.location=document.getElementById("cds_domain_locate").value+"main.php";
                    //window.location=document.getElementById("cds_domain_locate").value+"dashboard.php";
                }
                // Sino tuvo éxito, muestra el error correspondiente.
                else if ( response.trim()== "1" || response.trim() == "2"){
                        Swal.fire({
                            icon: 'error',
                            title: '<span style="font-size:1.3em;">Este usuario no existe</span>',
                            html: '<span style="font-size:1.3em;">Por favor comunicarse con el administrador.</span>',
                            confirmButtonText: 'Aceptar',
                            customClass: {
                                confirmButton: 'swal2-ok-btn-lg'
                            }
                        });
                    page.innerHTML="";
                }else if(response.trim() == "3"){
                        Swal.fire({
                            icon: 'error',
                            title: '<span style="font-size:1.3em;">Error del Servidor</span>',
                            html: '<span style="font-size:1.3em;">Por favor comunicarse con el administrador.</span>',
                            confirmButtonText: 'Aceptar',
                            customClass: {
                                confirmButton: 'swal2-ok-btn-lg'
                            }
                        });
                    page.innerHTML="";
                }else if(response.trim() == "4"){
                        Swal.fire({
                            icon: 'error',
                            title: '<span style="font-size:1.3em;">Cuenta deshabilitada</span>',
                            html: '<span style="font-size:1.3em;">Por favor comunicarse con el administrador.</span>',
                            confirmButtonText: 'Aceptar',
                            customClass: {
                                confirmButton: 'swal2-ok-btn-lg'
                            }
                        });
                    page.innerHTML="";
                }else if(response.trim() == "5"){
                        Swal.fire({
                            icon: 'error',
                            title: '<span style="font-size:1.3em;">Acceso denegado</span>',
                            html: '<span style="font-size:1.2em;">El usuario no pertenece a un grupo autorizado.<br>Por favor comunicarse con el administrador.</span>',
                            confirmButtonText: 'Aceptar',
                            customClass: {
                                confirmButton: 'swal2-ok-btn-lg'
                            }
                        });
                    page.innerHTML="";
                }else if(response.trim() == "6"){
                        Swal.fire({
                            icon: 'error',
                            title: '<span style="font-size:1.3em;">Datos inválidos</span>',
                            html: '<span style="font-size:1.2em;">Por favor verifique su usuario y contraseña.</span>',
                            confirmButtonText: 'Aceptar',
                            customClass: {
                                confirmButton: 'swal2-ok-btn-lg'
                            }
                        });
                    page.innerHTML="";
                }else if(response.trim() == "7"){
                        Swal.fire({
                            icon: 'error',
                            title: '<span style="font-size:1.3em;">Error de base de datos</span>',
                            html: '<span style="font-size:1.2em;">Por favor comunicarse con el administrador.</span>',
                            confirmButtonText: 'Aceptar',
                            customClass: {
                                confirmButton: 'swal2-ok-btn-lg'
                            }
                        });
                    page.innerHTML="";
                }else{
                        Swal.fire({
                            icon: 'error',
                            title: '<span style="font-size:1.3em;">Error</span>',
                            html: '<span style="font-size:1.3em;">Sucedió un error inesperado</span>',
                            confirmButtonText: 'Aceptar',
                            customClass: {
                                confirmButton: 'swal2-ok-btn-lg'
                            }
                        });
                }
                if (loginBtn) loginBtn.disabled = false;
            }
        }
    } else {
        if (loginBtn) loginBtn.disabled = false;
    }
    page.innerHTML="";
}