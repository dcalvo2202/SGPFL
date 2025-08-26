<?php

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
        $ldap->dn = $ldap_dn; // Base DN of our organisation
        if ($ldap->connect()) {
            if ($ldap->checkPass($user, $pass)) {
                $out = 0;
                if ($attrib = $ldap->getAttribute($user, "cn")) {
                    $user_name = $attrib[0];
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

    //Incluir un medio de control para seleccionar automaticamente el idioma,
    //ya sea obteniendo la conf del navegador o desde la base de datos  
    require'../../lang/lang.es';
    $sql1 = "SELECT id_roll FROM sis_login WHERE id='" . $user . "';";
    $sqlout1 = seleccion($sql1);
    $mySessionController = mySession::getIstance($_MYSESSION_CONF);
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

echo $out; // 0 todo bien / 1 contraseña erronea / 2 usuario no existe /3 problema con LDAP
?>