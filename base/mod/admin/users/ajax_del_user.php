
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
if (!check_permiso($mod3, $act5, $user_rol)) {
    http_response_code(403);
    echo 2;
    exit();
}

$id_user = isset($_GET['id_user']) ? trim($_GET['id_user']) : '';
if ($id_user === '') {
    echo 2;
    exit();
}

$id_user = mysqli_real_escape_string($id_con, $id_user);
$sql_a = "CALL delete_user('$id_user',@res);";
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