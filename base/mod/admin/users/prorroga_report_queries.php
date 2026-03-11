<?php
declare(strict_types=1);

require_once __DIR__ . "/../../../inc/db/db.php";

final class ProrrogaReportQueries
{
    private mysqli $conn;

    public function __construct()
    {
        global $db_host, $usuario, $clave, $db;

        $this->conn = new mysqli($db_host, $usuario, $clave, $db);
        if ($this->conn->connect_error) {
            throw new RuntimeException("Error de conexión: " . $this->conn->connect_error);
        }

        $this->conn->set_charset("utf8");
    }

    /**
     * Obtiene proyectos prorrogados consolidados para HU-009.
     *
     * @param int|null $anio Filtra por año de solicitud de prórroga aprobada.
     * @param string $estado Estado del proyecto aprobado. Por defecto: Prorrogado.
     * @param string|null $sede No implementado aún: falta fuente real en la BD actual.
     * @return array<int, array<string, mixed>>
     */
    public function obtenerProyectosProrrogados(
        ?int $anio = null,
        string $estado = 'Prorrogado',
        ?string $sede = null
    ): array {
        $this->validarFiltros($anio, $estado, $sede);

        $sql = "
            SELECT
                pa.id_aprobado,
                pa.proposal_id,
                pa.identificador,
                pa.nombre AS nombre_proyecto,
                pa.estado,
                pa.fecha_creacion,
                pa.fecha_finalizacion,

                tp.title AS titulo_propuesta,

                GROUP_CONCAT(
                    DISTINCT CONCAT(est.id, ' - ', est.nombre)
                    ORDER BY est.nombre SEPARATOR ' | '
                ) AS estudiantes,

                tutor.nombre AS tutor_nombre,
                asesor1.nombre AS asesor_1_nombre,
                asesor2.nombre AS asesor_2_nombre,

                pr.cantidad_prorrogas_aprobadas AS cantidad_prorrogas_activas,
                pr.ultima_fecha_solicitud,
                pr.detalle_fechas_solicitud,

                CASE
                    WHEN pr.cantidad_prorrogas_aprobadas > 1 THEN 1
                    ELSE 0
                END AS resaltar_multiples_prorrogas

            FROM proyecto_aprobado pa
            INNER JOIN tfg_proposals tp
                ON tp.id = pa.proposal_id
            INNER JOIN comite c
                ON c.Id = pa.comite_id
            LEFT JOIN sis_user tutor
                ON tutor.id = c.tutor
            LEFT JOIN sis_user asesor1
                ON asesor1.id = c.asesor_1
            LEFT JOIN sis_user asesor2
                ON asesor2.id = c.asesor_2
            LEFT JOIN proyecto_aprobado_estudiantes pae
                ON pae.id_aprobado = pa.id_aprobado
            LEFT JOIN sis_user est
                ON est.id = pae.estudiante_id
            INNER JOIN (
                SELECT
                    er.proposal_id,
                    COUNT(*) AS cantidad_prorrogas_aprobadas,
                    MAX(er.request_date) AS ultima_fecha_solicitud,
                    GROUP_CONCAT(
                        CONCAT(
                            CASE er.extension_number
                                WHEN 1 THEN '1ra'
                                WHEN 2 THEN '2da'
                                ELSE CONCAT(er.extension_number, 'ra')
                            END,
                            ' prórroga: ',
                            DATE_FORMAT(er.request_date, '%d/%m/%Y')
                        )
                        ORDER BY er.extension_number SEPARATOR ' | '
                    ) AS detalle_fechas_solicitud
                FROM tfg_extension_requests er
                WHERE er.status = 'aprobada'
                GROUP BY er.proposal_id
            ) pr
                ON pr.proposal_id = pa.proposal_id
            WHERE pa.estado = ?
        ";

        $types = "s";
        $params = [$estado];

        if ($anio !== null) {
            $sql .= "
                AND EXISTS (
                    SELECT 1
                    FROM tfg_extension_requests erf
                    WHERE erf.proposal_id = pa.proposal_id
                      AND erf.status = 'aprobada'
                      AND YEAR(erf.request_date) = ?
                )
            ";
            $types .= "i";
            $params[] = $anio;
        }

        $sql .= "
            GROUP BY
                pa.id_aprobado,
                pa.proposal_id,
                pa.identificador,
                pa.nombre,
                pa.estado,
                pa.fecha_creacion,
                pa.fecha_finalizacion,
                tp.title,
                tutor.nombre,
                asesor1.nombre,
                asesor2.nombre,
                pr.cantidad_prorrogas_aprobadas,
                pr.ultima_fecha_solicitud,
                pr.detalle_fechas_solicitud
            ORDER BY
                pr.ultima_fecha_solicitud DESC,
                pa.nombre ASC
        ";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException("Error al preparar consulta: " . $this->conn->error);
        }

        $this->bindParams($stmt, $types, $params);

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new RuntimeException("Error al ejecutar consulta: " . $error);
        }

        $result = $stmt->get_result();
        $rows = [];

        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        $stmt->close();
        return $rows;
    }

    /**
     * SELECT de conteo para futura paginación o resumen del panel.
     */
    public function contarProyectosProrrogados(
        ?int $anio = null,
        string $estado = 'Prorrogado',
        ?string $sede = null
    ): int {
        $this->validarFiltros($anio, $estado, $sede);

        $sql = "
            SELECT COUNT(DISTINCT pa.id_aprobado) AS total
            FROM proyecto_aprobado pa
            INNER JOIN (
                SELECT er.proposal_id
                FROM tfg_extension_requests er
                WHERE er.status = 'aprobada'
                GROUP BY er.proposal_id
            ) pr
                ON pr.proposal_id = pa.proposal_id
            WHERE pa.estado = ?
        ";

        $types = "s";
        $params = [$estado];

        if ($anio !== null) {
            $sql .= "
                AND EXISTS (
                    SELECT 1
                    FROM tfg_extension_requests erf
                    WHERE erf.proposal_id = pa.proposal_id
                      AND erf.status = 'aprobada'
                      AND YEAR(erf.request_date) = ?
                )
            ";
            $types .= "i";
            $params[] = $anio;
        }

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException("Error al preparar conteo: " . $this->conn->error);
        }

        $this->bindParams($stmt, $types, $params);

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new RuntimeException("Error al ejecutar conteo: " . $error);
        }

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return (int)($row['total'] ?? 0);
    }

    private function validarFiltros(?int $anio, string $estado, ?string $sede): void
    {
        if ($anio !== null && ($anio < 2000 || $anio > 2100)) {
            throw new InvalidArgumentException("El año indicado no es válido.");
        }

        if (trim($estado) === '') {
            throw new InvalidArgumentException("El estado no puede ir vacío.");
        }

        if ($sede !== null && trim($sede) !== '') {
            throw new InvalidArgumentException(
                "El filtro por sede aún no puede implementarse porque no existe una fuente de sede clara en las tablas compartidas."
            );
        }
    }

    /**
     * Helper para bind dinámico de parámetros.
     *
     * @param array<int, mixed> $params
     */
    private function bindParams(mysqli_stmt $stmt, string $types, array $params): void
    {
        $refs = [];
        $refs[] = &$types;

        foreach ($params as $key => $value) {
            $refs[] = &$params[$key];
        }

        call_user_func_array([$stmt, 'bind_param'], $refs);
    }

    public function __destruct()
    {
        if (isset($this->conn)) {
            $this->conn->close();
        }
    }
}