
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
include(dirname(__FILE__) . "/../../../lib/AuthLdap/class.AuthLdap.php");
include("../../../config.inc");

$user_rol = (int)$mySessionController->getVar("rol");
if (!check_permiso($mod3, $act1, $user_rol)) {
    http_response_code(403);
    echo "";
    exit();
}

$id = isset($_GET['id']) ? trim($_GET['id']) : '';
if ($id === '') {
    echo "";
    exit();
}

$ldap = new AuthLdap();
$ldap->server = $ldap_server;
$ldap->dn = $ldap_dn; // Base DN of our organisation
if ($ldap->connect()) {
    if ($attrib = $ldap->getAttribute($id, "cn")) {
        echo $attrib[0];
    }
}
?>