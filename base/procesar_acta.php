<?php
require('fpdf186/fpdf.php');

date_default_timezone_set('America/Costa_Rica');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: generar_acta.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| FUNCIONES AUXILIARES
|--------------------------------------------------------------------------
*/
function limpiar($valor)
{
    return trim((string)($valor ?? ''));
}

function pdfText($texto)
{
    return utf8_decode($texto);
}

function nombreMes($mes)
{
    $meses = [
        1 => 'enero',
        2 => 'febrero',
        3 => 'marzo',
        4 => 'abril',
        5 => 'mayo',
        6 => 'junio',
        7 => 'julio',
        8 => 'agosto',
        9 => 'septiembre',
        10 => 'octubre',
        11 => 'noviembre',
        12 => 'diciembre'
    ];

    return $meses[(int)$mes] ?? '';
}

function formatearFechaLarga($fecha)
{
    if (empty($fecha)) {
        return '';
    }

    $timestamp = strtotime($fecha);
    if ($timestamp === false) {
        return $fecha;
    }

    $dia = date('j', $timestamp);
    $mes = nombreMes(date('n', $timestamp));
    $anio = date('Y', $timestamp);

    return $dia . ' de ' . $mes . ' de ' . $anio;
}

function formatearHora($hora)
{
    if (empty($hora)) {
        return '';
    }

    $timestamp = strtotime($hora);
    if ($timestamp === false) {
        return $hora;
    }

    return date('H:i', $timestamp);
}

function nombreArchivoSeguro($texto)
{
    $texto = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
    if ($texto === false) {
        $texto = $texto;
    }

    $texto = preg_replace('/[^a-zA-Z0-9_-]/', '_', $texto);
    $texto = preg_replace('/_+/', '_', $texto);

    return trim($texto, '_');
}

function dibujarFirma($pdf, $x, $y, $w, $nombre, $cargo)
{
    $pdf->Line($x, $y, $x + $w, $y);

    $pdf->SetXY($x, $y + 2);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->MultiCell($w, 5, pdfText($nombre), 0, 'C');

    $pdf->SetX($x);
    $pdf->SetFont('Arial', '', 9);
    $pdf->MultiCell($w, 4, pdfText($cargo), 0, 'C');
}

/*
|--------------------------------------------------------------------------
| DATOS DEL FORMULARIO
|--------------------------------------------------------------------------
*/
$numero_acta           = limpiar($_POST['numero_acta'] ?? '');
$fecha                 = limpiar($_POST['fecha'] ?? '');
$hora_inicio           = limpiar($_POST['hora_inicio'] ?? '');
$hora_cierre           = limpiar($_POST['hora_cierre'] ?? '');
$modalidad_sesion      = limpiar($_POST['modalidad_sesion'] ?? '');
$plataforma            = limpiar($_POST['plataforma'] ?? '');

$titulo_tfg            = limpiar($_POST['titulo_tfg'] ?? '');
$modalidad_tfg         = limpiar($_POST['modalidad_tfg'] ?? '');
$grado                 = limpiar($_POST['grado'] ?? '');

$nombre_estudiante_1   = limpiar($_POST['nombre_estudiante_1'] ?? '');
$cedula_estudiante_1   = limpiar($_POST['cedula_estudiante_1'] ?? '');
$nombre_estudiante_2   = limpiar($_POST['nombre_estudiante_2'] ?? '');
$cedula_estudiante_2   = limpiar($_POST['cedula_estudiante_2'] ?? '');

$presidente_nombre     = limpiar($_POST['presidente_nombre'] ?? '');
$presidente_cargo      = limpiar($_POST['presidente_cargo'] ?? '');
$director_nombre       = limpiar($_POST['director_nombre'] ?? '');
$director_cargo        = limpiar($_POST['director_cargo'] ?? '');
$tutor_nombre          = limpiar($_POST['tutor_nombre'] ?? '');
$tutor_cargo           = limpiar($_POST['tutor_cargo'] ?? '');
$asesor_nombre         = limpiar($_POST['asesor_nombre'] ?? '');
$asesor_cargo          = limpiar($_POST['asesor_cargo'] ?? '');

$resultado             = limpiar($_POST['resultado'] ?? '');
$nota                  = limpiar($_POST['nota'] ?? '');
$tipo_observaciones    = limpiar($_POST['tipo_observaciones'] ?? '');
$mencion               = limpiar($_POST['mencion'] ?? '');
$observaciones_detalle = limpiar($_POST['observaciones_detalle'] ?? '');

/*
|--------------------------------------------------------------------------
| PREPARACIÓN DE TEXTO
|--------------------------------------------------------------------------
*/
$hay_segundo_postulante = ($nombre_estudiante_2 !== '' || $cedula_estudiante_2 !== '');

