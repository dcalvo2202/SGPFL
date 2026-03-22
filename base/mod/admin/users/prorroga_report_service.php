<?php
declare(strict_types=1);

require_once __DIR__ . '/prorroga_report_queries.php';

final class ProrrogaReportPdfData
{
    private ProrrogaReportQueries $queries;

    public function __construct(?ProrrogaReportQueries $queries = null)
    {
        $this->queries = $queries ?? new ProrrogaReportQueries();
    }

    /**
     * Construye la data consolidada para el PDF de HU-009.
     *
     * @param int|null $anio
     * @param string $estado
     * @param string|null $sede
     * @return array<string, mixed>
     */
    public function construirDataReporte(?int $anio = null, string $estado = 'Prorrogado', ?string $sede = null): array
    {
        $this->validarEntrada($anio, $estado, $sede);

        $registros = $this->queries->obtenerProyectosProrrogados($anio, $estado, $sede);
        $total = $this->queries->contarProyectosProrrogados($anio, $estado, $sede);

        $filas = [];
        $multiplesProrrogas = 0;

        foreach ($registros as $registro) {
            $cantidadProrrogas = (int)($registro['cantidad_prorrogas_activas'] ?? 0);
            $resaltarMultiples = ((int)($registro['resaltar_multiples_prorrogas'] ?? 0) === 1);

            if ($resaltarMultiples) {
                $multiplesProrrogas++;
            }

            $filas[] = [
                'id_aprobado' => (int)($registro['id_aprobado'] ?? 0),
                'proposal_id' => (int)($registro['proposal_id'] ?? 0),
                'identificador' => $this->limpiarTexto($registro['identificador'] ?? ''),
                'nombre_proyecto' => $this->resolverNombreProyecto($registro),
                'estado' => $this->limpiarTexto($registro['estado'] ?? ''),
                'fecha_creacion' => $this->formatearFecha($registro['fecha_creacion'] ?? null),
                'fecha_finalizacion' => $this->formatearFecha($registro['fecha_finalizacion'] ?? null),
                'estudiantes' => $this->formatearListado($registro['estudiantes'] ?? ''),
                'comite_asesor' => [
                    'tutor' => $this->valorNoDisponible($registro['tutor_nombre'] ?? ''),
                    'asesor_1' => $this->valorNoDisponible($registro['asesor_1_nombre'] ?? ''),
                    'asesor_2' => $this->valorNoDisponible($registro['asesor_2_nombre'] ?? ''),
                    'texto_resumen' => $this->construirResumenComite(
                        $registro['tutor_nombre'] ?? '',
                        $registro['asesor_1_nombre'] ?? '',
                        $registro['asesor_2_nombre'] ?? ''
                    ),
                ],
                'cantidad_prorrogas_activas' => $cantidadProrrogas,
                'ultima_fecha_solicitud' => $this->formatearFechaHora($registro['ultima_fecha_solicitud'] ?? null),
                'detalle_fechas_solicitud' => $this->valorNoDisponible($registro['detalle_fechas_solicitud'] ?? ''),
                'resaltar_multiples_prorrogas' => $resaltarMultiples,
                'marca_multiples_prorrogas' => $resaltarMultiples ? 'Sí' : 'No',
            ];
        }

        return [
            'meta' => [
                'titulo' => 'Reporte de proyectos en prórroga',
                'codigo_hu' => 'HU-009',
                'fecha_generacion' => date('d/m/Y H:i'),
                'filtros' => [
                    'anio' => $anio,
                    'estado' => $estado,
                    'sede' => $sede,
                    'descripcion' => $this->construirDescripcionFiltros($anio, $estado, $sede),
                ],
                'resumen' => [
                    'total_proyectos' => $total,
                    'proyectos_con_multiples_prorrogas' => $multiplesProrrogas,
                    'proyectos_con_una_prorroga' => max(0, $total - $multiplesProrrogas),
                ],
                'notas' => [
                    'Solo se incluyen proyectos con estado "Prorrogado".',
                    'La cantidad de prórrogas se calcula a partir de solicitudes aprobadas del proyecto.',
                    'El filtro por sede queda disponible solo cuando exista una fuente de datos real para sede en la base actual.',
                ],
            ],
            'columnas' => [
                'Identificador',
                'Proyecto',
                'Estado',
                'Estudiantes',
                'Comité Asesor',
                'Cantidad de prórrogas',
                'Última solicitud',
                'Detalle solicitudes',
            ],
            'filas' => $filas,
        ];
    }

    private function validarEntrada(?int $anio, string $estado, ?string $sede): void
    {
        if ($anio !== null && ($anio < 2000 || $anio > 2100)) {
            throw new InvalidArgumentException('El año enviado no es válido.');
        }

        if (trim($estado) === '') {
            throw new InvalidArgumentException('El estado no puede ir vacío.');
        }

        if ($sede !== null && trim($sede) !== '') {
            throw new InvalidArgumentException(
                'El filtro por sede aún no puede usarse porque no existe una fuente clara de sede en las tablas actuales.'
            );
        }
    }

    /**
     * @param array<string, mixed> $registro
     */
    private function resolverNombreProyecto(array $registro): string
    {
        $nombreProyecto = trim((string)($registro['nombre_proyecto'] ?? ''));
        $tituloPropuesta = trim((string)($registro['titulo_propuesta'] ?? ''));

        if ($nombreProyecto !== '') {
            return $nombreProyecto;
        }

        if ($tituloPropuesta !== '') {
            return $tituloPropuesta;
        }

        return 'No disponible';
    }

    private function construirResumenComite(string $tutor, string $asesor1, string $asesor2): string
    {
        $partes = [
            'Tutor: ' . $this->valorNoDisponible($tutor),
            'Asesor 1: ' . $this->valorNoDisponible($asesor1),
            'Asesor 2: ' . $this->valorNoDisponible($asesor2),
        ];

        return implode(' | ', $partes);
    }

    private function construirDescripcionFiltros(?int $anio, string $estado, ?string $sede): string
    {
        $partes = [];
        $partes[] = 'Estado: ' . $estado;
        $partes[] = 'Año: ' . ($anio !== null ? (string)$anio : 'Todos');
        $partes[] = 'Sede: ' . (($sede !== null && trim($sede) !== '') ? $sede : 'No disponible');

        return implode(' | ', $partes);
    }

    private function formatearFecha(?string $fecha): string
    {
        if (empty($fecha) || $fecha === '0000-00-00' || $fecha === '0000-00-00 00:00:00') {
            return 'No disponible';
        }

        $timestamp = strtotime($fecha);
        if ($timestamp === false) {
            return 'No disponible';
        }

        return date('d/m/Y', $timestamp);
    }

    private function formatearFechaHora(?string $fecha): string
    {
        if (empty($fecha) || $fecha === '0000-00-00' || $fecha === '0000-00-00 00:00:00') {
            return 'No disponible';
        }

        $timestamp = strtotime($fecha);
        if ($timestamp === false) {
            return 'No disponible';
        }

        return date('d/m/Y H:i', $timestamp);
    }

    private function formatearListado(string $texto): string
    {
        $texto = trim($texto);

        if ($texto === '') {
            return 'No disponible';
        }

        return preg_replace('/\s*\|\s*/', "\n", $texto) ?? $texto;
    }

    private function valorNoDisponible(string $valor): string
    {
        $valor = trim($valor);
        return $valor !== '' ? $valor : 'No disponible';
    }

    private function limpiarTexto(string $valor): string
    {
        $valor = trim($valor);
        return $valor !== '' ? $valor : 'No disponible';
    }
}