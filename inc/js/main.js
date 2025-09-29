/*
 * Este archivo contiene las funciones generales del sitio y sistema
 **/

/**
 * Codifica en formato UTF-8 a nivel de javascript
 * @param {String} string texto ingresado
 * @returns {String} texto en formato UTF-8
 */
function utf8_encode(string) {
    // Encodes an ISO-8859-1 string to UTF-8    
    //   
    // version: 812.316  
    // discuss at: http://phpjs.org/functions/utf8_encode  
    // +   original by: Webtoolkit.info (http://www.webtoolkit.info/)  
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)  
    // +   improved by: sowberry  
    // +    tweaked by: Jack  
    // +   bugfixed by: Onno Marsman  
    // +   improved by: Yves Sucaet  
    // +   bugfixed by: Onno Marsman  
    // *     example 1: utf8_encode('Tom van Mikes');  
    // *     returns 1: 'Tom van Mikes'  
    string = (string + '').replace(/\r\n/g, "\n").replace(/\r/g, "\n");
    var utftext = "";
    var start, end;
    var stringl = 0;
    start = end = 0;
    stringl = string.length;
    for (var n = 0; n < stringl; n++) {
        var c1 = string.charCodeAt(n);
        var enc = null;
        if (c1 < 128) {
            end++;
        } else if ((c1 > 127) && (c1 < 2048)) {
            enc = String.fromCharCode((c1 >> 6) | 192) + String.fromCharCode((c1 & 63) | 128);
        } else {
            enc = String.fromCharCode((c1 >> 12) | 224) + String.fromCharCode(((c1 >> 6) & 63) | 128) + String.fromCharCode((c1 & 63) | 128);
        }
        if (enc != null) {
            if (end > start) {
                utftext += string.substring(start, end);
            }
            utftext += enc;
            start = end = n + 1;
        }
    }
    if (end > start) {
        utftext += string.substring(start, string.length);
    }
    return utftext;
}

/**
 * Decodifica en formato UTF-8 a nivel de javascript
 * @param {String} str_data en formato UTF-8
 * @returns {String} texto
 */
