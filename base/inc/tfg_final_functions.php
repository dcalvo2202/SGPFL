<?php
/**
 * Funciones para gestión de Documentos Finales de TFG
 * HU-014: Subir Documento Final del TFG
 * 
 * NOTA IMPORTANTE: La validación de estructura (capítulos I-V, formato APA, firma del tutor)
 * es MANUAL por la CTFG al revisar el documento descargado.
 * El sistema solo valida: tipo de archivo, tamaño y estado del proyecto (Art. 73 RGPEA).
 */

if (!defined('TFG_FINAL_FUNCTIONS_LOADED')) {
    define('TFG_FINAL_FUNCTIONS_LOADED', true);

require_once(__DIR__ . '/db/db.php');

/**
 * Verifica si un estudiante puede subir el documento final
 * Valida que tenga propuesta aprobada y que el proyecto esté vigente
 * 
 * @param string $user_id ID del estudiante
 * @return array ['can_upload' => bool, 'message' => string, 'proposal_id' => int, 'project_status' => string]
 */
function canUploadFinalDocument($user_id) {
    try {
        require(__DIR__ . '/db/bdcommon.inc');
        $conn = new mysqli($db_host, $usuario, $clave, $db);
        
        if ($conn->connect_error) {
            return [
                'can_upload' => false,
                'message' => 'Error de conexión a la base de datos',
                'proposal_id' => null,
                'project_status' => null
            ];
        }
        
        $conn->set_charset("utf8");
        
        // 1. Verificar que tiene propuesta aprobada (propia o del grupo)
        // Primero buscar propuesta propia
        $sql = "SELECT id, status FROM tfg_proposals WHERE user_id = ? AND status IN ('Aprobado')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $proposal_id = null;
        
        if ($result->num_rows > 0) {
            $proposal = $result->fetch_assoc();
            $proposal_id = $proposal['id'];
        }
        $stmt->close();
        
        // Si no tiene propuesta propia, buscar si es miembro de un grupo con propuesta aprobada
        if (!$proposal_id) {
            $sql_group = "SELECT tp.id, tp.status
                          FROM project_members pm
                          INNER JOIN registered_projects rp ON pm.project_id = rp.id
                          INNER JOIN tfg_proposals tp ON rp.tfg_proposal_id = tp.id
                          WHERE pm.user_id = ? 
                          AND pm.status = 'Activo'
                          AND tp.status IN ('Aprobado')
                          ORDER BY pm.joined_at DESC
                          LIMIT 1";
            $stmt_group = $conn->prepare($sql_group);
            $stmt_group->bind_param("s", $user_id);
            $stmt_group->execute();
            $result_group = $stmt_group->get_result();
            
            if ($result_group->num_rows > 0) {
                $proposal = $result_group->fetch_assoc();
                $proposal_id = $proposal['id'];
            }
            $stmt_group->close();
        }
        
        if (!$proposal_id) {
            $conn->close();
            return [
                'can_upload' => false,
                'message' => 'No tienes una propuesta de TFG aprobada. Debes crear y aprobar una propuesta primero.',
                'proposal_id' => null,
                'project_status' => null
            ];
        }
        
        // 2. Verificar si ya subió documento final
        // HU-020: Si el documento está rechazado, redirigir al estudiante a la página de correcciones
        $sql_check = "SELECT id, status FROM tfg_final_documents WHERE proposal_id = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("i", $proposal_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            $doc_data = $result_check->fetch_assoc();
            $document_id = $doc_data['id'];
            $document_status = $doc_data['status'];
            $stmt_check->close();
            
            // Si está rechazado, el estudiante debe usar HU-020 (correcciones)
            if ($document_status === 'Rechazado') {
                $conn->close();
                return [
                    'can_upload' => false,
                    'message' => 'Tu documento final fue rechazado por la CTFG.',
                    'proposal_id' => $proposal_id,
                    'project_status' => null,
                    'document_id' => $document_id,
                    'is_rejected' => true
                ];
            }
            
            // Si está en otro estado (Pendiente, Aprobado), bloquear subida
            $conn->close();
            return [
                'can_upload' => false,
                'message' => 'Ya has subido un documento final para esta propuesta. Estado actual: ' . $document_status,
                'proposal_id' => $proposal_id,
                'project_status' => null
            ];
        }
        $stmt_check->close();
        
        // 3. Verificar estado del proyecto (Art. 73 RGPEA)
        $sql_timeline = "SELECT status, days_remaining FROM tfg_project_timeline WHERE proposal_id = ?";
        $stmt_timeline = $conn->prepare($sql_timeline);
        $stmt_timeline->bind_param("i", $proposal_id);
        $stmt_timeline->execute();
        $result_timeline = $stmt_timeline->get_result();
        
        if ($result_timeline->num_rows === 0) {
            // No existe timeline, asumir que está vigente (por compatibilidad)
            $project_status = 'Vigente';
        } else {
            $timeline = $result_timeline->fetch_assoc();
            $project_status = $timeline['status'];
            
            // Bloquear si está vencido
            if ($project_status === 'Vencido') {
                $stmt_timeline->close();
                $conn->close();
                return [
                    'can_upload' => false,
                    'message' => 'Tu proyecto está vencido según el Artículo 73 del RGPEA. No puedes subir el documento final sin una prórroga activa.',
                    'proposal_id' => $proposal_id,
                    'project_status' => $project_status
                ];
            }
        }
        $stmt_timeline->close();
        
        $conn->close();
        
        return [
            'can_upload' => true,
            'message' => 'Puedes subir el documento final',
            'proposal_id' => $proposal_id,
            'project_status' => $project_status ?? 'Vigente'
        ];
        
    } catch (Exception $e) {
        error_log("Error en canUploadFinalDocument: " . $e->getMessage());
        return [
            'can_upload' => false,
            'message' => 'Error al verificar permisos: ' . $e->getMessage(),
            'proposal_id' => null,
            'project_status' => null
        ];
    }
}


