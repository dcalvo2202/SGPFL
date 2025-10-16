<?php
    include(dirname(__FILE__) . "/lib/mysession/mySession.class.php");
    include(dirname(__FILE__) . "/lib/mysession/mySession.conf.php");

    $mySessionController = mySession::getIstance($_MYSESSION_CONF);
    $rol = $mySessionController->getVar("rol");

    // Redirigir al logout si la sesión es invalida 
    //(Sin esto es cuando sale el error: Fatal error: Unable to load session. in C:\xampp\htdocs\base\lib\mysession\mySession.class.php on line 644)
    // Pero si se habilita deja de funcionar el botón de inicio en el encabezado.
    /*if (isset($_MYSESSION_CONF)) {
        header("Location: logout.php");
        exit();
    }
    */

    // Redirigir al login si no hay rol definido
    if (!$rol) {
        header("Location: logout.php");
        exit();
    }

    switch ($rol) {
        case 1: // Administrador
            include 'main.php';
            break;
        case 2: // Gestor Académico
            include 'panel_subdireccion.php';
            break;
        case 3: // CTFG (Comisión TFG)
            include 'panel_ctfg.php';
            break;
        case 4: // Estudiante
            include 'Panel_SubirTFG.php';
            break;
        case 5: // Asesor Externo
            include 'panel_asesor.php';
            break;
        default:
            echo "Rol no reconocido.";
    }
?>