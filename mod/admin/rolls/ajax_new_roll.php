
<?php
// CORS headers para permitir peticiones desde otros orígenes
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
/**
 * Realiza las transacciones a la base de datos por medio de funciones
 */
include("../../login/check.php");
include("../../../inc/db/db.php");

$roll_name = $_GET['roll_name'];
$roll_desc = $_GET['roll_desc'];
$permisos = $_GET['permisos'];

$sql1 = "SELECT insert_roll('" . $roll_name . "','" . $roll_desc . "') as res;";
//echo $sql1; //DEBUG
$res_id = seleccion($sql1);
$id_roll = $res_id[0]['res'];
$sql = "CALL insert_roll_permits(" . $id_roll . ",'" . $permisos . "');";
//echo $sql; //DEBUG
$res = transaccion($sql);
if ($res[0] == 1) {
    echo "OK";
} else {
    echo "KO";
}
?>
