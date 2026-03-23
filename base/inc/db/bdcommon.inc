<?php
$db_host = getenv('DB_HOST') ?: 'localhost';
$usuario = getenv('DB_USER') ?: 'root';
$clave   = getenv('DB_PASS') ?: '';
$db      = getenv('DB_NAME') ?: 'base_db';
$DBMS = 'mysql';

// Ruta base para redirecciones (ajusta según tu estructura)
$base_url = '/base/';
?>
