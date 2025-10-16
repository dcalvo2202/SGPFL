<?php
function limitarVersionesYAgregarHistorial($conn, $tfg_id, $user_id, $unique_filename, $mime_type, $file_size, $document_data, $null_blob) {
    // Contar cuántas versiones existen para esta propuesta
    $stmt = $conn->prepare("SELECT id FROM tfg_proposal_history WHERE proposal_id = ? ORDER BY created_at ASC");
    $stmt->bind_param("i", $tfg_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $version_ids = [];
    while ($row = $result->fetch_assoc()) {
        $version_ids[] = $row['id'];
    }
    $stmt->close();

    // Si ya hay 5 versiones, eliminar la más antigua
    if (count($version_ids) >= 5) {
        $oldest_id = $version_ids[0];
        $stmt = $conn->prepare("DELETE FROM tfg_proposal_history WHERE id = ?");
        $stmt->bind_param("i", $oldest_id);
        $stmt->execute();
        $stmt->close();
    }

    // Insertar la versión inicial en el historial
    $history_sql = "INSERT INTO tfg_proposal_history (proposal_id, document, file_name, mime_type, file_size, status, reviewed_by, comments, created_at) 
                    VALUES (?, ?, ?, ?, ?, 'Pendiente de Revisión', ?, 'Versión inicial subida por el estudiante', NOW())";
    $history_stmt = $conn->prepare($history_sql);
    if ($history_stmt) {
        $history_stmt->bind_param("ibssis", $tfg_id, $null_blob, $unique_filename, $mime_type, $file_size, $user_id);
        if ($document_data !== null && strlen($document_data) > 0) {
            $history_stmt->send_long_data(1, $document_data);
        }
        if (!$history_stmt->execute()) {
            throw new Exception("Error al insertar en historial: " . $history_stmt->error);
        }
        $history_stmt->close();
    } else {
        throw new Exception("Error al preparar la consulta de historial: " . $conn->error);
    }
}