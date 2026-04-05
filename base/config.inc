<?php
/////////////////////// Configuracion del sistema ///////////////////////

/* ********* Servidor
 * $cds_domain
 *  Guarda el nombre del dominio; por ejemplo: "localhost", "www.mydominio.com"
 * $cds_locate
 *  Guarda la localizacion del sistema si esta en un subdominio del sitio;
 *  por ejemplo: "/sistema/", "/lib/sistema/"
 */

// Rutas dinámicas - Funcionan desde cualquier directorio
// Se calculan basado en la ubicación real del archivo
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$cds_domain = $protocol . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/";

// Calcular ruta relativa web basada en la ubicación del archivo
$base_real_path = str_replace('\\', '/', __DIR__); // c:/xampp/htdocs/base
$doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? 'c:/xampp/htdocs'); // c:/xampp/htdocs
$relative_from_root = str_replace($doc_root, '', $base_real_path); // /base
$cds_locate = $relative_from_root . '/'; 

$ldap_status = 1;
$ldap_server[0] = getenv('LDAP_HOST') ?: "ldap://localhost:389";
$ldap_dn = "dc=una,dc=ac,dc=cr";
$ldap_user = "cn=admin,dc=una,dc=ac,dc=cr";
$ldap_pass = "admin";


/* ********* Preferencias
 * $page_cant
 *  Guarda la cantidad de elementos a mostrar en la paginacion de listas
 *  en los diferentes modulos
 */

// Ruta dinámica del favicon - Funciona desde cualquier directorio
$favicon_url = rtrim($cds_locate, '/') . "/img/logo.webp";

$page_cant = 10;

$page_title = "SGPFL";

$footer_title= "Sistema Gestor de Proyectos Finales de Licenciatura<br/>Escuela de Informática, Universidad Nacional";

$act1 = 1; //Ver
$act2 = 2; //Listar
$act3 = 3; //Agregar
$act4 = 4; //Editar
$act5 = 5; //Eliminar
$act6 = 6; //Imprimir


$mod1 = 1; // Acceso
$mod2 = 2; // Busqueda
$mod3 = 3; // Historial y auditoria
$mod4 = 4; // Notificaciones
$mod5 = 5; // Documentacion y versionado
$mod6 = 6; // Gestion de proyectos
$mod7 = 7; // Reportes y paneles
$mod8 = 8; // Gestion academica

/*

Modulos anteriores.

$mod1 = 1; // Administracion
$mod2 = 2; // Modulos
$mod3 = 3; // Usuarios

// Configuración de alertas de vencimiento
define('DEADLINE_THRESHOLDS', [30, 15, 7, 3, 1]); // Días antes del vencimiento
define('DEADLINE_EMAIL_ENABLED', true);
define('DEADLINE_FROM_EMAIL', 'noreply@una.cr');
define('DEADLINE_FROM_NAME', 'SGPFL - Escuela de Informática');



?>