/**
 * Limita a 5 versiones por documento final en tfg_files para un usuario y tipo de documento.
 * Elimina la versión más antigua si ya existen 5 o más.
 *
 * @param mysqli $conn Conexión activa a la base de datos
 * @param string $user_id ID del usuario que sube el documento
 * @param string $document_type Tipo de documento (ej: 'Documento Final TFG')
 * @return void
 * @throws Exception Si ocurre un error en la base de datos
 */
function limitarVersionesTFGFiles($conn, $user_id, $document_type) {
    try {
        // 1. Obtener todas las versiones existentes para este usuario y tipo de documento
        // Obtener todas las versiones existentes para este usuario y tipo de documento
        $stmt = $conn->prepare("SELECT id FROM tfg_files WHERE uploaded_by = ? AND document_type = ? ORDER BY upload_date ASC");
        $stmt->bind_param("ss", $user_id, $document_type);
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
            $stmt = $conn->prepare("DELETE FROM tfg_final_documents WHERE file_id = ?");
            $stmt->bind_param("i", $oldest_id);
            $stmt->execute();
            $stmt->close();

            $oldest_id = $version_ids[0];
            $stmt = $conn->prepare("DELETE FROM tfg_files WHERE id = ?");
            $stmt->bind_param("i", $oldest_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
        }
    } catch (Exception $e) {
        error_log("Error al gestionar versiones: " . $e->getMessage());
        $conn->rollback();
    }
}

/**
 * Guarda el documento final en la base de datos
 * Sin validar estructura (validación manual por CTFG)
 * 
 * @param int $proposal_id ID de la propuesta
 * @param array $file_data Datos del archivo ($_FILES['document'])
 * @param string $user_id ID del usuario que sube
 * @param string $project_status Estado del proyecto
 * @return array ['success' => bool, 'message' => string, 'document_id' => int]
 */
