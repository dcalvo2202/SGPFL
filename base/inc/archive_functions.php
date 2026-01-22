<?php
/**
 * HU-027: Funciones para archivar proyectos concluidos o cancelados
 * 
 * Este archivo contiene las funciones para mover proyectos con estado
 * "Concluido" o "Cancelado" a las tablas de archivo histórico.
 * 
 * Según el Artículo 68 RGPEA, se debe conservar acceso para auditorías.
 */

require_once(__DIR__ . '/constants.php');

/**
 * Comprime datos usando gzip
 * @param string $data Datos a comprimir
 * @return array ['data' => datos comprimidos, 'original_size' => tamaño original, 'compressed_size' => tamaño comprimido]
 */
function compressData($data) {
    if (empty($data)) {
        return [
            'data' => null,
            'original_size' => 0,
            'compressed_size' => 0
        ];
    }
    
    $original_size = strlen($data);
    $compressed = gzencode($data, 9); // Máxima compresión
    $compressed_size = strlen($compressed);
    
    return [
        'data' => $compressed,
        'original_size' => $original_size,
        'compressed_size' => $compressed_size
    ];
}

/**
 * Descomprime datos gzip
 * @param string $compressed_data Datos comprimidos
 * @return string|false Datos descomprimidos o false si falla
 */
function decompressData($compressed_data) {
    if (empty($compressed_data)) {
        return '';
    }
    return gzdecode($compressed_data);
}

/**
 * Archiva una propuesta TFG y todo lo relacionado
 * Se ejecuta automáticamente cuando el proyecto cambia a "Concluido" o "Cancelado"
 * 
 * @param mysqli $conn Conexión a la base de datos
 * @param int $proposal_id ID de la propuesta a archivar
 * @param string $archive_reason Razón del archivado ('Concluido' o 'Cancelado')
 * @param string|null $archived_by Usuario que archiva (null si es automático)
 * @return array ['success' => bool, 'message' => string]
 */
