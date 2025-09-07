<?php
    include(dirname(__FILE__) . "/lib/mysession/mySession.class.php");
    include(dirname(__FILE__) . "/lib/mysession/mySession.conf.php");

    $mySessionController = mySession::getIstance($_MYSESSION_CONF);
    $rol = $mySessionController->getVar("rol");

    if (!$rol) {
        header("Location: login.php");
        exit();
    }

    switch ($rol) {
        case 1: // Estudiante
            include 'panel_estudiante.php';
            break;
        case 2: // CTFG
            include 'panel_ctfg.php';
            break;
        case 3: // Subdirección
            include 'panel_subdireccion.php';
            break;
        case 4: // Asesor Externo
            include 'panel_asesor.php';
            break;
        default:
            echo "Rol no reconocido.";
    }
?>