function saveFinalDocument($proposal_id, $file_data, $user_id, $project_status) {
    try {
        require(__DIR__ . '/db/bdcommon.inc');
        $conn = new mysqli($db_host, $usuario, $clave, $db);
        
        if ($conn->connect_error) {
            return [
                'success' => false,
                'message' => 'Error de conexión a la base de datos',
                'document_id' => null
            ];
        }
        
        $conn->set_charset("utf8");
        $conn->autocommit(false);
        
        // 1. Leer el archivo y preparar para BLOB
        $file_content = file_get_contents($file_data['tmp_name']);
        if ($file_content === false) {
            $conn->close();
            return [
                'success' => false,
                'message' => 'Error al leer el archivo',
                'document_id' => null
            ];
        }
        
        // =============================== LÍMITE DE VERSIONES POR DOCUMENTO ===============================
        $document_type = 'Documento Final TFG';

        // Eliminar versiones antiguas si hay más de 5
        limitarVersionesTFGFiles($conn, $user_id, $document_type);

        // Obtener la última versión para este usuario y tipo de documento
        $sql_version = "SELECT MAX(version) AS max_version 
                        FROM tfg_files 
                        WHERE uploaded_by = ? 
                        AND document_type = ?";

        $stmt_version = $conn->prepare($sql_version);
        $stmt_version->bind_param("ss", $user_id, $document_type);
        $stmt_version->execute();
        $result_version = $stmt_version->get_result();
        $row_version = $result_version->fetch_assoc();
        $next_version = 1; // Valor por defecto si no hay versiones previas

        if ($row_version['max_version']) {
            $next_version = $row_version['max_version'] + 1;
        }
        $stmt_version->close();

        // 2. Insertar en tfg_files
        $sql_file = "INSERT INTO tfg_files (file_name, mime_type, file_size, file_data, storage_path, uploaded_by, version, document_type) 
                    VALUES (?, ?, ?, ?, NULL, ?, ?, ?)";
        $stmt_file = $conn->prepare($sql_file);
        $stmt_file->bind_param("ssibsds", 
            $file_data['name'], 
            $file_data['type'], 
            $file_data['size'], 
            $file_content,
            $user_id,
            $next_version,
            $document_type
        );
        
        // Enviar el BLOB
        $stmt_file->send_long_data(3, $file_content);
        
        if (!$stmt_file->execute()) {
            $conn->rollback();
            $stmt_file->close();
            $conn->close();
            return [
                'success' => false,
                'message' => 'Error al guardar el archivo: ' . $stmt_file->error,
                'document_id' => null
            ];
        }
        
        $file_id = $conn->insert_id;
        $stmt_file->close();
        
        // 3. Insertar en tfg_final_documents (sin validar capítulos - validación manual por CTFG)
        $sql_doc = "INSERT INTO tfg_final_documents 
                    (proposal_id, file_id, status, project_status, submitted_by) 
                    VALUES (?, ?, 'Pendiente de Revision', ?, ?)";
        
        $stmt_doc = $conn->prepare($sql_doc);
        $stmt_doc->bind_param("iiss", 
            $proposal_id, 
            $file_id,
            $project_status,
            $user_id
        );
        
        if (!$stmt_doc->execute()) {
            $conn->rollback();
            $stmt_doc->close();
            $conn->close();
            return [
                'success' => false,
                'message' => 'Error al registrar el documento: ' . $stmt_doc->error,
                'document_id' => null
            ];
        }
        
        $document_id = $conn->insert_id;
        $stmt_doc->close();
        
        $conn->commit();
        $conn->close();
        
        // =============================== NOTIFICACIÓN ===============================
        // Llama al archivo de notificación estudiante, secretaria (ajusta la ruta si es necesario)
        //include_once(__DIR__ . '/../../mod/admin/users/tfg_update_document.php');

        return [
            'success' => true,
            'message' => 'Documento guardado exitosamente',
            'document_id' => $document_id
        ];
        
    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollback();
            $conn->close();
        }
        error_log("Error en saveFinalDocument: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error al guardar el documento: ' . $e->getMessage(),
            'document_id' => null
        ];
    }
}

/**
 * Notifica a la CTFG sobre la subida de un documento final
 * 
 * @param int $proposal_id ID de la propuesta
 * @param string $student_id ID del estudiante
 * @return bool
 */
