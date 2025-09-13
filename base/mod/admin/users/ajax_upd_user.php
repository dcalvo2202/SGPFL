<?php

include("../../login/check.php");
include("../../../inc/db/db.php");

$id = $_GET['id'];
$nombre = $_GET['nombre'];
$email = $_GET['email'];
$telefono = $_GET['telefono'];
$id_tipo_tel = $_GET['id_tipo_tel'];
$id_roll = $_GET['id_roll'];
$sql_a = "CALL update_user('$id','$nombre','$email','$telefono','$id_tipo_tel',$id_roll,@res);";
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