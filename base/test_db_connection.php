<?php
$host = "localhost";
$user = "root"; // Cambia si tienes otro usuario
$pass = "";     // Cambia si tienes contraseña
$db   = "base"; // Cambia por el nombre real

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
echo "¡Conexión exitosa a la base de datos!";
$conn->close();
?>