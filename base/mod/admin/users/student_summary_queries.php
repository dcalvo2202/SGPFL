<?php
declare(strict_types=1);

/**
 * Consultas SQL para HU-036: resumen consolidado por estudiante.
 *
 * Responsabilidad única de este archivo:
 * - exponer funciones de lectura a BD
 * - no aplicar reglas de negocio complejas
 * - no renderizar HTML/PDF
 * - no validar sesión/roles
 *
 * Todas las funciones reciben una conexión mysqli abierta.
 */

/**
 * Ejecuta una consulta preparada y retorna todas las filas.
 *
 * @param mysqli $conn
 * @param string $sql
 * @param string $types
 * @param array<int, mixed> $params
 * @return array<int, array<string, mixed>>
 */
function ssq_fetch_all(mysqli $conn, string $sql, string $types = '', array $params = []): array
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Error preparando consulta: ' . $conn->error);
    }

    if ($types !== '' && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new RuntimeException('Error ejecutando consulta: ' . $error);
    }

    $result = $stmt->get_result();
    $rows = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }

    $stmt->close();
    return $rows;
}

/**
 * Ejecuta una consulta preparada y retorna una sola fila o null.
 *
 * @param mysqli $conn
 * @param string $sql
 * @param string $types
 * @param array<int, mixed> $params
 * @return array<string, mixed>|null
 */
function ssq_fetch_one(mysqli $conn, string $sql, string $types = '', array $params = []): ?array
{
    $rows = ssq_fetch_all($conn, $sql, $types, $params);
    return $rows[0] ?? null;
}

/**
 * Información base del estudiante.
 *
 * @return array<string, mixed>|null
 */
function findStudentBasicInfo(mysqli $conn, string $studentId): ?array
{
    $sql = "SELECT
                u.id,
                u.nombre,
                u.email,
                r.roll_name
            FROM sis_user u
            INNER JOIN sis_login l ON l.id = u.id
            INNER JOIN sis_rolls r ON r.id_roll = l.id_roll
            WHERE u.id = ?
              AND l.id_roll = 4
            LIMIT 1";

    return ssq_fetch_one($conn, $sql, 's', [$studentId]);
}

/**
 * Propuestas TFG asociadas al estudiante.
 * Incluye propuestas creadas por el estudiante y propuestas donde figura
 * como miembro activo del proyecto registrado.
 *
 * @return array<int, array<string, mixed>>
 */
function findStudentProposalSummaries(mysqli $conn, string $studentId): array
{
    $sql = "SELECT DISTINCT
                tp.id AS proposal_id,
                tp.user_id AS proposal_owner_id,
                owner.nombre AS proposal_owner_name,
                tp.title,
                tp.disciplines,
                tp.status AS proposal_status,
                tp.reviewed_by,
                tp.reviewed_at,
                tp.created_at,
                tp.updated_at,
                CASE
                    WHEN tp.user_id = ? THEN 'Propietario'
                    WHEN pm.user_id IS NOT NULL THEN 'Miembro'
                    ELSE 'Relacionado'
                END AS relationship_to_student,
                rp.id AS registered_project_id,
                pt.type_name AS project_type_name,
                rp.status AS registered_project_status,
                rp.start_date,
                rp.end_date,
                rp.final_grade,
                supervisor.nombre AS supervisor_name
            FROM tfg_proposals tp
            INNER JOIN sis_user owner ON owner.id = tp.user_id
            LEFT JOIN registered_projects rp ON rp.tfg_proposal_id = tp.id
            LEFT JOIN project_types pt ON pt.id = rp.project_type_id
            LEFT JOIN sis_user supervisor ON supervisor.id = rp.supervisor_id
            LEFT JOIN project_members pm
                   ON pm.project_id = rp.id
                  AND pm.user_id = ?
                  AND pm.status = 'Activo'
            WHERE tp.user_id = ?
               OR pm.user_id = ?
            ORDER BY tp.created_at DESC, tp.id DESC";

    return ssq_fetch_all($conn, $sql, 'ssss', [$studentId, $studentId, $studentId, $studentId]);
}

/**
 * Miembros de un proyecto registrado.
 *
 * @return array<int, array<string, mixed>>
 */
function findProjectMembersByProjectId(mysqli $conn, int $projectId): array
{
    $sql = "SELECT
                pm.id,
                pm.project_id,
                pm.user_id,
                u.nombre AS user_name,
                u.email AS user_email,
                pm.role,
                pm.status,
                pm.joined_at,
                pm.left_at
            FROM project_members pm
            INNER JOIN sis_user u ON u.id = pm.user_id
            WHERE pm.project_id = ?
            ORDER BY
                CASE pm.role
                    WHEN 'Líder' THEN 1
                    ELSE 2
                END,
                pm.joined_at ASC,
                u.nombre ASC";

    return ssq_fetch_all($conn, $sql, 'i', [$projectId]);
}

