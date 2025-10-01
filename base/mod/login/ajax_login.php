<?php
/**
 * Para el funcionamiento correcto del login es necesario que esté activo el servidor SQL, porque sin este no va a funcionar.
 */

// =============================
// INICIALIZACIÓN Y CONFIGURACIÓN
// =============================

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
// Manejar excepción de conexión a la base de datos
try {
    include(dirname(__FILE__) . "/../../inc/db/db.php");
} catch (Throwable $e) {
    sendError(7); // Código: error en base de datos
}
include(dirname(__FILE__) . "/../../config.inc");

// =============================
// FUNCIONES AUXILIARES
// =============================

// Función para destruir sesión y eliminar cookie si login falla
function destroySessionAndCookie() {
    // Eliminar cookie
    if (isset($_COOKIE['base_sis'])) {
        setcookie('base_sis', '', time() - 3600, '/');
    }
    // Destruir sesión estándar de PHP
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

// Función centralizada para enviar errores, destruir sesión y terminar ejecución
function sendError($code, $message = '') {
    destroySessionAndCookie();
    echo $code;
    exit();
}

// Función para mapear grupo LDAP a rol interno usando la base de datos
function mapearGrupoALRol($grupo) {
    $mapa = [];
    $sql = "SELECT id_roll, roll_name FROM sis_rolls";
    $result = seleccion($sql);
    if ($result && count($result) > 0) {
        foreach ($result as $row) {
            $mapa[$row['roll_name']] = $row['id_roll'];
        }
    }
    // Si el grupo existe en la tabla, retorna el id_roll, si no, retorna el primer id_roll (por defecto)
    return isset($mapa[$grupo]) ? $mapa[$grupo] : (count($mapa) > 0 ? reset($mapa) : 1);
}

// =============================
// VALIDACIÓN DE ENTRADA
// =============================

// Sanitización y validación básica de entrada (compatible PHP 8.1+)
$user = isset($_POST['user']) ? strip_tags(trim($_POST['user'])) : '';
$pass = isset($_POST['pass']) ? trim($_POST['pass']) : '';
if ($user === '' || $pass === '') {
    sendError(6); // Código: datos de entrada inválidos
}
$out = "";
$user_name = "";

// =============================
// VERIFICACIÓN DE SESIÓN ACTIVA
// =============================

// Verificar si ya hay sesión activa para este usuario ---
$mySessionController = mySession::getIstance($_MYSESSION_CONF);
$usuario_sesion = $mySessionController->getVar('usuario');
// Si hay sesión activa y el usuario es el mismo, retornar éxito sin reloguear
if (!empty($usuario_sesion) && $usuario_sesion === $user) {
    // Ya hay sesión activa para este usuario, retornar éxito sin reloguear
    echo 0;
    exit();
}
// Si hay sesión activa y el usuario es diferente, destruir la sesión anterior
if (!empty($usuario_sesion) && $usuario_sesion !== $user) {
    // Logout automático igual al archivo logout.php
    $mySessionController->delete("SessionArray");
    $mySessionController->destroy($_MYSESSION_CONF['SID']);
    // Eliminar la cookie manualmente
    if (isset($_COOKIE[$_MYSESSION_CONF['SESSION_VAR_NAME']])) {
        setcookie($_MYSESSION_CONF['SESSION_VAR_NAME'], '', time() - 3600, '/');
        unset($_COOKIE[$_MYSESSION_CONF['SESSION_VAR_NAME']]);
    }
    // Opcional: puedes regenerar el ID de sesión aquí si lo deseas
    if (function_exists('session_regenerate_id')) {
        session_regenerate_id(true);
    }
}

// =============================
// AUTENTICACIÓN LOCAL (BASE DE DATOS)
// =============================

// 1. Verificar si el usuario existe en la base de datos local
$sql = "SELECT checklogin('" . $user . "','" . md5($pass) . "') as li_out;";
$sqlout = seleccion($sql);
$out = $sqlout[0]['li_out']; //2 si el susuario no existe en la tabla de usuarios
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
    if ($sqlout1 === false) {
        sendError(7); // Código: error en consulta a la base de datos
    }

    $id_roll = $sqlout1[0]['id_roll'];
    $nombre_final = $sqlout1[0]['nombre'];

    $mySessionController->save("usuario", $user);
    $mySessionController->save("nombre", $nombre_final);
    $mySessionController->save("rol", $id_roll);
    $mySessionController->save("cds_domain", $cds_domain);
    $mySessionController->save("cds_locate", $cds_locate);
    $mySessionController->save("page_cant", $page_cant);
    $mySessionController->save("page_title", $page_title);
    $mySessionController->save("footer_title", $footer_title);
    $mySessionController->save('vocab', $vocab);

    // === LÓGICA ESPECIAL PARA ESTUDIANTES (ROL 4) ===
    if ($id_roll == 4) {
        // Verificar si el estudiante ya tiene una propuesta TFG
        $tfg_check_sql = "SELECT COUNT(*) as tfg_count FROM tfg_proposals WHERE user_id = '" . $user . "'";
        $tfg_result = seleccion($tfg_check_sql);
        
        if ($tfg_result !== false && $tfg_result[0]['tfg_count'] == 0) {
            // Estudiante SIN propuesta TFG - Redirigir al formulario
            echo "estudiante_sin_tfg";
            exit();
        } else {
            // Estudiante CON propuesta - Redirigir a panel estudiante
            echo "estudiante_con_tfg";
            exit();
        }
    }

    echo $out; // 0 todo bien
    exit();
}

