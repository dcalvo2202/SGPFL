<?php
// test_db_connection.php
include 'inc/db/bdcommon.inc';

$conn = new mysqli($db_host, $usuario, $clave, $db);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
echo "¡Conexión exitosa!";
$conn->close();
?>