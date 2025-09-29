<?php
/**
 * Script para agregar estudiantes de prueba al sistema
 * Para poder probar login como estudiante
 */
include_once("inc/db/db.php");
include_once("functions.php");

echo "<h1>🎓 Agregar Estudiantes de Prueba</h1>";
echo "<p>Este script agregará estudiantes que puedes usar para hacer login y probar el sistema.</p>";

// Estudiantes de prueba a agregar
$estudiantes_prueba = [
    [
        'id' => 'estudiante1',
        'nombre' => 'Ana María Rodríguez',
        'email' => 'ana.rodriguez@est.una.ac.cr',
        'password' => '123456'
    ],
    [
        'id' => 'estudiante2', 
        'nombre' => 'Carlos José Méndez',
        'email' => 'carlos.mendez@est.una.ac.cr',
        'password' => '123456'
    ],
    [
        'id' => 'estudiante3',
        'nombre' => 'María Elena Castro',
        'email' => 'maria.castro@est.una.ac.cr', 
        'password' => '123456'
    ],
    [
        'id' => '112170040',
        'nombre' => 'José Luis Vargas',
        'email' => 'jose.vargas@est.una.ac.cr',
        'password' => '123456'
    ],
    [
        'id' => '112170041',
        'nombre' => 'Sofía Fernández',
        'email' => 'sofia.fernandez@est.una.ac.cr',
        'password' => '123456'
    ]
];

echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>👤 Estudiantes que se agregarán:</h3>";
foreach ($estudiantes_prueba as $est) {
    echo "<p><strong>ID:</strong> " . $est['id'] . " | <strong>Nombre:</strong> " . $est['nombre'] . " | <strong>Password:</strong> " . $est['password'] . "</p>";
}
echo "</div>";

$agregados = 0;
$errores = [];
$ya_existen = [];

foreach ($estudiantes_prueba as $estudiante) {
    try {
        // Verificar si ya existe en sis_login (tabla principal)
        $sql_check = "SELECT id FROM sis_login WHERE id = '" . mysqli_real_escape_string($id_con, $estudiante['id']) . "'";
        $result_check = seleccion($sql_check);
        
        if (!empty($result_check)) {
            $ya_existen[] = $estudiante['id'] . ' - ' . $estudiante['nombre'];
            continue;
        }
        
        // PRIMERO: Agregar a sis_login (tabla padre)
        $password_hash = md5($estudiante['password']); // Usando MD5 como el sistema existente
        
        $sql_login = "INSERT INTO sis_login (id, pass, id_roll) VALUES (
            '" . mysqli_real_escape_string($id_con, $estudiante['id']) . "',
            '" . $password_hash . "',
            4
        )";
        
        $result_login = mysqli_query($id_con, $sql_login);
        
        if (!$result_login) {
            throw new Exception("Error al insertar login: " . mysqli_error($id_con));
        }
        
        // SEGUNDO: Agregar a sis_user (tabla hija que depende de sis_login)
        $sql_user = "INSERT INTO sis_user (id, nombre, email) VALUES (
            '" . mysqli_real_escape_string($id_con, $estudiante['id']) . "',
            '" . mysqli_real_escape_string($id_con, $estudiante['nombre']) . "',
            '" . mysqli_real_escape_string($id_con, $estudiante['email']) . "'
        )";
        
        $result_user = mysqli_query($id_con, $sql_user);
        
        if (!$result_user) {
            // Si falla el usuario, eliminar el login ya creado
            $sql_delete = "DELETE FROM sis_login WHERE id = '" . mysqli_real_escape_string($id_con, $estudiante['id']) . "'";
            mysqli_query($id_con, $sql_delete);
            throw new Exception("Error al insertar usuario: " . mysqli_error($id_con));
        }
        
        $agregados++;
        echo "<p style='color: green;'>✅ <strong>" . $estudiante['nombre'] . "</strong> agregado correctamente</p>";
        
    } catch (Exception $e) {
        $errores[] = $estudiante['nombre'] . ': ' . $e->getMessage();
        echo "<p style='color: red;'>❌ Error con <strong>" . $estudiante['nombre'] . "</strong>: " . $e->getMessage() . "</p>";
    }
}

echo "<hr>";
echo "<h3>📊 Resumen:</h3>";
echo "<p><strong>Agregados exitosamente:</strong> " . $agregados . "</p>";
echo "<p><strong>Ya existían:</strong> " . count($ya_existen) . "</p>";
echo "<p><strong>Errores:</strong> " . count($errores) . "</p>";

if (!empty($ya_existen)) {
    echo "<div style='background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>⚠️ Estudiantes que ya existían:</h4>";
    foreach ($ya_existen as $existente) {
        echo "<p>• " . $existente . "</p>";
    }
    echo "</div>";
}

// Verificar cuántos estudiantes hay ahora
include_once("inc/student_functions.php");
$total_estudiantes = countStudents();
echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>🎯 Estado Final:</h3>";
echo "<p><strong>Total de estudiantes en el sistema:</strong> " . $total_estudiantes . "</p>";
echo "</div>";

echo "<h3>🚀 Cómo usar estos estudiantes:</h3>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo "<ol>";
echo "<li><strong>Cerrar sesión actual</strong> (logout)</li>";
echo "<li><strong>Hacer login con cualquiera de estos usuarios:</strong></li>";
echo "<ul>";
foreach ($estudiantes_prueba as $est) {
    echo "<li><strong>Usuario:</strong> " . $est['id'] . " | <strong>Password:</strong> " . $est['password'] . "</li>";
}
echo "</ul>";
echo "<li><strong>Probar funcionalidades de estudiante:</strong></li>";
echo "<ul>";
echo "<li>Panel_SubirTFG.php - Subir propuesta de TFG</li>";
echo "<li>Crear grupos de proyecto (desde admin)</li>";
echo "<li>Ver proyectos existentes</li>";
echo "</ul>";
echo "</ol>";
echo "</div>";

echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>💡 Recomendación:</h3>";
echo "<p><strong>Usa 'estudiante1' con password '123456' para hacer login como estudiante.</strong></p>";
echo "<p>Este usuario podrá acceder a todas las funciones de estudiante y podrás probar:</p>";
echo "<ul>";
echo "<li>✅ Subir propuestas TFG</li>";
echo "<li>✅ Ver su panel de estudiante</li>";
echo "<li>✅ Participar en grupos de proyecto</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<div class='text-center'>";
echo "<a href='mod/login/logout.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>🚪 Cerrar Sesión</a> ";
echo "<a href='index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>🔐 Ir a Login</a> ";
echo "<a href='dashboard.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>🏠 Dashboard</a>";
echo "</div>";

mysqli_close($id_con);
?>