$fecha_larga = formatearFechaLarga($fecha);
$hora_inicio_fmt = formatearHora($hora_inicio);
$hora_cierre_fmt = formatearHora($hora_cierre);

if ($hay_segundo_postulante) {
    $postulantes_nombres = $nombre_estudiante_1 . ' y ' . $nombre_estudiante_2;
    $postulantes_cedulas = $cedula_estudiante_1 . ' y ' . $cedula_estudiante_2;
    $texto_postulantes = 'las personas postulantes ' . $postulantes_nombres .
        ', cédulas de identidad número ' . $postulantes_cedulas;
    $texto_articulo_1 = 'ARTÍCULO 1: Se realizó la presentación pública del trabajo final de graduación titulado "' .
        $titulo_tfg . '", elaborado por ' . $postulantes_nombres .
        ', quienes optan por el grado de ' . $grado . '.';
    $texto_articulo_2 = 'ARTÍCULO 2: El Tribunal Examinador escuchó la exposición, analizó el contenido del trabajo, formuló las observaciones pertinentes y realizó la deliberación correspondiente.';
    $texto_articulo_3 = 'ARTÍCULO 3: Una vez finalizada la deliberación, se comunicó a las personas postulantes el resultado de la evaluación.';
} else {
    $postulantes_nombres = $nombre_estudiante_1;
    $postulantes_cedulas = $cedula_estudiante_1;
    $texto_postulantes = 'la persona postulante ' . $nombre_estudiante_1 .
        ', cédula de identidad número ' . $cedula_estudiante_1;
    $texto_articulo_1 = 'ARTÍCULO 1: Se realizó la presentación pública del trabajo final de graduación titulado "' .
        $titulo_tfg . '", elaborado por ' . $nombre_estudiante_1 .
        ', quien opta por el grado de ' . $grado . '.';
    $texto_articulo_2 = 'ARTÍCULO 2: El Tribunal Examinador escuchó la exposición, analizó el contenido del trabajo, formuló las observaciones pertinentes y realizó la deliberación correspondiente.';
    $texto_articulo_3 = 'ARTÍCULO 3: Una vez finalizada la deliberación, se comunicó a la persona postulante el resultado de la evaluación.';
}

$texto_modalidad = 'La sesión se llevó a cabo en modalidad ' . $modalidad_sesion;
if ($plataforma !== '') {
    $texto_modalidad .= ', utilizando ' . $plataforma;
}
$texto_modalidad .= '.';

$texto_resultado = 'ARTÍCULO 4: El Tribunal Examinador acordó por unanimidad / mayoría otorgar el resultado de "' .
    $resultado . '" con una calificación de ' . $nota . '.';

$texto_observaciones = 'Observaciones: ' . $tipo_observaciones . '.';
if ($tipo_observaciones === 'Con Observaciones' && $observaciones_detalle !== '') {
    $texto_observaciones .= ' ' . $observaciones_detalle;
}

$texto_mencion = '';
if ($mencion !== '') {
    $texto_mencion = 'Mención honorífica otorgada: ' . $mencion . '.';
}

$parrafo_inicial = 'ACTA N.° ' . $numero_acta . '. En la fecha ' . $fecha_larga .
    ', a las ' . $hora_inicio_fmt . ' horas, se reunieron las personas integrantes del Tribunal Examinador conformado por ' .
    $presidente_nombre . ', ' . $presidente_cargo . '; ' .
    $director_nombre . ', ' . $director_cargo . '; ' .
    $tutor_nombre . ', ' . $tutor_cargo . '; y ' .
    $asesor_nombre . ', ' . $asesor_cargo .
    ', con el propósito de llevar a cabo la presentación pública correspondiente a ' .
    $texto_postulantes . ', dentro de la modalidad de ' . $modalidad_tfg . '.';

/*
|--------------------------------------------------------------------------
| CREAR DIRECTORIO
|--------------------------------------------------------------------------
*/
$dir = 'actas/';
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

$timestamp = date("Ymd_His");
$base_nombre = nombreArchivoSeguro($postulantes_nombres ?: 'Acta');

$filename_pdf = $dir . 'Acta_' . $base_nombre . '_' . $timestamp . '.pdf';
$filename_csv = $dir . 'Acta_' . $base_nombre . '_' . $timestamp . '.csv';

/*
|--------------------------------------------------------------------------
| GENERAR PDF
|--------------------------------------------------------------------------
*/
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 20);

/* Logo */
if (file_exists('img/Logo-UNA-Rojo_FondoTransparente.png')) {
    $pdf->Image('img/Logo-UNA-Rojo_FondoTransparente.png', 10, 10, 25);
    $pdf->Ln(12);
}

