<?php
/**
 * HU-022: Funciones de negocio para Plantillas Oficiales
 *
 * Responsabilidad única: toda la lógica de acceso a datos
 * de plantillas está centralizada aquí (SRP - SOLID).
 * Las vistas y los controladores no hacen queries directamente.
 */

// ============================================================
// CONSULTAS (Lectura)
// ============================================================

/**
 * Retorna todas las plantillas activas, ordenadas por tipo y nombre.
 * Usada por el Estudiante para listar lo que puede descargar.
 *
 * @param  mysqli $conn Conexión activa a la BD
 * @return array  Filas sin el BLOB (para no cargar memoria en el listado)
 */
function getPlantillasActivas(mysqli $conn): array {
    $sql = "SELECT id, nombre, descripcion, tipo, file_name, mime_type, file_size, created_at
            FROM plantillas_oficiales
            WHERE activo = 1
            ORDER BY tipo, nombre";
    $result = $conn->query($sql);
    if (!$result) return [];

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

/**
 * Retorna TODAS las plantillas (activas e inactivas) para la vista del Gestor.
 *
 * @param  mysqli $conn
 * @return array
 */
function getTodasLasPlantillas(mysqli $conn): array {
    $sql = "SELECT p.id, p.nombre, p.descripcion, p.tipo,
                   p.file_name, p.mime_type, p.file_size,
                   p.activo, p.created_at, u.nombre AS subido_por_nombre
            FROM plantillas_oficiales p
            LEFT JOIN sis_user u ON u.id = p.subido_por
            ORDER BY p.tipo, p.nombre";
    $result = $conn->query($sql);
    if (!$result) return [];

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

/**
 * Obtiene una plantilla completa (incluyendo el BLOB) para su descarga.
 * Solo retorna la plantilla si está activa, a menos que el usuario sea Gestor/Admin.
 *
 * @param  mysqli $conn
 * @param  int    $id          ID de la plantilla
 * @param  int    $rol_usuario Rol del usuario que solicita la descarga
 * @return array|null          Fila completa o null si no existe/no tiene acceso
 */
function getPlantillaParaDescarga(mysqli $conn, int $id, int $rol_usuario): ?array {
    // Gestor (2) y Admin (1) pueden descargar aunque esté inactiva
    $filtro_activo = in_array($rol_usuario, [1, 2]) ? "" : "AND activo = 1";

    $stmt = $conn->prepare(
        "SELECT id, nombre, archivo, file_name, mime_type, file_size
         FROM plantillas_oficiales
         WHERE id = ? $filtro_activo"
    );
    if (!$stmt) return null;

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->num_rows > 0 ? $result->fetch_assoc() : null;
    $stmt->close();
    return $row;
}

// ============================================================
// COMANDOS (Escritura)
// ============================================================

/**
 * Inserta una nueva plantilla en la BD.
 * Valida tipo MIME y tamaño antes de insertar.
 *
 * @param  mysqli $conn
 * @param  array  $datos  Campos: nombre, descripcion, tipo, archivo (binario), file_name, mime_type, file_size, subido_por
 * @return array  ['ok' => bool, 'error' => string|null, 'id' => int|null]
 */
function insertarPlantilla(mysqli $conn, array $datos): array {
    // Validar tipo MIME permitido
    $mimes_permitidos = [
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];
    if (!in_array($datos['mime_type'], $mimes_permitidos)) {
        return ['ok' => false, 'error' => 'Tipo de archivo no permitido. Solo PDF o DOCX.', 'id' => null];
    }

    // Validar tamaño máximo (20 MB)
    $max_bytes = 20 * 1024 * 1024;
    if ($datos['file_size'] > $max_bytes) {
        return ['ok' => false, 'error' => 'El archivo supera el límite de 20 MB.', 'id' => null];
    }

    $stmt = $conn->prepare(
        "INSERT INTO plantillas_oficiales
            (nombre, descripcion, tipo, archivo, file_name, mime_type, file_size, subido_por)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    if (!$stmt) {
        return ['ok' => false, 'error' => 'Error preparando la consulta.', 'id' => null];
    }

    $stmt->bind_param(
        "ssssssis",
        $datos['nombre'],
        $datos['descripcion'],
        $datos['tipo'],
        $datos['archivo'],
        $datos['file_name'],
        $datos['mime_type'],
        $datos['file_size'],
        $datos['subido_por']
    );

    $ok = $stmt->execute();
    $id  = $ok ? (int)$conn->insert_id : null;
    $err = $ok ? null : $stmt->error;
    $stmt->close();

    return ['ok' => $ok, 'error' => $err, 'id' => $id];
}

/**
 * Elimina (borrado físico) una plantilla por ID.
 * Solo debe llamarse tras verificar permisos en el controlador.
 *
 * @param  mysqli $conn
 * @param  int    $id
 * @return bool
 */
function eliminarPlantilla(mysqli $conn, int $id): bool {
    $stmt = $conn->prepare("DELETE FROM plantillas_oficiales WHERE id = ?");
    if (!$stmt) return false;
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute() && $stmt->affected_rows > 0;
    $stmt->close();
    return $ok;
}

/**
 * Activa o desactiva una plantilla (visibilidad para estudiantes).
 *
 * @param  mysqli $conn
 * @param  int    $id
 * @param  int    $activo  1 = visible, 0 = oculta
 * @return bool
 */
function toggleActivoPlantilla(mysqli $conn, int $id, int $activo): bool {
    $stmt = $conn->prepare("UPDATE plantillas_oficiales SET activo = ? WHERE id = ?");
    if (!$stmt) return false;
    $stmt->bind_param("ii", $activo, $id);
    $ok = $stmt->execute() && $stmt->affected_rows > 0;
    $stmt->close();
    return $ok;
}

// ============================================================
// HELPERS
// ============================================================

/**
 * Retorna el icono Bootstrap Icons según el tipo MIME del archivo.
 *
 * @param  string $mime_type
 * @return string  Clase CSS del icono
 */
function getIconoPorMime(string $mime_type): string {
    if ($mime_type === 'application/pdf') {
        return 'bi-file-earmark-pdf-fill';
    }
    if (str_contains($mime_type, 'wordprocessingml') || str_contains($mime_type, 'msword')) {
        return 'bi-file-earmark-word-fill';
    }
    return 'bi-file-earmark-fill';
}

/**
 * Retorna el color de badge Bootstrap según el tipo de plantilla.
 *
 * @param  string $tipo
 * @return string  Clase CSS de color
 */
function getColorPorTipo(string $tipo): string {
    return match($tipo) {
        'Propuesta'     => 'bg-primary',
        'Informe Final' => 'bg-success',
        'Acta'          => 'bg-warning text-dark',
        default         => 'bg-secondary',
    };
}

/**
 * Formatea bytes a una cadena legible (KB, MB).
 *
 * @param  int $bytes
 * @return string
 */
function formatearTamano(int $bytes): string {
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 1) . ' MB';
    }
    return round($bytes / 1024, 0) . ' KB';
}
