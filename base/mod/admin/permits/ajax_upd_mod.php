<?php
/**
 * Realiza las transacciones a la base de datos por medio de funciones
 */
include("../../login/check.php");
include("../../../functions.php");
include("../../../inc/db/db.php");

$user_rol = (int)$mySessionController->getVar("rol");
if (!check_permiso($mod1, $act4, $user_rol)) {
    http_response_code(403);
    echo "KO";
    exit();
}

$id_mod = isset($_GET['id_mod']) ? (int)$_GET['id_mod'] : 0;
$name_mod = isset($_GET['name_mod']) ? trim($_GET['name_mod']) : '';
$desc_mod = isset($_GET['desc_mod']) ? trim($_GET['desc_mod']) : '';

if ($id_mod <= 0 || $name_mod === '') {
    echo "KO";
    exit();
}

$name_mod = mysqli_real_escape_string($id_con, $name_mod);
$desc_mod = mysqli_real_escape_string($id_con, $desc_mod);

$sql = "CALL update_mod(" . $id_mod . ",'" . $name_mod . "','" . $desc_mod . "',@respuesta);";
//echo $sql; //DEBUG
$res = transaccion($sql);
if ($res[0] == 1) {
    echo "OK";
} else {
    echo "KO";
}
?>