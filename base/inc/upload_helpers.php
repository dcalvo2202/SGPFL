<?php
/**
 * Funciones helper para el procesamiento de archivos subidos
 * Evita duplicación de código entre tfg_upload_process.php y tfg_upload_final_process.php
 */

/**
 * Responde con JSON y termina la ejecución
 * @param bool $success Estado de la operación
 * @param string $message Mensaje para el usuario
 * @param mixed $data Datos adicionales opcionales
 */
function respond_json($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Crea el directorio de uploads si no existe
 * @param string $path Ruta del directorio
 * @return string Ruta del directorio creado
 */
function ensureUploadDirectory($path) {
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
    return $path;
}

/**
 * Obtiene el tipo MIME real del archivo usando finfo
 * @param string $tmp_name Ruta temporal del archivo
 * @return string Tipo MIME detectado
 */
function getActualMimeType($tmp_name) {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    return $finfo->file($tmp_name) ?: '';
}

/**
 * Valida el tipo de archivo (PDF o DOCX)
 * @param string $mime_type Tipo MIME del archivo
 * @param string $file_name Nombre del archivo para mensajes de error
 * @param bool $pdf_only Si solo se permiten PDFs
 * @return bool True si es válido
 */
function validateFileType($mime_type, $file_name = '', $pdf_only = false) {
    $allowed_types = $pdf_only 
        ? ['application/pdf']
        : ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    
    if (!in_array($mime_type, $allowed_types)) {
        $file_info = $file_name ? "El archivo '$file_name'" : 'El archivo';
        $allowed_text = $pdf_only ? 'PDF válido' : 'PDF o DOCX válido';
        respond_json(false, "$file_info no es un $allowed_text. Tipo detectado: $mime_type");
    }
    return true;
}

/**
 * Valida la extensión del archivo
 * @param string $file_name Nombre del archivo
 * @param array $allowed_extensions Extensiones permitidas
 * @return bool True si es válido
 */
function validateFileExtension($file_name, $allowed_extensions = ['pdf', 'docx']) {
    $extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed_extensions)) {
        respond_json(false, "El archivo '$file_name' debe tener extensión: " . implode(', ', $allowed_extensions));
    }
    return true;
}

/**
 * Valida el tamaño del archivo
 * @param int $file_size Tamaño en bytes
 * @param int $max_size_mb Tamaño máximo en MB (predeterminado 8MB)
 * @param string $file_name Nombre del archivo para mensajes de error
 * @return bool True si es válido
 */
function validateFileSize($file_size, $max_size_mb = 8, $file_name = '') {
    $max_bytes = $max_size_mb * 1024 * 1024;
    if ($file_size > $max_bytes) {
        $file_info = $file_name ? "El archivo '$file_name'" : 'El archivo';
        respond_json(false, "$file_info excede el tamaño máximo de {$max_size_mb}MB");
    }
    return true;
}

/**
 * Valida el tamaño mínimo del archivo
 * @param int $file_size Tamaño en bytes
 * @param int $min_size_kb Tamaño mínimo en KB
 * @param string $file_name Nombre del archivo para mensajes de error
 * @return bool True si es válido
 */
function validateMinFileSize($file_size, $min_size_kb = 100, $file_name = '') {
    $min_bytes = $min_size_kb * 1024;
    if ($file_size < $min_bytes) {
        $file_info = $file_name ? "El archivo '$file_name'" : 'El archivo';
        respond_json(false, "$file_info es demasiado pequeño. Debe ser un documento completo.");
    }
    return true;
}

/**
 * Genera un nombre único para el archivo
 * @param string $user_id ID del usuario
 * @param string $original_name Nombre original del archivo
 * @param int $index Índice para múltiples archivos
 * @return string Nombre único generado
 */
function generateUniqueFilename($user_id, $original_name, $index = 0) {
    $extension = pathinfo($original_name, PATHINFO_EXTENSION);
    $suffix = $index > 0 ? '_' . $index : '';
    return $user_id . '_' . date('Y-m-d_H-i-s') . '_' . uniqid() . $suffix . '.' . $extension;
}

/**
 * Obtiene el mensaje de error de upload según el código
 * @param int $error_code Código de error de PHP
 * @return string Mensaje de error legible
 */
function getUploadErrorMessage($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'El archivo excede el tamaño máximo permitido';
        case UPLOAD_ERR_PARTIAL:
            return 'El archivo se subió parcialmente';
        case UPLOAD_ERR_NO_FILE:
            return 'No se seleccionó ningún archivo';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'No hay directorio temporal disponible';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Error al escribir el archivo en disco';
        case UPLOAD_ERR_EXTENSION:
            return 'Una extensión de PHP detuvo la subida del archivo';
        default:
            return 'Error desconocido al subir el archivo';
    }
}

/**
 * Procesa un archivo subido para propuestas TFG (PDF/DOCX)
 * @param array $file_info Información del archivo ($_FILES)
 * @param string $upload_dir Directorio de destino
 * @param string $user_id ID del usuario
 * @param int $index Índice del archivo
 * @return array Array con info del archivo procesado
 */
