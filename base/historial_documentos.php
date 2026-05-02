<?php
// =============================== INICIALIZACIÓN Y SEGURIDAD ===============================

// Incluir archivos de configuración y utilidades
include("mod/login/check.php");
include('lang/lang.es');
include('inc/db/db.php');

require_once __DIR__ . '/lib/mysession/mySession.conf.php';
require_once __DIR__ . '/lib/mysession/mySession.class.php';
$mySessionController = mySession::getIstance($_MYSESSION_CONF);

// Obtener variables de sesión del usuario autenticado
$current_user_id = $mySessionController->getVar("usuario");
$current_user_name = $mySessionController->getVar("nombre");
$current_user_rol = $mySessionController->getVar("rol");
$cds_domain = $mySessionController->getVar("cds_domain");
$cds_locate = $mySessionController->getVar("cds_locate");
$base_url = $cds_domain . $cds_locate;

$has_committee_approval = false;
try {
    $authConn = new mysqli($db_host, $usuario, $clave, $db);
    if (!$authConn->connect_error) {
        $authConn->set_charset('utf8');
        $authStmt = $authConn->prepare("SELECT id FROM external_advisor_profile_requests WHERE applicant_id = ? AND status = 'Aprobado' LIMIT 1");
        if ($authStmt) {
            $authStmt->bind_param('s', $current_user_id);
            $authStmt->execute();
            $has_committee_approval = $authStmt->get_result()->num_rows > 0;
            $authStmt->close();
        }
        $authConn->close();
    }
} catch (Exception $e) {
    error_log("Error validando aprobación de comité en historial: " . $e->getMessage());
}

// Restringir acceso: estudiantes (4), asesores externos (5), admin (1), asesores internos de comité (3)
if ($current_user_rol != 4 && $current_user_rol != 5 && $current_user_rol != 1 && $current_user_rol != 3 && !$has_committee_approval) {
    header('Location: dashboard.php');
    exit;
}

// =============================== DETERMINAR ID DEL ESTUDIANTE A CONSULTAR ===============================
// Para asesores externos (rol 5), obtener el estudiante vinculado (externo o interno de comité)
// Para asesores internos de comité (rol 3), buscar vinculación por internal_advisor_id
// Para estudiantes (rol 4), detectar si pertenecen a un grupo y mostrar documentos de todos
$target_student_id = $current_user_id; // Por defecto, el mismo usuario
$linked_student_name = '';
$linked_students_list = []; // Array para todos los estudiantes del grupo
$group_member_ids = []; // IDs de todos los miembros del grupo (para consultas)
$is_external_advisor = ($current_user_rol == 5 || $has_committee_approval);
$is_internal_advisor = ($current_user_rol == 3); // Miembro de comité (tutor/asesor interno)
$is_advisor = ($is_external_advisor || $is_internal_advisor);
$is_student = ($current_user_rol == 4);

// Función para obtener los miembros del grupo de un estudiante
function getGroupMembersForHistorial($conn, $student_id) {
    $members = [];
    
    // Primero buscar el proyecto del estudiante
    $sql = "SELECT pm.project_id 
            FROM project_members pm 
            WHERE pm.user_id = ?
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return [$student_id]; // Fallback al estudiante solo
    
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $project_id = $row['project_id'];
        $stmt->close();
        
        // Obtener todos los miembros de ese proyecto
        $sql = "SELECT pm.user_id, u.nombre as member_name
                FROM project_members pm
                LEFT JOIN sis_user u ON pm.user_id = u.id
                WHERE pm.project_id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $project_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $members[] = [
                    'id' => $row['user_id'],
                    'name' => $row['member_name']
                ];
            }
            $stmt->close();
        }
    } else {
        $stmt->close();
    }
    
    // Si no encontró miembros, devolver solo el estudiante original
    if (empty($members)) {
        return [$student_id];
    }
    
    return $members;
}

