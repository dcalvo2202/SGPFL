<?php
session_start();
require_once __DIR__ . '/../../../inc/db/db.php';
require_once __DIR__ . '/tfg_aprobado_upload.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function generarIdentificador(): string {
    return 'UNA-TFG-' . str_pad((string)rand(0,9999), 4, '0', STR_PAD_LEFT) . '-' . date('Y');
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method Not Allowed', 405);
    }

    $nombre        = trim($_POST['nombre'] ?? '');
    $estudiante_id = trim($_POST['estudiante'] ?? '');
    $comite_id     = (int)($_POST['comite'] ?? 0);
    $categoria_id  = (int)($_POST['categoria'] ?? 0);

    if ($nombre === '' || $estudiante_id === '' || $comite_id <= 0 || $categoria_id <= 0 ||
        empty($_FILES['documento']) || $_FILES['documento']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Validación fallida');
    }

    // Tomar el identificador que vio el usuario (desde la sesión)
    $identificador = $_SESSION['identificador_preview'] ?? generarIdentificador();

    $dataBase = [
        'nombre'         => $nombre,
        'estudiante_id'  => $estudiante_id,
        'comite_id'      => $comite_id,
        'categoria_id'   => $categoria_id,
        'aprobado'       => 1,
        'identificador'  => $identificador,
        'fecha_creacion' => date('Y-m-d H:i:s'),
    ];

    // Intentar hasta 3 veces si hay duplicado de identificador
    $ok = false;
    for ($i = 0; $i < 3; $i++) {
        try {
            $ok = guardarProyectoAprobado($id_con, $dataBase, $_FILES['documento']['tmp_name']);
            if ($ok) break;
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                // Duplicado: regenerar y reintentar
                $dataBase['identificador'] = generarIdentificador();
                continue;
            }
            throw $e; // Otro error
        }
    }

    if (!$ok) {
        throw new Exception('Inserción fallida');
    }

    // Consumir el identificador usado
    unset($_SESSION['identificador_preview']);

    header('Location: /SGPFL/base/proyecto_aprobado.php?ok=1');
    exit;

} catch (Throwable $e) {
    header('Location: /SGPFL/base/proyecto_aprobado.php?err=1');
    exit;
}