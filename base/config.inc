<?php
/////////////////////// Configuracion del sistema ///////////////////////

/* ********* Servidor
 * $cds_domain
 *  Guarda el nombre del dominio; por ejemplo: "localhost", "www.mydominio.com"
 * $cds_locate
 *  Guarda la localizacion del sistema si esta en un subdominio del sitio;
 *  por ejemplo: "/sistema/", "/lib/sistema/"
 */

$cds_domain = "https://localhost";
$cds_locate = "/base/"; 

$ldap_status = 1;
/*$ldap_server[0] = "10.0.2.53";*/
//$ldap_server[0] = "localhost";

$ldap_server[0] = "ldap://localhost:389";
$ldap_dn = "dc=una,dc=ac,dc=cr";


/* ********* Preferencias
 * $page_cant
 *  Guarda la cantidad de elementos a mostrar en la paginacion de listas
 *  en los diferentes modulos
 */

$favicon_url = "img/logo.webp";

$page_cant = 10;

$page_title = "SGPFL";

$footer_title= "Sistema Gestor de Proyectos Finales de Licenciatura<br/>SGPFL";

$act1 = 1; //Ver
$act2 = 2; //Listar
$act3 = 3; //Agregar
$act4 = 4; //Editar
$act5 = 5; //Eliminar
$act6 = 6; //Imprimir

$mod1 = 1; // Administracion
$mod2 = 2; // Modulos
$mod3 = 3; // Usuarios

?>