function utf8_decode(str_data) {
    // Converts a UTF-8 encoded string to ISO-8859-1    
    //   
    // version: 810.1317  
    // discuss at: http://phpjs.org/functions/utf8_decode  
    // +   original by: Webtoolkit.info (http://www.webtoolkit.info/)  
    // +      input by: Aman Gupta  
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)  
    // +   improved by: Norman "zEh" Fuchs  
    // +   bugfixed by: hitwork  
    // +   bugfixed by: Onno Marsman  
    // *     example 1: utf8_decode('Kevin van Zonneveld');  
    // *     returns 1: 'Kevin van Zonneveld'  
    var tmp_arr = [], i = ac = c1 = c2 = c3 = 0;
    str_data += '';
    while (i < str_data.length) {
        c1 = str_data.charCodeAt(i);
        if (c1 < 128) {
            tmp_arr[ac++] = String.fromCharCode(c1);
            i++;
        } else if ((c1 > 191) && (c1 < 224)) {
            c2 = str_data.charCodeAt(i + 1);
            tmp_arr[ac++] = String.fromCharCode(((c1 & 31) << 6) | (c2 & 63));
            i += 2;
        } else {
            c2 = str_data.charCodeAt(i + 1);
            c3 = str_data.charCodeAt(i + 2);
            tmp_arr[ac++] = String.fromCharCode(((c1 & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
            i += 3;
        }
    }
    return tmp_arr.join('');
}

/**
 * Funcion que crea un Objeto Navegador basado en el Navegador de internet
 * utilizado para ingresar en la aplicación.
 * @returns {XMLHttpRequest|ActiveXObject|ajax|Boolean}
 */
function NuevoAjax() {
    try {
        ajax = new ActiveXObject("Msxml2.XMLHTTP");
    } catch (e) {
        try {
            ajax = new ActiveXObject("Microsoft.XMLHTTP");
        } catch (E) {
            ajax = false;
        }
    }
    if (!ajax && typeof XMLHttpRequest != 'undefined') {
        ajax = new XMLHttpRequest();
    }
    return ajax;
}

/**
 * El siguiente codigo es parte de la libreria prototype que permite cargar
 * elementos de javascriop a una pagina que ya esta cargada, es decir permite
 * incluir nuevas funciones de javascript en un sitio que ya esta cargado.
 */
/******************************************************************************/
var tagScript = '(?:<script.*?>)((\n|\r|.)*?)(?:<\/script>)';
/**
 * Eval script fragment
 * @return String
 */
String.prototype.evalScript = function()
{
    return (this.match(new RegExp(tagScript, 'img')) || []).evalScript();
};
/**
 * strip script fragment
 * @return String
 */
String.prototype.stripScript = function()
{
    return this.replace(new RegExp(tagScript, 'img'), '');
};
/**
 * extract script fragment
 * @return String
 */
String.prototype.extractXScript = function()
{
    var matchAll = new RegExp(tagScript, 'img');
    return (this.match(matchAll) || []);
};
/**
 * Eval scripts
 * @param {string} extracted javascript code
 * @return String
 */
Array.prototype.evalScript = function(extracted)
{
    var s = this.map(function(sr) {
        var sc = (sr.match(new RegExp(tagScript, 'im')) || ['', ''])[1];
        if (window.execScript) {
            window.execScript(sc);
        }
        else
        {
            window.setTimeout(sc, 0);
        }
    });
    return true;
};
/**
 * Map array elements
 * @param {Function} fun
 * @return Function
 */
Array.prototype.map = function(fun)
{
    if (typeof fun !== "function") {
        return false;
    }
    var i = 0, l = this.length;
    for (i = 0; i < l; i++)
    {
        fun(this[i]);
    }
    return true;
};
/******************************************************************************/

//Variables global utilizada en en el procesamiento de AJAX
// basicamente añaden gif de imagenes mientras se realiza la llamada
var cargando = '<div style="text-align:center; top: 50px;"><img src="img/loader_circle.gif"/><div>';
var cargando_bar = '<div style="text-align:center"><img src="img/loader_bar.gif"/><div>';

function OpcionMenu(opcion, parametros) {
    var page = document.getElementById('container');
    var ajax = NuevoAjax();
    var page_dir = document.getElementById("cds_domain_locate").value;
    ajax.open("GET", page_dir + opcion + "&" + parametros, true);
    ajax.onreadystatechange = function() {
        if (ajax.readyState == 1) {
            page.innerHTML = cargando;
        } else if (ajax.readyState == 4) {
            var scs = ajax.responseText.extractXScript();
            var out = ajax.responseText;
            page.innerHTML = out;
            scs.evalScript();
        }
    };
    ajax.send(null);
    window.scrollTo(0, 0);
}

/**
 * Muestra u oculta formularios de busqueda
 * @returns {undefined} cambia atributos del DOM
 */
function show_hide_search() {
    var estado = document.getElementById('Buscar').style.display;
    if (estado == 'none') {
        document.getElementById('Buscar').style.display = 'block';
        document.getElementById('Buscar_btn2').style.display = 'block';
        document.getElementById('Buscar_btn1').style.display = 'none';
    } else if (estado == 'block') {
        document.getElementById('Buscar').style.display = 'none';
        document.getElementById('Buscar_btn2').style.display = 'none';
        document.getElementById('Buscar_btn1').style.display = 'block';
    }
}

/*
 * Verifica si el navegador es Chrome
 */
function is_chrome() {
    var is_chrome = navigator.userAgent.toLowerCase().indexOf('chrome') > -1;
    return is_chrome;
}


(function() {
    var isBootstrapEvent = false;
    if (window.jQuery) {
        var all = jQuery('*');
        jQuery.each(['hide.bs.dropdown', 
            'hide.bs.collapse', 
            'hide.bs.modal', 
            'hide.bs.tooltip',
            'hide.bs.popover'], function(index, eventName) {
            all.on(eventName, function( event ) {
                isBootstrapEvent = true;
            });
        });
    }
    var originalHide = Element.hide;
    Element.addMethods({
        hide: function(element) {
            if(isBootstrapEvent) {
                isBootstrapEvent = false;
                return element;
            }
            return originalHide(element);
        }
    });
})();