function processProposalFile($file_info, $upload_dir, $user_id, $index = 0) {
    // Validar tipo y tamaño
    validateFileType($file_info['type'], $file_info['name'], false);
    validateFileSize($file_info['size'], 10, $file_info['name']);
    
    // Generar nombre único
    $unique_filename = generateUniqueFilename($user_id, $file_info['name'], $index);
    $file_path = $upload_dir . $unique_filename;
    
    // Mover archivo
    if (!move_uploaded_file($file_info['tmp_name'], $file_path)) {
        respond_json(false, "Error al guardar el archivo '{$file_info['name']}'");
    }
    
    // Leer contenido
    $file_content = file_get_contents($file_path);
    
    return [
        'name' => $file_info['name'],
        'unique_name' => $unique_filename,
        'type' => $file_info['type'],
        'size' => $file_info['size'],
        'path' => 'uploads/tfg_proposals/' . $unique_filename,
        'content' => $file_content
    ];
}

/**
 * Valida un archivo PDF para documento final
 * @param array $file_info Información del archivo
 * @param int $max_size_mb Tamaño máximo en MB
 * @param int $min_size_kb Tamaño mínimo en KB
 * @return array Información del archivo validado con MIME real
 */
function validateFinalDocumentFile($file_info, $max_size_mb = 20, $min_size_kb = 100) {
    // Obtener MIME real
    $mime_type = getActualMimeType($file_info['tmp_name']);
    
    // Validar tipo (solo PDF)
    validateFileType($mime_type, $file_info['name'], true);
    
    // Validar extensión
    validateFileExtension($file_info['name'], ['pdf']);
    
    // Validar tamaño máximo
    validateFileSize($file_info['size'], $max_size_mb, $file_info['name']);
    
    // Validar tamaño mínimo
    validateMinFileSize($file_info['size'], $min_size_kb, $file_info['name']);
    
    return [
        'name' => $file_info['name'],
        'type' => $mime_type,
        'size' => $file_info['size'],
        'tmp_name' => $file_info['tmp_name'],
        'error' => UPLOAD_ERR_OK
    ];
}

/**
 * Procesa múltiples archivos subidos
 * @param array $files Array de $_FILES (formato multi-archivo)
 * @param callable $validator Función de validación para cada archivo
 * @return array Array de archivos procesados
 */
function processMultipleFiles($files, $validator) {
    $processed_files = [];
    $files_count = count($files['name']);
    
    if ($files_count === 0 || $files['error'][0] === UPLOAD_ERR_NO_FILE) {
        respond_json(false, 'No se seleccionó ningún archivo');
    }
    
    for ($i = 0; $i < $files_count; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $file_info = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'size' => $files['size'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i]
            ];
            
            $processed_files[] = $validator($file_info);
            
        } elseif ($files['error'][$i] !== UPLOAD_ERR_NO_FILE) {
            respond_json(false, getUploadErrorMessage($files['error'][$i]));
        }
    }
    
    return $processed_files;
}

/**
 * Inserta archivos adicionales en la tabla tfg_files
 * @param mysqli $conn Conexión a la base de datos
 * @param array $files Array de archivos a insertar (sin el primero)
 * @param string $user_id ID del usuario
 * @param string $document_type Tipo de documento
 * @return int Número de archivos guardados exitosamente
 */
function saveAdditionalFiles($conn, $files, $user_id, $document_type = 'Anexo') {
    $saved_count = 0;
    
    for ($i = 1; $i < count($files); $i++) {
        $file = $files[$i];
        
        // Obtener contenido del archivo con validación
        if (isset($file['content'])) {
            $file_content = $file['content'];
        } else {
            if (!isset($file['tmp_name']) || !is_readable($file['tmp_name'])) {
                error_log("No se pudo acceder al archivo temporal adicional: " . ($file['tmp_name'] ?? 'undefined'));
                continue; // Saltar este archivo pero continuar con los demás
            }
            $file_content = file_get_contents($file['tmp_name']);
            if ($file_content === false) {
                error_log("Error al leer el archivo temporal adicional: " . $file['tmp_name']);
                continue; // Saltar este archivo pero continuar con los demás
            }
        }
        
        $sql = "INSERT INTO tfg_files (file_name, mime_type, file_size, file_data, storage_path, uploaded_by, version, document_type) 
                VALUES (?, ?, ?, ?, NULL, ?, 1, ?)";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $null_blob = null;
            $file_name = isset($file['unique_name']) ? $file['unique_name'] : $file['name'];
            // bind_param types: s = string, i = integer, b = blob
            // "ssibss" => file_name (s), mime_type (s), file_size (i), file_data (b), uploaded_by (s), document_type (s)
            $stmt->bind_param("ssibss", 
                $file_name, 
                $file['type'], 
                $file['size'], 
                $null_blob,
                $user_id,
                $document_type
            );
            
            if (!empty($file_content)) {
                $stmt->send_long_data(3, $file_content);
            }
            
            if ($stmt->execute()) {
                $saved_count++;
            }
            $stmt->close();
        }
    }
    
    return $saved_count;
}
