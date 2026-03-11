<?php
/**
 * HU-009 - Descarga del reporte de proyectos en prórroga.
 *
 * Responsabilidad única:
 * - validar parámetros de entrada
 * - validar sesión / rol autorizado
 * - construir la data consolidada del reporte
 * - generar la descarga del PDF
 *
 * No renderiza paneles HTML ni consulta datos fuera del service.
 */

declare(strict_types=1);

ob_start();

include('../../login/check.php');
require_once __DIR__ . '/prorroga_report_service.php';
require_once __DIR__ . '/prorroga_report_pdf.php';

try {
    $currentUserId = $mySessionController->getVar('usuario');
    $currentUserRole = (int)$mySessionController->getVar('rol');

    if (!$currentUserId) {
        if (ob_get_length()) {
            ob_end_clean();
        }
        http_response_code(401);
        exit('No autorizado.');
    }

    // Secretaría / Gestor Académico y administrador.
    $allowedRoles = [1, 2];
    if (!in_array($currentUserRole, $allowedRoles, true)) {
        if (ob_get_length()) {
            ob_end_clean();
        }
        http_response_code(403);
        exit('No tiene permisos para descargar este reporte.');
    }

    $anioRaw = $_GET['anio'] ?? null;
    $anio = null;

    if ($anioRaw !== null && $anioRaw !== '') {
        if (!preg_match('/^\d{4}$/', (string)$anioRaw)) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            http_response_code(400);
            exit('Parametro anio invalido.');
        }

        $anio = (int)$anioRaw;
        if ($anio <= 0) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            http_response_code(400);
            exit('Parametro anio invalido.');
        }
    }

    $estado = trim((string)($_GET['estado'] ?? 'Prorrogado'));
    if ($estado === '') {
        $estado = 'Prorrogado';
    }

    if ($estado !== 'Prorrogado') {
        if (ob_get_length()) {
            ob_end_clean();
        }
        http_response_code(400);
        exit('HU-009 solo permite generar reportes con estado Prorrogado.');
    }

    $sede = trim((string)($_GET['sede'] ?? ''));
    $sede = $sede !== '' ? $sede : null;

    $builder = new ProrrogaReportPdfData();
    $report = $builder->construirDataReporte($anio, $estado, $sede);

    $fileName = sprintf(
        'reporte_prorrogas_%s_%s.pdf',
        $anio !== null ? $anio : 'todos',
        date('Ymd_His')
    );

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    outputProrrogaReportPdf($report, 'D', $fileName);
    exit;

} catch (Throwable $e) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    error_log('Error en download_reporte_prorrogas.php: ' . $e->getMessage());
    http_response_code(500);
    echo 'Error al generar el reporte de prórrogas.';
    exit;
}