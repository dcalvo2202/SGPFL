<?php
function guardarProyectoAprobado(mysqli $conn, array $data, string $tmpPath): bool {

    // Tabla real: proyecto_aprobado (sin 's') y SIN columna categoria_id
    $sql = "INSERT INTO proyecto_aprobado
        (nombre, estudiante_id, comite_id, documento, aprobado, identificador, fecha_creacion, fecha_finalizacion)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return false;

    $blob = file_get_contents($tmpPath);

    // Calcular +1 año
    $fecha_creacion = $data['fecha_creacion'];
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $fecha_creacion);
    $dt->modify('+1 year');
    $fecha_finalizacion = $dt->format('Y-m-d H:i:s');

    // nombre (s), estudiante_id (s), comite_id (i), documento (s), aprobado (i), identificador (s), fecha_creacion (s)
    mysqli_stmt_bind_param(
        $stmt,
        "ssisisss",
        $data['nombre'],
        $data['estudiante_id'],
        $data['comite_id'],
        $blob,
        $data['aprobado'],
        $data['identificador'],
        $fecha_creacion,
        $fecha_finalizacion
    );

    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}