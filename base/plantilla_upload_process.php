<?php
/**
 * HU-022: Procesador de subida de plantillas oficiales
 *
 * Recibe el formulario del modal (panel_plantillas.php) vía POST,
 * valida, inserta en BD y responde JSON para SweetAlert2.
 * Solo accesible por Gestor (2) y Administrador (1).
 */

// ── Sesión ──────────────────────────────────────────────────
include_once __DIR__ . '/lib/mysession/mySession.class.php';
include_once __DIR__ . '/lib/mysession/mySession.conf.php';

$mySessionController = mySession::getIstance($_MYSESSION_CONF);
$current_user_id  = $mySessionController->getVar('usuario');
$current_user_rol = (int)$mySessionController->getVar('rol');

// Respuesta siempre en JSON
header('Content-Type: application/json; charset=utf-8');

// ── Control de acceso ────────────────────────────────────────
if (!$current_user_id) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autorizado.']);
    exit;
}

// Solo Gestor (2) y Admin (1) pueden subir plantillas
if (!in_array($current_user_rol, [1, 2])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'No tiene permisos para esta acción.']);
    exit;
}

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido.']);
    exit;
}

// ── Validar campos del formulario ────────────────────────────
$nombre      = trim($_POST['nombre']      ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$tipo        = trim($_POST['tipo']        ?? '');

$tipos_validos = ['Propuesta', 'Informe Final', 'Acta', 'Otro'];

if (empty($nombre)) {
    echo json_encode(['ok' => false, 'error' => 'El nombre de la plantilla es obligatorio.']);
    exit;
}
if (!in_array($tipo, $tipos_validos)) {
    echo json_encode(['ok' => false, 'error' => 'Tipo de plantilla inválido.']);
    exit;
}

// ── Validar archivo ──────────────────────────────────────────
if (empty($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
    $upload_errors = [
        UPLOAD_ERR_INI_SIZE   => 'El archivo supera el límite del servidor.',
        UPLOAD_ERR_FORM_SIZE  => 'El archivo supera el límite del formulario.',
        UPLOAD_ERR_PARTIAL    => 'El archivo se subió parcialmente.',
        UPLOAD_ERR_NO_FILE    => 'No se seleccionó ningún archivo.',
        UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal del servidor.',
        UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en disco.',
    ];
    $codigo_error = $_FILES['archivo']['error'] ?? UPLOAD_ERR_NO_FILE;
    $msg = $upload_errors[$codigo_error] ?? 'Error desconocido al subir el archivo.';
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

$file_tmp   = $_FILES['archivo']['tmp_name'];
$file_name  = basename($_FILES['archivo']['name']);
$file_size  = (int)$_FILES['archivo']['size'];

// Detectar MIME real del archivo subido.
// En Windows/XAMPP, mime_content_type() puede retornar 'application/octet-stream'
// para archivos DOCX, por lo que se usa la extensión como fallback seguro.
$mime_type = mime_content_type($file_tmp);
$extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

// Mapa de extensiones permitidas a su MIME correcto
$mime_por_extension = [
    'pdf'  => 'application/pdf',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];

// Si el MIME detectado es genérico, usar el del mapa de extensiones
if ($mime_type === 'application/octet-stream' || $mime_type === 'application/zip') {
    $mime_type = $mime_por_extension[$extension] ?? $mime_type;
}

// ── Leer binario del archivo ─────────────────────────────────
$archivo_binario = file_get_contents($file_tmp);
if ($archivo_binario === false) {
    echo json_encode(['ok' => false, 'error' => 'No se pudo leer el archivo subido.']);
    exit;
}

// ── Insertar en BD ───────────────────────────────────────────
require_once __DIR__ . '/inc/db/bdcommon.inc';
require_once __DIR__ . '/inc/plantillas_functions.php';

try {
    $conn = new mysqli($db_host, $usuario, $clave, $db);
    if ($conn->connect_error) {
        throw new Exception('Error de conexión: ' . $conn->connect_error);
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
        echo json_encode(['ok' => false, 'error' => $resultado['error']]);
        exit;
    }

    echo json_encode(['ok' => true, 'id' => $resultado['id']]);

} catch (Exception $e) {
    error_log('HU-022 plantilla_upload_process.php: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Error interno del servidor.']);
}
