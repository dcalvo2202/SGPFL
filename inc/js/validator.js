/* 
 * Este archivo contiene la funciones par validar campos
 */
/*****************************************
 *Descripcion: Permite solo digitar numeros
 *Parametros: texto a evaluar
 *Respuesta: texto evaluado
 * @param {char} keytxt codigo de la tecla presionada
 * @param {int} opc determina el patron de caracteres a permitir, 0 = numeros, 1 = numeros + "/", 2 = numeros + "-"
 *****************************************/
function onlyNumbers(keytxt, opc) {
    var key = (document.all) ? keytxt.keyCode : keytxt.which;
    if (keytxt.keyCode == 9 || keytxt.keyCode == 37 || keytxt.keyCode == 39) {
        return keytxt.keyCode;
    }
    if (key == 8 || key == 46)
        return true;
    switch (opc) {
        case 0:
            patron = /[.0-9]/;
            break;
        case 1:
            patron = /[.0-9/]/;
            break;
        case 2:
            patron = /[.0-9-]/;
            break;
    }
    var out = String.fromCharCode(key);
    return patron.test(out);
}

/*****************************************
 *Descripcion: Revisa el formato de la fecha
 *Parametros: texto con formato fecha
 *Respuesta: error si la fecha es invalida
 * @param {string} date fecha con formato dd/mm/aaaa
 *****************************************/
function check_date(date) {
    if (date != undefined && date != "") {
        if (!/^\d{2}\/\d{2}\/\d{4}$/.test(date)) {
            jAlert("Formato de fecha no válido (dd/mm/aaaa)", "Dato erróneo");
            return false;
        }
        var n = date.split("/");
        var day = parseInt(n[0], 10);
        var month = parseInt(n[1], 10);
        var year = parseInt(n[2], 10);
        var numdays;
        switch (month) {
            case 1:
            case 3:
            case 5:
            case 7:
            case 8:
            case 10:
            case 12:
                numdays = 31;
                break;
            case 4:
            case 6:
            case 9:
            case 11:
                numdays = 30;
                break;
            case 2:
                if (check_year(year)) {
                    numdays = 29;
                } else {
                    numdays = 28;
                }
                break;
            default:
                jAlert("Fecha introducida errónea", "Dato erróneo");
                return false;
        }
        if (day > numdays || day == 0) {
            jAlert("Fecha introducida errónea o año no bisiesto", "Dato erróneo");
            return false;
        }
        return true;
    }
    jAlert("Fecha introducida errónea", "Dato erróneo");
    return false;
}
/*****************************************
 *Descripcion: Revisa si el año es bisiesto
 *Parametros: año
 *Respuesta: verdadero o falso
 * @param {string} year año a evaluar
 *****************************************/
function check_year(year) {
    if ((year % 100 != 0) && ((year % 4 == 0) || (year % 400 == 0))) {
        return true;
    } else {
        return false;
    }
}

/*****************************************
 *Descripcion: Remplaza texto por otro
 *Parametros: texto a remplazar, texto nuevo, texto original
 *Respuesta: texto evaluado
 * @param {string} busca texto a buscar
 * @param {string} repla texto para remplasar
 * @param {string} orig texto a original
 *****************************************/
function str_replace(busca, repla, orig) {
    str = new String(orig);
    rExp = "/" + busca + "/g";
    rExp = eval(rExp);
    newS = String(repla);
    str = new String(str.replace(rExp, newS));
    return str;
}

/*****************************************
 *Descripcion: Valida la estructura del correo
 *Parametros: Texto a validar
 *Respuesta: True/False
 * @param {object} email objeto tipo input que contiene el email del usuario
 *****************************************/
