<?php
require('fpdf186/fpdf.php');
include_once __DIR__ . '/mod/login/check.php';

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

function numeroEnPalabrasBasico($numero)
{
    $mapa = [
        0 => 'cero',
        1 => 'uno',
        2 => 'dos',
        3 => 'tres',
        4 => 'cuatro',
        5 => 'cinco',
        6 => 'seis',
        7 => 'siete',
        8 => 'ocho',
        9 => 'nueve',
        10 => 'diez',
        11 => 'once',
        12 => 'doce',
        13 => 'trece',
        14 => 'catorce',
        15 => 'quince',
        16 => 'dieciseis',
        17 => 'diecisiete',
        18 => 'dieciocho',
        19 => 'diecinueve',
        20 => 'veinte',
        21 => 'veintiuno',
        22 => 'veintidos',
        23 => 'veintitres',
        24 => 'veinticuatro',
        25 => 'veinticinco',
        26 => 'veintiseis',
        27 => 'veintisiete',
        28 => 'veintiocho',
        29 => 'veintinueve',
        30 => 'treinta',
        40 => 'cuarenta',
        50 => 'cincuenta',
        60 => 'sesenta',
        70 => 'setenta',
        80 => 'ochenta',
        90 => 'noventa'
    ];

    $numero = (int) $numero;
    if (isset($mapa[$numero])) {
        return $mapa[$numero];
    }

    $decenas = (int) (floor($numero / 10) * 10);
    $unidad = $numero % 10;

    if (!isset($mapa[$decenas])) {
        return (string) $numero;
    }

    return $mapa[$decenas] . ' y ' . ($mapa[$unidad] ?? (string) $unidad);
}

function anioEnPalabras($anio)
{
    $anio = (int) $anio;
    if ($anio < 2000 || $anio > 2099) {
        return (string) $anio;
    }

    $resto = $anio - 2000;
    if ($resto === 0) {
        return 'dos mil';
    }

    return 'dos mil ' . numeroEnPalabrasBasico($resto);
}

function fechaEnPalabras($fecha)
{
    if (empty($fecha)) {
        return '';
    }

    $timestamp = strtotime($fecha);
    if ($timestamp === false) {
        return $fecha;
    }

    $dia = numeroEnPalabrasBasico((int) date('j', $timestamp));
    $mes = nombreMes(date('n', $timestamp));
    $anio = anioEnPalabras(date('Y', $timestamp));

    return $dia . ' de ' . $mes . ' del año ' . $anio;
}

function horaEnPalabras($hora)
{
    if (empty($hora)) {
        return '';
    }

    $timestamp = strtotime($hora);
    if ($timestamp === false) {
        return $hora;
    }

    $horaNumero = (int) date('H', $timestamp);
    $minutos = (int) date('i', $timestamp);

    if ($minutos === 0) {
        return numeroEnPalabrasBasico($horaNumero);
    }

    return numeroEnPalabrasBasico($horaNumero) . ' con ' . numeroEnPalabrasBasico($minutos);
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

    $pdf->SetXY($x, $y + 1.5);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->MultiCell($w, 4.2, pdfText($nombre), 0, 'C');

    $pdf->SetX($x);
    $pdf->SetFont('Arial', '', 8.5);
    $pdf->MultiCell($w, 3.8, pdfText($cargo), 0, 'C');
}

function xmlEscape($texto)
{
    return htmlspecialchars((string) $texto, ENT_XML1, 'UTF-8');
}

