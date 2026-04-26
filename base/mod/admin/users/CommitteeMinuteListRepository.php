<?php

declare(strict_types=1);

final class CommitteeMinuteListRepository
{
    public function __construct(private mysqli $connection)
    {
    }

    /**
     * Lista las minutas registradas para un proyecto.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findMinutesByProjectId(int $project_id): array
    {
        $sql = "
            SELECT
                pm.id,
                pm.project_id,
                pm.session_date,
                pm.file_name,
                pm.mime_type,
                pm.file_size,
                pm.created_at,
                pm.updated_at,
                pm.uploaded_by,
                su.nombre AS uploaded_by_name,
                SUM(CASE WHEN pma.attended = 1 THEN 1 ELSE 0 END) AS attended_count,
                COUNT(pma.id) AS total_participants
            FROM project_minutes pm
            INNER JOIN sis_user su
                ON su.id = pm.uploaded_by
            LEFT JOIN project_minute_attendees pma
                ON pma.minute_id = pm.id
            WHERE pm.project_id = ?
            GROUP BY
                pm.id,
                pm.project_id,
                pm.session_date,
                pm.file_name,
                pm.mime_type,
                pm.file_size,
                pm.created_at,
                pm.updated_at,
                pm.uploaded_by,
                su.nombre
            ORDER BY
                pm.session_date DESC,
                pm.created_at DESC,
                pm.id DESC
        ";

        $stmt = $this->connection->prepare($sql);
        if ($stmt === false) {
            throw new RuntimeException('No se pudo preparar la consulta de minutas del proyecto.');
        }

        $stmt->bind_param('i', $project_id);

        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('No se pudo ejecutar la consulta de minutas del proyecto.');
        }

        $result = $stmt->get_result();
        $minutes = [];

        while ($row = $result->fetch_assoc()) {
            $minutes[] = $row;
        }

        $stmt->close();

        return $minutes;
    }
}