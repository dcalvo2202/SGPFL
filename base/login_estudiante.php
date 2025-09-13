<?php
<?php
require_once "config.inc";
require_once "lib/AuthLdap/class.AuthLdap.php";

// 1. Obtener datos del formulario
$usuario = $_POST['usuario'] ?? '';
$contrasena = $_POST['contrasena'] ?? '';

// Validar que se recibieron datos
if (empty($usuario) || empty($contrasena)) {
    die("Debe ingresar usuario y contraseña.");
}

// 2. Configuración LDAP desde config.inc
$ldap_server = "ldap://{$ldap_server[0]}:389";
$ldap_dn = $ldap_dn;
$serverType = ""; // Cambia a "ActiveDirectory" si usas AD
$domain = "";     // Solo si usas AD
$searchUser = ""; // Solo si tu LDAP requiere usuario de búsqueda
$searchPassword = "";

// 3. Instanciar y conectar
$ldap = new AuthLdap($ldap_server, $ldap_dn, $serverType, $domain, $searchUser, $searchPassword);
$ldap->people = "People";   // Ajusta si tu estructura LDAP es diferente
$ldap->groups = "Groups";   // Ajusta si tu estructura LDAP es diferente

if (!$ldap->connect()) {
    die("No se pudo conectar al servidor LDAP");
}

// 4. Autenticación
if (!$ldap->checkPass($usuario, $contrasena)) {
    die("Credenciales incorrectas");
}

// 5. Validar userAccountControl (solo si es Active Directory)
if ($serverType === "ActiveDirectory") {
    $uac = $ldap->getAttribute($usuario, 'userAccountControl');
    if ($uac && isset($uac[0]) && ($uac[0] & 2)) {
        die("Cuenta deshabilitada");
    }
}

// 6. Verificar grupo autorizado (por ejemplo, "Estudiantes")
if (!$ldap->checkGroup($usuario, "Estudiantes")) {
    die("No pertenece al grupo autorizado");
}

// 7. Aquí puedes continuar con el registro o login en tu sistema local
echo "Autenticación y validación exitosa. Usuario autorizado.";

// Ejemplo: aquí podrías crear la sesión, importar usuario a la base de datos, etc.

?>