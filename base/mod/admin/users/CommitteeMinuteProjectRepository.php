<?php

declare(strict_types=1);

final class CommitteeMinuteProjectRepository
{
    public function __construct(private mysqli $connection)
    {
    }

    /**
     * Retorna los proyectos donde el usuario pertenece al comité asesor.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findProjectsByCommitteeMember(string $user_id): array
    {
        $sql = "
            SELECT
                p.id_aprobado,
                p.identificador,
                p.nombre,
                p.estado,
                p.fecha_creacion,
                p.fecha_finalizacion,
                CASE
                    WHEN c.tutor = ? THEN 'TUTOR'
                    WHEN c.asesor_1 = ? THEN 'ASESOR_1'
                    WHEN c.asesor_2 = ? THEN 'ASESOR_2'
                    ELSE 'MIEMBRO_COMITE'
                END AS committee_role
            FROM proyecto_aprobado p
            INNER JOIN comite c
                ON c.Id = p.comite_id
            WHERE p.aprobado = 1
              AND (
                    c.tutor = ?
                 OR c.asesor_1 = ?
                 OR c.asesor_2 = ?
              )
            ORDER BY p.fecha_creacion DESC, p.nombre ASC
        ";

        $stmt = $this->connection->prepare($sql);
        if ($stmt === false) {
            throw new RuntimeException('No se pudo preparar la consulta de proyectos del comité.');
        }

        $stmt->bind_param(
            'ssssss',
            $user_id,
            $user_id,
            $user_id,
            $user_id,
            $user_id,
            $user_id
        );

        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('No se pudo ejecutar la consulta de proyectos del comité.');
        }

        $result = $stmt->get_result();
        $projects = [];

        while ($row = $result->fetch_assoc()) {
            $projects[] = $row;
        }

        $stmt->close();

        return $projects;
    }
}