function reemplazarPlaceholdersDocx(DOMDocument $dom, array $reemplazos)
{
    if (empty($reemplazos)) {
        return;
    }

    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

    $placeholders = array_keys($reemplazos);
    $paragraphs = $xpath->query('//w:p');

    foreach ($paragraphs as $para) {
        $textNodes = $xpath->query('.//w:t', $para);
        if ($textNodes->length === 0) {
            continue;
        }

        $nodes = [];
        foreach ($textNodes as $node) {
            $nodes[] = $node;
        }

        $rebuild = function () use (&$nodes) {
            $fullText = '';
            $positions = [];
            $offset = 0;
            foreach ($nodes as $node) {
                $text = $node->nodeValue;
                $start = $offset;
                $end = $start + strlen($text);
                $positions[] = [$start, $end];
                $fullText .= $text;
                $offset = $end;
            }
            return [$fullText, $positions];
        };

        [$fullText, $positions] = $rebuild();

        $changed = true;
        while ($changed) {
            $changed = false;
            foreach ($placeholders as $placeholder) {
                $pos = strpos($fullText, $placeholder);
                if ($pos === false) {
                    continue;
                }

                $startPos = $pos;
                $endPos = $pos + strlen($placeholder);
                $firstIndex = null;
                $lastIndex = null;

                foreach ($positions as $index => $range) {
                    [$start, $end] = $range;
                    if ($end <= $startPos) {
                        continue;
                    }
                    if ($start >= $endPos) {
                        break;
                    }
                    if ($firstIndex === null) {
                        $firstIndex = $index;
                    }
                    $lastIndex = $index;
                }

                if ($firstIndex === null || $lastIndex === null) {
                    continue;
                }

                $firstNode = $nodes[$firstIndex];
                $lastNode = $nodes[$lastIndex];

                $prefix = '';
                $suffix = '';
                $firstStart = $positions[$firstIndex][0];
                $lastStart = $positions[$lastIndex][0];

                if ($firstStart < $startPos) {
                    $prefix = substr($firstNode->nodeValue, 0, $startPos - $firstStart);
                }
                if ($endPos > $lastStart) {
                    $suffix = substr($lastNode->nodeValue, $endPos - $lastStart);
                }

                $firstNode->nodeValue = $prefix . $reemplazos[$placeholder] . $suffix;
                for ($i = $firstIndex + 1; $i <= $lastIndex; $i++) {
                    $nodes[$i]->nodeValue = '';
                }

                [$fullText, $positions] = $rebuild();
                $changed = true;
                break;
            }
        }
    }
}

function generarDocxDesdePlantilla($templatePath, $outputPath, array $reemplazos, array $opciones = [])
{
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('ZipArchive no está disponible en el servidor.');
    }

    if (!file_exists($templatePath)) {
        throw new RuntimeException('No se encontró la plantilla DOCX.');
    }

    if (!copy($templatePath, $outputPath)) {
        throw new RuntimeException('No se pudo copiar la plantilla DOCX.');
    }

    $zip = new ZipArchive();
    if ($zip->open($outputPath) !== true) {
        throw new RuntimeException('No se pudo abrir el archivo DOCX.');
    }

    $documentXml = $zip->getFromName('word/document.xml');
    if ($documentXml === false) {
        $zip->close();
        throw new RuntimeException('No se encontró el contenido principal del DOCX.');
    }

    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    
    libxml_use_internal_errors(true);
    if (!$dom->loadXML($documentXml)) {
        $zip->close();
        $errors = libxml_get_errors();
        libxml_clear_errors();
        $errorMsg = 'XML inválido tras reemplazo';
        if (!empty($errors)) {
            $errorMsg .= ': ' . $errors[0]->message;
        }
        throw new RuntimeException($errorMsg);
    }
    libxml_clear_errors();

    if (!empty($opciones['sin_postulante2'])) {
        $reemplazos['{{postulante2_nombre}}'] = '';
        $reemplazos['{{postulante2_cedula}}'] = '';
    }

    reemplazarPlaceholdersDocx($dom, $reemplazos);

    // Limpiar párrafos vacíos innecesarios (solo en espacios entre secciones)
    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
    
    // Remover párrafos que solo contienen elementos vacíos <t/> o solo espacios
    $emptyParagraphs = $xpath->query('//w:p[not(.//w:t[normalize-space()])]');
    foreach ($emptyParagraphs as $para) {
        // Solo remover si está entre dos párrafos normales (no es parte de una estructura importante)
        if ($para->parentNode) {
            $para->parentNode->removeChild($para);
        }
    }

    $zip->deleteName('word/document.xml');
    $zip->addFromString('word/document.xml', $dom->saveXML());
    $zip->close();
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
$fecha_larga_texto = fechaEnPalabras($fecha);
$hora_inicio_fmt = formatearHora($hora_inicio);
$hora_cierre_fmt = formatearHora($hora_cierre);
$hora_inicio_texto = horaEnPalabras($hora_inicio);
$hora_cierre_texto = horaEnPalabras($hora_cierre);

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