if ($is_external_advisor) {
    try {
        $conn = new mysqli($db_host, $usuario, $clave, $db);
        $conn->set_charset("utf8");
        
        // Primero intentar con la nueva tabla de vinculaciones múltiples
        $sql = "SELECT eals.student_id as linked_student_id, u.nombre as student_name, eals.is_primary
                FROM external_advisor_linked_students eals
                INNER JOIN external_advisor_profile_requests ear ON eals.advisor_request_id = ear.id
                LEFT JOIN sis_user u ON eals.student_id = u.id
                WHERE ear.applicant_id = ? 
                AND ear.status = 'Aprobado'
                ORDER BY eals.is_primary DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $current_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $linked_students_list[] = [
                'id' => $row['linked_student_id'],
                'name' => $row['student_name'],
                'is_primary' => $row['is_primary']
            ];
            // El primero (is_primary DESC) será el estudiante principal
            if (empty($target_student_id) || $row['is_primary']) {
                $target_student_id = $row['linked_student_id'];
                $linked_student_name = $row['student_name'];
            }
        }
        $stmt->close();
        
        // Fallback: si no hay registros en nueva tabla, usar tabla anterior
        if (empty($linked_students_list)) {
            $sql = "SELECT ear.linked_student_id, u.nombre as student_name
                    FROM external_advisor_profile_requests ear
                    LEFT JOIN sis_user u ON ear.linked_student_id = u.id
                    WHERE ear.applicant_id = ? 
                    AND ear.status = 'Aprobado'
                    AND ear.linked_student_id IS NOT NULL
                    LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $current_user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $target_student_id = $row['linked_student_id'];
                $linked_student_name = $row['student_name'];
                $linked_students_list[] = [
                    'id' => $row['linked_student_id'],
                    'name' => $row['student_name'],
                    'is_primary' => 1
                ];
            }
            $stmt->close();
        }

        // También verificar vinculación como asesor interno de comité (rol 5 puede ser tutor)
        $stmt_int = $conn->prepare(
            "SELECT eals.student_id as linked_student_id, u.nombre as student_name, eals.is_primary
             FROM external_advisor_linked_students eals
             LEFT JOIN sis_user u ON eals.student_id = u.id
             WHERE eals.internal_advisor_id = ?
             ORDER BY eals.is_primary DESC, eals.linked_at DESC"
        );
        if ($stmt_int) {
            $stmt_int->bind_param("s", $current_user_id);
            $stmt_int->execute();
            $result_int = $stmt_int->get_result();
            while ($row = $result_int->fetch_assoc()) {
                $already = false;
                foreach ($linked_students_list as $ex) {
                    if ($ex['id'] === $row['linked_student_id']) { $already = true; break; }
                }
                if (!$already) {
                    $linked_students_list[] = [
                        'id'         => $row['linked_student_id'],
                        'name'       => $row['student_name'],
                        'is_primary' => $row['is_primary']
                    ];
                    if (empty($target_student_id) || $row['is_primary']) {
                        $target_student_id   = $row['linked_student_id'];
                        $linked_student_name = $row['student_name'];
                    }
                }
            }
            $stmt_int->close();
        }
        $conn->close();
    } catch (Exception $e) {
        error_log("Error obteniendo estudiante vinculado: " . $e->getMessage());
    }
    
    // Obtener todos los IDs para las consultas (para asesores)
    foreach ($linked_students_list as $student) {
        $group_member_ids[] = $student['id'];
    }
}

// Para asesores internos de comité (rol 3): buscar estudiantes vinculados por internal_advisor_id
if ($is_internal_advisor) {
    try {
        $conn = new mysqli($db_host, $usuario, $clave, $db);
        $conn->set_charset("utf8");

        $sql = "SELECT eals.student_id as linked_student_id, u.nombre as student_name, eals.is_primary
                FROM external_advisor_linked_students eals
                LEFT JOIN sis_user u ON eals.student_id = u.id
                WHERE eals.internal_advisor_id = ?
                ORDER BY eals.is_primary DESC, eals.linked_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $current_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $linked_students_list[] = [
                'id'         => $row['linked_student_id'],
                'name'       => $row['student_name'],
                'is_primary' => $row['is_primary']
            ];
            if (empty($target_student_id) || $row['is_primary']) {
                $target_student_id   = $row['linked_student_id'];
                $linked_student_name = $row['student_name'];
            }
        }
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        error_log("Error obteniendo estudiantes vinculados (asesor interno): " . $e->getMessage());
    }

    foreach ($linked_students_list as $student) {
        $group_member_ids[] = $student['id'];
    }
}

// Para estudiantes: obtener los miembros del grupo para mostrar documentos de todos
if ($is_student) {
    try {
        $conn = new mysqli($db_host, $usuario, $clave, $db);
        $conn->set_charset("utf8");
        
        $group_members = getGroupMembersForHistorial($conn, $current_user_id);
        
        if (is_array($group_members) && !empty($group_members)) {
            // Si devuelve array de arrays con 'id' y 'name'
            if (isset($group_members[0]['id'])) {
                foreach ($group_members as $member) {
                    $group_member_ids[] = $member['id'];
                    $linked_students_list[] = [
                        'id' => $member['id'],
                        'name' => $member['name'],
                        'is_primary' => ($member['id'] == $current_user_id) ? 1 : 0
                    ];
                }
            } else {
                // Si devuelve array simple de IDs
                $group_member_ids = $group_members;
            }
        }
        
        $conn->close();
    } catch (Exception $e) {
        error_log("Error obteniendo miembros del grupo: " . $e->getMessage());
    }
}

