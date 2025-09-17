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

// 1. Intentar autenticación por LDAP primero
if ($ldap_status == 1) {
    $ldap = new AuthLdap();
    $ldap->server = $ldap_server;
    $ldap->dn = $ldap_dn; // Base DN of our organization
    $searchUser = $ldap_user;
    $searchPassword = $ldap_pass;
    $ldap->people = "People";   // Ajusta si tu estructura LDAP es diferente
    $ldap->groups = "Groups";   // Ajusta si tu estructura LDAP es diferente

    if ($ldap->connect()) {
        if ($ldap->checkPass($user, $pass)) {
            $out = 0;
            
            // Buscando información adicional del usuario
            $base_dn = "ou=People,dc=una,dc=ac,dc=cr";
            $filtro = "(uid=$user)";
            $atributos = ["cn", "mail", "sn", "telephonenumber"];

            // Hay que hacer un bind porque la conexión anónima no permite búsquedas
            $bind = @ldap_bind($ldap->connection, $searchUser, $searchPassword);
            if ($bind) {
                // Buscar atributos
                $search = ldap_search($ldap->connection, $base_dn, $filtro);
                if ($search) {
                    // Obtener entradas y cargarlas en varibles
                    $entries = ldap_get_entries($ldap->connection, $search);
                    if ($entries["count"] > 0) {
                        $user_name = isset($entries[0]["cn"][0]) ? $entries[0]["cn"][0] : ' ';
                        $user_email = isset($entries[0]["mail"][0]) ? $entries[0]["mail"][0] : ' ';
                        $user_tel = isset($entries[0]["telephonenumber"][0]) ? $entries[0]["telephonenumber"][0] : ' ';
                    } else {
                        $user_name = $user_email = $user_tel = ' ';
                    }
                } else {
                    $user_name = $user_email = $user_tel = '';
                }
            } else {
                $user_name = $user_email = $user_tel = ' ';
            }
        } else {
            $out = 1;
        }
        $ldap->close();
    } else {
        $out = 3;
    }
} else {
    $out = 1;
}


if ($out == 0) {
    // Regenerar el ID de sesión para prevenir session fixation
    $mySessionController = mySession::getIstance($_MYSESSION_CONF);
    if (method_exists($mySessionController, 'regenerateId')) {
        $mySessionController->regenerateId();
    } elseif (function_exists('session_regenerate_id')) {
        session_regenerate_id(true);
    }
    require __DIR__ . '/../../lang/lang.es';

    // --- Lógica para usuarios LDAP: crear si no existe y mapear rol ---
    // Obtener nombre y rol usando JOIN
    $sql1 = "SELECT l.id_roll, u.nombre FROM sis_login l LEFT JOIN sis_user u ON l.id = u.id WHERE l.id='" . $user . "';";
    $sqlout1 = seleccion($sql1);

    // Si no existe, sincronizar el usuario en ambas tablas
    if (!$sqlout1 || count($sqlout1) == 0) {
        // Mapear grupo LDAP a rol interno
        function mapearGrupoALRol($grupo) {
            $mapa = [
                'Administradores' => 1,
                'CTFG' => 2,
                'Estudiantes' => 3,
                'Asesores Externos' => 4
            ];
            return isset($mapa[$grupo]) ? $mapa[$grupo] : 3;
        }
        $rol_ldap = isset($rol_ldap) ? $rol_ldap : 'Estudiantes';
        $rol_interno = mapearGrupoALRol($rol_ldap);

        // Insertar en sis_login con pass en md5
        $pass_md5 = md5($pass);
        $sql_insert_login = "INSERT INTO sis_login (id, pass, id_roll) VALUES ('" . $user . "', '" . $pass_md5 . "', '" . $rol_interno . "');";
        transaccion($sql_insert_login);
        // Insertar en sis_user con todos los campos
        $sql_insert_user = "INSERT INTO sis_user (id, nombre, email, telefono, id_tipo_tel) VALUES ('" . $user . "', '" . $user_name . "', '" . $user_email . "', '" . $user_tel . "', 'M');";
        transaccion($sql_insert_user);
        $id_roll = $rol_interno;
        $nombre_final = $user_name;
    } else {
        $id_roll = $sqlout1[0]['id_roll'];
        $nombre_final = $sqlout1[0]['nombre'];
    }
    $mySessionController->save("usuario", $user);
    $mySessionController->save("nombre", $nombre_final);
    $mySessionController->save("rol", $id_roll);
    $mySessionController->save("cds_domain", $cds_domain);
    $mySessionController->save("cds_locate", $cds_locate);
    $mySessionController->save("page_cant", $page_cant);
    $mySessionController->save("page_title", $page_title);
    $mySessionController->save("footer_title", $footer_title);
    $mySessionController->save('vocab', $vocab);
}

echo $out; // 0 todo bien / 1 contraseña erronea / 2 usuario no existe /3 problema con LDAP /4 cuenta deshabilitada /5 no pertenece al grupo autorizado
?>