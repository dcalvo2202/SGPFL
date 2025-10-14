<?php
    include(dirname(__FILE__) . "/lib/mysession/mySession.class.php");
    include(dirname(__FILE__) . "/lib/mysession/mySession.conf.php");

    // Redirigir a logout si la configuración de sesión está definida para evitar conflictos
    if (isset($_MYSESSION_CONF)) {
        header("Location: logout.php");
        exit();
    }

    $mySessionController = mySession::getIstance($_MYSESSION_CONF);
    $rol = $mySessionController->getVar("rol");

    // Redirigir al login si no hay rol definido
    if (!$rol) {
        header("Location: login.php");
        exit();
    }

    switch ($rol) {
        case 1: // Administrador
             include 'panel_subdireccion.php';
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