function archiveProposal($conn, $proposal_id, $archive_reason, $archived_by = null) {
    // Validar razón de archivado
    if (!in_array($archive_reason, ['Concluido', 'Cancelado'])) {
        return ['success' => false, 'message' => 'Razón de archivado inválida'];
    }
    
    $conn->begin_transaction();
    
    try {
        // 1. Obtener datos de la propuesta
        $stmt = $conn->prepare("
            SELECT id, user_id, title, disciplines, project_description, document, 
                   file_name, mime_type, file_size, status, admin_comments, 
                   reviewed_by, reviewed_at, created_at, updated_at
            FROM tfg_proposals 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $proposal_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Propuesta no encontrada");
        }
        
        $proposal = $result->fetch_assoc();
        $stmt->close();
        
        // 2. Comprimir documento principal
        $compressed = compressData($proposal['document']);
        
        // 3. Insertar en tabla de archivo
        $archive_stmt = $conn->prepare("
            INSERT INTO tfg_proposals_archive (
                original_proposal_id, user_id, title, disciplines, project_description,
                document, file_name, mime_type, file_size, compressed_size, is_compressed,
                original_status, archive_reason, admin_comments, reviewed_by, reviewed_at,
                original_created_at, original_updated_at, archived_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $archive_stmt->bind_param(
            "isssssssiissssssss",
            $proposal['id'],
            $proposal['user_id'],
            $proposal['title'],
            $proposal['disciplines'],
            $proposal['project_description'],
            $compressed['data'],
            $proposal['file_name'],
            $proposal['mime_type'],
            $compressed['original_size'],
            $compressed['compressed_size'],
            $proposal['status'],
            $archive_reason,
            $proposal['admin_comments'],
            $proposal['reviewed_by'],
            $proposal['reviewed_at'],
            $proposal['created_at'],
            $proposal['updated_at'],
            $archived_by
        );
        
        // Enviar el blob
        if ($compressed['data'] !== null) {
            $archive_stmt->send_long_data(5, $compressed['data']);
        }
        
        if (!$archive_stmt->execute()) {
            throw new Exception("Error al insertar en archivo: " . $archive_stmt->error);
        }
        $archived_proposal_id = $conn->insert_id;
        $archive_stmt->close();
        
        // 4. Archivar proyecto registrado asociado
        archiveRegisteredProject($conn, $proposal_id, $archive_reason);
        
        // 5. Archivar archivos adicionales (tfg_files)
        archiveAdditionalFiles($conn, $proposal_id);
        
        // 6. Liberar espacio: poner document a NULL en la propuesta original
        // Mantenemos el registro para integridad referencial con tablas dependientes
        // (tfg_proposal_history, tfg_project_timeline, etc.) pero liberamos el BLOB
        $update_stmt = $conn->prepare("UPDATE tfg_final_documents SET status = ? WHERE proposal_id = ?");
        // Determinar nuevo estado basado en razón de archivado
        if ($archive_reason === 'Concluido') {
            $archived_status = 'Aprobado';
        } else {
            $archived_status = 'Rechazado';
        }
        $update_stmt->bind_param("si", $archived_status, $proposal_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception("Error al actualizar documento final: " . $update_stmt->error);
        }

        $update_stmt = $conn->prepare("UPDATE tfg_proposals SET document = NULL WHERE id = ?");
        $update_stmt->bind_param("i", $proposal_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception("Error al actualizar propuesta: " . $update_stmt->error);
        }

        $update_stmt->close();
        
        $conn->commit();
        
        // Registrar en log
        error_log("HU-027: Propuesta $proposal_id archivada exitosamente. Razón: $archive_reason");
        
        return [
            'success' => true, 
            'message' => "Proyecto archivado exitosamente",
            'archived_id' => $archived_proposal_id
        ];
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("HU-027 Error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Archiva el proyecto registrado asociado a una propuesta
 */
function archiveRegisteredProject($conn, $proposal_id, $archive_reason) {
    // Obtener proyecto registrado
    $stmt = $conn->prepare("
        SELECT rp.*, pt.type_name,
               u.nombre as supervisor_name
        FROM registered_projects rp
        LEFT JOIN project_types pt ON rp.project_type_id = pt.id
        LEFT JOIN sis_user u ON rp.supervisor_id = u.id
        WHERE rp.tfg_proposal_id = ?
    ");
    $stmt->bind_param("i", $proposal_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return; // No hay proyecto registrado
    }
    
    $project = $result->fetch_assoc();
    $stmt->close();
    
    // Archivar miembros primero
    archiveProjectMembers($conn, $project['id']);
    
    // Insertar en archivo
    $archive_stmt = $conn->prepare("
        INSERT INTO registered_projects_archive (
            original_project_id, original_proposal_id, project_type_id, project_type_name,
            original_status, archive_reason, start_date, end_date, final_grade,
            supervisor_id, supervisor_name, original_created_at, original_updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $archive_stmt->bind_param(
        "iiisssssdssss",
        $project['id'],
        $proposal_id,
        $project['project_type_id'],
        $project['type_name'],
        $project['status'],
        $archive_reason,
        $project['start_date'],
        $project['end_date'],
        $project['final_grade'],
        $project['supervisor_id'],
        $project['supervisor_name'],
        $project['created_at'],
        $project['updated_at']
    );
    
    $archive_stmt->execute();
    $archive_stmt->close();
}

/**
 * Archiva los miembros de un proyecto
 */
function archiveProjectMembers($conn, $project_id) {
    $stmt = $conn->prepare("
        SELECT pm.*, u.nombre as user_name
        FROM project_members pm
        LEFT JOIN sis_user u ON pm.user_id = u.id
        WHERE pm.project_id = ?
    ");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($member = $result->fetch_assoc()) {
        $archive_stmt = $conn->prepare("
            INSERT INTO project_members_archive (
                original_member_id, original_project_id, user_id, user_name,
                role, member_status, joined_at, left_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $archive_stmt->bind_param(
            "iissssss",
            $member['id'],
            $project_id,
            $member['user_id'],
            $member['user_name'],
            $member['role'],
            $member['status'],
            $member['joined_at'],
            $member['left_at']
        );
        
        $archive_stmt->execute();
        $archive_stmt->close();
    }
    
    $stmt->close();
}

/**
 * Archiva archivos adicionales de una propuesta
 */
function archiveAdditionalFiles($conn, $proposal_id) {
    // Buscar archivos en tfg_files que pertenezcan a esta propuesta
    // Nota: Ajustar según la estructura real de tfg_files
    $stmt = $conn->prepare("
        SELECT * FROM tfg_files 
        WHERE uploaded_by IN (
            SELECT user_id FROM tfg_proposals WHERE id = ?
        )
    ");
    $stmt->bind_param("i", $proposal_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($file = $result->fetch_assoc()) {
        // Comprimir archivo
        $compressed = compressData($file['file_data']);
        
        $archive_stmt = $conn->prepare("
            INSERT INTO tfg_files_archive (
                original_file_id, original_proposal_id, file_name, mime_type,
                original_size, compressed_size, file_data, is_compressed,
                document_type, uploaded_by, original_created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?)
        ");
        
        $archive_stmt->bind_param(
            "iissiissss",
            $file['id'],
            $proposal_id,
            $file['file_name'],
            $file['mime_type'],
            $compressed['original_size'],
            $compressed['compressed_size'],
            $compressed['data'],
            $file['document_type'],
            $file['uploaded_by'],
            $file['created_at']
        );
        
        if ($compressed['data'] !== null) {
            $archive_stmt->send_long_data(6, $compressed['data']);
        }
        
        $archive_stmt->execute();
        $archive_stmt->close();
    }
    
    $stmt->close();
}

/**
 * Registra acceso al archivo histórico para auditoría
 */
function logArchiveAccess($conn, $user_id, $action_type, $archived_proposal_id = null, $archived_project_id = null, $details = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    $stmt = $conn->prepare("
        INSERT INTO archive_audit_log (user_id, action_type, archived_proposal_id, archived_project_id, details, ip_address)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param("ssiiss", $user_id, $action_type, $archived_proposal_id, $archived_project_id, $details, $ip);
    $stmt->execute();
    $stmt->close();
}

/**
 * Obtiene propuestas archivadas con filtros
 */
function getArchivedProposals($conn, $filters = [], $limit = 50, $offset = 0) {
    $where_clauses = ["1=1"];
    $params = [];
    $types = "";
    
    if (!empty($filters['search'])) {
        $where_clauses[] = "(title LIKE ? OR user_id LIKE ?)";
        $search = "%" . $filters['search'] . "%";
        $params[] = $search;
        $params[] = $search;
        $types .= "ss";
    }
    
    if (!empty($filters['archive_reason'])) {
        $where_clauses[] = "archive_reason = ?";
        $params[] = $filters['archive_reason'];
        $types .= "s";
    }
    
    if (!empty($filters['date_from'])) {
        $where_clauses[] = "archived_at >= ?";
        $params[] = $filters['date_from'];
        $types .= "s";
    }
    
    if (!empty($filters['date_to'])) {
        $where_clauses[] = "archived_at <= ?";
        $params[] = $filters['date_to'] . " 23:59:59";
        $types .= "s";
    }
    
    $where = implode(" AND ", $where_clauses);
    
    $sql = "
        SELECT tpa.*, 
               u.nombre as user_name
        FROM tfg_proposals_archive tpa
        LEFT JOIN sis_user u ON tpa.user_id = u.id
        WHERE $where
        ORDER BY archived_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $proposals = [];
    while ($row = $result->fetch_assoc()) {
        // No incluir el documento blob en la lista
        unset($row['document']);
        $proposals[] = $row;
    }
    
    $stmt->close();
    return $proposals;
}

/**
 * Obtiene un documento archivado y lo descomprime para descarga
 */
function getArchivedDocument($conn, $archived_proposal_id, $user_id) {
    // Registrar acceso
    logArchiveAccess($conn, $user_id, 'DOWNLOAD', $archived_proposal_id);
    
    $stmt = $conn->prepare("
        SELECT document, file_name, mime_type, is_compressed, title
        FROM tfg_proposals_archive
        WHERE id = ?
    ");
    $stmt->bind_param("i", $archived_proposal_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return null;
    }
    
    $doc = $result->fetch_assoc();
    $stmt->close();
    
    // Descomprimir si está comprimido
    if ($doc['is_compressed'] && !empty($doc['document'])) {
        $doc['document'] = decompressData($doc['document']);
    }
    
    return $doc;
}
/**
 * Archiva un documento final TFG
 * Se llama cuando el documento final es aprobado para defensa
 * 
 * @param mysqli $conn Conexión a la base de datos
 * @param int $document_id ID del documento final
 * @param int $proposal_id ID de la propuesta asociada
 * @param string $archived_by Usuario que archiva
 * @return array ['success' => bool, 'message' => string]
 */
function archiveFinalDocument($conn, $document_id, $proposal_id, $archived_by = null) {
    try {
        // Obtener datos del documento final
        $stmt = $conn->prepare("
            SELECT fd.id, fd.proposal_id, fd.file_id, fd.status, fd.submitted_by, fd.submitted_at,
                   f.file_name, f.mime_type, f.file_size, f.file_data, f.version
            FROM tfg_final_documents fd
            INNER JOIN tfg_files f ON fd.file_id = f.id
            WHERE fd.id = ?
        ");
        $stmt->bind_param("i", $document_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            return ['success' => false, 'message' => 'Documento final no encontrado'];
        }
        
        $doc = $result->fetch_assoc();
        $stmt->close();
        
        // Comprimir el documento
        $compressed = compressData($doc['file_data']);
        
        // Insertar en tfg_files_archive
        $archive_stmt = $conn->prepare("
            INSERT INTO tfg_files_archive (
                original_file_id, original_proposal_id, file_name, mime_type,
                original_size, compressed_size, file_data, is_compressed,
                document_type, uploaded_by, original_created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 1, 'Documento Final TFG', ?, ?)
        ");
        
        $null_blob = null;
        $archive_stmt->bind_param(
            "iissiisss",
            $doc['file_id'],
            $proposal_id,
            $doc['file_name'],
            $doc['mime_type'],
            $compressed['original_size'],
            $compressed['compressed_size'],
            $null_blob,
            $doc['submitted_by'],
            $doc['submitted_at']
        );
        
        // Enviar el blob comprimido
        if ($compressed['data'] !== null) {
            $archive_stmt->send_long_data(6, $compressed['data']);
        }
        
        if (!$archive_stmt->execute()) {
            throw new Exception("Error al archivar documento final: " . $archive_stmt->error);
        }
        $archive_stmt->close();
        
        // HU-027: Liberar espacio - poner blob a NULL en tfg_files original
        $update_stmt = $conn->prepare("UPDATE tfg_files SET file_data = NULL WHERE id = ?");
        $update_stmt->bind_param("i", $doc['file_id']);
        if (!$update_stmt->execute()) {
            error_log("HU-027 Warning: No se pudo limpiar blob de tfg_files ID {$doc['file_id']}");
        }
        $update_stmt->close();
        
        error_log("HU-027: Documento final $document_id archivado exitosamente, blob liberado");
        return ['success' => true, 'message' => 'Documento final archivado'];
        
    } catch (Exception $e) {
        error_log("HU-027 Error archivando documento final: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}