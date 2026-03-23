
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
include("../../../inc/db/db.php");
$id = isset($_GET['id']) ? trim($_GET['id']) : '';
$email = isset($_GET['email']) ? trim($_GET['email']) : '';
$telefono = isset($_GET['telefono']) ? trim($_GET['telefono']) : '';
$id_tipo_tel = isset($_GET['id_tipo_tel']) ? trim($_GET['id_tipo_tel']) : '';
$pass = isset($_GET['pass']) ? (string)$_GET['pass'] : "";

$session_user = (string)$mySessionController->getVar("usuario");
if ($id === '' || $id !== $session_user || $email === '' || $telefono === '' || $id_tipo_tel === '') {
    http_response_code(403);
    echo 2;
    exit();
}

$id = mysqli_real_escape_string($id_con, $id);
$email = mysqli_real_escape_string($id_con, $email);
$telefono = mysqli_real_escape_string($id_con, $telefono);
$id_tipo_tel = mysqli_real_escape_string($id_con, $id_tipo_tel);
$sql_a = "CALL update_perfil('$id','$email','$telefono','$id_tipo_tel','" . md5($pass) . "',@res);";
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