/* Encabezado institucional */
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 6, pdfText('UNIVERSIDAD NACIONAL'), 0, 1, 'C');
$pdf->Cell(0, 6, pdfText('FACULTAD DE CIENCIAS EXACTAS Y NATURALES'), 0, 1, 'C');
$pdf->Cell(0, 6, pdfText('ESCUELA DE INFORMÁTICA'), 0, 1, 'C');
$pdf->Ln(4);

$pdf->SetFont('Arial', 'B', 13);
$pdf->MultiCell(0, 7, pdfText('ACTA DE PRESENTACIÓN PÚBLICA DE TRABAJO FINAL DE GRADUACIÓN'), 0, 'C');
$pdf->Ln(2);

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 6, pdfText('Acta N.° ' . $numero_acta), 0, 1, 'R');
$pdf->Ln(3);

/* Cuerpo */
$pdf->SetFont('Arial', '', 11);
$pdf->MultiCell(0, 6, pdfText($parrafo_inicial), 0, 'J');
$pdf->Ln(3);

$pdf->MultiCell(0, 6, pdfText($texto_modalidad), 0, 'J');
$pdf->Ln(3);

$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 6, pdfText('DATOS DEL TRABAJO FINAL DE GRADUACIÓN'), 0, 1);
$pdf->SetFont('Arial', '', 11);

$pdf->MultiCell(0, 6, pdfText('Título del TFG: ' . $titulo_tfg), 0, 'J');
$pdf->MultiCell(0, 6, pdfText('Modalidad: ' . $modalidad_tfg), 0, 'J');
$pdf->MultiCell(0, 6, pdfText('Grado: ' . $grado), 0, 'J');
$pdf->Ln(2);

$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 6, pdfText('ARTÍCULOS'), 0, 1);
$pdf->SetFont('Arial', '', 11);

$pdf->MultiCell(0, 6, pdfText($texto_articulo_1), 0, 'J');
$pdf->Ln(2);
$pdf->MultiCell(0, 6, pdfText($texto_articulo_2), 0, 'J');
$pdf->Ln(2);
$pdf->MultiCell(0, 6, pdfText($texto_articulo_3), 0, 'J');
$pdf->Ln(2);
$pdf->MultiCell(0, 6, pdfText($texto_resultado), 0, 'J');
$pdf->Ln(3);

$pdf->MultiCell(0, 6, pdfText($texto_observaciones), 0, 'J');

if ($texto_mencion !== '') {
    $pdf->Ln(2);
    $pdf->MultiCell(0, 6, pdfText($texto_mencion), 0, 'J');
}

$pdf->Ln(4);
$pdf->MultiCell(
    0,
    6,
    pdfText('No habiendo más asuntos que tratar, se da por concluida la sesión a las ' . $hora_cierre_fmt . ' horas del día ' . $fecha_larga . '.'),
    0,
    'J'
);

$pdf->Ln(10);

/* Firmas del tribunal */
$startY = $pdf->GetY();
$leftX  = 20;
$rightX = 115;
$wFirma = 70;

dibujarFirma($pdf, $leftX,  $startY,      $wFirma, $presidente_nombre, $presidente_cargo);
dibujarFirma($pdf, $rightX, $startY,      $wFirma, $director_nombre,   $director_cargo);

$ySegundaFila = $startY + 28;
dibujarFirma($pdf, $leftX,  $ySegundaFila, $wFirma, $tutor_nombre,  $tutor_cargo);
dibujarFirma($pdf, $rightX, $ySegundaFila, $wFirma, $asesor_nombre, $asesor_cargo);

/* Firmas de postulantes */
$yPostulantes = $ySegundaFila + 35;

if ($hay_segundo_postulante) {
    dibujarFirma($pdf, $leftX,  $yPostulantes, $wFirma, $nombre_estudiante_1, 'Postulante');
    dibujarFirma($pdf, $rightX, $yPostulantes, $wFirma, $nombre_estudiante_2, 'Postulante');
} else {
    dibujarFirma($pdf, 62, $yPostulantes, 85, $nombre_estudiante_1, 'Postulante');
}

/* Guardar PDF */
$pdf->Output('F', $filename_pdf);

/*
|--------------------------------------------------------------------------
| GENERAR CSV
|--------------------------------------------------------------------------
*/
$fp = fopen($filename_csv, 'w');

if ($fp === false) {
    die('No se pudo crear el archivo CSV.');
}

/* BOM UTF-8 para Excel */
fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));

/* Encabezado */
fputcsv($fp, ['Campo', 'Valor']);

