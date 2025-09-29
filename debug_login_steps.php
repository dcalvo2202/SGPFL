<?php
/**
 * Script paso a paso para reproducir exactamente el login
 */

echo "<h1>🔍 Depuración Paso a Paso del Login</h1>";

// Paso 1: Verificar archivos requeridos
echo "<h3>Paso 1: Verificación de archivos</h3>";
$files_to_check = [
    'lib/mysession/mySession.class.php',
    'lib/mysession/mySession.conf.php', 
    'inc/db/db.php',
    'config.inc'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ $file existe</p>";
    } else {
        echo "<p style='color: red;'>❌ $file NO existe</p>";
    }
}

echo "<h3>Paso 2: Incluir archivos</h3>";
try {
    include_once("lib/mysession/mySession.class.php");
    echo "<p style='color: green;'>✅ mySession.class.php incluido</p>";
    
    include_once("lib/mysession/mySession.conf.php");
    echo "<p style='color: green;'>✅ mySession.conf.php incluido</p>";
    
    include_once("inc/db/db.php");
    echo "<p style='color: green;'>✅ db.php incluido</p>";
    
    include_once("config.inc");
    echo "<p style='color: green;'>✅ config.inc incluido</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error incluyendo archivos: " . $e->getMessage() . "</p>";
}

echo "<h3>Paso 3: Verificar credenciales</h3>";
$user = 'estudiante1';
$pass = '123456';

$sql = "SELECT checklogin('" . $user . "','" . md5($pass) . "') as li_out;";
$sqlout = seleccion($sql);
$out = $sqlout[0]['li_out'];

echo "<p><strong>Resultado checklogin():</strong> $out</p>";

if ($out == 0) {
    echo "<p style='color: green;'>✅ Credenciales válidas</p>";
    
    echo "<h3>Paso 4: Crear instancia de sesión</h3>";
    try {
        $mySessionController = mySession::getIstance($_MYSESSION_CONF);
        echo "<p style='color: green;'>✅ Instancia de sesión creada</p>";
        
        echo "<h3>Paso 5: Obtener datos del usuario</h3>";
        $sql1 = "SELECT l.id_roll, u.nombre FROM sis_login l LEFT JOIN sis_user u ON l.id = u.id WHERE l.id='" . $user . "';";
        $sqlout1 = seleccion($sql1);
        
        if ($sqlout1) {
            $id_roll = $sqlout1[0]['id_roll'];
            $nombre_final = $sqlout1[0]['nombre'];
            
            echo "<p><strong>Rol:</strong> $id_roll</p>";
            echo "<p><strong>Nombre:</strong> $nombre_final</p>";
            
            echo "<h3>Paso 6: Guardar variables de sesión</h3>";
            
            $mySessionController->save("usuario", $user);
            echo "<p>usuario guardado: $user</p>";
            
            $mySessionController->save("nombre", $nombre_final);
            echo "<p>nombre guardado: $nombre_final</p>";
            
            $mySessionController->save("rol", $id_roll);
            echo "<p>rol guardado: $id_roll</p>";
            
            // Incluir lang.es
            include_once("lang/lang.es");
            if (isset($vocab)) {
                $mySessionController->save("vocab", $vocab);
                echo "<p>vocab guardado</p>";
            }
            
            $mySessionController->save("cds_domain", $cds_domain);
            $mySessionController->save("cds_locate", $cds_locate);
            
            echo "<p style='color: green;'>✅ Variables de sesión guardadas</p>";
            
            echo "<h3>Paso 7: Verificar variables guardadas</h3>";
            
            $usuario_verificado = $mySessionController->getVar("usuario");
            $nombre_verificado = $mySessionController->getVar("nombre");
            $rol_verificado = $mySessionController->getVar("rol");
            
            echo "<p><strong>usuario verificado:</strong> '$usuario_verificado'</p>";
            echo "<p><strong>nombre verificado:</strong> '$nombre_verificado'</p>";
            echo "<p><strong>rol verificado:</strong> '$rol_verificado'</p>";
            
            if ($usuario_verificado == $user) {
                echo "<p style='color: green;'>✅ <strong>SESIÓN CREADA CORRECTAMENTE</strong></p>";
                echo "<p><strong>El login debería funcionar ahora</strong></p>";
            } else {
                echo "<p style='color: red;'>❌ <strong>ERROR:</strong> La variable 'usuario' no se guardó correctamente</p>";
                echo "<p>Esperado: '$user', Obtenido: '$usuario_verificado'</p>";
            }
            
        } else {
            echo "<p style='color: red;'>❌ No se pudieron obtener datos del usuario</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error con sesión: " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<p style='color: red;'>❌ Credenciales inválidas: $out</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Ir a Login</a> | <a href='check_session_vars.php'>Ver Variables de Sesión</a></p>";
?>