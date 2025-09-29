<?php

include(dirname(__FILE__) . "/../../lib/mysession/mySession.class.php");
include(dirname(__FILE__) . "/../../lib/mysession/mySession.conf.php");

$mySessionController = mySession::getIstance($_MYSESSION_CONF);
$t1 = $mySessionController->getVar("cds_domain");
$t2 = $mySessionController->getVar("cds_locate");
if (isset($t1) && isset($t2)) {
    $domain = $t1;
    $locate = $t2;
} else {
    require_once dirname(__FILE__) . '/../../config.inc';
    $domain = $cds_domain;
    $locate = $cds_locate;
};
$location = $domain . $locate;
$mySessionController->delete("SessionArray");
$mySessionController->destroy($_MYSESSION_CONF['SID']);
header('Location:' . $location);
?>