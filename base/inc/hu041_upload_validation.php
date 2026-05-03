<?php
/**
 * HU-041: Validación de archivos para solicitudes de asesores.
 *
 * Este archivo contiene lógica reutilizable y testeable.
 * No debe leer $_FILES directamente, redireccionar ni renderizar HTML.
 */

function detect_mime(string $tmp_name): string
{
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    return $finfo->file($tmp_name) ?: '';
}

function is_valid_docx_file(string $tmp_name): bool
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

function ini_size_to_bytes(string $value): int
{
    $value = trim($value);

    if ($value === '') {
        return 0;
    }

    $last = strtolower(substr($value, -1));
    $num = (float)$value;

    switch ($last) {
        case 'g':
            return (int)($num * 1024 * 1024 * 1024);
        case 'm':
            return (int)($num * 1024 * 1024);
        case 'k':
            return (int)($num * 1024);
        default:
            return (int)$num;
    }
}

function bytes_to_human(int $bytes): string
{
    if ($bytes <= 0) {
        return '0 B';
    }

    $units = ['B', 'KB', 'MB', 'GB'];
    $i = (int)floor(log($bytes, 1024));
    $i = max(0, min($i, count($units) - 1));

    $value = $bytes / (1024 ** $i);

    return ($i === 0 ? (string)(int)$value : number_format($value, 2)) . ' ' . $units[$i];
}

function upload_error_to_message(int $code, string $label): string
{
    $uploadMax = ini_get('upload_max_filesize') ?: '';
    $postMax = ini_get('post_max_size') ?: '';
    $postMaxBytes = ini_size_to_bytes($postMax);

    switch ($code) {
        case UPLOAD_ERR_INI_SIZE:
            return "El archivo de {$label} excede el límite del servidor (upload_max_filesize={$uploadMax}).";

        case UPLOAD_ERR_FORM_SIZE:
            return "El archivo de {$label} excede el límite del formulario.";

        case UPLOAD_ERR_PARTIAL:
            return "El archivo de {$label} se subió parcialmente. Intente de nuevo.";

        case UPLOAD_ERR_NO_FILE:
            return "No se adjuntó el archivo de {$label}.";

        case UPLOAD_ERR_NO_TMP_DIR:
            return "Falta la carpeta temporal del servidor para subir {$label}.";

        case UPLOAD_ERR_CANT_WRITE:
            return "El servidor no pudo escribir el archivo de {$label} en disco.";

        case UPLOAD_ERR_EXTENSION:
            return "Una extensión de PHP bloqueó la subida de {$label}.";

        default:
            $hint = '';

            if ($postMaxBytes > 0) {
                $hint = " (post_max_size={$postMax}, upload_max_filesize={$uploadMax})";
            }

            return "Error al subir {$label} (código {$code}){$hint}.";
    }
}

function validate_uploaded_file(
    array $file,
    array $allowed_mimes,
    array $allowed_exts,
    int $max_mb,
    string $label,
    bool $require_uploaded_file = true
): array {
    $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($error !== UPLOAD_ERR_OK) {
        throw new Exception(upload_error_to_message($error, $label));
    }

    $name = (string)($file['name'] ?? '');
    $tmp = (string)($file['tmp_name'] ?? '');
    $size = (int)($file['size'] ?? 0);

    if ($name === '') {
        throw new Exception("$label no tiene nombre de archivo");
    }

    if ($tmp === '' || ($require_uploaded_file && !is_uploaded_file($tmp))) {
        throw new Exception("$label no fue recibido correctamente");
    }

    if (!is_readable($tmp)) {
        throw new Exception("No se pudo leer el archivo de $label");
    }

    if ($size <= 0) {
        throw new Exception("$label está vacío o no es válido");
    }

    $max_bytes = $max_mb * 1024 * 1024;

    if ($size > $max_bytes) {
        throw new Exception("$label excede el tamaño máximo de {$max_mb} MB");
    }

    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed_exts, true)) {
        throw new Exception("$label debe tener extensión: " . implode(', ', $allowed_exts));
    }

    $mime = detect_mime($tmp);

    $is_docx = $ext === 'docx' && in_array('docx', $allowed_exts, true);

    if ($is_docx && in_array($mime, ['application/zip', 'application/octet-stream'], true)) {
        if (!is_valid_docx_file($tmp)) {
            throw new Exception("$label no es un DOCX válido");
        }

        $mime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    }

    if (!in_array($mime, $allowed_mimes, true)) {
        throw new Exception("$label no es un archivo válido. Tipo detectado: $mime");
    }

    $content = file_get_contents($tmp);

    if ($content === false || strlen($content) === 0) {
        throw new Exception("No se pudo leer el contenido de $label");
    }

    return [
        'name' => $name,
        'mime' => $mime,
        'size' => $size,
        'content' => $content,
    ];
}