/**
 * Timeline / fechas clave del proyecto asociadas a la propuesta.
 *
 * @return array<string, mixed>|null
 */
function findProposalTimeline(mysqli $conn, int $proposalId): ?array
{
    $sql = "SELECT
                id,
                proposal_id,
                approval_date,
                original_deadline,
                status,
                days_remaining,
                last_updated
            FROM tfg_project_timeline
            WHERE proposal_id = ?
            LIMIT 1";

    return ssq_fetch_one($conn, $sql, 'i', [$proposalId]);
}

/**
 * Documentos finales asociados a una propuesta y estudiante.
 * Incluye documento principal y metadatos del archivo actual.
 *
 * @return array<int, array<string, mixed>>
 */
function findStudentFinalDocuments(mysqli $conn, string $studentId): array
{
    $sql = "SELECT
                fd.id AS final_document_id,
                fd.proposal_id,
                tp.title AS proposal_title,
                fd.file_id,
                fd.submitted_by,
                submitter.nombre AS submitted_by_name,
                fd.status,
                fd.project_status,
                fd.submitted_at,
                f.file_name,
                f.mime_type,
                f.file_size,
                f.document_type,
                f.version,
                f.uploaded_by,
                f.upload_date AS uploaded_at
            FROM tfg_final_documents fd
            INNER JOIN tfg_proposals tp ON tp.id = fd.proposal_id
            INNER JOIN sis_user submitter ON submitter.id = fd.submitted_by
            INNER JOIN tfg_files f ON f.id = fd.file_id
            LEFT JOIN registered_projects rp ON rp.tfg_proposal_id = tp.id
            LEFT JOIN project_members pm
                   ON pm.project_id = rp.id
                  AND pm.user_id = ?
                  AND pm.status = 'Activo'
            WHERE fd.submitted_by = ?
               OR tp.user_id = ?
               OR pm.user_id = ?
            ORDER BY fd.submitted_at DESC, fd.id DESC";

    return ssq_fetch_all($conn, $sql, 'ssss', [$studentId, $studentId, $studentId, $studentId]);
}

/**
 * Historial de revisiones de documentos finales del estudiante.
 *
 * @return array<int, array<string, mixed>>
 */
function findStudentDocumentReviews(mysqli $conn, string $studentId): array
{
    $sql = "SELECT
                dr.id AS review_id,
                dr.document_id,
                dr.file_version,
                dr.reviewer_id,
                reviewer.nombre AS reviewer_name,
                dr.review_type,
                dr.status,
                dr.corrections_summary,
                dr.corrections_count,
                dr.reviewed_at,
                fd.proposal_id,
                tp.title AS proposal_title,
                fd.submitted_by,
                submitter.nombre AS submitted_by_name
            FROM tfg_document_reviews dr
            INNER JOIN tfg_final_documents fd ON fd.id = dr.document_id
            INNER JOIN tfg_proposals tp ON tp.id = fd.proposal_id
            INNER JOIN sis_user submitter ON submitter.id = fd.submitted_by
            LEFT JOIN sis_user reviewer ON reviewer.id = dr.reviewer_id
            LEFT JOIN registered_projects rp ON rp.tfg_proposal_id = tp.id
            LEFT JOIN project_members pm
                   ON pm.project_id = rp.id
                  AND pm.status = 'Activo'
            WHERE fd.submitted_by = ?
               OR tp.user_id = ?
               OR pm.user_id = ?
            ORDER BY dr.reviewed_at DESC, dr.id DESC";

    return ssq_fetch_all($conn, $sql, 'sss', [$studentId, $studentId, $studentId]);
}

/**
 * Datos del proyecto aprobado legacy vinculados al estudiante.
 *
 * @return array<int, array<string, mixed>>
 */
