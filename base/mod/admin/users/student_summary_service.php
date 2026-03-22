<?php
require_once __DIR__ . '/student_summary_queries.php';

/**
 * Construye el resumen consolidado de un estudiante.
 */
function buildStudentSummary(mysqli $conn, int $studentId): array
{
    $studentIdStr = (string)$studentId;

    $summary = [
        "student" => null,
        "proposals" => [],
        "project" => null,
        "timeline" => null,
        "documents" => [],
        "reviews" => [],
        "legacy" => [
            "approved_projects" => [],
            "notes" => [],
            "cancellations" => [],
            "acta_context" => []
        ],
        "important_dates" => []
    ];

    /* ============================
       DATOS BÁSICOS DEL ESTUDIANTE
       ============================ */
    $student = findStudentBasicInfo($conn, $studentIdStr);

    if (!$student) {
        throw new Exception("El estudiante no existe.");
    }

    $summary["student"] = $student;

    /* ============================
       PROPUESTAS
       ============================ */
    $proposals = findStudentProposalSummaries($conn, $studentIdStr);
    $summary["proposals"] = $proposals;

    $mainProposal = $proposals[0] ?? null;
    $proposalId = isset($mainProposal["proposal_id"]) ? (int)$mainProposal["proposal_id"] : 0;
    $registeredProjectId = isset($mainProposal["registered_project_id"]) ? (int)$mainProposal["registered_project_id"] : 0;

    /* ============================
       PROYECTO REGISTRADO
       ============================ */
    if ($registeredProjectId > 0) {
        $project = ssq_fetch_one(
            $conn,
            "SELECT * FROM registered_projects WHERE id = ?",
            "i",
            [$registeredProjectId]
        );

        if ($project) {
            $summary["project"] = $project;
            $summary["project"]["members"] = findProjectMembersByProjectId($conn, $registeredProjectId);
        }
    } elseif ($proposalId > 0) {
        $project = ssq_fetch_one(
            $conn,
            "SELECT * FROM registered_projects WHERE tfg_proposal_id = ?",
            "i",
            [$proposalId]
        );

        if ($project) {
            $summary["project"] = $project;
            $summary["project"]["members"] = findProjectMembersByProjectId($conn, (int)$project["id"]);
        }
    }

    /* ============================
       TIMELINE
       ============================ */
    if ($proposalId > 0) {
        $summary["timeline"] = findProposalTimeline($conn, $proposalId);
    }

    /* ============================
       DOCUMENTOS FINALES
       ============================ */
    $documents = findStudentFinalDocuments($conn, $studentIdStr);

    foreach ($documents as &$doc) {
        $finalDocumentId = isset($doc["final_document_id"]) ? (int)$doc["final_document_id"] : 0;
        $doc["additional_files"] = $finalDocumentId > 0
            ? findAdditionalFinalDocumentFiles($conn, $finalDocumentId)
            : [];
    }
    unset($doc);

    $summary["documents"] = $documents;

    /* ============================
       REVISIONES
       ============================ */
    $summary["reviews"] = findStudentDocumentReviews($conn, $studentIdStr);

    /* ============================
       LEGACY
       ============================ */
    $summary["legacy"]["approved_projects"] = findStudentLegacyApprovedProjects($conn, $studentIdStr);
    $summary["legacy"]["notes"] = findLegacyProjectNotesByStudent($conn, $studentIdStr);
    $summary["legacy"]["cancellations"] = findStudentCancellationAgreements($conn, $studentIdStr);
    $summary["legacy"]["acta_context"] = findStudentActaContext($conn, $studentIdStr);

    /* ============================
       FECHAS IMPORTANTES
       ============================ */
    $dates = [];

    foreach ($summary["proposals"] as $p) {
        if (!empty($p["created_at"])) {
            $dates[] = [
                "type" => "Creación de propuesta",
                "date" => $p["created_at"]
            ];
        }

        if (!empty($p["reviewed_at"])) {
            $dates[] = [
                "type" => "Revisión de propuesta",
                "date" => $p["reviewed_at"]
            ];
        }
    }

    if (!empty($summary["timeline"])) {
        $tl = $summary["timeline"];

        if (!empty($tl["approval_date"])) {
            $dates[] = [
                "type" => "Aprobación del proyecto",
                "date" => $tl["approval_date"]
            ];
        }

        if (!empty($tl["original_deadline"])) {
            $dates[] = [
                "type" => "Fecha límite original",
                "date" => $tl["original_deadline"]
            ];
        }

        if (!empty($tl["last_updated"])) {
            $dates[] = [
                "type" => "Última actualización del estado del proyecto",
                "date" => $tl["last_updated"]
            ];
        }
    }

    foreach ($summary["documents"] as $doc) {
        if (!empty($doc["submitted_at"])) {
            $dates[] = [
                "type" => "Entrega de documento final",
                "date" => $doc["submitted_at"]
            ];
        }

        if (!empty($doc["uploaded_at"])) {
            $dates[] = [
                "type" => "Archivo subido",
                "date" => $doc["uploaded_at"]
            ];
        }
    }

    foreach ($summary["reviews"] as $rev) {
        if (!empty($rev["reviewed_at"])) {
            $dates[] = [
                "type" => "Revisión de documento",
                "date" => $rev["reviewed_at"]
            ];
        }
    }

    foreach ($summary["legacy"]["notes"] as $note) {
        if (!empty($note["creado_en"])) {
            $dates[] = [
                "type" => "Observación / nota del proyecto",
                "date" => $note["creado_en"]
            ];
        }
    }

    foreach ($summary["legacy"]["approved_projects"] as $legacyProject) {
        if (!empty($legacyProject["fecha_creacion"])) {
            $dates[] = [
                "type" => "Registro de proyecto aprobado",
                "date" => $legacyProject["fecha_creacion"]
            ];
        }

        if (!empty($legacyProject["fecha_finalizacion"])) {
            $dates[] = [
                "type" => "Fecha finalización del proyecto",
                "date" => $legacyProject["fecha_finalizacion"]
            ];
        }

        if (!empty($legacyProject["fecha_ultimo_avance"])) {
            $dates[] = [
                "type" => "Último avance registrado",
                "date" => $legacyProject["fecha_ultimo_avance"]
            ];
        }
    }

    foreach ($summary["legacy"]["cancellations"] as $c) {
        if (!empty($c["fecha_cancelacion"])) {
            $dates[] = [
                "type" => "Cancelación del proyecto",
                "date" => $c["fecha_cancelacion"]
            ];
        }
    }

    usort($dates, function ($a, $b) {
        return strtotime($a["date"]) <=> strtotime($b["date"]);
    });

    $summary["important_dates"] = $dates;

    return $summary;
}