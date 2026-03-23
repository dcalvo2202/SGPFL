
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
include("../../../functions.php");
include("../../../inc/db/db.php");

$user_rol = (int)$mySessionController->getVar("rol");
if (!check_permiso($mod2, $act5, $user_rol)) {
    http_response_code(403);
    echo "KO";
    exit();
}

$id_roll = isset($_GET['id_roll']) ? (int)$_GET['id_roll'] : 0;
if ($id_roll <= 0) {
    echo "KO";
    exit();
}

$sql = "CALL delete_roll(" . $id_roll . ");";
//echo $sql; //DEBUG
$res = transaccion($sql);
if ($res[0] == 1) {
    echo "OK";
} else {
    echo "KO";
}
?>
