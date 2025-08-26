<?php
/**
 * Realiza las transacciones a la base de datos por medio de funciones
 */
include("../../login/check.php");
include("../../../inc/db/db.php");

$id_roll = $_GET['id_roll'];
$sql = "CALL delete_roll(" . $id_roll . ");";
//echo $sql; //DEBUG
$res = transaccion($sql);
if ($res[0] == 1) {
    echo "OK";
} else {
    echo "KO";
}
?>
