<?php
/**
 * HU-041: Reglas de agrupación y validación de comités asesores.
 *
 * Este archivo contiene lógica reutilizable y testeable.
 * No debe renderizar HTML, leer $_POST, iniciar sesión ni redireccionar.
 */

/**
 * Consulta solicitudes aprobadas y pendientes de vincular a un comité.
 *
 * @param mysqli $conn Conexión activa a la base de datos.
 * @return array<int, array<string, mixed>>
 */
function hu041_fetch_pending_committee_requests(mysqli $conn): array
{
    $sql = "SELECT ear.id,
                   ear.applicant_id,
                   ear.full_name,
                   ear.committee_role,
                   ear.postulation_type,
                   ear.linked_student_id,
                   ear.created_at,
                   su.nombre AS linked_student_name
            FROM external_advisor_profile_requests ear
            LEFT JOIN sis_user su ON su.id = ear.linked_student_id
            WHERE ear.status = 'Aprobado'
              AND ear.linked_comite_id IS NULL
              AND ear.linked_student_id IS NOT NULL
              AND ear.linked_student_id <> ''
            ORDER BY ear.linked_student_id ASC, ear.created_at ASC";

    $result = $conn->query($sql);
    $rows = [];

    while ($result && ($row = $result->fetch_assoc())) {
        $rows[] = $row;
    }

    return hu041_group_committee_requests($rows);
}

/**
 * Agrupa solicitudes aprobadas por estudiante.
 *
 * @param array<int, array<string, mixed>> $rows
 * @return array<int, array<string, mixed>>
 */
function hu041_group_committee_requests(array $rows): array
{
    $groups = [];

    foreach ($rows as $row) {
        $studentId = trim((string)($row['linked_student_id'] ?? ''));

        if ($studentId === '') {
            continue;
        }

        if (!isset($groups[$studentId])) {
            $groups[$studentId] = [
                'student_id' => $studentId,
                'student_name' => (string)($row['linked_student_name'] ?? ''),
                'roles' => [
                    'Tutor' => [],
                    'Asesor 1' => [],
                    'Asesor 2' => [],
                ],
                'all' => [],
            ];
        }

        $role = trim((string)($row['committee_role'] ?? ''));

        if (!isset($groups[$studentId]['roles'][$role])) {
            $groups[$studentId]['roles'][$role] = [];
        }

        $groups[$studentId]['roles'][$role][] = $row;
        $groups[$studentId]['all'][] = $row;
    }

    $pending = [];

    foreach ($groups as $group) {
        $pending[] = hu041_evaluate_committee_group($group);
    }

    return $pending;
}

/**
 * Evalúa si un grupo de solicitudes forma un comité válido.
 *
 * Reglas:
 * - Debe existir exactamente un Tutor.
 * - Debe existir exactamente un Asesor 1.
 * - Debe existir exactamente un Asesor 2.
 * - La misma persona no puede ocupar más de un rol.
 *
 * @param array<string, mixed> $group
 * @return array<string, mixed>
 */
function hu041_evaluate_committee_group(array $group): array
{
    $tutores = $group['roles']['Tutor'] ?? [];
    $asesor1 = $group['roles']['Asesor 1'] ?? [];
    $asesor2 = $group['roles']['Asesor 2'] ?? [];

    $isComplete = count($tutores) === 1
        && count($asesor1) === 1
        && count($asesor2) === 1;

    $ids = [];

    if ($isComplete) {
        $ids = [
            (string)$tutores[0]['applicant_id'],
            (string)$asesor1[0]['applicant_id'],
            (string)$asesor2[0]['applicant_id'],
        ];
    }

    $isUniquePeople = $isComplete && count(array_unique($ids)) === 3;

    $group['can_approve'] = $isComplete && $isUniquePeople;
    $group['validation_message'] = '';

    if (!$isComplete) {
        $group['validation_message'] = 'Falta completar el comité: debe tener un Tutor, un Asesor 1 y un Asesor 2.';
    } elseif (!$isUniquePeople) {
        $group['validation_message'] = 'Una misma persona no puede ocupar más de un rol dentro del mismo comité.';
    }

    return $group;
}

/**
 * Busca un grupo de solicitudes por estudiante.
 *
 * @param array<int, array<string, mixed>> $pendingGroups
 */
function hu041_get_group_by_student(array $pendingGroups, string $studentId): ?array
{
    foreach ($pendingGroups as $group) {
        if ((string)$group['student_id'] === $studentId) {
            return $group;
        }
    }

    return null;
}

/**
 * Valida la entrada del endpoint procesar_decision_asesor.php.
 *
 * @throws Exception
 */
function hu041_validate_advisor_decision_input(int $id, string $decision, string $comentarios): void
{
    if ($id <= 0) {
        throw new Exception('ID de solicitud inválido.');
    }

    if (!in_array($decision, ['Aprobado', 'Rechazado'], true)) {
        throw new Exception('Decisión inválida.');
    }

    if ($decision === 'Rechazado' && mb_strlen(trim($comentarios)) < 10) {
        throw new Exception('Debe especificar un motivo de rechazo (mínimo 10 caracteres).');
    }
}

/**
 * Indica si una solicitud sigue pendiente de revisión.
 */
function hu041_is_request_pending_status(?string $status): bool
{
    return in_array(trim((string)$status), ['En Revision', 'En Revisión'], true);
}

/**
 * Valida que una solicitud no haya sido procesada previamente.
 *
 * @throws Exception
 */
function hu041_validate_request_pending_status(?string $status): void
{
    if (!hu041_is_request_pending_status($status)) {
        throw new Exception('Esta solicitud ya fue procesada anteriormente (Estado actual: ' . (string)$status . ').');
    }
}

/**
 * Valida que una aprobación tenga estudiante asociado.
 *
 * @throws Exception
 */
function hu041_validate_approval_has_linked_student(array $solicitud): void
{
    $linkedStudentId = trim((string)($solicitud['linked_student_id'] ?? ''));

    if ($linkedStudentId === '') {
        throw new Exception('La solicitud aprobada debe estar asociada a un estudiante.');
    }
}

/**
 * Valida la acción del panel de comités asesores.
 *
 * @throws Exception
 */
function hu041_validate_committee_panel_action(
    string $action,
    string $requestStudentId,
    string $rejectionReason = ''
): void {
    if (trim($requestStudentId) === '') {
        throw new Exception('Debe indicar la solicitud de comite a procesar.');
    }

    if (!in_array($action, ['approve_committee_request', 'reject_committee_request'], true)) {
        throw new Exception('Acción de comité inválida.');
    }

    if ($action === 'reject_committee_request' && mb_strlen(trim($rejectionReason)) < 10) {
        throw new Exception('Debe indicar un motivo de rechazo de al menos 10 caracteres.');
    }
}

/**
 * Valida que el grupo de solicitudes pueda aprobarse como comité.
 *
 * @throws Exception
 */
function hu041_validate_target_committee_group(?array $target): void
{
    if (!$target) {
        throw new Exception('La solicitud de comite ya no esta disponible o ya fue procesada.');
    }

    if (empty($target['can_approve'])) {
        throw new Exception('No se puede aprobar: ' . ($target['validation_message'] ?? 'configuracion invalida.'));
    }
}