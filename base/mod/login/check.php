<?php

include(dirname(__FILE__) . "/../../lib/mysession/mySession.class.php");
include(dirname(__FILE__) . "/../../lib/mysession/mySession.conf.php");
$mySessionController = mySession::getIstance($_MYSESSION_CONF);
$usuario = $mySessionController->getVar("usuario");

if ($usuario == "") {
    require_once dirname(__FILE__) . '/../../config.inc';
    $mySessionController->destroy($_MYSESSION_CONF['SID']);
    $location = $cds_domain . $cds_locate;
    echo "<script language='JavaScript' type='text/javascript'>  
                alert('Area Restringida');
                window.location='" . $location . "'; 
          </script>";
} else {
    return true;
}
?>