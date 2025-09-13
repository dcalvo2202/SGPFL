<?php
/////////////////////// Configuracion del sistema ///////////////////////

/* ********* Servidor
 * $cds_domain
 *  Guarda el nombre del dominio; por ejemplo: "localhost", "www.mydominio.com"
 * $cds_locate
 *  Guarda la localizacion del sistema si esta en un subdominio del sitio;
 *  por ejemplo: "/sistema/", "/lib/sistema/"
 */

$cds_domain = "http://localhost";
$cds_locate = "/base/"; 

$ldap_status = 1;
$ldap_server[0] = "10.0.2.53";
$ldap_dn = "dc=una,dc=ac,dc=cr";


/* ********* Preferencias
 * $page_cant
 *  Guarda la cantidad de elementos a mostrar en la paginacion de listas
 *  en los diferentes modulos
 */

$page_cant = 10;

$page_title = "BASE";

$footer_title= "Sistema BASE para la creacion de otros sistemas<br/>USTDS";

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
