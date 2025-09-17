
<?php
// CORS headers para permitir peticiones desde otros orígenes
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include(dirname(__FILE__) . "/../../lib/mysession/mySession.class.php");
include(dirname(__FILE__) . "/../../lib/mysession/mySession.conf.php");
include(dirname(__FILE__) . "/../../lib/AuthLdap/class.AuthLdap.php");
include("../../inc/db/db.php");
include("../../config.inc");

$user = $_POST['user'];
$pass = $_POST['pass'];
$out = "";
$user_name = "";

if ($ldap_status == 1) {
    $sql = "SELECT checklogin('" . $user . "','" . md5($pass) . "') as li_out;";
    $sqlout = seleccion($sql);
    $out = $sqlout[0]['li_out']; //2 si el susuario no existe en la tabla de usuarios
    if ($out == 1) {
        $ldap = new AuthLdap();
        $ldap->server = $ldap_server;
        $ldap->dn = $ldap_dn; // Base DN of our organization
        $ldap->people = "People";   // Ajusta si tu estructura LDAP es diferente
        $ldap->groups = "Groups";   // Ajusta si tu estructura LDAP es diferente

        if ($ldap->connect()) {
            if ($ldap->checkPass($user, $pass)) {
                // Validar userAccountControl (solo si es Active Directory)
                if ($serverType === "ActiveDirectory") {
                    $uac = $ldap->getAttribute($user, 'userAccountControl');
                    if ($uac && isset($uac[0]) && ($uac[0] & 2)) {
                        $out = 4; // Cuenta deshabilitada
                    }
                }
                // Verificar grupo autorizado (por ejemplo, "Estudiantes")
                if ($out == 1 || !$ldap->checkGroup($user, "Estudiantes")) {
                    $out = 5; // No pertenece al grupo autorizado
                } else {
                    $out = 0;
                    if ($attrib = $ldap->getAttribute($user, "cn")) {
                        $user_name = $attrib[0];
                    }
                }
            } else {
                $out = 1;
            }
            $ldap->close();
        } else {
            $out = 3;
        }
    }
} else {
    $sql = "SELECT checklogin('" . $user . "','" . md5($pass) . "') as li_out;";
    $sqlout = seleccion($sql);
    $out = $sqlout[0]['li_out'];
    if ($out == 0) {
        $sql0 = "SELECT nombre FROM sis_user WHERE id='" . $user . "';";
        $sqlout0 = seleccion($sql0);
        $user_name = $sqlout0[0]['nombre'];
    }
}


if ($out == 0) {
    // Regenerar el ID de sesión para prevenir session fixation
    $mySessionController = mySession::getIstance($_MYSESSION_CONF);
    if (method_exists($mySessionController, 'regenerateId')) {
        $mySessionController->regenerateId();
    } elseif (function_exists('session_regenerate_id')) {
        session_regenerate_id(true);
    }
    //Incluir un medio de control para seleccionar automaticamente el idioma,
    //ya sea obteniendo la conf del navegador o desde la base de datos  
    // require'../../lang/lang.es';
    require __DIR__ . '/../../lang/lang.es';
    $sql1 = "SELECT id_roll FROM sis_login WHERE id='" . $user . "';";
    $sqlout1 = seleccion($sql1);
    $mySessionController->save("usuario", $user);
    $mySessionController->save("nombre", $user_name);
    $mySessionController->save("rol", $sqlout1[0]['id_roll']);
    $mySessionController->save("cds_domain", $cds_domain);
    $mySessionController->save("cds_locate", $cds_locate);
    $mySessionController->save("page_cant", $page_cant);
    $mySessionController->save("page_title", $page_title);
    $mySessionController->save("footer_title", $footer_title);
    $mySessionController->save('vocab', $vocab);
}

echo $out; // 0 todo bien / 1 contraseña erronea / 2 usuario no existe /3 problema con LDAP /4 cuenta deshabilitada /5 no pertenece al grupo autorizado
?>