// =============================
// AUTENTICACIÓN POR LDAP
// =============================

// 2. Intentar autenticación por LDAP
else if ($ldap_status == 1) {

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
        sendError(3); // Código: problema con servidor LDAP
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
                            sendError(5); // Código: no pertenece al grupo autorizado
                        }

                        // Definir grupos autorizados dinámicamente desde la base de datos
                        $grupos_autorizados = array();
                        $sql_grupos = "SELECT roll_name FROM sis_rolls";
                        $result_grupos = seleccion($sql_grupos);
                        if ($result_grupos && count($result_grupos) > 0) {
                            foreach ($result_grupos as $row) {
                                $grupos_autorizados[] = $row['roll_name'];
                            }
                        }
                        $grupo_valido = '';
                        foreach ($grupos_usuario as $g) {
                            if (in_array($g, $grupos_autorizados)) {
                                $grupo_valido = $g;
                                break; // Solo el primero directo
                            }
                        }
                        // Si no pertenece a grupo autorizado, denegar acceso antes de importar/login
                        if ($grupo_valido == '') {
                            sendError(5); // Código: no pertenece al grupo autorizado
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
            $out = 1; // Contraseña incorrecta
        }
        $ldap->close();
    } else {
        $out = 3; // Problema con LDAP
    }
} else {
    $out = 1; // Usuario no existe en la base de datos local y LDAP está deshabilitado
}

// =============================
// RESPUESTA FINAL Y MANEJO DE ERRORES
// =============================

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
    if ($sqlout1 === false) {
        sendError(7); // Código: error en consulta a la base de datos
    }

    // Si no existe, sincronizar el usuario en ambas tablas
    if (!$sqlout1 || count($sqlout1) == 0) {
        // Mapear grupo LDAP a rol interno
        $rol_ldap = isset($rol_ldap) ? $rol_ldap : 'Estudiante'; // Valor por defecto si no se obtuvo del LDAP
        $rol_interno = mapearGrupoALRol($rol_ldap);

        // Insertar en sis_login con pass en md5
        $pass_md5 = md5($pass);
        $sql_insert_login = "INSERT INTO sis_login (id, pass, id_roll) VALUES ('" . $user . "', '" . $pass_md5 . "', '" . $rol_interno . "');";
        if (transaccion($sql_insert_login) === false) {
            sendError(7); // Código: error en base de datos
        }
        // Insertar en sis_user con todos los campos
        $sql_insert_user = "INSERT INTO sis_user (id, nombre, email, telefono, id_tipo_tel) VALUES ('" . $user . "', '" . $user_name . "', '" . $user_email . "', '" . $user_tel . "', 'M');";
        if (transaccion($sql_insert_user) === false) {
            sendError(7); // Código: error en base de datos
        }
        $id_roll = $rol_interno;
        $nombre_final = $user_name;

        // --- Informe de sincronización: registrar usuario nuevo sincronizado ---
        $sync_log = __DIR__ . '/ldap_sync.log';
        $sync_msg = date('Y-m-d H:i:s') . " | Nuevo usuario sincronizado | ID: $user | Nombre: $user_name | Email: $user_email | Rol: $rol_ldap\n";
        file_put_contents($sync_log, $sync_msg, FILE_APPEND);
    } else {
        // Ya existe, usar datos existentes
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

    // === LÓGICA ESPECIAL PARA ESTUDIANTES LDAP (ROL 4) ===
    if ($id_roll == 4) {
        // Verificar si el estudiante ya tiene una propuesta TFG
        $tfg_check_sql = "SELECT COUNT(*) as tfg_count FROM tfg_proposals WHERE user_id = '" . $user . "'";
        $tfg_result = seleccion($tfg_check_sql);
        
        if ($tfg_result !== false && $tfg_result[0]['tfg_count'] == 0) {
            // Estudiante SIN propuesta TFG - Redirigir al formulario
            echo "estudiante_sin_tfg";
            exit();
        } else {
            // Estudiante CON propuesta - Redirigir a panel estudiante
            echo "estudiante_con_tfg";
            exit();
        }
    }

    echo 0; // Login LDAP exitoso
}
else{
    // Si falla el login, enviar error
    sendError($out);
}

// echo $out; / 0 todo bien / 1 contraseña erronea / 2 usuario no existe /3 problema con LDAP /4 cuenta deshabilitada /5 no pertenece al grupo autorizado / 6 datos de entrada inválidos /7 error en base de datos
?>