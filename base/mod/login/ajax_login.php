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
include(dirname(__FILE__) . "/../../lang/lang.es");

// =============================
// FUNCIONES AUXILIARES (INICIALES)
// =============================

// Funciones para obtener información del cliente (se necesitan temprano para logging)
function getClientIpAddress() {
    $keys = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];

    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ipList = explode(',', $_SERVER[$key]);
            $ip = trim($ipList[0]);
            if ($ip !== '') {
                return substr($ip, 0, 45);
            }
        }
    }

    return '';
}

function getClientDeviceInfo() {
    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? trim($_SERVER['HTTP_USER_AGENT']) : '';
    return substr($ua, 0, 255);
}

// Capturar IP del cliente temprano
$ipAddress = getClientIpAddress();
$deviceInfo = getClientDeviceInfo();

// Manejar excepción de conexión a la base de datos
try {
    include(dirname(__FILE__) . "/../../inc/db/db.php");
} catch (Throwable $e) {
    // Registrar intento fallido por error de BD
    $detail = "Fallo de inicio de sesion: Error de conexion a base de datos. Excepcion: " . substr($e->getMessage(), 0, 200);
    // Aquí no podemos registrar en BD, pero registramos en log local
    error_log("[" . date('Y-m-d H:i:s') . "] LOGIN_DB_ERROR | IP: $ipAddress | Mensaje: " . $detail);
    echo 7;
    exit();
}
include(dirname(__FILE__) . "/../../config.inc");

define('LOGIN_FAILED_ATTEMPT_WINDOW_MINUTES', 15);
define('LOGIN_FAILED_ATTEMPT_THRESHOLD', 5);

// =============================
// FUNCIONES AUXILIARES
// =============================