$texto_observaciones = $tipo_observaciones . ':';
if ($tipo_observaciones === 'Con Observaciones' && $observaciones_detalle !== '') {
    $texto_observaciones .= ' ' . $observaciones_detalle;
}

$texto_mencion = '';
if ($mencion !== '') {
    $texto_mencion = 'Mención honorífica otorgada: ' . $mencion . '.';
}

$parrafo_inicial = 'ACTA N.° ' . $numero_acta . '. En la fecha ' . $fecha_larga . ' (' . $fecha_larga_texto . ')' .
    ', a las ' . $hora_inicio_fmt . ' (' . $hora_inicio_texto . ') horas, se reunieron las personas integrantes del Tribunal Examinador conformado por ' .
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
$filename_docx = $dir . 'Acta_' . $base_nombre . '_' . $timestamp . '.docx';
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

$pdf->Ln(4);

/*
|--------------------------------------------------------------------------
| FIRMAS
|--------------------------------------------------------------------------
*/
$bottomMargin = 20;
$pageHeight   = 297;
$espacioDisponible = $pageHeight - $bottomMargin - $pdf->GetY();
$altoBloqueFirmas = $hay_segundo_postulante ? 72 : 60;

if ($espacioDisponible < $altoBloqueFirmas) {
    $pdf->AddPage();
}

$startY   = $pdf->GetY() + 5;
$leftX    = 16;
$rightX   = 110;
$wFirma   = 80;
$saltoFila = 24;
$saltoPost = 26;

/* Tribunal - fila 1 */
dibujarFirma($pdf, $leftX,  $startY, $wFirma, $presidente_nombre, $presidente_cargo);
dibujarFirma($pdf, $rightX, $startY, $wFirma, $director_nombre,   $director_cargo);

/* Tribunal - fila 2 */
$ySegundaFila = $startY + $saltoFila;
dibujarFirma($pdf, $leftX,  $ySegundaFila, $wFirma, $tutor_nombre,  $tutor_cargo);
dibujarFirma($pdf, $rightX, $ySegundaFila, $wFirma, $asesor_nombre, $asesor_cargo);

/* Postulantes */
$yPostulantes = $ySegundaFila + $saltoPost;

if ($hay_segundo_postulante) {
    dibujarFirma($pdf, $leftX,  $yPostulantes, $wFirma, $nombre_estudiante_1, 'Postulante');
    dibujarFirma($pdf, $rightX, $yPostulantes, $wFirma, $nombre_estudiante_2, 'Postulante');
} else {
    dibujarFirma($pdf, 55, $yPostulantes, 100, $nombre_estudiante_1, 'Postulante');
}

/* Guardar PDF */
$pdf->Output('F', $filename_pdf);

