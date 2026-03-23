
<?php
// CORS headers para permitir peticiones desde otros orígenes
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include("../../login/check.php");
include("../../../functions.php");
include("../../../inc/db/db.php");

$user_rol = (int)$mySessionController->getVar("rol");
if (!check_permiso($mod3, $act3, $user_rol)) {
    http_response_code(403);
    echo 2;
    exit();
}

$id = isset($_GET['id']) ? trim($_GET['id']) : '';
$nombre = isset($_GET['nombre']) ? trim($_GET['nombre']) : '';
$email = isset($_GET['email']) ? trim($_GET['email']) : '';
$telefono = isset($_GET['telefono']) ? trim($_GET['telefono']) : '';
$id_tipo_tel = isset($_GET['id_tipo_tel']) ? trim($_GET['id_tipo_tel']) : '';
$id_roll = isset($_GET['id_roll']) ? (int)$_GET['id_roll'] : 0;
$pass = isset($_GET['pass']) ? (string)$_GET['pass'] : "";

if ($id === '' || $nombre === '' || $email === '' || $telefono === '' || $id_tipo_tel === '' || $id_roll <= 0) {
    echo 2;
    exit();
}

$id = mysqli_real_escape_string($id_con, $id);
$nombre = mysqli_real_escape_string($id_con, $nombre);
$email = mysqli_real_escape_string($id_con, $email);
$telefono = mysqli_real_escape_string($id_con, $telefono);
$id_tipo_tel = mysqli_real_escape_string($id_con, $id_tipo_tel);
$sql_a = "CALL insert_user('$id','$nombre','$email','$telefono','$id_tipo_tel',$id_roll,'" . md5($pass) . "',@res);";
$sql_b = "SELECT @res as res;";
//echo $sql_a.$sql_b;
$res = transaccion_verificada($sql_a, $sql_b);

// ************** Registro en bitacora  ************** //
if ($res[0]['res'] == 0) {
    $user = $mySessionController->getVar("usuario");
    $sql_log = "CALL insert_log('$user','$sql_a',@res);";
    $res_log = transaccion($sql_log);
}
// ************** Resgistro en bitacora ************** //

echo $res[0]['res'];
?>