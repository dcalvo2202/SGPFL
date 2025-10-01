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

$q = "SELECT c.Id,
            t.nombre  AS tutor_nombre,
            a1.nombre AS asesor1_nombre,
            a2.nombre AS asesor2_nombre
      FROM comite c
      JOIN sis_user t  ON t.id  = c.tutor
      JOIN sis_user a1 ON a1.id = c.asesor_1
      JOIN sis_user a2 ON a2.id = c.asesor_2
      ORDER BY c.Id";
$res = mysqli_query($id_con, $q);
?>