/*
|--------------------------------------------------------------------------
| GENERAR DOCX
|--------------------------------------------------------------------------
*/
$docx_generado = false;
$docx_error = '';
try {
    $docx_template = __DIR__ . '/templates/ACTA PRES.PUB-BORRADOR.docx';
    $observaciones_docx = $observaciones_detalle;
    if ($observaciones_docx === '') {
        $observaciones_docx = $tipo_observaciones;
    }

    $reemplazos_docx = [
        '{{numero_acta}}' => xmlEscape($numero_acta),
        '{{hora_inicio_texto}}' => xmlEscape($hora_inicio_texto),
        '{{hora_cierre_texto}}' => xmlEscape($hora_cierre_texto),
        '{{fecha_larga_texto}}' => xmlEscape($fecha_larga_texto),
        '{{modalidad_sesion}}' => xmlEscape($modalidad_sesion),
        '{{plataforma}}' => xmlEscape($plataforma),
        '{{titulo_tfg}}' => xmlEscape($titulo_tfg),
        '{{postulantes_nombres}}' => xmlEscape($postulantes_nombres),
        '{{postulantes_cedulas}}' => xmlEscape($postulantes_cedulas),
        '{{modalidad_tfg}}' => xmlEscape($modalidad_tfg),
        '{{grado}}' => xmlEscape($grado),
        '{{presidente_nombre}}' => xmlEscape($presidente_nombre),
        '{{presidente_cargo}}' => xmlEscape($presidente_cargo),
        '{{director_nombre}}' => xmlEscape($director_nombre),
        '{{director_cargo}}' => xmlEscape($director_cargo),
        '{{tutor_nombre}}' => xmlEscape($tutor_nombre),
        '{{tutor_cargo}}' => xmlEscape($tutor_cargo),
        '{{asesor_nombre}}' => xmlEscape($asesor_nombre),
        '{{asesor_cargo}}' => xmlEscape($asesor_cargo),
        '{{resultado}}' => xmlEscape($resultado),
        '{{nota}}' => xmlEscape($nota),
        '{{tipo_observaciones}}' => xmlEscape($tipo_observaciones),
        '{{mencion}}' => xmlEscape($mencion),
        '{{postulante1_nombre}}' => xmlEscape($nombre_estudiante_1),
        '{{postulante1_cedula}}' => xmlEscape($cedula_estudiante_1),
        '{{postulante2_nombre}}' => xmlEscape(!empty($nombre_estudiante_2) ? $nombre_estudiante_2 : ''),
        '{{postulante2_cedula}}' => xmlEscape(!empty($cedula_estudiante_2) ? $cedula_estudiante_2 : ''),
        '{{observaciones_detalle}}' => xmlEscape($observaciones_docx),
    ];

    generarDocxDesdePlantilla($docx_template, $filename_docx, $reemplazos_docx, [
        'sin_postulante2' => !$hay_segundo_postulante
    ]);
    $docx_generado = true;
} catch (Throwable $e) {
    $docx_error = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    error_log('DOCX acta: ' . $e->getMessage());
}

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
| SINCRONIZACIÓN GOOGLE CALENDAR
|--------------------------------------------------------------------------
*/
try {
    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/inc/db/bdcommon.inc';

    $gc_db_host = isset($db_host) ? $db_host : (getenv('DB_HOST') ?: 'localhost');
    $gc_db_user = isset($usuario) ? $usuario : (getenv('DB_USER') ?: 'root');
    $gc_db_pass = isset($clave) ? $clave : (getenv('DB_PASS') ?: '');
    $gc_db_name = isset($db) ? $db : (getenv('DB_NAME') ?: 'base_db');

    $gc_conn = new mysqli($gc_db_host, $gc_db_user, $gc_db_pass, $gc_db_name);
    if (!$gc_conn->connect_error) {
        $gc_conn->set_charset('utf8');

        if (class_exists('Service\GoogleCalendarService')) {
            $googleCalendarService = new Service\GoogleCalendarService($gc_conn);

            // Obtener todos los usuarios asociados al proyecto (estudiantes + comité)
            $gc_user_ids = [];
            
            // 1. Obtener cedulas de estudiantes y buscar sus user_ids
            $cedulas = array_filter([$cedula_estudiante_1, $cedula_estudiante_2]);
            foreach ($cedulas as $cedula) {
                $stmtUser = $gc_conn->prepare("SELECT id FROM sis_user WHERE id = ? LIMIT 1");
                if ($stmtUser) {
                    $stmtUser->bind_param('s', $cedula);
                    $stmtUser->execute();
                    $rsUser = $stmtUser->get_result();
                    if ($rsUser && ($rowUser = $rsUser->fetch_assoc())) {
                        $uid = (string) ($rowUser['id'] ?? '');
                        if ($uid !== '') {
                            $gc_user_ids[$uid] = true;
                        }
                    }
                    $stmtUser->close();
                }
            }

            // 2. Obtener IDs de comité (presidente, director, tutor, asesor)
            $comite_names = array_filter([$presidente_nombre, $director_nombre, $tutor_nombre, $asesor_nombre]);
            foreach ($comite_names as $nombre) {
                $stmtComite = $gc_conn->prepare("SELECT id FROM sis_user WHERE nombre LIKE ? LIMIT 1");
                if ($stmtComite) {
                    $searchName = '%' . $nombre . '%';
                    $stmtComite->bind_param('s', $searchName);
                    $stmtComite->execute();
                    $rsComite = $stmtComite->get_result();
                    if ($rsComite && ($rowComite = $rsComite->fetch_assoc())) {
                        $uid = (string) ($rowComite['id'] ?? '');
                        if ($uid !== '') {
                            $gc_user_ids[$uid] = true;
                        }
                    }
                    $stmtComite->close();
                }
            }

            // 3. Preparar datos del acta
            $gc_attendees = array_filter([
                $nombre_estudiante_1,
                $nombre_estudiante_2,
                $presidente_nombre,
                $director_nombre,
                $tutor_nombre,
                $asesor_nombre,
            ]);

            $minutesData = [
                'project_name' => $titulo_tfg,
                'meeting_date' => $fecha,
                'attendees'    => implode(', ', $gc_attendees),
                'notes'        => $observaciones_detalle,
            ];

            // 4. Sincronizar para cada usuario
            foreach (array_keys($gc_user_ids) as $gc_user_id) {
                $gc_user_id = (string) $gc_user_id;
                
                if ($googleCalendarService->isSyncEnabled($gc_user_id)) {
                    try {
                        $googleCalendarService->syncProjectMinutes(
                            $gc_user_id,
                            $numero_acta,
                            $minutesData
                        );
                    } catch (Throwable $e) {
                        error_log('Google Calendar sync (acta) para usuario ' . $gc_user_id . ': ' . $e->getMessage());
                    }
                }
            }
        }
        $gc_conn->close();
    }
} catch (Throwable $e) {
    error_log('Google Calendar sync (acta): ' . $e->getMessage());
}

