<?php
/**
 * Test específico de login para asesor externo
 * Acceso: http://localhost/base/mod/login/test_login_advisor.php
 */

// Solo localhost
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    die('Acceso denegado');
}

require_once __DIR__ . '/../../config.inc';
require_once __DIR__ . '/../../inc/db/db.php';

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test Login Asesor</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .section { background: #252526; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .warning { color: #dcdcaa; }
        input, button { padding: 8px; margin: 5px; font-size: 14px; }
        button { background: #0e639c; color: white; border: none; cursor: pointer; }
        button:hover { background: #1177bb; }
        pre { background: #1e1e1e; padding: 10px; overflow-x: auto; }
        code { background: #333; padding: 2px 5px; }
    </style>
</head>
<body>

<h1>🔐 Test de Login - Asesor Externo</h1>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo '<div class="section">';
    echo '<h2>Resultado del Test</h2>';
    
    $test_user = trim($_POST['user_id']);
    $test_pass = trim($_POST['password']);
    
    if (empty($test_user) || empty($test_pass)) {
        echo '<p class="error">❌ User ID y contraseña son requeridos</p>';
    } else {
        echo '<p class="warning">📋 Probando login con:</p>';
        echo '<p>User ID: <code>' . htmlspecialchars($test_user) . '</code></p>';
        echo '<p>Contraseña: <code>' . htmlspecialchars($test_pass) . '</code></p>';
        echo '<hr>';
        
        require_once __DIR__ . '/../../inc/db/bdcommon.inc';
        $id_con = mysqli_connect($db_host, $usuario, $clave, $db);
        
        if (!$id_con) {
            echo '<p class="error">❌ Error conectando a BD</p>';
        } else {
            mysqli_set_charset($id_con, "utf8");
            
            // Buscar usuario
            $sql = "SELECT id, pass, id_roll FROM sis_login WHERE id = '" . mysqli_real_escape_string($id_con, $test_user) . "'";
            $result = mysqli_query($id_con, $sql);
            
            if (!$result || mysqli_num_rows($result) === 0) {
                echo '<p class="error">❌ Usuario NO existe en sis_login</p>';
            } else {
                $row = mysqli_fetch_assoc($result);
                $hash_bd = $row['pass'];
                $id_roll = $row['id_roll'];
                
                echo '<p class="success">✅ Usuario encontrado</p>';
                echo '<p>ID: <code>' . htmlspecialchars($row['id']) . '</code></p>';
                echo '<p>Rol: <code>' . $id_roll . '</code></p>';
                echo '<p>Hash en BD (primeros 60 chars): <code>' . htmlspecialchars(substr($hash_bd, 0, 60)) . '</code></p>';
                echo '<p>Longitud del hash: <code>' . strlen($hash_bd) . '</code></p>';
                
                // Detectar tipo de hash
                if (preg_match('/^[a-f0-9]{32}$/', $hash_bd)) {
                    echo '<p class="warning">⚠️ Hash es MD5 (formato antiguo)</p>';
                    
                    // Test MD5
                    $md5_test = md5($test_pass);
                    echo '<p>MD5 de tu contraseña: <code>' . $md5_test . '</code></p>';
                    
                    if ($md5_test === $hash_bd) {
                        echo '<p class="success">✅ ¡ÉXITO! La contraseña coincide con MD5</p>';
                        echo '<p class="warning">⚠️ NOTA: Se recomienda cambiar la contraseña para actualizar a bcrypt</p>';
                    } else {
                        echo '<p class="error">❌ FALLO: La contraseña NO coincide con MD5</p>';
                    }
                } else if (substr($hash_bd, 0, 4) === '$2y$' || substr($hash_bd, 0, 4) === '$2a$' || substr($hash_bd, 0, 4) === '$2b$') {
                    echo '<p class="success">✅ Hash es bcrypt (formato seguro)</p>';
                    
                    // Test password_verify
                    if (password_verify($test_pass, $hash_bd)) {
                        echo '<p class="success">✅ ¡ÉXITO! La contraseña es CORRECTA con password_verify()</p>';
                        echo '<hr>';
                        echo '<h3>🎉 Login exitoso</h3>';
                        echo '<p>Puedes usar esta contraseña en el sistema de login normal</p>';
                    } else {
                        echo '<p class="error">❌ FALLO: La contraseña es INCORRECTA</p>';
                        echo '<hr>';
                        echo '<h3>Posibles razones:</h3>';
                        echo '<ul>';
                        echo '<li>La contraseña ingresada no es la correcta</li>';
                        echo '<li>Estás probando con un usuario diferente</li>';
                        echo '<li>El cambio de contraseña no se completó</li>';
                        echo '</ul>';
                        
                        // Verificar si hay un token activo para este usuario
                        $token_check = mysqli_query($id_con, "SELECT token, expires_at, used FROM password_recovery_tokens WHERE user_id = '" . mysqli_real_escape_string($id_con, $test_user) . "' ORDER BY created_at DESC LIMIT 1");
                        if ($token_check && mysqli_num_rows($token_check) > 0) {
                            $token_row = mysqli_fetch_assoc($token_check);
                            echo '<hr>';
                            echo '<h3>Token de recuperación:</h3>';
                            echo '<p>Usado: ' . ($token_row['used'] ? 'SÍ' : 'NO') . '</p>';
                            echo '<p>Expira: ' . $token_row['expires_at'] . '</p>';
                        }
                    }
                } else {
                    echo '<p class="error">❌ Formato de hash desconocido</p>';
                }
            }
            
            mysqli_close($id_con);
        }
    }
    
    echo '</div>';
}
?>

<div class="section">
    <h2>🧪 Probar Login</h2>
    <form method="POST">
        <div>
            <label>User ID (cédula del asesor):</label><br>
            <input type="text" name="user_id" placeholder="118910482" required>
        </div>
        <div>
            <label>Contraseña:</label><br>
            <input type="password" name="password" placeholder="Nueva contraseña" required>
            <br><small style="color: #888;">Ingresa la contraseña que acabas de establecer</small>
        </div>
        <button type="submit">🔍 Probar Login</button>
    </form>
</div>

<div class="section">
    <h2>📖 Instrucciones</h2>
    <ol>
        <li>Ingresa el <strong>User ID</strong> (cédula) del asesor externo</li>
        <li>Ingresa la <strong>contraseña</strong> que estableciste en el reset</li>
        <li>Haz clic en "Probar Login"</li>
        <li>El sistema te dirá si la combinación es correcta</li>
    </ol>
    <p><strong>Si pasa el test:</strong> La contraseña está correctamente guardada y deberías poder hacer login.</p>
    <p><strong>Si falla el test:</strong> Hay un problema con la contraseña que estás ingresando.</p>
</div>

</body>
</html>