function notifyCTFGNewDocument($proposal_id, $student_id) {
    try {
        require(__DIR__ . '/db/bdcommon.inc');
        $conn = new mysqli($db_host, $usuario, $clave, $db);
        
        if ($conn->connect_error) {
            error_log("Error de conexión en notifyCTFGNewDocument");
            return false;
        }
        
        $conn->set_charset("utf8");
        
        // Obtener información del estudiante y propuesta
        $sql_info = "SELECT tp.title, u.nombre 
                     FROM tfg_proposals tp 
                     INNER JOIN sis_user u ON tp.user_id = u.id 
                     WHERE tp.id = ?";
        $stmt_info = $conn->prepare($sql_info);
        $stmt_info->bind_param("i", $proposal_id);
        $stmt_info->execute();
        $result = $stmt_info->get_result();
        
        if ($result->num_rows === 0) {
            $stmt_info->close();
            $conn->close();
            return false;
        }
        
        $info = $result->fetch_assoc();
        $stmt_info->close();
        
        // Crear notificación para CTFG (rol 3)
        $message = "El estudiante {$info['nombre']} (ID: {$student_id}) ha subido el documento final del TFG: \"{$info['title']}\". El documento está pendiente de revisión.";
        $recipient_role = 3; // CTFG
        
        $sql_notif = "INSERT INTO tfg_notifications 
                      (notification_type, proposal_id, sender_id, recipient_role_id, message, status) 
                      VALUES ('Documento Final Subido', ?, ?, ?, ?, 'Enviada')";
        $stmt_notif = $conn->prepare($sql_notif);
        $stmt_notif->bind_param("isis", $proposal_id, $student_id, $recipient_role, $message);
        
        $result = $stmt_notif->execute();
        $stmt_notif->close();
        $conn->close();
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Error en notifyCTFGNewDocument: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene los miembros de la CTFG
 * 
 * @return array Lista de usuarios con rol CTFG
 */
function getCTFGMembers() {
    try {
        require(__DIR__ . '/db/bdcommon.inc');
        $conn = new mysqli($db_host, $usuario, $clave, $db);
        
        if ($conn->connect_error) {
            return [];
        }
        
        $conn->set_charset("utf8");
        
        $sql = "SELECT u.id, u.nombre, u.email 
                FROM sis_user u 
                INNER JOIN sis_login l ON u.id = l.id 
                WHERE l.id_roll = 3 
                ORDER BY u.nombre";
        
        $result = $conn->query($sql);
        $members = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $members[] = $row;
            }
        }
        
        $conn->close();
        return $members;
        
    } catch (Exception $e) {
        error_log("Error en getCTFGMembers: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene información del documento final por ID de propuesta
 * 
 * @param int $proposal_id
 * @return array|null
 */
function getFinalDocumentByProposal($proposal_id) {
    try {
        require(__DIR__ . '/db/bdcommon.inc');
        $conn = new mysqli($db_host, $usuario, $clave, $db);
        
        if ($conn->connect_error) {
            return null;
        }
        
        $conn->set_charset("utf8");
        
        $sql = "SELECT fd.*, f.file_name, f.file_size, f.mime_type, u.nombre as submitted_by_name
                FROM tfg_final_documents fd
                JOIN tfg_files f ON fd.file_id = f.id
                JOIN sis_user u ON fd.submitted_by = u.id
                WHERE fd.proposal_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $proposal_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $document = null;
        if ($result->num_rows > 0) {
            $document = $result->fetch_assoc();
        }
        
        $stmt->close();
        $conn->close();
        
        return $document;
        
    } catch (Exception $e) {
        error_log("Error en getFinalDocumentByProposal: " . $e->getMessage());
        return null;
    }
}

/**
 * Obtiene la información necesaria para enviar un correo de notificación sobre la revisión de un documento final.
 *
 * @param int $document_id ID del registro en tfg_final_documents.
 * @return array|null Un array con la información o null si no se encuentra.
 */
function getFinalDocumentInfoForEmail($document_id) {
    require(__DIR__ . '/db/bdcommon.inc');
    $conn = new mysqli($db_host, $usuario, $clave, $db);
    if ($conn->connect_error) {
        error_log("Error de conexión en getFinalDocumentInfoForEmail: " . $conn->connect_error);
        return null;
    }
    $conn->set_charset("utf8");

    $sql = "SELECT
                u.id AS user_id,
                u.nombre AS student_name,
                u.email AS student_email,
                p.title AS project_title,
                d.status AS document_status,
                d.review_comments AS review_comments
            FROM tfg_final_documents d
            JOIN tfg_proposals p ON d.proposal_id = p.id
            JOIN sis_user u ON p.user_id = u.id
            WHERE d.id = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar la consulta en getFinalDocumentInfoForEmail: " . $conn->error);
        $conn->close();
        return null;
    }
    
    $stmt->bind_param("i", $document_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    return $data;
}

} // End of if (!defined('TFG_FINAL_FUNCTIONS_LOADED'))