// Para estudiantes: obtener información de asesores asignados (externos e internos)
$assigned_advisors = [];
if ($is_student) {
    try {
        $conn = new mysqli($db_host, $usuario, $clave, $db);
        $conn->set_charset("utf8");
        
        $sql = "SELECT ear.applicant_id, ear.full_name as advisor_name, ear.email as advisor_email, 
                  ear.institution as institucion_procedencia,
                  ear.postulation_type,
                  ear.committee_role,
                  eals.linked_at
                FROM external_advisor_linked_students eals
                INNER JOIN external_advisor_profile_requests ear ON eals.advisor_request_id = ear.id
                WHERE eals.student_id = ? 
                AND ear.status = 'Aprobado'
                ORDER BY eals.linked_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $current_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $roleLabel = trim((string)($row['committee_role'] ?? ''));
            if ($roleLabel === '') {
                $roleLabel = trim((string)($row['postulation_type'] ?? 'Asesor'));
            }
            $assigned_advisors[] = [
                'advisor_name' => $row['advisor_name'],
                'advisor_email' => $row['advisor_email'],
                'institucion_procedencia' => $row['institucion_procedencia'] ?? '',
                'advisor_type' => $roleLabel
            ];
        }
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        error_log("Error obteniendo asesor asignado: " . $e->getMessage());
    }

    // Obtener asesor(es) interno(s) asignado(s) vía tabla de vinculaciones
    try {
        $conn = new mysqli($db_host, $usuario, $clave, $db);
        $conn->set_charset("utf8");

        $sql = "SELECT DISTINCT su.id as advisor_id, su.nombre as advisor_name, su.email as advisor_email
                FROM external_advisor_linked_students eals
                INNER JOIN sis_user su ON su.id = eals.internal_advisor_id
                WHERE eals.student_id = ?
                AND eals.internal_advisor_id IS NOT NULL
                ORDER BY su.nombre ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $current_user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $already = false;
            foreach ($assigned_advisors as $advisor) {
                if (($advisor['advisor_name'] ?? '') === ($row['advisor_name'] ?? '') && ($advisor['advisor_email'] ?? '') === ($row['advisor_email'] ?? '')) {
                    $already = true;
                    break;
                }
            }
            if ($already) {
                continue;
            }
            $assigned_advisors[] = [
                'advisor_name' => $row['advisor_name'],
                'advisor_email' => $row['advisor_email'] ?? '',
                'institucion_procedencia' => '',
                'advisor_type' => 'Asesor Interno'
            ];
        }

        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        error_log("Error obteniendo asesores internos asignados: " . $e->getMessage());
    }
}

// Si no hay miembros del grupo, usar solo el target_student_id
if (empty($group_member_ids)) {
    $group_member_ids = [$target_student_id];
}

// Función helper para construir cláusula IN con placeholders
function buildInClause($ids) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('s', count($ids));
    return ['placeholders' => $placeholders, 'types' => $types];
}

function formatoLegible($mime_type) {
    // Normalizar a minúsculas para comparaciones más fáciles
    $mime = strtolower($mime_type);
    
    // Extraer la parte después del slash si existe
    $parts = explode('/', $mime);
    $mime = isset($parts[1]) ? $parts[1] : $mime;
    
    // Mapeo de tipos comunes
    $tipos = [
        'pdf' => 'PDF',
        'vnd.openxmlformats-officedocument.wordprocessingml.document' => 'DOCX',
        'vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'XLSX',
        'vnd.openxmlformats-officedocument.presentationml.presentation' => 'PPTX',
        'msword' => 'DOC',
        'vnd.ms-excel' => 'XLS',
        'vnd.ms-powerpoint' => 'PPT',
        'plain' => 'TXT',
        'jpeg' => 'JPEG',
        'png' => 'PNG',
        'gif' => 'GIF',
        'zip' => 'ZIP',
        'x-rar-compressed' => 'RAR',
        'x-zip-compressed' => 'ZIP'
    ];
    
    // Comprobar si existe en el mapeo, o devolver formato original en mayúsculas
    return isset($tipos[$mime]) ? $tipos[$mime] : strtoupper($mime);
}

// =============================== OBTENER ID DE PROYECTO ===============================

// Buscar el project_id asociado al estudiante (propio o vinculado)
$project_id = 0;
try {
    $conn = new mysqli($db_host, $usuario, $clave, $db);
    $conn->set_charset("utf8");
    
    $in_clause = buildInClause($group_member_ids);
    $sql = "SELECT rp.id
            FROM registered_projects rp
            INNER JOIN tfg_proposals tp ON rp.tfg_proposal_id = tp.id
            WHERE tp.user_id IN ({$in_clause['placeholders']})
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($in_clause['types'], ...$group_member_ids);
    $stmt->execute();
    $stmt->bind_result($project_id);
    $stmt->fetch();
    $stmt->close();
} catch (Exception $e) {
    error_log("Error obteniendo project_id: " . $e->getMessage());
}

// =============================== OBTENER DOCUMENTOS PRINCIPALES ===============================
// Array para almacenar los documentos y sus versiones
$documentos = [];

// --- Propuesta TFG principal ---
try {
    $conn = new mysqli($db_host, $usuario, $clave, $db);
    $conn->set_charset("utf8");
    
    $in_clause = buildInClause($group_member_ids);
    $sql = "SELECT tp.id, tp.title, tp.file_name, tp.mime_type, tp.file_size, tp.status, tp.created_at, tp.user_id, u.nombre as uploaded_by_name
            FROM tfg_proposals tp 
            LEFT JOIN sis_user u ON tp.user_id = u.id
            WHERE tp.user_id IN ({$in_clause['placeholders']})
            ORDER BY tp.created_at DESC
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($in_clause['types'], ...$group_member_ids);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $row['tipo'] = 'Propuesta TFG'; // Identificador de tipo de documento
        $row['version'] = 1; // Versión inicial
        $row['_is_first_proposal'] = true;
        $documentos[] = $row;
    }
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    error_log("Error obteniendo propuesta TFG: " . $e->getMessage());
}

