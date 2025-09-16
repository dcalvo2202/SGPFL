<?php
$ldapconn = ldap_connect("ldap://localhost:389");
if ($ldapconn) {
    echo "Conexión LDAP exitosa";
} else {
    echo "Error de conexión LDAP";
}
?>