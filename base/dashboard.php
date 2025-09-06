<?php
    session_start();
    $rol = $_SESSION['rol'];

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
<!DOCTYPE html>
<html>

</html>