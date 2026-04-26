<?php

declare(strict_types=1);

final class CommitteeMinuteParticipantsRepository
{
    public function __construct(private mysqli $connection)
    {
    }

    /**
     * Retorna los participantes válidos para registrar asistencia en una minuta.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findParticipantsByProjectId(int $project_id): array
    {
        $sql = "
            SELECT
                participants.user_id,
                participants.participant_role,
                u.nombre,
                u.email,
                u.telefono
            FROM (
                SELECT
                    c.tutor AS user_id,
                    'TUTOR' AS participant_role
                FROM proyecto_aprobado p
                INNER JOIN comite c
                    ON c.Id = p.comite_id
                WHERE p.id_aprobado = ?
                  AND c.tutor IS NOT NULL

                UNION

                SELECT
                    c.asesor_1 AS user_id,
                    'ASESOR_1' AS participant_role
                FROM proyecto_aprobado p
                INNER JOIN comite c
                    ON c.Id = p.comite_id
                WHERE p.id_aprobado = ?
                  AND c.asesor_1 IS NOT NULL

                UNION

                SELECT
                    c.asesor_2 AS user_id,
                    'ASESOR_2' AS participant_role
                FROM proyecto_aprobado p
                INNER JOIN comite c
                    ON c.Id = p.comite_id
                WHERE p.id_aprobado = ?
                  AND c.asesor_2 IS NOT NULL

                UNION

                SELECT
                    pae.estudiante_id AS user_id,
                    'ESTUDIANTE' AS participant_role
                FROM proyecto_aprobado_estudiantes pae
                WHERE pae.id_aprobado = ?
            ) AS participants
            INNER JOIN sis_user u
                ON u.id = participants.user_id
            ORDER BY
                FIELD(participants.participant_role, 'TUTOR', 'ASESOR_1', 'ASESOR_2', 'ESTUDIANTE'),
                u.nombre ASC
        ";

        $stmt = $this->connection->prepare($sql);
        if ($stmt === false) {
            throw new RuntimeException('No se pudo preparar la consulta de participantes del proyecto.');
        }

        $stmt->bind_param(
            'iiii',
            $project_id,
            $project_id,
            $project_id,
            $project_id
        );

        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('No se pudo ejecutar la consulta de participantes del proyecto.');
        }

        $result = $stmt->get_result();
        $participants = [];

        while ($row = $result->fetch_assoc()) {
            $participants[] = $row;
        }

        $stmt->close();

        return $participants;
    }
}