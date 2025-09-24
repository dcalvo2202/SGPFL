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


// Sanitización y validación básica de entrada (compatible PHP 8.1+)
$user = isset($_POST['user']) ? strip_tags(trim($_POST['user'])) : '';
$pass = isset($_POST['pass']) ? trim($_POST['pass']) : '';
if ($user === '' || $pass === '') {
    echo 6; // Código: datos de entrada inválidos
    exit();
}
$out = "";
$user_name = "";

// --- NUEVO: Verificar si ya hay sesión activa para este usuario ---
$mySessionController = mySession::getIstance($_MYSESSION_CONF);
$usuario_sesion = $mySessionController->getVar('usuario');
if (!empty($usuario_sesion) && $usuario_sesion === $user) {
    // Ya hay sesión activa para este usuario, retornar éxito sin reloguear
    echo 0;
    exit();
}

// 1. Verificar si el usuario existe en la base de datos local
$sql = "SELECT checklogin('" . $user . "','" . md5($pass) . "') as li_out;";
$sqlout = seleccion($sql);
$out = $sqlout[0]['li_out']; //2 si el susuario no existe en la tabla de usuarios
if ($out == 0) {
       goto skip_ldap;
}
// 2. Intentar autenticación por LDAP
elseif ($ldap_status == 1) {

    // --- Verificar disponibilidad del servidor LDAP con socket ---
    $ldap_server_str = is_array($ldap_server) ? $ldap_server[0] : $ldap_server;
    if (preg_match('/ldap:\/\/([^:]+):(\d+)/', $ldap_server_str, $matches)) {
        $ldap_host = $matches[1];
        $ldap_port = (int)$matches[2];
    } else {
        $ldap_host = $ldap_server_str;
        $ldap_port = 389;
    }
    $socket_timeout = 1; // segundos
    $fp = @fsockopen($ldap_host, $ldap_port, $errno, $errstr, $socket_timeout);
    if (!$fp) {
        $out = 3; // problema con LDAP
        echo $out;
        exit();
    } else {
        fclose($fp);
    }

    // Continuar con la autenticación LDAP
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
                $search = ldap_search($ldap->connection, $base_dn, $filtro, array("cn", "mail", "sn", "telephonenumber", "memberOf"));
                if ($search) {
                    // Obtener entradas y cargarlas en varibles
                    $entries = ldap_get_entries($ldap->connection, $search);
                    if ($entries["count"] > 0) {
                        $user_name = isset($entries[0]["cn"][0]) ? $entries[0]["cn"][0] : ' ';
                        $user_email = isset($entries[0]["mail"][0]) ? $entries[0]["mail"][0] : ' ';
                        $user_tel = isset($entries[0]["telephonenumber"][0]) ? $entries[0]["telephonenumber"][0] : ' ';

                        // --- Validación de grupo autorizado y mapeo a rol interno ---
                        // Obtener grupos directos (sin anidados)
                        
                        // --- DEBUG: Guardar atributos LDAP para inspección temporal ---
                        // file_put_contents('ldap_debug.txt', print_r($entries[0], true));

                        $grupos_usuario = array();
                        if (isset($entries[0]["memberof"]) && is_array($entries[0]["memberof"])) {
                            for ($i = 0; $i < $entries[0]["memberof"]["count"]; $i++) {
                                $dn_grupo = $entries[0]["memberof"][$i];
                                // Extraer CN del grupo (mayúsculas/minúsculas tolerantes)
                                if (preg_match('/cn=([^,]+)/i', $dn_grupo, $matches)) {
                                    $grupos_usuario[] = $matches[1];
                                }
                            }
                        }
                        // Si no tiene grupos, denegar acceso
                        if (empty($grupos_usuario)) {
                            echo 5; // No pertenece a ningún grupo
                            exit();
                        }

                        // Definir grupos autorizados
                        $grupos_autorizados = array('Administradores', 'CTFG/Subdireccion', 'Estudiantes', 'Asesores Externos');
                        $grupo_valido = '';
                        foreach ($grupos_usuario as $g) {
                            if (in_array($g, $grupos_autorizados)) {
                                $grupo_valido = $g;
                                break; // Solo el primero directo
                            }
                        }
                        // Si no pertenece a grupo autorizado, denegar acceso antes de importar/login
                        if ($grupo_valido == '') {
                            echo 5; // Código: no pertenece a un grupo autorizado
                            exit();
                        }
                        // Mapeo simple a rol interno (sin modificar variables originales)
                        // $grupo_valido contiene el grupo autorizado
                        // Ejemplo de mapeo (puedes usarlo donde lo necesites):
                        // $rol_interno = ($grupo_valido == 'Administradores') ? 1 : (($grupo_valido == 'CTFG/Subdireccion') ? 2 : (($grupo_valido == 'Estudiantes') ? 3 : 4));
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

    skip_ldap:
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
    if ($sqlout1 === false) {
        echo 7; // Código: error en consulta a la base de datos
        exit();
    }

    // Si no existe, sincronizar el usuario en ambas tablas
    if (!$sqlout1 || count($sqlout1) == 0) {
        // Mapear grupo LDAP a rol interno
        function mapearGrupoALRol($grupo) {
            $mapa = [
                'Administradores' => 1,
                'CTFG/Subdireccion' => 2,
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
        if (transaccion($sql_insert_login) === false) {
            echo 7; // Código: error en inserción a la base de datos
            exit();
        }
        // Insertar en sis_user con todos los campos
        $sql_insert_user = "INSERT INTO sis_user (id, nombre, email, telefono, id_tipo_tel) VALUES ('" . $user . "', '" . $user_name . "', '" . $user_email . "', '" . $user_tel . "', 'M');";
        if (transaccion($sql_insert_user) === false) {
            echo 7; // Código: error en inserción a la base de datos
            exit();
        }
        $id_roll = $rol_interno;
        $nombre_final = $user_name;

        // --- Informe de sincronización: registrar usuario nuevo sincronizado ---
        $sync_log = __DIR__ . '/ldap_sync.log';
        $sync_msg = date('Y-m-d H:i:s') . " | Nuevo usuario sincronizado | ID: $user | Nombre: $user_name | Email: $user_email | Rol: $rol_ldap\n";
        file_put_contents($sync_log, $sync_msg, FILE_APPEND);
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

echo $out; // 0 todo bien / 1 contraseña erronea / 2 usuario no existe /3 problema con LDAP /4 cuenta deshabilitada /5 no pertenece al grupo autorizado / 6 datos de entrada inválidos /7 error en base de datos
?>