// Guardar el ID de la propuesta principal para excluirla de las versiones
$propuesta_principal_id = $documentos[0]['id'] ?? 0;

// Archivos adicionales del lote actual de la propuesta (mismo batch de subida)
if ($propuesta_principal_id > 0) {
    try {
        $conn = new mysqli($db_host, $usuario, $clave, $db);
        $conn->set_charset("utf8");
        $stmt_pf = $conn->prepare(
            "SELECT f.id, f.file_name, f.mime_type, f.file_size, f.version, f.document_type,
                    f.upload_date, f.uploaded_by, u.nombre as uploaded_by_name
             FROM tfg_files f
             LEFT JOIN sis_user u ON f.uploaded_by = u.id
             WHERE f.proposal_id = ?
             ORDER BY f.id ASC"
        );
        $stmt_pf->bind_param("i", $propuesta_principal_id);
        $stmt_pf->execute();
        $res_pf = $stmt_pf->get_result();
        while ($pf = $res_pf->fetch_assoc()) {
            $documentos[] = [
                'id'                 => $pf['id'],
                'title'              => $pf['file_name'],
                'file_name'          => $pf['file_name'],
                'mime_type'          => $pf['mime_type'],
                'file_size'          => $pf['file_size'],
                'status'             => $documentos[0]['status'] ?? '-',
                'created_at'         => $pf['upload_date'],
                'tipo'               => 'Propuesta TFG',
                'document_type'      => $pf['document_type'],
                'version'            => $pf['version'],
                'uploaded_by'        => $pf['uploaded_by'],
                'uploaded_by_name'   => $pf['uploaded_by_name'],
                '_is_first_proposal' => false,
            ];
        }
        $stmt_pf->close();
        $conn->close();
    } catch (Exception $e) {
        error_log("Error obteniendo archivos adicionales de propuesta: " . $e->getMessage());
    }
}

// =============================== OBTENER VERSIONES DE PROPUESTA TFG ===============================

// Versiones históricas de la propuesta TFG (excluyendo la principal)
$propuestas_vers = [];
try {
    $conn = new mysqli($db_host, $usuario, $clave, $db);
    $conn->set_charset("utf8");
    
    $in_clause = buildInClause($group_member_ids);
    // Consulta para ordenar de más antigua a más reciente
    $sql = "SELECT tp.id id_pro,tph.id, tph.proposal_id, tph.file_name, tph.mime_type, tph.file_size, 
                   tph.status, tph.comments, tph.created_at, tp.title, tp.user_id, u.nombre as uploaded_by_name
        FROM tfg_proposal_history tph
        INNER JOIN tfg_proposals tp ON tph.proposal_id = tp.id
        LEFT JOIN sis_user u ON tp.user_id = u.id
        WHERE tp.user_id IN ({$in_clause['placeholders']})
        AND tp.id != ?  /* Excluir la propuesta principal */
        ORDER BY tp.created_at DESC /* Orden de más antigua a más reciente */
        LIMIT 4"; // Limitamos a 5 versiones
    
    // Preparar tipos y parámetros
    $types = $in_clause['types'] . 'i';
    $params = array_merge($group_member_ids, [$propuesta_principal_id]);
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $version = 1; // Contador de versión
    while ($row = $result->fetch_assoc()) {
        $propuestas_vers[] = $row;
    }
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    error_log("Error obteniendo versiones de propuestas: " . $e->getMessage());
}

// Archivos adicionales de cada versión histórica de propuesta [id_pro => [files...]]
$propuestas_vers_files = [];
if (!empty($propuestas_vers)) {
    try {
        $conn = new mysqli($db_host, $usuario, $clave, $db);
        $conn->set_charset("utf8");
        $prop_ids_hist = array_unique(array_column($propuestas_vers, 'id_pro'));
        $placeholders_hist = implode(',', array_fill(0, count($prop_ids_hist), '?'));
        $types_hist = str_repeat('i', count($prop_ids_hist));
        $stmt_pf2 = $conn->prepare(
            "SELECT f.id, f.file_name, f.mime_type, f.file_size, f.version,
                    f.upload_date, f.uploaded_by, f.proposal_id, u.nombre as uploaded_by_name
             FROM tfg_files f
             LEFT JOIN sis_user u ON f.uploaded_by = u.id
             WHERE f.proposal_id IN ($placeholders_hist)
             ORDER BY f.proposal_id, f.id ASC"
        );
        $stmt_pf2->bind_param($types_hist, ...$prop_ids_hist);
        $stmt_pf2->execute();
        $res_pf2 = $stmt_pf2->get_result();
        while ($pf2 = $res_pf2->fetch_assoc()) {
            $propuestas_vers_files[$pf2['proposal_id']][] = $pf2;
        }
        $stmt_pf2->close();
        $conn->close();
    } catch (Exception $e) {
        error_log("Error obteniendo archivos adicionales de versiones históricas: " . $e->getMessage());
    }
}

