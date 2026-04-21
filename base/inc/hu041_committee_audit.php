<?php
/**
 * HU-041: Auditoria de acciones sobre solicitudes/comites asesores.
 * Si la tabla aun no existe, la funcion no interrumpe el flujo.
 */

if (!function_exists('hu041_register_audit')) {
    function hu041_register_audit(mysqli $conn, string $actorId, string $actionType, string $entityType, ?int $entityId = null, array $details = []): bool
    {
        $actorId = trim($actorId);
        $actionType = trim($actionType);
        $entityType = trim($entityType);

        if ($actorId === '' || $actionType === '' || $entityType === '') {
            return false;
        }

        // Si el actor no existe en sis_user, se omite auditoría para no bloquear el flujo principal.
        $actorExistsStmt = $conn->prepare("SELECT id FROM sis_user WHERE id = ? LIMIT 1");
        if (!$actorExistsStmt) {
            error_log('HU-041 audit actor check prepare error: ' . $conn->error);
            return false;
        }
        $actorExistsStmt->bind_param('s', $actorId);
        if (!$actorExistsStmt->execute()) {
            error_log('HU-041 audit actor check execute error: ' . $actorExistsStmt->error);
            $actorExistsStmt->close();
            return false;
        }
        $actorExists = $actorExistsStmt->get_result()->num_rows > 0;
        $actorExistsStmt->close();

        if (!$actorExists) {
            error_log('HU-041 audit skipped: actor_id not found in sis_user (' . $actorId . ')');
            return false;
        }

        $jsonDetails = null;
        if (!empty($details)) {
            $json = json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $jsonDetails = ($json === false) ? null : $json;
        }

        $sql = "INSERT INTO hu041_committee_audit (actor_id, action_type, entity_type, entity_id, details, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log('HU-041 audit prepare error: ' . $conn->error);
            return false;
        }

        $stmt->bind_param('sssis', $actorId, $actionType, $entityType, $entityId, $jsonDetails);
        try {
            $ok = $stmt->execute();
        } catch (Throwable $e) {
            error_log('HU-041 audit execute exception: ' . $e->getMessage());
            $stmt->close();
            return false;
        }
        if (!$ok) {
            error_log('HU-041 audit execute error: ' . $stmt->error);
        }
        $stmt->close();
        return $ok;
    }
}
