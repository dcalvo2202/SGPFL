<?php
$ldapconn = ldap_connect("localhost");
if ($ldapconn) {
    $ldap_host = "ldap://localhost:389"; // Cambia si tu host/puerto es diferente
    $ldap_user = "cn=admin,dc=una,dc=ac,dc=cr"; // DN de un usuario válido
    $ldap_pass = "admin"; // Contraseña del usuario

    $ldapconn = ldap_connect($ldap_host);
    if ($ldapconn) {
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);

        // Autenticación simple con usuario y contraseña
        $bind = @ldap_bind($ldapconn, $ldap_user, $ldap_pass);

        if ($bind) {
            echo "Conexión y bind LDAP exitosos<br>";
            // Ejemplo: buscar usuario por uid y mostrar atributos
            $base_dn = "ou=People,dc=una,dc=ac,dc=cr";
            $filtro = "(uid=rrodrigo123)"; // Cambia el uid según tu usuario
            $atributos = ["cn", "mail", "sn", "telephonenumber"];
            $search = ldap_search($ldapconn, $base_dn, $filtro);
            if ($search) {
                $entries = ldap_get_entries($ldapconn, $search);
                echo "<pre>";
                print_r($entries);
                echo "</pre>";
                if ($entries["count"] > 0) {
                    echo "<b>cn:</b> " . ($entries[0]["cn"][0] ?? "") . "<br>";
                    echo "<b>mail:</b> " . ($entries[0]["mail"][0] ?? "") . "<br>";
                    echo "<b>sn:</b> " . ($entries[0]["sn"][0] ?? "") . "<br>";
                } else {
                    echo "No se encontró el usuario con ese uid.";
                }
            } else {
                echo "Error en la búsqueda LDAP.";
            }
        } else {
            echo "Error de conexión o bind LDAP";
        }
    } else {
    echo "Error de conexión LDAP";
    }
}
?>