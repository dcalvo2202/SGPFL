<?php

declare(strict_types=1);

/**
 * HU-039: validadores puros para archivos subidos.
 *
 * Estas funciones NO imprimen JSON, NO hacen exit, NO mueven archivos
 * y NO dependen obligatoriamente de is_uploaded_file() cuando se usan en tests.
 */

function hu039_detect_mime(string $tmp_name): string
{
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    return $finfo->file($tmp_name) ?: '';
}

function hu039_upload_error_message(int $error_code, string $label): string
{
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return "$label excede el tamaño máximo permitido por el servidor.";
        case UPLOAD_ERR_PARTIAL:
            return "$label se subió parcialmente. Intente nuevamente.";
        case UPLOAD_ERR_NO_FILE:
            return "No se adjuntó $label.";
        case UPLOAD_ERR_NO_TMP_DIR:
            return "No existe carpeta temporal para procesar $label.";
        case UPLOAD_ERR_CANT_WRITE:
            return "No se pudo escribir $label en el servidor.";
        case UPLOAD_ERR_EXTENSION:
            return "Una extensión de PHP bloqueó la subida de $label.";
        default:
            return "Error desconocido al subir $label.";
    }
}

function hu039_is_valid_docx_file(string $tmp_name): bool
{
    if (!class_exists('ZipArchive')) {
        return false;
    }

    $zip = new ZipArchive();

    if ($zip->open($tmp_name) !== true) {
        return false;
    }

    $has_content_types = $zip->locateName('[Content_Types].xml') !== false;
    $has_document_xml = $zip->locateName('word/document.xml') !== false;

    $zip->close();

    return $has_content_types && $has_document_xml;
}

/**
 * Validador base de archivos.
 *
 * @param array $file Estructura similar a $_FILES['campo'].
 * @param array $allowed_mimes MIME types permitidos.
 * @param array $allowed_exts Extensiones permitidas sin punto.
 * @param int $max_mb Tamaño máximo en MB.
 * @param string $label Nombre legible para mensajes.
 * @param array $options Opciones:
 *                       - min_kb: tamaño mínimo en KB, default 0.
 *                       - require_uploaded_file: usar is_uploaded_file(), default true.
 *
 * @return array{
 *   valid: bool,
 *   message: string,
 *   name?: string,
 *   mime?: string,
 *   type?: string,
 *   size?: int,
 *   tmp_name?: string,
 *   extension?: string
 * }
 */
function hu039_validate_uploaded_file_payload(
    array $file,
    array $allowed_mimes,
    array $allowed_exts,
    int $max_mb,
    string $label,
    array $options = []
): array {
    $min_kb = (int)($options['min_kb'] ?? 0);
    $require_uploaded_file = (bool)($options['require_uploaded_file'] ?? true);

    $error_code = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($error_code !== UPLOAD_ERR_OK) {
        return [
            'valid' => false,
            'message' => hu039_upload_error_message($error_code, $label),
        ];
    }

    $name = (string)($file['name'] ?? '');
    $tmp = (string)($file['tmp_name'] ?? '');
    $size = (int)($file['size'] ?? 0);

    if ($name === '') {
        return [
            'valid' => false,
            'message' => "$label no tiene nombre de archivo.",
        ];
    }

    if ($tmp === '' || !is_readable($tmp)) {
        return [
            'valid' => false,
            'message' => "No se pudo leer el archivo temporal de $label.",
        ];
    }

    if ($require_uploaded_file && !is_uploaded_file($tmp)) {
        return [
            'valid' => false,
            'message' => "El archivo temporal de $label no proviene de una subida HTTP válida.",
        ];
    }

    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    if (!in_array($extension, $allowed_exts, true)) {
        return [
            'valid' => false,
            'message' => "$label debe tener extensión: " . implode(', ', $allowed_exts),
        ];
    }

    if ($size <= 0) {
        return [
            'valid' => false,
            'message' => "$label está vacío o no es válido.",
        ];
    }

    $max_bytes = $max_mb * 1024 * 1024;
    if ($size > $max_bytes) {
        return [
            'valid' => false,
            'message' => "$label excede el tamaño máximo de {$max_mb}MB.",
        ];
    }

    if ($min_kb > 0) {
        $min_bytes = $min_kb * 1024;
        if ($size < $min_bytes) {
            return [
                'valid' => false,
                'message' => "$label es demasiado pequeño. Debe ser un documento completo.",
            ];
        }
    }

    $mime = hu039_detect_mime($tmp);

    $is_docx = $extension === 'docx' && in_array('docx', $allowed_exts, true);

    if ($is_docx) {
        if (!hu039_is_valid_docx_file($tmp)) {
            return [
                'valid' => false,
                'message' => "$label no es un DOCX válido.",
            ];
        }

        $mime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    }

    if (!in_array($mime, $allowed_mimes, true)) {
        return [
            'valid' => false,
            'message' => "$label no es un archivo válido. Tipo detectado: $mime",
        ];
    }

    return [
        'valid' => true,
        'message' => "$label válido.",
        'name' => $name,
        'mime' => $mime,
        'type' => $mime,
        'size' => $size,
        'tmp_name' => $tmp,
        'extension' => $extension,
    ];
}

function hu039_validate_proposal_file(array $file, bool $require_uploaded_file = true): array
{
    return hu039_validate_uploaded_file_payload(
        $file,
        [
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ],
        ['pdf', 'docx'],
        10,
        'la propuesta TFG',
        ['require_uploaded_file' => $require_uploaded_file]
    );
}

function hu039_validate_final_document_file(array $file, bool $require_uploaded_file = true): array
{
    return hu039_validate_uploaded_file_payload(
        $file,
        ['application/pdf'],
        ['pdf'],
        20,
        'el documento final TFG',
        [
            'min_kb' => 100,
            'require_uploaded_file' => $require_uploaded_file,
        ]
    );
}

function hu039_validate_correction_file(array $file, bool $require_uploaded_file = true): array
{
    return hu039_validate_final_document_file($file, $require_uploaded_file);
}

function hu039_validate_advisor_cv_file(array $file, bool $require_uploaded_file = true): array
{
    return hu039_validate_uploaded_file_payload(
        $file,
        [
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ],
        ['pdf', 'docx'],
        5,
        'el Currículum',
        ['require_uploaded_file' => $require_uploaded_file]
    );
}

function hu039_validate_advisor_id_copy_file(array $file, bool $require_uploaded_file = true): array
{
    return hu039_validate_uploaded_file_payload(
        $file,
        ['application/pdf', 'image/jpeg', 'image/png'],
        ['pdf', 'jpg', 'jpeg', 'png'],
        2,
        'la fotocopia de cédula',
        ['require_uploaded_file' => $require_uploaded_file]
    );
}

function hu039_validate_advisor_cover_letter_file(array $file, bool $require_uploaded_file = true): array
{
    return hu039_validate_uploaded_file_payload(
        $file,
        ['application/pdf'],
        ['pdf'],
        5,
        'la carta de solicitud',
        ['require_uploaded_file' => $require_uploaded_file]
    );
}

function hu039_validate_prorroga_support_file(array $file, bool $require_uploaded_file = true): array
{
    return hu039_validate_uploaded_file_payload(
        $file,
        ['application/pdf'],
        ['pdf'],
        20,
        'el documento de respaldo de prórroga',
        ['require_uploaded_file' => $require_uploaded_file]
    );
}