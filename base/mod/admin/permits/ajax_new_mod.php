<?php
/**
 * Realiza las transacciones a la base de datos por medio de funciones
 */
include("../../login/check.php");
include("../../../inc/db/db.php");

$name_mod = $_GET['name_mod'];
$desc_mod = $_GET['desc_mod'];

$sql = "CALL insert_mod('" . $name_mod . "','" . $desc_mod . "',@respuesta);";
//echo $sql; //DEBUG
$res = transaccion($sql);
if ($res[0] == 1) {
    echo "OK";
} else {
    echo "KO";
}
?>
