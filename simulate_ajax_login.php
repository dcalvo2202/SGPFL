<?php
/**
 * Simulador de llamada AJAX para diagnóstico
 * Exactamente lo mismo que haría el JavaScript
 */

// Simular POST como lo hace el AJAX
$_POST['user'] = 'estudiante1';
$_POST['pass'] = '123456';

echo "<h1>🔍 Simulación de Llamada AJAX</h1>";
echo "<p><strong>Usuario:</strong> " . $_POST['user'] . "</p>";
echo "<p><strong>Password:</strong> " . $_POST['pass'] . "</p>";

echo "<h3>📡 Respuesta del backend:</h3>";
echo "<div style='background: #f8f9fa; padding: 15px; border: 2px solid #007bff; border-radius: 5px;'>";
echo "<pre>";

// Capturar exactamente lo que devuelve ajax_login.php
ob_start();
include("mod/login/ajax_login.php");
$response = ob_get_clean();

// Mostrar la respuesta tal como la recibe JavaScript
echo "RESPUESTA COMPLETA:\n";
echo "'" . $response . "'\n\n";

echo "RESPUESTA DESPUÉS DE TRIM:\n";
echo "'" . trim($response) . "'\n\n";

echo "LONGITUD DE LA RESPUESTA: " . strlen($response) . " caracteres\n";
echo "LONGITUD DESPUÉS DE TRIM: " . strlen(trim($response)) . " caracteres\n\n";

// Análisis caracter por caracter
echo "ANÁLISIS CARACTER POR CARACTER:\n";
for ($i = 0; $i < strlen($response); $i++) {
    $char = $response[$i];
    $ascii = ord($char);
    echo "Posición $i: '$char' (ASCII: $ascii)\n";
}

echo "</pre>";
echo "</div>";

echo "<h3>🎯 Interpretación:</h3>";
$trimmed_response = trim($response);

switch ($trimmed_response) {
    case '0':
        echo "<p style='color: green;'>✅ <strong>CÓDIGO 0:</strong> Login exitoso - debería redireccionar</p>";
        break;
    case '1':
    case '2':
        echo "<p style='color: red;'>❌ <strong>CÓDIGO $trimmed_response:</strong> Usuario no existe</p>";
        break;
    case '3':
        echo "<p style='color: red;'>❌ <strong>CÓDIGO 3:</strong> Error del servidor</p>";
        break;
    case '4':
        echo "<p style='color: red;'>❌ <strong>CÓDIGO 4:</strong> Cuenta deshabilitada</p>";
        break;
    case '5':
        echo "<p style='color: red;'>❌ <strong>CÓDIGO 5:</strong> Acceso denegado</p>";
        break;
    case '6':
        echo "<p style='color: red;'>❌ <strong>CÓDIGO 6:</strong> Datos inválidos</p>";
        break;
    case '7':
        echo "<p style='color: red;'>❌ <strong>CÓDIGO 7:</strong> Error de base de datos</p>";
        break;
    default:
        echo "<p style='color: purple;'>❓ <strong>CÓDIGO DESCONOCIDO:</strong> '" . htmlspecialchars($trimmed_response) . "'</p>";
        echo "<p><strong>ESTO ES LO QUE CAUSA EL 'Error inesperado'</strong></p>";
        
        // Verificar si hay caracteres invisibles
        if (strlen($response) > strlen($trimmed_response)) {
            echo "<p style='color: orange;'>⚠️ <strong>HAY CARACTERES INVISIBLES</strong> en la respuesta (espacios, saltos de línea, etc.)</p>";
        }
        
        // Verificar si es HTML o texto con errores
        if (strpos($response, '<') !== false) {
            echo "<p style='color: red;'>❌ <strong>LA RESPUESTA CONTIENE HTML</strong> - Posible error PHP</p>";
        }
        
        break;
}

echo "<hr>";
echo "<p><strong>💡 Solución:</strong> Basándome en esta respuesta, podré identificar exactamente por qué JavaScript muestra 'Error inesperado'</p>";
?>