// =============================== OBTENER DOCUMENTO FINAL DE TFG ===============================
// Todos los archivos de la entrega actual se muestran como hermanos (misma versión).
// Las versiones anteriores son las de rondas previas tras rechazos.
$doc_final_previous_versions = []; // [version_num => [files...]]

try {
    $conn = new mysqli($db_host, $usuario, $clave, $db);
    $conn->set_charset("utf8");

    // 1. Obtener el registro tfg_final_documents del grupo
    $in_clause = buildInClause($group_member_ids);
    $sql = "SELECT fd.id as doc_id, fd.proposal_id, fd.status, fd.project_status, fd.submitted_at
            FROM tfg_final_documents fd
            INNER JOIN tfg_proposals tp ON fd.proposal_id = tp.id
            WHERE tp.user_id IN ({$in_clause['placeholders']})
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($in_clause['types'], ...$group_member_ids);
    $stmt->execute();
    $doc_final_row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($doc_final_row) {
        $doc_id = $doc_final_row['doc_id'];

        // 2. Versión actual = la más alta subida para este documento
        $stmt_ver = $conn->prepare("SELECT MAX(version) as max_ver FROM tfg_files WHERE final_document_id = ?");
        $stmt_ver->bind_param("i", $doc_id);
        $stmt_ver->execute();
        $current_version = (float)$stmt_ver->get_result()->fetch_assoc()['max_ver'];
        $stmt_ver->close();

        // 3. Todos los archivos de la entrega actual (misma versión = mismo lote)
        $stmt_cur = $conn->prepare(
            "SELECT f.id, f.file_name, f.mime_type, f.file_size, f.version, f.document_type,
                    f.upload_date, f.uploaded_by, u.nombre as uploaded_by_name
             FROM tfg_files f
             LEFT JOIN sis_user u ON f.uploaded_by = u.id
             WHERE f.final_document_id = ? AND f.version = ?
             ORDER BY f.id ASC"
        );
        $stmt_cur->bind_param("id", $doc_id, $current_version);
        $stmt_cur->execute();
        $result_cur = $stmt_cur->get_result();
        $is_first = true;
        while ($file = $result_cur->fetch_assoc()) {
            $documentos[] = [
                'id'               => $file['id'],
                'title'            => $file['file_name'],
                'file_name'        => $file['file_name'],
                'mime_type'        => $file['mime_type'],
                'file_size'        => $file['file_size'],
                'status'           => $doc_final_row['status'],
                'created_at'       => $file['upload_date'],
                'tipo'             => 'Documento Final TFG',
                'document_type'    => 'Documento Final TFG',
                'version'          => $file['version'],
                'uploaded_by'      => $file['uploaded_by'],
                'uploaded_by_name' => $file['uploaded_by_name'],
                '_is_first_final'  => $is_first,
            ];
            $is_first = false;
        }
        $stmt_cur->close();

        // 4. Entregas anteriores (versiones previas = rondas tras rechazo), agrupadas por versión
        $stmt_prev = $conn->prepare(
            "SELECT f.id, f.file_name, f.mime_type, f.file_size, f.version,
                    f.upload_date, f.uploaded_by, u.nombre as uploaded_by_name
             FROM tfg_files f
             LEFT JOIN sis_user u ON f.uploaded_by = u.id
             WHERE f.final_document_id = ? AND f.version < ?
             ORDER BY f.version DESC, f.id ASC"
        );
        $stmt_prev->bind_param("id", $doc_id, $current_version);
        $stmt_prev->execute();
        $result_prev = $stmt_prev->get_result();
        while ($prev = $result_prev->fetch_assoc()) {
            $v = number_format((float)$prev['version'], 1);
            $doc_final_previous_versions[$v][] = $prev;
        }
        $stmt_prev->close();
    }

    $conn->close();
} catch (Exception $e) {
    error_log("Error obteniendo documentos finales: " . $e->getMessage());
}

// =============================== OBTENER OTROS DOCUMENTOS DEL USUARIO ===============================

