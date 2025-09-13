<?php

include("inc/db/db.php");
include("config.inc");

/**
 * Revisa los permisos del usuario para cada acción controlada por el sistema
 * @param $mod Modulo del sistema
 * @param $act Accion del sistema
 * @param $rol Rol del usuario 
 */
function check_permiso($mod, $act, $rol) {
    $sql = "SELECT check_permits(" . $mod . "," . $act . "," . $rol . ") AS cant;";
    $res = seleccion($sql);
    return $res[0]['cant'];
}

function get_ldap_name($id) {
    require_once(dirname(__FILE__) . "/lib/AuthLdap/class.AuthLdap.php");
    include("config.inc");
    $ldap = new AuthLdap();
    $ldap->server = $ldap_server;
    $ldap->dn = $ldap_dn; // Base DN of our organisation
    if ($ldap->connect()) {
        if ($attrib = $ldap->getAttribute($id, "cn")) {
            return $attrib[0];
        } else {
            return "NULL";
        }
    }
}

?>