// Función para verificar si un estudiante tiene propuesta TFG o es miembro de grupo
function verificarEstudiante($user){
        // Primero verificar si es miembro de algún proyecto grupal (aunque no sea el propietario)
        $group_check_sql = "SELECT pm.project_id, pm.role, tp.title, tp.user_id as owner_id, 
                                   tp.status as proposal_status, u.nombre as owner_name
                            FROM project_members pm
                            INNER JOIN registered_projects rp ON pm.project_id = rp.id
                            INNER JOIN tfg_proposals tp ON rp.tfg_proposal_id = tp.id
                            LEFT JOIN sis_user u ON tp.user_id = u.id
                            WHERE pm.user_id = '" . $user . "' AND pm.status = 'Activo'
                            ORDER BY pm.joined_at DESC
                            LIMIT 1";
        $group_result = seleccion($group_check_sql);
        
        if ($group_result !== false && count($group_result) > 0) {
            // Es miembro de un grupo - redirigir directamente al panel
            echo "estudiante_con_tfg";
            exit();
        }
        
        // Verificar si el estudiante tiene una propuesta TFG propia
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

function registrarAuditoriaAcceso($user, $actionType, $actionResult, $ipAddress, $deviceInfo, $detail) {
    $sql = "INSERT INTO sis_log (id_user, date_bi, action_type, action_result, ip_address, device_info, detail)
            VALUES (?, NOW(), ?, ?, ?, ?, ?)";

    $result = ejecutar_query($sql, [
        $user,
        $actionType,
        $actionResult,
        $ipAddress,
        $deviceInfo,
        $detail
    ]);

    if (isset($result['success']) && $result['success'] === false) {
        // Fallback para ambientes donde id_user tenga restricciones (FK/NOT NULL)
        // en intentos fallidos con usuario inexistente/no sincronizado.
        $fallbackDetail = $detail;
        if (!empty($user)) {
            $fallbackDetail .= " | attempted_user=" . $user;
        }

        $fallback = ejecutar_query($sql, [
            null,
            $actionType,
            $actionResult,
            $ipAddress,
            $deviceInfo,
            $fallbackDetail
        ]);

        if (isset($fallback['success']) && $fallback['success'] === false) {
            error_log("[" . date('Y-m-d H:i:s') . "] LOGIN_AUDIT_INSERT_ERROR | user=" . $user . " | action=" . $actionType . " | result=" . $actionResult . " | db_error=" . $fallback['error']);
        }

        return $fallback;
    }

    return $result;
}

function getFailedAttemptsCount($user, $ipAddress, $windowMinutes = LOGIN_FAILED_ATTEMPT_WINDOW_MINUTES) {
    $cutoff = date('Y-m-d H:i:s', time() - ($windowMinutes * 60));
    $sql = "SELECT COUNT(*) AS total
            FROM sis_log
            WHERE action_type = 'LOGIN'
              AND action_result = 'FAIL'
              AND date_bi >= ?
              AND (
                    (id_user = ?)
                    OR (ip_address = ?)
              )";

    $rows = seleccion_segura($sql, [$cutoff, $user, $ipAddress]);
    if (!$rows || !isset($rows[0]['total'])) {
        return 0;
    }

    return (int)$rows[0]['total'];
}

function registrarAlertaIntentosFallidos($user, $ipAddress, $deviceInfo, $failedAttempts) {
    $detail = "Alerta de seguridad: multiples intentos fallidos de acceso detectados. " .
              "Usuario: {$user}. IP: {$ipAddress}. Intentos en ventana de " .
              LOGIN_FAILED_ATTEMPT_WINDOW_MINUTES . " minutos: {$failedAttempts}.";

    registrarAuditoriaAcceso($user, 'SECURITY_ALERT', 'ALERT', $ipAddress, $deviceInfo, $detail);
}

function shouldRaiseFailedLoginAlert($user, $ipAddress, $deviceInfo) {
    $failedAttempts = getFailedAttemptsCount($user, $ipAddress, LOGIN_FAILED_ATTEMPT_WINDOW_MINUTES);
    if ($failedAttempts >= LOGIN_FAILED_ATTEMPT_THRESHOLD) {
        registrarAlertaIntentosFallidos($user, $ipAddress, $deviceInfo, $failedAttempts);
        return true;
    }

    return false;
}

// Función para crear sesión y guardar variables luego de login exitoso
function finalizarLoginExitoso($mySessionController, $user, $id_roll, $nombre_final, $cds_domain, $cds_locate, $page_cant, $page_title, $footer_title, $vocab, $authMethod, $ipAddress, $deviceInfo) {
    $mySessionController->save("usuario", $user);
    $mySessionController->save("nombre", $nombre_final);
    $mySessionController->save("rol", $id_roll);
    $mySessionController->save("cds_domain", $cds_domain);
    $mySessionController->save("cds_locate", $cds_locate);
    $mySessionController->save("page_cant", $page_cant);
    $mySessionController->save("page_title", $page_title);
    $mySessionController->save("footer_title", $footer_title);
    $mySessionController->save('vocab', $vocab);

    // IMPORTANTE: Asegurar que el nuevo ID de sesión se envíe al cliente
    session_write_close(); // Fuerza escritura de sesión en BD

    $detail = "Inicio de sesion exitoso por {$authMethod}. Rol interno: {$id_roll}.";
    registrarAuditoriaAcceso($user, 'LOGIN', 'SUCCESS', $ipAddress, $deviceInfo, $detail);

    // === LÓGICA ESPECIAL PARA ESTUDIANTES LDAP (ROL 4) ===
    if ($id_roll == 4) {
        // Verificar si el estudiante ya tiene una propuesta TFG
        verificarEstudiante($user);
    }

    echo 0; // Login LDAP exitoso
    exit();
}

function regenerarIdSesion($_MYSESSION_CONF) {
    $mySessionController = mySession::getIstance($_MYSESSION_CONF);
    if (method_exists($mySessionController, 'regenerateId')) {
        $mySessionController->regenerateId();
    } elseif (function_exists('session_regenerate_id')) {
        session_regenerate_id(true);
    }
}

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
$ipAddress = getClientIpAddress();
$deviceInfo = getClientDeviceInfo();

if ($user === '' || $pass === '') {
    // Registrar intento fallido por datos inválidos (usando IP si user está vacío)
    $userForLog = !empty($user) ? $user : 'UNKNOWN_' . substr($ipAddress, 0, 15);
    $detail = "Intento de login con datos de entrada inválidos (usuario y/o contraseña vacíos).";
    registrarAuditoriaAcceso($userForLog, 'LOGIN', 'FAIL', $ipAddress, $deviceInfo, $detail);
    
    sendError(6); // Código: datos de entrada inválidos
}
$out = "";
$user_name = "";
$authMethod = 'LOCAL';

// =============================
// VERIFICACIÓN DE SESIÓN ACTIVA
// =============================

// Verificar si ya hay sesión activa para este usuario ---
$mySessionController = mySession::getIstance($_MYSESSION_CONF);
$usuario_sesion = $mySessionController->getVar('usuario');
// Si hay sesión activa y el usuario es el mismo, retornar éxito sin reloguear
if (!empty($usuario_sesion) && $usuario_sesion === $user) {
    // Ya hay sesión activa para este usuario, retornar éxito sin reloguear
    registrarAuditoriaAcceso($user, 'LOGIN', 'SUCCESS', $ipAddress, $deviceInfo, 'Login reutilizado: sesion activa ya existente para el usuario.');
    echo 0;
    exit();
}

// Si hay sesión activa y el usuario es diferente, destruir la sesión anterior
if (!empty($usuario_sesion) && $usuario_sesion !== $user) {
    // --- Inicio de la lógica de logout ---

    // Destruir la sesión activa usando el mecanismo de PHP.
    // Esto llamará automáticamente al método 'destroy' de mySession.
    session_destroy();

    // Eliminar la cookie de sesión del navegador.
    if (isset($_COOKIE[$_MYSESSION_CONF['SESSION_VAR_NAME']])) {
        setcookie($_MYSESSION_CONF['SESSION_VAR_NAME'], '', time() - 3600, '/');
        unset($_COOKIE[$_MYSESSION_CONF['SESSION_VAR_NAME']]);
    }

    // Forzar la obtención de una nueva instancia de sesión.
    $reflector = new ReflectionClass('mySession');
    $instanceProperty = $reflector->getProperty('instance');
    $instanceProperty->setAccessible(true);
    $instanceProperty->setValue(null, null);
    $instanceProperty->setAccessible(false);

    $mySessionController = mySession::getIstance($_MYSESSION_CONF);

    // --- Fin de la lógica de logout ---
}

// =============================
// AUTENTICACIÓN LOCAL (BASE DE DATOS)
// =============================

// 1. Verificar si el usuario existe en la base de datos local
// Autenticación local con migración de hash(MD5 a password_hash->Versión actual de hashing)
$sql = "SELECT pass, id_roll FROM sis_login WHERE id = '" . $user . "'";
$sqlout = seleccion($sql);

if (!$sqlout || count($sqlout) == 0) {
    $out = 2; // Usuario no existe en la tabla de usuarios
} else {
    $hash_bd = $sqlout[0]['pass'];
    $id_roll = $sqlout[0]['id_roll'];

    // Detectar si es MD5 (32 caracteres hexadecimales) para migrar a password_hash()
    if (preg_match('/^[a-f0-9]{32}$/', $hash_bd)) {
        // Verificar con MD5
        if (md5($pass) === $hash_bd) {
            // Actualizar a password_hash()
            $new_hash = password_hash($pass, PASSWORD_DEFAULT);
            $sql_update = "UPDATE sis_login SET pass = '$new_hash' WHERE id = '$user'";
            $update1 = ejecutar_query($sql_update);
            if ($update1 === false) {
                $detail = "Fallo de inicio de sesion: Error en actualización de credenciales (migración MD5 a password_hash).";
                registrarAuditoriaAcceso($user, 'LOGIN', 'FAIL', $ipAddress, $deviceInfo, $detail);
                sendError(7); // Código: error en update a la base de datos
            }
            // Login exitoso
            $out = 0;
        } else {
            $out = 1; // Contraseña incorrecta
        }
    } else {
        // Verificar con password_verify()
        if (password_verify($pass, $hash_bd)) {
            // Login exitoso
            $out = 0;
        } else {
            $out = 1; // Contraseña incorrecta
        }
    }
}
if ($out == 0) {
    // Regenerar el ID de sesión para prevenir session fixation
    regenerarIdSesion($_MYSESSION_CONF);

    // Obtener nombre y rol usando JOIN
    $sql1 = "SELECT l.id_roll, u.nombre FROM sis_login l LEFT JOIN sis_user u ON l.id = u.id WHERE l.id='" . $user . "';";
    $sqlout1 = seleccion($sql1);
    if ($sqlout1 === false) {
        $detail = "Fallo de inicio de sesion: Error al recuperar información de usuario/rol de BD (autenticacion LOCAL).";
        registrarAuditoriaAcceso($user, 'LOGIN', 'FAIL', $ipAddress, $deviceInfo, $detail);
        sendError(7); // Código: error en consulta a la base de datos
    }

    $id_roll = $sqlout1[0]['id_roll'];
    $nombre_final = $sqlout1[0]['nombre'];

    // Guardar los datos en sesión y retornar éxito
    finalizarLoginExitoso($mySessionController, $user, $id_roll, $nombre_final, $cds_domain, $cds_locate, $page_cant, $page_title, $footer_title, $vocab, 'LOCAL', $ipAddress, $deviceInfo);

}

// =============================
// AUTENTICACIÓN POR LDAP
// =============================

// 2. Intentar autenticación por LDAP
else if ($ldap_status == 1) {
    $authMethod = 'LDAP';

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
        $detail = "Fallo de inicio de sesion: servidor LDAP no disponible (socket). Host: {$ldap_host}, puerto: {$ldap_port}.";
        registrarAuditoriaAcceso($user, 'LOGIN', 'FAIL', $ipAddress, $deviceInfo, $detail);
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
                            $detail = "Fallo de inicio de sesion: usuario LDAP sin grupos autorizados asociados.";
                            registrarAuditoriaAcceso($user, 'LOGIN', 'FAIL', $ipAddress, $deviceInfo, $detail);
                            if (shouldRaiseFailedLoginAlert($user, $ipAddress, $deviceInfo)) {
                                sendError(8); // Código: alerta por múltiples intentos fallidos
                            }
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
                            $detail = "Fallo de inicio de sesion: usuario LDAP no pertenece a un grupo permitido en sis_rolls.";
                            registrarAuditoriaAcceso($user, 'LOGIN', 'FAIL', $ipAddress, $deviceInfo, $detail);
                            if (shouldRaiseFailedLoginAlert($user, $ipAddress, $deviceInfo)) {
                                sendError(8); // Código: alerta por múltiples intentos fallidos
                            }
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
    regenerarIdSesion($_MYSESSION_CONF);

    // --- Lógica para usuarios LDAP: crear si no existe y mapear rol ---
    // Obtener nombre y rol usando JOIN
    $sql1 = "SELECT l.id_roll, u.nombre FROM sis_login l LEFT JOIN sis_user u ON l.id = u.id WHERE l.id='" . $user . "';";
    $sqlout1 = seleccion($sql1);
    if ($sqlout1 === false) {
        $detail = "Fallo de inicio de sesion: Error al recuperar información de usuario/rol de BD (autenticacion LDAP).";
        registrarAuditoriaAcceso($user, 'LOGIN', 'FAIL', $ipAddress, $deviceInfo, $detail);
        sendError(7); // Código: error en consulta a la base de datos
    }

    // Si no existe, sincronizar el usuario en ambas tablas
    if (!$sqlout1 || count($sqlout1) == 0) {
        // Mapear grupo LDAP a rol interno
        $rol_ldap = isset($rol_ldap) ? $rol_ldap : 'Estudiante'; // Valor por defecto si no se obtuvo del LDAP
        $rol_interno = mapearGrupoALRol($rol_ldap);

        // Insertar en sis_login con pass en md5
        $pass_hash = password_hash($pass, PASSWORD_DEFAULT);
        $sql_insert_login = "INSERT INTO sis_login (id, pass, id_roll) VALUES ('" . $user . "', '" . $pass_hash . "', '" . $rol_interno . "');";
        if (transaccion($sql_insert_login) === false) {
            $detail = "Fallo de inicio de sesion: Error insertando usuario en tabla sis_login (sincronización LDAP)";
            registrarAuditoriaAcceso($user, 'LOGIN', 'FAIL', $ipAddress, $deviceInfo, $detail);
            sendError(7); // Código: error en base de datos
        }
        // Insertar en sis_user con todos los campos
        $sql_insert_user = "INSERT INTO sis_user (id, nombre, email, telefono, id_tipo_tel) VALUES ('" . $user . "', '" . $user_name . "', '" . $user_email . "', '" . $user_tel . "', 'M');";
        if (transaccion($sql_insert_user) === false) {
            $detail = "Fallo de inicio de sesion: Error insertando usuario en tabla sis_user (sincronización LDAP)";
            registrarAuditoriaAcceso($user, 'LOGIN', 'FAIL', $ipAddress, $deviceInfo, $detail);
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

    // Guardar los datos en sesión y retornar éxito
    finalizarLoginExitoso($mySessionController, $user, $id_roll, $nombre_final, $cds_domain, $cds_locate, $page_cant, $page_title, $footer_title, $vocab, $authMethod, $ipAddress, $deviceInfo);
}
else{
    // Generar detalle específico según el código de error
    $errorMessages = [
        1 => "Contraseña incorrecta para usuario existente.",
        2 => "Usuario no existe en sistema local. Intento con usuario: {$user}",
        3 => "Fallo de autenticación: Servidor LDAP no disponible o error de conexión.",
        4 => "Cuenta deshabilitada o no autorizada.",
        5 => "Usuario no pertenece a grupo LDAP autorizado.",
        6 => "Datos de entrada inválidos (usuario y/o contraseña vacíos).",
        7 => "Error de conexión o consulta a base de datos durante autenticación.",
        8 => "Múltiples intentos fallidos detectados - Acceso bloqueado temporalmente."
    ];
    
    $specificDetail = isset($errorMessages[$out]) ? $errorMessages[$out] : "Error desconocido.";
    $detail = "Intento fallido de inicio de sesion. Metodo: {$authMethod}. Razon: {$specificDetail}";
    registrarAuditoriaAcceso($user, 'LOGIN', 'FAIL', $ipAddress, $deviceInfo, $detail);

    if (in_array((int)$out, [1, 2, 5], true) && shouldRaiseFailedLoginAlert($user, $ipAddress, $deviceInfo)) {
        sendError(8); // Código: alerta por múltiples intentos fallidos
    }

    // Si falla el login, enviar error
    sendError($out);
}

// echo $out; / 0 todo bien / 1 contraseña erronea / 2 usuario no existe /3 problema con LDAP /4 cuenta deshabilitada /5 no pertenece al grupo autorizado / 6 datos de entrada inválidos /7 error en base de datos /8 alerta por intentos fallidos
?>