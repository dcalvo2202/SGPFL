<?php
function guardarProyectoAprobadoSP(mysqli $conn, array $data, string $tmpPath): bool {
    $ests = array_unique($data['estudiantes'] ?? []);
    if (count($ests) < 1 || count($ests) > 8) return false;
    $blob = file_get_contents($tmpPath);
    $fecha_aprob = substr($data['fecha_creacion'], 0, 10); // YYYY-MM-DD
    $jsonEst = json_encode(array_values($ests), JSON_UNESCAPED_UNICODE);

    $sql = "CALL registrar_proyecto_aprobado(?,?,?,?,?,?,?)";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return false;
    mysqli_stmt_bind_param(
        $stmt,
        "sibisss", // nombre(s), comite_id(i), documento(b), aprobado(i), identificador(s), fecha(s), estudiantes_json(s)
        $data['nombre'],
        (int)$data['comite_id'],
        $blob,
        (int)$data['aprobado'],
        $data['identificador'],
        $fecha_aprob,
        $jsonEst
    );
    $ok = mysqli_stmt_execute($stmt);
    // Consumir posibles result sets del CALL
    while (mysqli_more_results($conn) && mysqli_next_result($conn)) { /* limpiar */ }
    mysqli_stmt_close($stmt);
    return $ok;
}