<?php
/**
 * HU-041: Helpers reutilizables para historial y documentos.
 *
 * Este archivo contiene funciones puras y testeables.
 * No debe abrir conexión, leer sesión, renderizar HTML ni redireccionar.
 */

/**
 * Construye placeholders y tipos para consultas IN con prepared statements.
 *
 * Ejemplo:
 * ['1', '2', '3'] => placeholders "?,?,?", types "sss"
 *
 * @param array<int, string|int> $ids
 * @return array{placeholders: string, types: string}
 */
function buildInClause(array $ids): array
{
    if (empty($ids)) {
        return [
            'placeholders' => '',
            'types' => '',
        ];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('s', count($ids));

    return [
        'placeholders' => $placeholders,
        'types' => $types,
    ];
}

/**
 * Convierte MIME types comunes a etiquetas legibles.
 */
function formatoLegible(?string $mime_type): string
{
    $mime = strtolower(trim((string)$mime_type));

    if ($mime === '') {
        return 'DESCONOCIDO';
    }

    $parts = explode('/', $mime);
    $mime = $parts[1] ?? $mime;

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
        'jpg' => 'JPG',
        'png' => 'PNG',
        'gif' => 'GIF',
        'zip' => 'ZIP',
        'x-rar-compressed' => 'RAR',
        'x-zip-compressed' => 'ZIP',
    ];

    return $tipos[$mime] ?? strtoupper($mime);
}