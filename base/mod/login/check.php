<?php
include(dirname(__FILE__) . "/../../lib/mysession/mySession.class.php");
include(dirname(__FILE__) . "/../../lib/mysession/mySession.conf.php");
$mySessionController = mySession::getIstance($_MYSESSION_CONF);
$session_usuario = $mySessionController->getVar("usuario");

if ($session_usuario == "") {
    require_once dirname(__FILE__) . '/../../config.inc';
    $mySessionController->destroy($_MYSESSION_CONF['SID']);
    
    // IMPORTANTE: Eliminar físicamente la cookie del navegador
    setcookie($_MYSESSION_CONF['SID'], '', time() - 3600, '/base/', '', false, true);
    
    $base = rtrim($cds_domain, '/') . '/' . trim($cds_locate, '/') . '/';
    $requested = $_SERVER['REQUEST_URI'];
    $loginUrl = $base . 'login.php?return_to=' . urlencode($requested);
    echo "<script language='JavaScript' type='text/javascript'>
            alert('Area Restringida');
            window.location='" . $loginUrl . "';
          </script>";
    exit;
} else {
    return true;
}
?>