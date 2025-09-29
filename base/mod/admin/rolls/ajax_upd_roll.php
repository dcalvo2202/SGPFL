<?php
/**
 * Realiza las transacciones a la base de datos por medio de funciones
 */
include("../../login/check.php");
include("../../../inc/db/db.php");

$id_roll = $_GET['id_roll'];
$roll_name = $_GET['roll_name'];
$roll_desc = $_GET['roll_desc'];
$permisos = $_GET['permisos'];

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