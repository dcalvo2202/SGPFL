<?php
function guardarProyectoAprobado(mysqli $conn, array $data, string $tmpPath): bool {
    $sql = "INSERT INTO proyecto_aprobado
        (nombre, estudiante_id, comite_id, categoria_id, documento, aprobado, identificador, fecha_creacion)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return false;

    $blob = file_get_contents($tmpPath);

    mysqli_stmt_bind_param(
        $stmt,
        "ssiisiss",
        $data['nombre'],
        $data['estudiante_id'],
        $data['comite_id'],
        $data['categoria_id'],
        $blob,
        $data['aprobado'],
        $data['identificador'],
        $data['fecha_creacion']
    );

    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}