<?php
// Script temporal para limpiar la sesión y forzar nuevo login
session_start();

// Guardar el nombre de la sesión para eliminar cookies
$session_name = session_name();
$session_id = session_id();

// Limpiar todas las variables de sesión
$_SESSION = array();

// Eliminar cookie de sesión
if (isset($_COOKIE[$session_name])) {
    setcookie($session_name, '', time() - 3600, '/');
    setcookie($session_name, '', time() - 3600, '/SGPFL/');
    setcookie($session_name, '', time() - 3600, '/SGPFL/Sistema-Gestor-de-Proyectos-Finales-de-Licenciatura/');
    setcookie($session_name, '', time() - 3600, '/SGPFL/Sistema-Gestor-de-Proyectos-Finales-de-Licenciatura/base/');
}

// Destruir la sesión
session_unset();
session_destroy();

// Limpiar también PHPSESSID si existe
if (isset($_COOKIE['PHPSESSID'])) {
    setcookie('PHPSESSID', '', time() - 3600, '/');
    setcookie('PHPSESSID', '', time() - 3600, '/SGPFL/');
    setcookie('PHPSESSID', '', time() - 3600, '/SGPFL/Sistema-Gestor-de-Proyectos-Finales-de-Licenciatura/');
    setcookie('PHPSESSID', '', time() - 3600, '/SGPFL/Sistema-Gestor-de-Proyectos-Finales-de-Licenciatura/base/');
}

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Sesión Limpiada</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #034991, #c8102e);
        }
        .card {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 500px;
        }
        .success-icon {
            color: #28a745;
            font-size: 60px;
            margin-bottom: 20px;
        }
        h1 {
            color: #034991;
            margin-bottom: 20px;
        }
        .info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            font-size: 14px;
            text-align: left;
        }
        .btn {
            background: #034991;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin-top: 20px;
            font-weight: bold;
        }
        .btn:hover {
            background: #c8102e;
        }
    </style>
</head>
<body>
    <div class='card'>
        <div class='success-icon'>✓</div>
        <h1>Sesión Completamente Limpiada</h1>
        <p>Tu sesión y cookies han sido eliminadas.</p>
        <div class='info'>
            <strong>Sesión eliminada:</strong> $session_id<br>
            <strong>Cookie eliminada:</strong> $session_name<br><br>
            <strong>⚠️ IMPORTANTE:</strong> Cierra completamente tu navegador antes de volver a hacer login.
        </div>
        <a href='login.php' class='btn'>Ir al Login</a>
    </div>
</body>
</html>";
?>
