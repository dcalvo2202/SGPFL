<?php
/**
 * Realiza las transacciones a la base de datos por medio de funciones
 */
include("../../login/check.php");
include("../../../functions.php");
include("../../../inc/db/db.php");

$user_rol = (int)$mySessionController->getVar("rol");
if (!check_permiso($mod2, $act4, $user_rol)) {
    http_response_code(403);
    echo "KO";
    exit();
}

$id_roll = isset($_GET['id_roll']) ? (int)$_GET['id_roll'] : 0;
$roll_name = isset($_GET['roll_name']) ? trim($_GET['roll_name']) : '';
$roll_desc = isset($_GET['roll_desc']) ? trim($_GET['roll_desc']) : '';
$permisos = isset($_GET['permisos']) ? trim($_GET['permisos']) : '';

if ($id_roll <= 0 || $roll_name === '') {
    echo "KO";
    exit();
}

$roll_name = mysqli_real_escape_string($id_con, $roll_name);
$roll_desc = mysqli_real_escape_string($id_con, $roll_desc);
$permisos = preg_replace('/[^0-9ma]/', '', $permisos);

$sql = "CALL update_roll(" . $id_roll . ",'" . $roll_name . "','" . $roll_desc . "',@respuesta);";
$res = transaccion($sql);
if ($res[0] == 1) {
    $sql = "CALL delete_roll_permits(" . $id_roll . ");";
    $res = transaccion($sql);
    $sql = "CALL insert_roll_permits(" . $id_roll . ",'" . $permisos . "');";
    $res = transaccion($sql);
    if ($res[0] == 1) {
        echo "OK";
    } else {
        echo "KO";
    }
} else {
    echo "KO";
}
?>