<?php
/**
 * HU-022: Procesador de subida de plantillas oficiales
 *
 * Valida, inserta en BD y responde JSON para SweetAlert2.
 * Solo accesible por Gestor (2) y Administrador (1).
 * Usa excepciones en lugar de exit/die; sanitiza todos los outputs JSON.
 */

// Sesion
include_once __DIR__ . '/lib/mysession/mySession.class.php';
include_once __DIR__ . '/lib/mysession/mySession.conf.php';

$mySessionController = mySession::getIstance($_MYSESSION_CONF);
$current_user_id  = $mySessionController->getVar('usuario');
$current_user_rol = (int)$mySessionController->getVar('rol');

header('Content-Type: application/json; charset=utf-8');

/**
 * Responde JSON de error con codigo HTTP opcional.
 * Sanitiza el mensaje antes de incluirlo en la respuesta (CWE-79).
 */
function responderJson(array $payload, int $httpCode = 200): void {
    if ($httpCode !== 200) {
        http_response_code($httpCode);
    }

    $json = json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );

    if ($json === false) {
        http_response_code(500);
        echo '{"ok":false,"error":"Error interno del servidor."}';
        return;
    }

    echo $json;
}

function responderError(string $mensaje, int $httpCode = 200): void {
    responderJson([
        'ok'    => false,
        'error' => $mensaje,
    ], $httpCode);
}

try {
    // Control de acceso
    if (!$current_user_id) {
        responderError('No autorizado.', 401);
        return;
    }

    if (!in_array($current_user_rol, [1, 2], true)) {
        responderError('No tiene permisos para esta accion.', 403);
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        responderError('Metodo no permitido.', 405);
        return;
    }

    // Validar campos de texto
    $nombre      = trim($_POST['nombre']      ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $tipo        = trim($_POST['tipo']        ?? '');

    $tipos_validos = ['Propuesta', 'Informe Final', 'Acta', 'Otro'];

    if ($nombre === '') {
        responderError('El nombre de la plantilla es obligatorio.');
        return;
    }
    if (!in_array($tipo, $tipos_validos, true)) {
        responderError('Tipo de plantilla invalido.');
        return;
    }

    // Validar archivo
    $upload_errors = [
        UPLOAD_ERR_INI_SIZE   => 'El archivo supera el limite del servidor.',
        UPLOAD_ERR_FORM_SIZE  => 'El archivo supera el limite del formulario.',
        UPLOAD_ERR_PARTIAL    => 'El archivo se subio parcialmente.',
        UPLOAD_ERR_NO_FILE    => 'No se selecciono ningun archivo.',
        UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal del servidor.',
        UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en disco.',
    ];

    if (empty($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        $codigo = $_FILES['archivo']['error'] ?? UPLOAD_ERR_NO_FILE;
        responderError($upload_errors[$codigo] ?? 'Error desconocido al subir el archivo.');
        return;
    }

    $file_tmp  = $_FILES['archivo']['tmp_name'];
    $file_name = basename($_FILES['archivo']['name']);
    $file_size = (int)$_FILES['archivo']['size'];

    // Verificar que sea un archivo subido real (previene path traversal)
    if (!is_uploaded_file($file_tmp)) {
        responderError('El archivo no es valido.');
        return;
    }

    // Detectar MIME real; en Windows/XAMPP DOCX puede llegar como octet-stream
    $mime_type = mime_content_type($file_tmp);
    $extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    $mime_por_extension = [
        'pdf'  => 'application/pdf',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    if (in_array($mime_type, ['application/octet-stream', 'application/zip'], true)) {
        $mime_type = $mime_por_extension[$extension] ?? $mime_type;
    }

    // Leer binario del archivo validado
    // Verificación adicional: debe ser un archivo real y legible
    if (!is_file($file_tmp) || !is_readable($file_tmp)) {
        responderError('Archivo temporal invalido.');
        return;
    }
    
    $archivo_binario = @file_get_contents($file_tmp);
    if ($archivo_binario === false || strlen($archivo_binario) === 0) {
        responderError('El archivo esta vacio o no se pudo leer.');
        return;
    }

    // Insertar en BD
    require_once __DIR__ . '/inc/db/bdcommon.inc';
    require_once __DIR__ . '/inc/plantillas_functions.php';

    $conn = new mysqli($db_host, $usuario, $clave, $db);
    if ($conn->connect_error) {
        throw new RuntimeException('Error de conexion: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8');

    $resultado = insertarPlantilla($conn, [
        'nombre'      => $nombre,
        'descripcion' => $descripcion,
        'tipo'        => $tipo,
        'archivo'     => $archivo_binario,
        'file_name'   => $file_name,
        'mime_type'   => $mime_type,
        'file_size'   => $file_size,
        'subido_por'  => $current_user_id,
    ]);

    $conn->close();

    if (!$resultado['ok']) {
        responderError($resultado['error'] ?? 'Error al guardar la plantilla.');
        return;
    }

    echo json_encode(['ok' => true, 'id' => $resultado['id']]);

} catch (Throwable $e) {
    error_log('HU-022 plantilla_upload_process.php: ' . $e->getMessage());
    responderError('Error interno del servidor.', 500);
}