/* Datos generales */
fputcsv($fp, ['Número de acta', $numero_acta]);
fputcsv($fp, ['Fecha', $fecha]);
fputcsv($fp, ['Fecha larga', $fecha_larga]);
fputcsv($fp, ['Hora de inicio', $hora_inicio_fmt]);
fputcsv($fp, ['Hora de cierre', $hora_cierre_fmt]);
fputcsv($fp, ['Modalidad de la sesión', $modalidad_sesion]);
fputcsv($fp, ['Plataforma / lugar', $plataforma]);

/* TFG */
fputcsv($fp, ['Título del TFG', $titulo_tfg]);
fputcsv($fp, ['Modalidad del TFG', $modalidad_tfg]);
fputcsv($fp, ['Grado', $grado]);

/* Postulantes */
fputcsv($fp, ['Nombre postulante 1', $nombre_estudiante_1]);
fputcsv($fp, ['Cédula postulante 1', $cedula_estudiante_1]);
fputcsv($fp, ['Nombre postulante 2', $nombre_estudiante_2]);
fputcsv($fp, ['Cédula postulante 2', $cedula_estudiante_2]);

/* Tribunal */
fputcsv($fp, ['Presidente del tribunal', $presidente_nombre]);
fputcsv($fp, ['Cargo presidente', $presidente_cargo]);
fputcsv($fp, ['Director de escuela', $director_nombre]);
fputcsv($fp, ['Cargo director', $director_cargo]);
fputcsv($fp, ['Tutor', $tutor_nombre]);
fputcsv($fp, ['Cargo tutor', $tutor_cargo]);
fputcsv($fp, ['Asesor', $asesor_nombre]);
fputcsv($fp, ['Cargo asesor', $asesor_cargo]);

/* Resultado */
fputcsv($fp, ['Resultado', $resultado]);
fputcsv($fp, ['Calificación', $nota]);
fputcsv($fp, ['Tipo de observaciones', $tipo_observaciones]);
fputcsv($fp, ['Detalle de observaciones', $observaciones_detalle]);
fputcsv($fp, ['Mención honorífica', $mencion]);

/* Texto generado */
fputcsv($fp, ['Párrafo inicial', $parrafo_inicial]);
fputcsv($fp, ['Artículo 1', $texto_articulo_1]);
fputcsv($fp, ['Artículo 2', $texto_articulo_2]);
fputcsv($fp, ['Artículo 3', $texto_articulo_3]);
fputcsv($fp, ['Artículo 4', $texto_resultado]);
fputcsv($fp, ['Texto observaciones', $texto_observaciones]);
fputcsv($fp, ['Texto mención', $texto_mencion]);

fclose($fp);

/*
|--------------------------------------------------------------------------
| RESPUESTA HTML
|--------------------------------------------------------------------------
*/
$pdf_url = htmlspecialchars($filename_pdf, ENT_QUOTES, 'UTF-8');
$csv_url = htmlspecialchars($filename_csv, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acta generada</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body{
            font-family: Arial, sans-serif;
            background:#f5f5f5;
            margin:0;
            padding:40px 20px;
            color:#222;
        }
        .contenedor{
            max-width:700px;
            margin:0 auto;
            background:#fff;
            border-radius:12px;
            box-shadow:0 4px 18px rgba(0,0,0,.08);
            padding:30px;
        }
        h1{
            margin-top:0;
            color:#b30000;
        }
        .acciones{
            margin-top:25px;
            display:flex;
            flex-wrap:wrap;
            gap:12px;
        }
        .btn{
            display:inline-block;
            text-decoration:none;
            padding:12px 18px;
            border-radius:8px;
            font-weight:bold;
        }
        .btn-pdf{
            background:#198754;
            color:#fff;
        }
        .btn-csv{
            background:#0d6efd;
            color:#fff;
        }
        .btn-volver{
            background:#6c757d;
            color:#fff;
        }
        .detalle{
            margin-top:18px;
            line-height:1.6;
        }
        .archivo{
            font-family: Consolas, monospace;
            background:#f0f0f0;
            padding:4px 6px;
            border-radius:6px;
        }
    </style>
</head>
<body>
    <div class="contenedor">
        <h1>Acta generada correctamente</h1>

        <p class="detalle">
            Se generaron ambos archivos del acta:
        </p>

        <ul class="detalle">
            <li><strong>PDF:</strong> <span class="archivo"><?php echo $pdf_url; ?></span></li>
            <li><strong>CSV:</strong> <span class="archivo"><?php echo $csv_url; ?></span></li>
        </ul>

        <div class="acciones">
            <a class="btn btn-pdf" href="<?php echo $pdf_url; ?>" target="_blank">Ver PDF</a>
            <a class="btn btn-csv" href="<?php echo $csv_url; ?>" download>Descargar CSV</a>
            <a class="btn btn-volver" href="generar_acta.php">Generar otra acta</a>
        </div>
    </div>
</body>
</html>