/*
|--------------------------------------------------------------------------
| RESPUESTA HTML
|--------------------------------------------------------------------------
*/
$pdf_url = htmlspecialchars($filename_pdf, ENT_QUOTES, 'UTF-8');
$docx_url = htmlspecialchars($filename_docx, ENT_QUOTES, 'UTF-8');
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
        .alert-error{
            background:#f8d7da;
            color:#721c24;
            padding:12px 16px;
            border-radius:8px;
            margin-bottom:16px;
            border:1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="contenedor">
        <h1>Acta generada correctamente</h1>

        <?php if (!$docx_generado && $docx_error): ?>
            <div class="alert-error">
                <strong>Error al generar DOCX:</strong> <?php echo $docx_error; ?>
            </div>
        <?php endif; ?>

        <p class="detalle">
            Se generaron ambos archivos del acta:
        </p>

        <ul class="detalle">
            <li><strong>DOCX:</strong> <span class="archivo"><?php echo $docx_generado ? $docx_url : 'No disponible'; ?></span></li>
            <li><strong>PDF:</strong> <span class="archivo"><?php echo $pdf_url; ?></span></li>
            <li><strong>CSV:</strong> <span class="archivo"><?php echo $csv_url; ?></span></li>
        </ul>

        <div class="acciones">
            <?php if ($docx_generado): ?>
                <a class="btn btn-pdf" href="<?php echo $docx_url; ?>" target="_blank">Ver DOCX</a>
            <?php endif; ?>
            <a class="btn btn-pdf" href="<?php echo $pdf_url; ?>" target="_blank">Ver PDF</a>
            <a class="btn btn-csv" href="<?php echo $csv_url; ?>" download>Descargar CSV</a>
            <a class="btn btn-volver" href="generar_acta.php">Generar otra acta</a>
        </div>
    </div>
</body>
</html>