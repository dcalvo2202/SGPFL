<?php

include(dirname(__FILE__) . "/../../../lib/AuthLdap/class.AuthLdap.php");
include("../../../config.inc");

$id = $_GET['id'];
$ldap = new AuthLdap();
$ldap->server = $ldap_server;
$ldap->dn = $ldap_dn; // Base DN of our organisation
if ($ldap->connect()) {
    if ($attrib = $ldap->getAttribute($id, "cn")) {
        echo $attrib[0];
    }
}
?>