function findStudentLegacyApprovedProjects(mysqli $conn, string $studentId): array
{
    $sql = "SELECT
                pa.id_aprobado,
                pa.identificador,
                pa.nombre,
                pa.proposal_id,
                pa.comite_id,
                pa.aprobado,
                pa.fecha_creacion,
                pa.fecha_finalizacion,
                pa.fecha_ultimo_avance,
                pa.estado,
                c.tutor,
                tutor.nombre AS tutor_name,
                c.asesor_1,
                asesor1.nombre AS asesor_1_name,
                c.asesor_2,
                asesor2.nombre AS asesor_2_name,
                pae.estudiante_id,
                su.nombre AS student_name
            FROM proyecto_aprobado_estudiantes pae
            INNER JOIN proyecto_aprobado pa ON pa.id_aprobado = pae.id_aprobado
            LEFT JOIN comite c ON c.Id = pa.comite_id
            LEFT JOIN sis_user tutor ON tutor.id = c.tutor
            LEFT JOIN sis_user asesor1 ON asesor1.id = c.asesor_1
            LEFT JOIN sis_user asesor2 ON asesor2.id = c.asesor_2
            LEFT JOIN sis_user su ON su.id = pae.estudiante_id
            WHERE pae.estudiante_id = ?
            ORDER BY pa.fecha_creacion DESC, pa.id_aprobado DESC";

    return ssq_fetch_all($conn, $sql, 's', [$studentId]);
}

/**
 * Notas legacy del proyecto aprobado.
 *
 * @return array<int, array<string, mixed>>
 */
function findLegacyProjectNotesByStudent(mysqli $conn, string $studentId): array
{
    $sql = "SELECT
                pn.id_nota,
                pn.proyecto_id,
                pn.titulo,
                pn.notas,
                pn.creado_por,
                creator.nombre AS creado_por_nombre,
                pn.creado_en,
                pn.etapa_proyecto,
                pa.identificador,
                pa.nombre AS project_name,
                pae.estudiante_id
            FROM proyecto_notas pn
            INNER JOIN proyecto_aprobado pa ON pa.id_aprobado = pn.proyecto_id
            INNER JOIN proyecto_aprobado_estudiantes pae ON pae.id_aprobado = pa.id_aprobado
            LEFT JOIN sis_user creator ON creator.id = pn.creado_por
            WHERE pae.estudiante_id = ?
            ORDER BY pn.creado_en DESC, pn.id_nota DESC";

    return ssq_fetch_all($conn, $sql, 's', [$studentId]);
}

/**
 * Acuerdo de cancelación legacy asociado al estudiante.
 *
 * @return array<int, array<string, mixed>>
 */
function findStudentCancellationAgreements(mysqli $conn, string $studentId): array
{
    $sql = "SELECT
                ac.id,
                ac.proyecto_id,
                ac.usuario_id,
                cancel_user.nombre AS usuario_nombre,
                ac.motivo,
                ac.observaciones,
                ac.fecha_cancelacion,
                ac.fecha_ultimo_avance_usada,
                pa.identificador,
                pa.nombre AS project_name,
                pa.estado,
                pae.estudiante_id
            FROM acuerdo_cancelacion ac
            INNER JOIN proyecto_aprobado pa ON pa.id_aprobado = ac.proyecto_id
            INNER JOIN proyecto_aprobado_estudiantes pae ON pae.id_aprobado = pa.id_aprobado
            LEFT JOIN sis_user cancel_user ON cancel_user.id = ac.usuario_id
            WHERE pae.estudiante_id = ?
            ORDER BY ac.fecha_cancelacion DESC, ac.id DESC";

    return ssq_fetch_all($conn, $sql, 's', [$studentId]);
}

/**
 * Devuelve las propuestas relacionadas con actas existentes en el filesystem.
 * No enlaza archivos físicos; solo deja preparados los identificadores de negocio.
 *
 * @return array<int, array<string, mixed>>
 */
function findStudentActaContext(mysqli $conn, string $studentId): array
{
    $sql = "SELECT
                pae.estudiante_id,
                pa.id_aprobado,
                pa.identificador,
                pa.nombre AS project_name,
                pa.fecha_creacion,
                pa.estado,
                pa.proposal_id,
                tp.title AS proposal_title,
                rp.id AS registered_project_id,
                rp.final_grade
            FROM proyecto_aprobado_estudiantes pae
            INNER JOIN proyecto_aprobado pa ON pa.id_aprobado = pae.id_aprobado
            LEFT JOIN tfg_proposals tp ON tp.id = pa.proposal_id
            LEFT JOIN registered_projects rp ON rp.tfg_proposal_id = pa.proposal_id
            WHERE pae.estudiante_id = ?
            ORDER BY pa.fecha_creacion DESC, pa.id_aprobado DESC";

    return ssq_fetch_all($conn, $sql, 's', [$studentId]);
}

/**
 * Archivos adicionales del documento final ligados al documento principal.
 *
 * @return array<int, array<string, mixed>>
 */
function findAdditionalFinalDocumentFiles(mysqli $conn, int $finalDocumentId): array
{
    return [];
}
