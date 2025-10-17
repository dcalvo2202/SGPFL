<?php
function limitarVersionesYAgregarHistorial($conn, $user_id) {
    //Contar cuántas versiones existen para esta propuesta
    try{
        $sql = "SELECT id
                    FROM tfg_proposals
                    WHERE user_id = ? ORDER BY id ASC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $proposal_ids = [];
            while ($row = $result->fetch_assoc()) {
                $proposal_ids[] = $row['id'];
            }
            $stmt->close();

            $version_ids = [];
            $version_ids = $proposal_ids; // Asignar a la variable global para usar en la verificación

        // Si ya hay 5 versiones, eliminar la más antigua. Se usa -1 para permitir 5 versiones (0 a 4)
        if (count($version_ids)-1 >= 5) {
            $oldest_id = $version_ids[0];
            // 1. Eliminar historial de versiones
            $stmt = $conn->prepare("DELETE FROM tfg_proposal_history WHERE proposal_id = ?");
            $stmt->bind_param("i", $oldest_id);
            $stmt->execute();
            $stmt->close();

            // 2. Eliminar el proyecto registrado (opcional, ON DELETE CASCADE lo hace automáticamente)
            $stmt = $conn->prepare("DELETE FROM registered_projects WHERE tfg_proposal_id = ?");
            $stmt->bind_param("i", $oldest_id);
            $stmt->execute();
            $stmt->close();

            // 3. Eliminar la propuesta principal
            $stmt = $conn->prepare("DELETE FROM tfg_proposals WHERE id = ?");
            $stmt->bind_param("i", $oldest_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
        }
    }catch (Exception $e) {
        error_log("Error al gestionar versiones: " . $e->getMessage());
        $conn->rollback();
    }
}