function check_email(email) {
    if (email.value == "") {
        jAlert("Debe escribir su correo electronico", "Dato erróneo");
        return false;
    }
    email.value = email.value.toLowerCase();
    emailStr = email.value;
    // document.getElementById('txt_email').value = emailStr.toLowerCase();
    var emailPat = /^(.+)@(.+)$/;
    /* Verificar la existencia de caracteres. ( ) < > @ , ; : \ " . [ ] */
    var specialChars = "\\(\\)<>@,;:\\\\\\\"\\.\\[\\]";
    /* Verifica los caracteres que son validos en una direccion de email */
    var validChars = "\[^\\s" + specialChars + "\]";
    var quotedUser = "(\"[^\"]*\")";
    /* Verifica si la direccion de email esta representada con una direccion IP Valida */
    var ipDomainPat = /^\[(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})\]$/;
    /* Verificar caracteres invalidos */
    var atom = validChars + '+';
    var word = "(" + atom + "|" + quotedUser + ")";
    var userPat = new RegExp("^" + word + "(\\." + word + ")*$");
    /*domain, as opposed to ipDomainPat, shown above. */
    var domainPat = new RegExp("^" + atom + "(\\." + atom + ")*$");
    var matchArray = emailStr.match(emailPat);
    if ((emailStr.length != 0) && (matchArray == null)) {
        jAlert("La Direccion de Correo Electronico No Tiene un Formato de Correo Correcto!", "Dato erróneo");
        return false;
    }
    var user = matchArray[1];
    var domain = matchArray[2];
    // Si el user "user" es valido
    if (user.match(userPat) == null) {
        jAlert("La Direccion de Correo Electronico No Tiene un Formato de Correo Correcto!", "Dato erróneo");
        return false;
    }
    /* Si la direccion IP es valida */
    var IPArray = domain.match(ipDomainPat);
    if (IPArray != null) {
        for (var i = 1; i <= 4; i++) {
            if (IPArray[i] > 255) {
                jAlert("Servidor de correo invalido", "Dato erróneo");
                return false;
            }
        }
    }
    var domainArray = domain.match(domainPat);
    if (domainArray == null) {
        jAlert("La Direccion de Correo Electronico No Tiene un Formato de Correo Correcto!", "Dato erróneo");
        return false;
    }
    var atomPat = new RegExp(atom, "g");
    var domArr = domain.match(atomPat);
    var len = domArr.length;
    if (domArr[domArr.length - 1].length < 2 || domArr[domArr.length - 1].length > 3) {
        jAlert("La Direccion de Correo Electronico No Tiene un Formato de Correo Correcto!", "Dato erróneo");
        return false;
    }
    if (len < 2) {
        jAlert("La Direccion de Correo Electronico No Tiene un Formato de Correo Correcto!", "Dato erróneo");
        return false;
    }
    return true;
}
/**
 * Verifica el tamaño minimo que tiene que cuemplir un string
 * @param {string} str texto a evaluar
 * @param {int} length tamaño minimo
 * @returns {Boolean}
 */
function IsEnoughLength(str, length){
    if ((str == null) || isNaN(length))
        return false;
    else if (str.length < length)
        return false;
    return true;

}
/**
 * Verifica si el string tiene Mayusculas y Minusculas
 * @param {string} passwd texto a evaluar
 * @returns {Boolean}
 */
function HasMixedCase(passwd){
    if (passwd.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/))
        return true;
    else
        return false;
}
/**
 * Verifica si el string contiene numeros
 * @param {string} passwd texto a evaluar
 * @returns {Boolean}
 */
function HasNumeral(passwd){
    if (passwd.match(/[0-9]/))
        return true;
    else
        return false;
}
/**
 * Verifica si el string tiene caracteres especiales
 * @param {string} passwd texto a evaluar
 * @returns {Boolean}
 */
function HasSpecialChars(passwd){
    if (passwd.match(/.[!,@,#,$,%,^,&,*,?,_,~]/))
        return true;
    else
        return false;
}
/**
 * Verifica si una cadena de carracteres se considera una contraseña
 * segura
 * @param {string} pwd texto a evaluar
 * Muestra en el formulario Muy seguro, Seguro, Bueno e Inseguro dependiendo del
 * caso
 */
function CheckPasswordStrength(pwd){
    var pass_strength;
    if (IsEnoughLength(pwd, 12) && HasMixedCase(pwd) && HasNumeral(pwd) && HasSpecialChars(pwd))
        pass_strength = "<font style='color:olive'>&nbsp;&nbsp;*Muy Seguro</font>";
    else if (IsEnoughLength(pwd, 8) && HasMixedCase(pwd) && (HasNumeral(pwd) || HasSpecialChars(pwd)))
        pass_strength = "<font style='color:Blue'>&nbsp;&nbsp;*Seguro</font>";
    else if (IsEnoughLength(pwd, 6) && HasNumeral(pwd))
        pass_strength = "<font style='color:Green'>&nbsp;&nbsp;*Bueno</font>";
    else
        pass_strength = "<font style='color:red'>&nbsp;&nbsp;*Inseguro</font>";
    document.getElementById('pass_strength').innerHTML = pass_strength;
}
/**
 * Le asigna una puntuacion a la contraseña, dependiendo de su seguridad
 * @param {string} pwd texto a evaluar
 * @returns {int} puntuasion asignada a una contraseña
 */
function scorePassword(pwd) {
    var score = 0;
    if (IsEnoughLength(pwd, 12) && HasMixedCase(pwd) && HasNumeral(pwd) && HasSpecialChars(pwd))
        score = 100;
    else if (IsEnoughLength(pwd, 8) && HasMixedCase(pwd) && (HasNumeral(pwd) || HasSpecialChars(pwd)))
        score = 75;
    else if (IsEnoughLength(pwd, 6) && HasNumeral(pwd))
        score = 50;
    else
        score = 25;
    return score;
}