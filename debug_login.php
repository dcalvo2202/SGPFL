<?php
/**
 * Script para diagnosticar problemas de login
 */
include_once("inc/db/db.php");

echo "<h1>🔍 Diagnóstico de Login</h1>";

// Verificar si el POST viene del formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario']) && isset($_POST['clave'])) {
    $usuario = $_POST['usuario'];
    $clave = $_POST['clave'];
    
    echo "<h3>📋 Datos recibidos del formulario:</h3>";
    echo "<p><strong>Usuario:</strong> " . htmlspecialchars($usuario) . "</p>";
    echo "<p><strong>Password:</strong> " . htmlspecialchars($clave) . "</p>";
    echo "<p><strong>Password MD5:</strong> " . md5($clave) . "</p>";
    
    // Verificar si existe en sis_login
    echo "<h3>🔍 Verificación en base de datos:</h3>";
    
    $sql_check = "SELECT id, pass, id_roll FROM sis_login WHERE id = '" . mysqli_real_escape_string($id_con, $usuario) . "'";
    $result = seleccion($sql_check);
    
    if (empty($result)) {
        echo "<p style='color: red;'>❌ Usuario NO encontrado en sis_login</p>";
        
        // Mostrar usuarios disponibles
        echo "<h4>Usuarios disponibles en sis_login:</h4>";
        $all_users = seleccion("SELECT id, id_roll FROM sis_login ORDER BY id");
        foreach ($all_users as $user) {
            echo "<p>• ID: <strong>" . $user['id'] . "</strong> | Rol: " . $user['id_roll'] . "</p>";
        }
    } else {
        $user_data = $result[0];
        echo "<p style='color: green;'>✅ Usuario encontrado en sis_login</p>";
        echo "<p><strong>ID:</strong> " . $user_data['id'] . "</p>";
        echo "<p><strong>Password en DB:</strong> " . $user_data['pass'] . "</p>";
        echo "<p><strong>Rol:</strong> " . $user_data['id_roll'] . "</p>";
        
        // Verificar password
        $password_hash = md5($clave);
        if ($user_data['pass'] === $password_hash) {
            echo "<p style='color: green;'>✅ Password correcto</p>";
            
            // Verificar en sis_user
            $sql_user = "SELECT * FROM sis_user WHERE id = '" . mysqli_real_escape_string($id_con, $usuario) . "'";
            $user_result = seleccion($sql_user);
            
            if (!empty($user_result)) {
                echo "<p style='color: green;'>✅ Usuario encontrado en sis_user</p>";
                $user_info = $user_result[0];
                echo "<p><strong>Nombre:</strong> " . $user_info['nombre'] . "</p>";
                echo "<p><strong>Email:</strong> " . $user_info['email'] . "</p>";
            } else {
                echo "<p style='color: red;'>❌ Usuario NO encontrado en sis_user</p>";
            }
            
        } else {
            echo "<p style='color: red;'>❌ Password incorrecto</p>";
            echo "<p><strong>Esperado:</strong> " . $password_hash . "</p>";
            echo "<p><strong>En DB:</strong> " . $user_data['pass'] . "</p>";
        }
    }
    
} else {
    // Mostrar formulario de prueba
    echo "<p>Usa este formulario para probar el login:</p>";
    echo "<form method='POST' style='background: #f8f9fa; padding: 20px; border-radius: 5px; max-width: 400px;'>";
    echo "<div style='margin-bottom: 15px;'>";
    echo "<label for='usuario'><strong>Usuario:</strong></label><br>";
    echo "<input type='text' id='usuario' name='usuario' value='estudiante1' style='width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 3px;'>";
    echo "</div>";
    echo "<div style='margin-bottom: 15px;'>";
    echo "<label for='clave'><strong>Password:</strong></label><br>";
    echo "<input type='password' id='clave' name='clave' value='123456' style='width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 3px;'>";
    echo "</div>";
    echo "<button type='submit' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>🔍 Probar Login</button>";
    echo "</form>";
    
    echo "<h3>📊 Estudiantes disponibles para login:</h3>";
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px;'>";
    
    $estudiantes = seleccion("SELECT l.id, l.id_roll, u.nombre FROM sis_login l LEFT JOIN sis_user u ON l.id = u.id WHERE l.id_roll = 4 ORDER BY l.id");
    
    if (!empty($estudiantes)) {
        foreach ($estudiantes as $est) {
            echo "<p><strong>Usuario:</strong> " . $est['id'] . " | <strong>Nombre:</strong> " . ($est['nombre'] ?? 'Sin nombre') . " | <strong>Password:</strong> 123456</p>";
        }
    } else {
        echo "<p>No hay estudiantes registrados.</p>";
    }
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Ir al Login Real</a> | <a href='dashboard.php'>Dashboard</a></p>";

mysqli_close($id_con);
?>