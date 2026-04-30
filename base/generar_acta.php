<?php
include("mod/login/check.php");
include('lang/lang.es');
require_once __DIR__ . '/inc/db/db.php';

$current_user_name = $mySessionController->getVar("nombre");
$current_user_rol  = (int)$mySessionController->getVar("rol");
$cds_domain        = $mySessionController->getVar("cds_domain");
$cds_locate        = $mySessionController->getVar("cds_locate");
$base_url          = $cds_domain . $cds_locate;

if (!in_array($current_user_rol, [2, 3], true)) {
    header('Location: dashboard.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| CONEXIÓN
|--------------------------------------------------------------------------
*/
$conn = new mysqli($db_host, $usuario, $clave, $db);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
$conn->set_charset("utf8");

/*
|--------------------------------------------------------------------------
| FUNCIONES AUXILIARES
|--------------------------------------------------------------------------
*/
function h($valor)
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function valorParametro(array $params, string $clave, string $default = ''): string
{
    return isset($params[$clave]) && trim((string)$params[$clave]) !== ''
        ? trim((string)$params[$clave])
        : $default;
}

/*
|--------------------------------------------------------------------------
| DATOS INICIALES
|--------------------------------------------------------------------------
*/
$proyecto_id       = isset($_GET['proyecto_id']) ? (int)$_GET['proyecto_id'] : 0;
$proyectos         = [];
$datosProyecto     = null;
$estudiantes       = [];
$parametrosActa    = [];
$mensaje_error     = null;

/*
|--------------------------------------------------------------------------
| CARGAR PROYECTOS APROBADOS ACTIVOS
|--------------------------------------------------------------------------
| Se usa proyecto_aprobado como fuente principal del título del acta.
|--------------------------------------------------------------------------
*/
$sqlProyectos = "
    SELECT 
        pa.id_aprobado,
        pa.identificador,
        pa.nombre
    FROM proyecto_aprobado pa
    WHERE pa.aprobado = 1
      AND pa.estado = 'ACTIVO'
    ORDER BY pa.nombre ASC
";

$resProyectos = $conn->query($sqlProyectos);

if ($resProyectos) {
    while ($row = $resProyectos->fetch_assoc()) {
        $proyectos[] = $row;
    }
} else {
    $mensaje_error = "No se pudieron cargar los proyectos aprobados.";
}

/*
|--------------------------------------------------------------------------
| CARGAR PARÁMETROS GENERALES DEL ACTA
|--------------------------------------------------------------------------
| Si no existen en sis_parametros_varios, se usan defaults.
|--------------------------------------------------------------------------
*/
$sqlParams = "
    SELECT parametro, valor
    FROM sis_parametros_varios
    WHERE parametro IN (
        'acta_presidente_nombre',
        'acta_presidente_cargo',
        'acta_director_nombre',
        'acta_director_cargo',
        'acta_tutor_cargo',
        'acta_asesor_cargo',
        'acta_modalidad_tfg_default',
        'acta_grado_default',
        'acta_plataforma_default'
    )
";

$resParams = $conn->query($sqlParams);

if ($resParams) {
    while ($row = $resParams->fetch_assoc()) {
        $parametrosActa[$row['parametro']] = $row['valor'];
    }
}

/*
|--------------------------------------------------------------------------
| CARGAR DETALLE DEL PROYECTO SELECCIONADO
|--------------------------------------------------------------------------
| Trae:
| - título del TFG
| - comité
| - tutor
| - asesores del comité
|--------------------------------------------------------------------------
*/
if ($proyecto_id > 0) {
    $sqlDetalle = "
        SELECT
            pa.id_aprobado,
            pa.identificador,
            pa.nombre AS titulo_tfg,
            pa.comite_id,

            c.tutor,
            c.asesor_1,
            c.asesor_2,

            tutorUser.nombre   AS tutor_nombre,
            asesor1User.nombre AS asesor_1_nombre,
            asesor2User.nombre AS asesor_2_nombre

        FROM proyecto_aprobado pa
        INNER JOIN comite c
            ON c.Id = pa.comite_id
        LEFT JOIN sis_user tutorUser
            ON tutorUser.id = c.tutor
        LEFT JOIN sis_user asesor1User
            ON asesor1User.id = c.asesor_1
        LEFT JOIN sis_user asesor2User
            ON asesor2User.id = c.asesor_2
        WHERE pa.id_aprobado = ?
          AND pa.aprobado = 1
          AND pa.estado = 'ACTIVO'
        LIMIT 1
    ";

    $stmt = $conn->prepare($sqlDetalle);

    if ($stmt) {
        $stmt->bind_param("i", $proyecto_id);
        $stmt->execute();
        $resDetalle = $stmt->get_result();
        $datosProyecto = $resDetalle->fetch_assoc();
        $stmt->close();
    }

    if (!$datosProyecto) {
        $mensaje_error = "El proyecto seleccionado no existe o no está activo.";
        $proyecto_id = 0;
    }
}

/*
|--------------------------------------------------------------------------
| CARGAR ESTUDIANTES DEL PROYECTO
|--------------------------------------------------------------------------
| Se usan para llenar automáticamente Postulante 1 y Postulante 2.
|--------------------------------------------------------------------------
*/
if ($datosProyecto) {
    $sqlEstudiantes = "
        SELECT
            su.id,
            su.nombre
        FROM proyecto_aprobado_estudiantes pae
        INNER JOIN sis_user su
            ON su.id = pae.estudiante_id
        WHERE pae.id_aprobado = ?
        ORDER BY su.nombre ASC
    ";

    $stmtEst = $conn->prepare($sqlEstudiantes);

    if ($stmtEst) {
        $stmtEst->bind_param("i", $proyecto_id);
        $stmtEst->execute();
        $resEst = $stmtEst->get_result();

        while ($row = $resEst->fetch_assoc()) {
            $estudiantes[] = $row;
        }

        $stmtEst->close();
    }
}

/*
|--------------------------------------------------------------------------
| PREPARAR VALORES POR DEFECTO PARA EL FORMULARIO
|--------------------------------------------------------------------------
*/
$estudiante1 = $estudiantes[0] ?? ['id' => '', 'nombre' => ''];
$estudiante2 = $estudiantes[1] ?? ['id' => '', 'nombre' => ''];

$asesoresDisponibles = [];
if (!empty($datosProyecto['asesor_1_nombre'])) {
    $asesoresDisponibles[] = $datosProyecto['asesor_1_nombre'];
}
if (!empty($datosProyecto['asesor_2_nombre']) &&
    $datosProyecto['asesor_2_nombre'] !== $datosProyecto['asesor_1_nombre']) {
    $asesoresDisponibles[] = $datosProyecto['asesor_2_nombre'];
}

$valoresForm = [
    'numero_acta'        => '',
    'fecha'              => date('Y-m-d'),
    'hora_inicio'        => '',
    'hora_cierre'        => '',
    'modalidad_sesion'   => '',
    'plataforma'         => valorParametro($parametrosActa, 'acta_plataforma_default', ''),

    'titulo_tfg'         => $datosProyecto['titulo_tfg'] ?? '',
    'modalidad_tfg'      => valorParametro($parametrosActa, 'acta_modalidad_tfg_default', 'Proyecto de Graduación'),
    'grado'              => valorParametro(
        $parametrosActa,
        'acta_grado_default',
        'Licenciatura en Informática con énfasis en Sistema de Información'
    ),

    'nombre_estudiante_1' => $estudiante1['nombre'],
    'cedula_estudiante_1' => $estudiante1['id'],
    'nombre_estudiante_2' => $estudiante2['nombre'],
    'cedula_estudiante_2' => $estudiante2['id'],

    'presidente_nombre'   => valorParametro($parametrosActa, 'acta_presidente_nombre', ''),
    'presidente_cargo'    => valorParametro($parametrosActa, 'acta_presidente_cargo', 'Presidente(a) del tribunal'),
    'director_nombre'     => valorParametro($parametrosActa, 'acta_director_nombre', ''),
    'director_cargo'      => valorParametro($parametrosActa, 'acta_director_cargo', 'Director(a) de la Escuela de Informática'),
    'tutor_nombre'        => $datosProyecto['tutor_nombre'] ?? '',
    'tutor_cargo'         => valorParametro($parametrosActa, 'acta_tutor_cargo', 'Tutor(a)'),
    'asesor_nombre'       => $asesoresDisponibles[0] ?? '',
    'asesor_cargo'        => valorParametro($parametrosActa, 'acta_asesor_cargo', 'Asesor(a)'),

    'resultado'            => '',
    'nota'                 => '',
    'tipo_observaciones'   => '',
    'mencion'              => '',
    'observaciones_detalle'=> ''
];
?>
<!DOCTYPE html>
<html lang="es">
<?php include('head.php'); ?>
<style>
    /* Estilos responsive para generar_acta.php */
    .dashboard-header h1 {
        font-size: 2.3rem;
        font-weight: 700;
    }

    .dashboard-header p {
        font-size: 1.1rem;
    }

    @media (max-width: 754px) {
        .dashboard-header h1 {
            font-size: 1.8rem;
        }

        .dashboard-header p {
            font-size: 1rem;
        }

        .row.g-3 [class*="col-"] {
            flex: 0 0 100% !important;
            max-width: 100%;
        }

        .btn-lg {
            padding: 0.5rem 1rem;
            font-size: 0.95rem;
        }

        .form-control,
        .form-select {
            font-size: 1.15rem;
            padding: 0.5rem 0.75rem;
        }

        .form-label {
            font-size: 0.95rem;
        }
    }

    @media (max-width: 576px) {
        .dashboard-header {
            margin-bottom: 1.5rem !important;
        }

        .dashboard-header h1 {
            font-size: 1.4rem;
            line-height: 1.3;
        }

        .dashboard-header p {
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .card {
            margin-bottom: 1rem !important;
        }

        .card-body {
            padding: 1rem !important;
        }

        h4 {
            font-size: 1.1rem;
            margin-bottom: 1rem !important;
        }

        .form-control,
        .form-select {
            font-size: 1.15rem;
            padding: 0.45rem 0.65rem;
            height: auto;
        }

        .form-label {
            font-size: 0.9rem;
            margin-bottom: 0.35rem;
        }

        .btn-lg {
            display: block;
            width: 100%;
            padding: 0.6rem 1rem;
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
        }

        .text-center.mt-4 {
            margin-top: 1rem !important;
        }
    }

    @media (max-width: 420px) {
        .dashboard-header h1 {
            font-size: 1.2rem;
        }

        .dashboard-header p {
            font-size: 0.85rem;
        }

        h4 {
            font-size: 1rem;
            margin-bottom: 0.75rem !important;
        }

        .form-control,
        .form-select {
            font-size: 1.15rem;
            padding: 0.4rem 0.55rem;
        }

        .form-label {
            font-size: 0.95rem;
        }

        .btn-lg {
            padding: 0.5rem 0.8rem;
            font-size: 0.85rem;
        }

        .card-body {
            padding: 0.75rem !important;
        }

        .row.g-3 {
            gap: 0.75rem !important;
        }
    }

    @media (max-width: 360px) {
        .dashboard-header h1 {
            font-size: 1rem;
        }

        .dashboard-header p {
            font-size: 0.8rem;
        }

        h4 {
            font-size: 0.95rem;
            margin-bottom: 0.5rem !important;
        }

        .form-control,
        .form-select {
            font-size: 1.15rem;
            padding: 0.35rem 0.5rem;
        }

        .form-label {
            font-size: 0.95rem;
            margin-bottom: 0.25rem;
        }

        .btn-lg {
            padding: 0.45rem 0.6rem;
            font-size: 0.8rem;
        }

        .card-body {
            padding: 0.5rem !important;
        }

        .row.g-3 {
            gap: 0.5rem !important;
        }

        .card {
            margin-bottom: 0.75rem !important;
        }

        .buttons-container {
            gap: 0.3rem;
        }
    }

    .proyecto-selection-form {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .proyecto-selection-form .form-group {
        margin-bottom: 0;
    }

    .buttons-container {
        display: flex;
        gap: 0.75rem;
        justify-content: center;
        flex-wrap: wrap;
    }

    .buttons-container .btn {
        flex: 1 1 auto;
        min-width: 180px;
    }

    @media (max-width: 576px) {
        .buttons-container {
            flex-direction: column;
            gap: 0.5rem;
        }

        .buttons-container .btn {
            width: 100%;
            min-width: 0;
            margin-bottom: 0.5rem;
        }

        .buttons-container .btn:last-child {
            margin-bottom: 0;
        }
    }

    @media (max-width: 420px) {
        .buttons-container {
            gap: 0.4rem;
        }
    }
</style>
<body class="fondo-una d-flex flex-column min-vh-100">
<?php include('header.php'); ?>

<main class="flex-fill">
    <div class="container my-5">

        <div class="dashboard-header text-center mb-5">
            <h1>Generar Acta de Presentación Pública</h1>
            <p class="lead">Seleccione un proyecto aprobado para autocompletar la mayor parte de la información.</p>
        </div>

        <?php if (!empty($mensaje_error)): ?>
            <div class="alert alert-danger" role="alert">
                <?= h($mensaje_error) ?>
            </div>
        <?php endif; ?>

        <!-- SELECCIÓN DE PROYECTO -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body text-dark">
                <form action="generar_acta.php" method="GET" class="proyecto-selection-form">
                    <div class="form-group mb-3">
                        <label for="proyecto_id" class="form-label fw-bold">Proyecto aprobado:</label>
                        <select name="proyecto_id" id="proyecto_id" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($proyectos as $p): ?>
                                <option value="<?= (int)$p['id_aprobado'] ?>"
                                    <?= ($proyecto_id === (int)$p['id_aprobado']) ? 'selected' : '' ?>>
                                    <?= h($p['identificador'] . ' - ' . $p['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Cargar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($datosProyecto): ?>
            <div class="card shadow-sm border-0">
                <div class="card-body text-dark">

                    <form action="procesar_acta.php" method="POST" target="_blank" class="needs-validation" novalidate>
                        <input type="hidden" name="proyecto_id" value="<?= (int)$proyecto_id ?>">

                        <!-- DATOS GENERALES DEL ACTA -->
                        <h4 class="mb-3">Datos generales del acta</h4>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label for="numero_acta" class="form-label fw-bold">Número de acta:</label>
                                <input type="text"
                                       name="numero_acta"
                                       id="numero_acta"
                                       class="form-control"
                                       value="<?= h($valoresForm['numero_acta']) ?>"
                                       placeholder="Ejemplo: 023-2026"
                                       required>
                            </div>

                            <div class="col-md-4">
                                <label for="fecha" class="form-label fw-bold">Fecha de la presentación:</label>
                                <input type="date"
                                       name="fecha"
                                       id="fecha"
                                       class="form-control"
                                       value="<?= h($valoresForm['fecha']) ?>"
                                       required>
                            </div>

                            <div class="col-md-2">
                                <label for="hora_inicio" class="form-label fw-bold">Hora de inicio:</label>
                                <input type="time"
                                       name="hora_inicio"
                                       id="hora_inicio"
                                       class="form-control"
                                       value="<?= h($valoresForm['hora_inicio']) ?>"
                                       required>
                            </div>

                            <div class="col-md-2">
                                <label for="hora_cierre" class="form-label fw-bold">Hora de cierre:</label>
                                <input type="time"
                                       name="hora_cierre"
                                       id="hora_cierre"
                                       class="form-control"
                                       value="<?= h($valoresForm['hora_cierre']) ?>"
                                       required>
                            </div>

                            <div class="col-md-4">
                                <label for="modalidad_sesion" class="form-label fw-bold">Modalidad de la sesión:</label>
                                <select name="modalidad_sesion" id="modalidad_sesion" class="form-select" required>
                                    <option value="">Seleccione...</option>
                                    <option value="virtual" <?= ($valoresForm['modalidad_sesion'] === 'virtual') ? 'selected' : '' ?>>Virtual</option>
                                    <option value="presencial" <?= ($valoresForm['modalidad_sesion'] === 'presencial') ? 'selected' : '' ?>>Presencial</option>
                                </select>
                            </div>

                            <div class="col-md-8">
                                <label for="plataforma" class="form-label fw-bold">Plataforma / lugar:</label>
                                <input type="text"
                                       name="plataforma"
                                       id="plataforma"
                                       class="form-control"
                                       list="lista_plataformas"
                                       value="<?= h($valoresForm['plataforma']) ?>"
                                       placeholder="Ejemplo: Microsoft Teams / Aula 205"
                                       required>
                                <datalist id="lista_plataformas">
                                    <option value="Microsoft Teams"></option>
                                    <option value="Zoom"></option>
                                    <option value="Google Meet"></option>
                                    <option value="Aula 205"></option>
                                    <option value="Aula 210"></option>
                                    <option value="Laboratorio 1"></option>
                                </datalist>
                            </div>
                        </div>

                        <!-- DATOS DEL TFG -->
                        <h4 class="mb-3">Datos del trabajo final de graduación</h4>
                        <div class="row g-3 mb-4">
                            <div class="col-md-12">
                                <label for="titulo_tfg" class="form-label fw-bold">Título del TFG:</label>
                                <input type="text"
                                       name="titulo_tfg"
                                       id="titulo_tfg"
                                       class="form-control"
                                       value="<?= h($valoresForm['titulo_tfg']) ?>"
                                       readonly
                                       required>
                            </div>

                            <div class="col-md-6">
                                <label for="modalidad_tfg" class="form-label fw-bold">Modalidad del TFG:</label>
                                <input type="text"
                                       name="modalidad_tfg"
                                       id="modalidad_tfg"
                                       class="form-control"
                                       value="<?= h($valoresForm['modalidad_tfg']) ?>"
                                       required>
                            </div>

                            <div class="col-md-6">
                                <label for="grado" class="form-label fw-bold">Grado al que opta:</label>
                                <input type="text"
                                       name="grado"
                                       id="grado"
                                       class="form-control"
                                       value="<?= h($valoresForm['grado']) ?>"
                                       required>
                            </div>
                        </div>

                        <!-- POSTULANTES -->
                        <h4 class="mb-3">Postulante 1</h4>
                        <div class="row g-3 mb-4">
                            <div class="col-md-8">
                                <label for="nombre_estudiante_1" class="form-label fw-bold">Nombre completo:</label>
                                <input type="text"
                                       name="nombre_estudiante_1"
                                       id="nombre_estudiante_1"
                                       class="form-control"
                                       value="<?= h($valoresForm['nombre_estudiante_1']) ?>"
                                       readonly
                                       required>
                            </div>

                            <div class="col-md-4">
                                <label for="cedula_estudiante_1" class="form-label fw-bold">Cédula:</label>
                                <input type="text"
                                       name="cedula_estudiante_1"
                                       id="cedula_estudiante_1"
                                       class="form-control"
                                       value="<?= h($valoresForm['cedula_estudiante_1']) ?>"
                                       readonly
                                       required>
                            </div>
                        </div>

                        <h4 class="mb-3">Postulante 2 (opcional)</h4>
                        <div class="row g-3 mb-4">
                            <div class="col-md-8">
                                <label for="nombre_estudiante_2" class="form-label fw-bold">Nombre completo:</label>
                                <input type="text"
                                       name="nombre_estudiante_2"
                                       id="nombre_estudiante_2"
                                       class="form-control"
                                       value="<?= h($valoresForm['nombre_estudiante_2']) ?>"
                                       readonly>
                            </div>

                            <div class="col-md-4">
                                <label for="cedula_estudiante_2" class="form-label fw-bold">Cédula:</label>
                                <input type="text"
                                       name="cedula_estudiante_2"
                                       id="cedula_estudiante_2"
                                       class="form-control"
                                       value="<?= h($valoresForm['cedula_estudiante_2']) ?>"
                                       readonly>
                            </div>
                        </div>

                        <!-- TRIBUNAL -->
                        <h4 class="mb-3">Tribunal evaluador</h4>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="presidente_nombre" class="form-label fw-bold">Presidente(a) del tribunal:</label>
                                <input type="text"
                                       name="presidente_nombre"
                                       id="presidente_nombre"
                                       class="form-control"
                                       value="<?= h($valoresForm['presidente_nombre']) ?>"
                                       required>
                            </div>

                            <div class="col-md-6">
                                <label for="presidente_cargo" class="form-label fw-bold">Cargo del/de la presidente(a):</label>
                                <input type="text"
                                       name="presidente_cargo"
                                       id="presidente_cargo"
                                       class="form-control"
                                       value="<?= h($valoresForm['presidente_cargo']) ?>"
                                       required>
                            </div>

                            <div class="col-md-6">
                                <label for="director_nombre" class="form-label fw-bold">Director(a) de Escuela:</label>
                                <input type="text"
                                       name="director_nombre"
                                       id="director_nombre"
                                       class="form-control"
                                       value="<?= h($valoresForm['director_nombre']) ?>"
                                       required>
                            </div>

                            <div class="col-md-6">
                                <label for="director_cargo" class="form-label fw-bold">Cargo:</label>
                                <input type="text"
                                       name="director_cargo"
                                       id="director_cargo"
                                       class="form-control"
                                       value="<?= h($valoresForm['director_cargo']) ?>"
                                       required>
                            </div>

                            <div class="col-md-6">
                                <label for="tutor_nombre" class="form-label fw-bold">Tutor(a):</label>
                                <input type="text"
                                       name="tutor_nombre"
                                       id="tutor_nombre"
                                       class="form-control"
                                       value="<?= h($valoresForm['tutor_nombre']) ?>"
                                       readonly
                                       required>
                            </div>

                            <div class="col-md-6">
                                <label for="tutor_cargo" class="form-label fw-bold">Cargo:</label>
                                <input type="text"
                                       name="tutor_cargo"
                                       id="tutor_cargo"
                                       class="form-control"
                                       value="<?= h($valoresForm['tutor_cargo']) ?>"
                                       required>
                            </div>

                            <div class="col-md-6">
                                <label for="asesor_nombre" class="form-label fw-bold">Asesor(a):</label>
                                <select name="asesor_nombre" id="asesor_nombre" class="form-select" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($asesoresDisponibles as $asesor): ?>
                                        <option value="<?= h($asesor) ?>"
                                            <?= ($valoresForm['asesor_nombre'] === $asesor) ? 'selected' : '' ?>>
                                            <?= h($asesor) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="asesor_cargo" class="form-label fw-bold">Cargo:</label>
                                <input type="text"
                                       name="asesor_cargo"
                                       id="asesor_cargo"
                                       class="form-control"
                                       value="<?= h($valoresForm['asesor_cargo']) ?>"
                                       required>
                            </div>
                        </div>

                        <!-- RESULTADO -->
                        <h4 class="mb-3">Resultado del tribunal</h4>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label for="resultado" class="form-label fw-bold">Resultado:</label>
                                <select name="resultado" id="resultado" class="form-select" required>
                                    <option value="">Seleccione...</option>
                                    <option value="Aprobado" <?= ($valoresForm['resultado'] === 'Aprobado') ? 'selected' : '' ?>>Aprobado</option>
                                    <option value="Reprobado" <?= ($valoresForm['resultado'] === 'Reprobado') ? 'selected' : '' ?>>Reprobado</option>
                                    <option value="Con Observaciones" <?= ($valoresForm['resultado'] === 'Con Observaciones') ? 'selected' : '' ?>>Con Observaciones</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="nota" class="form-label fw-bold">Calificación:</label>
                                <input type="number"
                                       step="0.01"
                                       name="nota"
                                       id="nota"
                                       class="form-control"
                                       value="<?= h($valoresForm['nota']) ?>"
                                       min="0"
                                       max="10"
                                       required>
                            </div>

                            <div class="col-md-4">
                                <label for="tipo_observaciones" class="form-label fw-bold">Observaciones:</label>
                                <select name="tipo_observaciones" id="tipo_observaciones" class="form-select" required>
                                    <option value="">Seleccione...</option>
                                    <option value="Sin Observaciones" <?= ($valoresForm['tipo_observaciones'] === 'Sin Observaciones') ? 'selected' : '' ?>>Sin Observaciones</option>
                                    <option value="Con Observaciones" <?= ($valoresForm['tipo_observaciones'] === 'Con Observaciones') ? 'selected' : '' ?>>Con Observaciones</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="mencion" class="form-label fw-bold">Mención honorífica:</label>
                                <select name="mencion" id="mencion" class="form-select">
                                    <option value="" <?= ($valoresForm['mencion'] === '') ? 'selected' : '' ?>>Sin mención</option>
                                    <option value="Cum Laude" <?= ($valoresForm['mencion'] === 'Cum Laude') ? 'selected' : '' ?>>Cum Laude</option>
                                    <option value="Magna Cum Laude" <?= ($valoresForm['mencion'] === 'Magna Cum Laude') ? 'selected' : '' ?>>Magna Cum Laude</option>
                                    <option value="Summa Cum Laude" <?= ($valoresForm['mencion'] === 'Summa Cum Laude') ? 'selected' : '' ?>>Summa Cum Laude</option>
                                </select>
                            </div>

                            <div class="col-md-12">
                                <label for="observaciones_detalle" class="form-label fw-bold">Detalle de observaciones:</label>
                                <textarea name="observaciones_detalle"
                                          id="observaciones_detalle"
                                          class="form-control"
                                          rows="5"
                                          placeholder="Escriba aquí las observaciones del tribunal, si aplica."><?= h($valoresForm['observaciones_detalle']) ?></textarea>
                            </div>
                        </div>

                        <div class="buttons-container mt-4">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-file-earmark-pdf-fill"></i> Generar Acta en PDF
                            </button>

                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left-circle"></i> Volver al panel principal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info shadow-sm border-0">
                Seleccione primero un proyecto aprobado y luego presione <strong>Cargar</strong> para autocompletar el formulario.
            </div>

            <div class="text-center mt-4">
                <a href="dashboard.php" class="btn btn-secondary px-4">
                    <i class="bi bi-arrow-left-circle"></i> Volver al Panel Principal
                </a>
            </div>
        <?php endif; ?>
    </div>
</main>

<footer class="footer-una mt-auto">
    <div class="container">
        <p class="mb-1">&copy; <?= date('Y') ?> Universidad Nacional de Costa Rica</p>
        <small>Escuela de Informática - Proyecto SGPFL v3.0</small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
(() => {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');

    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

</body>
</html>