// Otros archivos subidos por cualquier miembro del grupo (no propuestas ni documentos finales)
try {
    $conn = new mysqli($db_host, $usuario, $clave, $db);
    $conn->set_charset("utf8");
    
    $in_clause = buildInClause($group_member_ids);
    // Consulta para obtener otros tipos de documentos de cualquier miembro del grupo
    $sql = "SELECT f.id, f.file_name, f.mime_type, f.file_size, f.version, f.document_type, f.upload_date, f.uploaded_by, u.nombre as uploaded_by_name
            FROM tfg_files f
            LEFT JOIN sis_user u ON f.uploaded_by = u.id
            WHERE f.uploaded_by IN ({$in_clause['placeholders']})
            AND f.document_type NOT IN ('Propuesta TFG', 'Documento Final TFG')
            AND f.final_document_id IS NULL
            AND f.proposal_id IS NULL
            ORDER BY f.upload_date DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($in_clause['types'], ...$group_member_ids);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $doc = [
            'id' => $row['id'],
            'title' => $row['file_name'],
            'file_name' => $row['file_name'],
            'mime_type' => $row['mime_type'],
            'file_size' => $row['file_size'],
            'status' => '-', // Estado genérico
            'created_at' => $row['upload_date'],
            'tipo' => $row['document_type'],
            'document_type' => $row['document_type'],
            'version' => $row['version'] ?? 1.0,
            'uploaded_by' => $row['uploaded_by'],
            'uploaded_by_name' => $row['uploaded_by_name']
        ];
        $documentos[] = $doc;
    }
    
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    error_log("Error obteniendo otros documentos: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="es">
<!-- =============================== HEAD =============================== -->
<?php include 'head.php'; ?>
<body class="d-flex flex-column min-vh-100 fondo-una">
    <!-- =============================== HEADER =============================== -->
    <?php include 'header.php'; ?> 
    <!-- =============================== CONTENIDO PRINCIPAL =============================== -->
    <main class="flex-fill">
        <div class="container my-4">
            <h1 class="text-center mb-4">Historial de documentos</h1>
            
            <?php if ($is_advisor && !empty($linked_students_list)): ?>
            <!-- Banner informativo para asesores -->
            <div class="alert alert-info d-flex align-items-center mb-4" role="alert" style="font-size: 1.1rem;">
                <i class="bi bi-mortarboard-fill me-3" style="font-size: 1.5rem;"></i>
                <div>
                    <?php if (count($linked_students_list) > 1): ?>
                        <strong>Estudiantes asignados (Grupo TFG):</strong>
                        <ul class="mb-0 mt-1">
                        <?php foreach ($linked_students_list as $student): ?>
                            <li><?= htmlspecialchars($student['name']) ?><?= $student['is_primary'] ? ' <span class="badge bg-primary">Principal</span>' : '' ?></li>
                        <?php endforeach; ?>
                        </ul>
                        <small class="text-muted">Como asesor, puede ver los documentos de todos los estudiantes de su grupo asignado (solo lectura).</small>
                    <?php else: ?>
                        <strong>Visualizando documentos del estudiante:</strong> <?= htmlspecialchars($linked_student_name) ?>
                        <br><small class="text-muted">Como asesor, puede ver los documentos de su estudiante asignado (solo lectura).</small>
                    <?php endif; ?>
                </div>
            </div>
            <?php elseif ($is_advisor && empty($linked_students_list)): ?>
            <!-- Mensaje cuando no hay estudiante vinculado -->
            <div class="alert alert-warning d-flex align-items-center mb-4" role="alert" style="font-size: 1.1rem;">
                <i class="bi bi-exclamation-triangle-fill me-3" style="font-size: 1.5rem;"></i>
                <div>
                    <strong>Sin estudiante vinculado</strong>
                    <br><small>No tiene un estudiante asociado aprobado como asesor. Contacte a la Subdirección si cree que esto es un error.</small>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($is_student && count($linked_students_list) > 1): ?>
            <!-- Banner informativo para estudiante en grupo -->
            <div class="alert alert-info d-flex align-items-center mb-4" role="alert" style="font-size: 1.1rem;">
                <i class="bi bi-people-fill me-3" style="font-size: 1.5rem;"></i>
                <div>
                    <strong>Grupo TFG:</strong> Estás viendo los documentos de todo tu grupo.
                    <ul class="mb-0 mt-1">
                    <?php foreach ($linked_students_list as $student): ?>
                        <li><?= htmlspecialchars($student['name']) ?><?= ($student['id'] == $current_user_id) ? ' <span class="badge bg-success">Tú</span>' : '' ?></li>
                    <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($is_student && !empty($assigned_advisors)): ?>
            <!-- Banner informativo unificado de asesores asignados -->
            <div class="alert alert-success d-flex align-items-center mb-4" role="alert" style="font-size: 1.1rem;">
                <i class="bi bi-person-badge-fill me-3" style="font-size: 1.5rem;"></i>
                <div>
                    <strong>Asesores Asignados:</strong>
                    <ul class="mb-0 mt-1">
                    <?php foreach ($assigned_advisors as $advisor): ?>
                        <li>
                            <?= htmlspecialchars($advisor['advisor_name']) ?>
                            <small class="text-muted">(<?= htmlspecialchars($advisor['advisor_type']) ?>)</small>
                            <?php if (!empty($advisor['advisor_email'])): ?>
                                <small class="text-muted"> | <i class="bi bi-envelope"></i> <?= htmlspecialchars($advisor['advisor_email']) ?></small>
                            <?php endif; ?>
                            <?php if (!empty($advisor['institucion_procedencia'])): ?>
                                <small class="text-muted"> | <i class="bi bi-building"></i> <?= htmlspecialchars($advisor['institucion_procedencia']) ?></small>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table class="table table-bordered align-middle text-center">
                    <thead class="table-secondary table-responsive">
                        <tr>
                            <th>Documento</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <?php if (count($linked_students_list) > 1): ?>
                            <th>Subido por</th>
                            <?php endif; ?>
                            <th>Versión</th>
                            <th>Tamaño</th>
                            <th>Formato</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $colspan_value = count($linked_students_list) > 1 ? 8 : 7; ?>
                        <?php foreach ($documentos as $i => $doc): ?>
                            <?php $collapseId = 'versionesCollapse_' . $i; ?>
                            <!-- Fila principal -->
                            <tr>
                                <td data-label="Documento"><?= htmlspecialchars($doc['tipo']) ?>: <?= htmlspecialchars($doc['title'] ?? $doc['file_name']) ?></td>
                                <td data-label="Fecha"><?= date('d/m/Y', strtotime($doc['created_at'])) ?></td>
                                <td data-label="Estado"><?= htmlspecialchars($doc['status']) ?></td>
                                <?php if (count($linked_students_list) > 1): ?>
                                <td data-label="Subido por">
                                    <?php 
                                    $uploader_name = $doc['uploaded_by_name'] ?? '';
                                    $uploader_id = $doc['uploaded_by'] ?? $doc['user_id'] ?? '';
                                    if (!empty($uploader_name)) {
                                        echo htmlspecialchars($uploader_name);
                                        if ($uploader_id == $current_user_id) {
                                            echo ' <span class="badge bg-success">Tú</span>';
                                        }
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <?php endif; ?>
                                <td data-label="Versión">
                                    <?php if ($doc['tipo'] === 'Propuesta TFG' && !empty($doc['_is_first_proposal'])): ?>
                                        <?php $prop_ver_num = count($propuestas_vers) + 1; ?>
                                        <?= number_format($prop_ver_num, 1) ?>
                                        <?php if (!empty($propuestas_vers)): ?>
                                        <br><button class="btn btn-link-una toggle-collapse" type="button"
                                            data-target="<?= $collapseId ?>">
                                            Versiones
                                        </button>
                                        <?php endif; ?>
                                    <?php elseif ($doc['tipo'] === 'Documento Final TFG' && !empty($doc['_is_first_final'])): ?>
                                        <?= number_format($doc['version'], 1) ?>
                                        <?php if (!empty($doc_final_previous_versions)): ?>
                                        <br><button class="btn btn-link-una toggle-collapse" type="button"
                                            data-target="finalVersionCollapse">
                                            Versiones
                                        </button>
                                        <?php endif; ?>
                                    <?php elseif (isset($doc['version'])): ?>
                                        <?= number_format($doc['version'], 1) ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td data-label="Tamaño"><?= number_format($doc['file_size'] / (1024 * 1024), 2) ?> MB</td>
                                <td data-label="Formato"><?= formatoLegible($doc['mime_type']) ?></td>
                                <td data-label="Acción">
                                    <?php if ($doc['tipo'] === 'Propuesta TFG' && !empty($doc['_is_first_proposal'])): ?>  
                                        <a href="<?= $base_url . 'mod/admin/users/tfg_download.php?id=' . $doc['id'] ?>" class="btn-link-una">
                                            <i class="bi bi-download me-1"></i>
                                            <span>Descargar</span>
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= $base_url . 'mod/admin/users/tfg_download_file.php?id=' . $doc['id'] ?>" class="btn-link-una">
                                            <i class="bi bi-download me-1"></i>
                                            <span>Descargar</span>    
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            
                            <!-- Collapse container para mostrar versiones -->
                            <?php 
                            $colspan_value = count($linked_students_list) > 1 ? 8 : 7;
                            ?>
                            <?php if ($doc['tipo'] === 'Propuesta TFG' && !empty($doc['_is_first_proposal']) && !empty($propuestas_vers)): ?>
                            <tr class="collapse-row">
                                <td colspan="<?= $colspan_value ?>" class="p-0">
                                    <div class="custom-collapse" id="<?= $collapseId ?>" style="display: none;">
                                        <div class="bg-light py-2 px-4">
                                            <?php
                                            $num_prop_vers = count($propuestas_vers);
                                            foreach ($propuestas_vers as $k => $version):
                                                $ver_num = number_format($num_prop_vers - $k, 1);
                                            ?>
                                            <h6 class="mt-2 mb-1 text-muted">
                                                Entrega v<?= $ver_num ?> &mdash; <?= date('d/m/Y', strtotime($version['created_at'])) ?>
                                            </h6>
                                            <table class="table table-bordered mb-2 version-table">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Nombre</th>
                                                        <th>Fecha</th>
                                                        <th>Estado</th>
                                                        <?php if (count($linked_students_list) > 1): ?>
                                                        <th>Subido por</th>
                                                        <?php endif; ?>
                                                        <th>Versión</th>
                                                        <th>Tamaño</th>
                                                        <th>Formato</th>
                                                        <th>Acción</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><?= htmlspecialchars($version['title']) ?></td>
                                                        <td><?= date('d/m/Y', strtotime($version['created_at'])) ?></td>
                                                        <td><?= htmlspecialchars($version['status']) ?></td>
                                                        <?php if (count($linked_students_list) > 1): ?>
                                                        <td>
                                                            <?php
                                                            $v_uploader = $version['uploaded_by_name'] ?? '';
                                                            $v_uploader_id = $version['user_id'] ?? '';
                                                            echo !empty($v_uploader) ? htmlspecialchars($v_uploader) : '-';
                                                            if ($v_uploader_id == $current_user_id) echo ' <span class="badge bg-success">Tú</span>';
                                                            ?>
                                                        </td>
                                                        <?php endif; ?>
                                                        <td class="text-center"><?= $ver_num ?></td>
                                                        <td><?= number_format($version['file_size'] / (1024 * 1024), 2) ?> MB</td>
                                                        <td><?= formatoLegible($version['mime_type']) ?></td>
                                                        <td>
                                                            <a href="<?= $base_url . 'mod/admin/users/tfg_download.php?id=' . $version['id_pro'] ?>" class="btn-link-una">
                                                                <i class="bi bi-download me-1"></i>
                                                                <span>Descargar</span>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <?php foreach ($propuestas_vers_files[$version['id_pro']] ?? [] as $pf_hist): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($pf_hist['file_name']) ?></td>
                                                        <td><?= date('d/m/Y', strtotime($pf_hist['upload_date'])) ?></td>
                                                        <td><?= htmlspecialchars($version['status']) ?></td>
                                                        <?php if (count($linked_students_list) > 1): ?>
                                                        <td>
                                                            <?php
                                                            echo !empty($pf_hist['uploaded_by_name']) ? htmlspecialchars($pf_hist['uploaded_by_name']) : '-';
                                                            if ($pf_hist['uploaded_by'] == $current_user_id) echo ' <span class="badge bg-success">Tú</span>';
                                                            ?>
                                                        </td>
                                                        <?php endif; ?>
                                                        <td class="text-center"><?= $ver_num ?></td>
                                                        <td><?= number_format($pf_hist['file_size'] / (1024 * 1024), 2) ?> MB</td>
                                                        <td><?= formatoLegible($pf_hist['mime_type']) ?></td>
                                                        <td>
                                                            <a href="<?= $base_url . 'mod/admin/users/tfg_download_file.php?id=' . $pf_hist['id'] ?>" class="btn-link-una">
                                                                <i class="bi bi-download me-1"></i>
                                                                <span>Descargar</span>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php elseif ($doc['tipo'] === 'Documento Final TFG' && !empty($doc['_is_first_final']) && !empty($doc_final_previous_versions)): ?>
                            <tr class="collapse-row">
                                <td colspan="<?= $colspan_value ?>" class="p-0">
                                    <div class="custom-collapse" id="finalVersionCollapse" style="display: none;">
                                        <div class="bg-light py-2 px-4">
                                            <?php foreach ($doc_final_previous_versions as $ver_num => $ver_files): ?>
                                            <h6 class="mt-2 mb-1 text-muted">
                                                Entrega v<?= $ver_num ?> &mdash; <?= date('d/m/Y', strtotime($ver_files[0]['upload_date'])) ?>
                                            </h6>
                                            <table class="table table-bordered mb-2 version-table">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Nombre</th>
                                                        <?php if (count($linked_students_list) > 1): ?>
                                                        <th>Subido por</th>
                                                        <?php endif; ?>
                                                        <th>Versión</th>
                                                        <th>Tamaño</th>
                                                        <th>Formato</th>
                                                        <th>Acción</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($ver_files as $vf): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($vf['file_name']) ?></td>
                                                        <?php if (count($linked_students_list) > 1): ?>
                                                        <td>
                                                            <?php
                                                            echo !empty($vf['uploaded_by_name']) ? htmlspecialchars($vf['uploaded_by_name']) : '-';
                                                            if ($vf['uploaded_by'] == $current_user_id) echo ' <span class="badge bg-success">Tú</span>';
                                                            ?>
                                                        </td>
                                                        <?php endif; ?>
                                                        <td><?= $ver_num ?></td>
                                                        <td><?= number_format($vf['file_size'] / (1024 * 1024), 2) ?> MB</td>
                                                        <td><?= formatoLegible($vf['mime_type']) ?></td>
                                                        <td>
                                                            <a href="<?= $base_url . 'mod/admin/users/tfg_download_file.php?id=' . $vf['id'] ?>" class="btn-link-una">
                                                                <i class="bi bi-download me-1"></i>
                                                                <span>Descargar</span>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if (empty($documentos)): ?>
                            <tr>
                                <td colspan="<?= $colspan_value ?>" class="text-center text-muted">No se han encontrado documentos.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-center mt-4">
                <a href="Panel_SubirTFG.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left-circle"></i> Volver al Panel Principal
                </a>
            </div>
        </div>
    </main>
  <!-- =============================== FOOTER =============================== -->
    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.toggle-collapse').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var targetId = this.getAttribute('data-target');
                var target = document.getElementById(targetId);
                if (target) {
                    target.style.display = (target.style.display === 'none' || target.style.display === '') ? 'block' : 'none';
                }
            });
        });
    });
    </script>
</body>
</html>