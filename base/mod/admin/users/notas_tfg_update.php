<?php
// Inserta una nota y devuelve el ID insertado o 0 si falla
function guardarNotaProyecto(mysqli $conn, int $proyectoId, string $titulo, string $notas, ?string $usuarioCreador): int {
    $sql = "INSERT INTO proyecto_notas (proyecto_id, titulo, notas, creado_por) VALUES (?,?,?,?)";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return 0;

    mysqli_stmt_bind_param($stmt, "isss", $proyectoId, $titulo, $notas, $usuarioCreador);
    $ok = mysqli_stmt_execute($stmt);
    $newId = $ok ? (int)mysqli_insert_id($conn) : 0;

    mysqli_stmt_close($stmt);
    return $newId;
}