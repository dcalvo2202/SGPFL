<?php
require('fpdf186/fpdf.php');
require 'vendor/autoload.php'; // Necesario para PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Datos recibidos
$nombre        = $_POST['nombre'];
$id_estudiante = $_POST['id_estudiante'];
$nota          = $_POST['nota'];
$tribunal      = $_POST['tribunal'];
$fecha         = $_POST['fecha'];

// Crear carpeta si no existe
$dir = "actas/";
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

// ===================== GENERAR PDF =====================
    // Crear PDF
    $pdf = new FPDF();
    $pdf->AddPage();

    // Logo UNA
    $pdf->Image('img/Logo-UNA-Rojo_FondoTransparente.png',10,10,30);

    // Título
    $pdf->SetFont('Arial','B',16);
    $pdf->Cell(0,40,utf8_decode('Acta de Proyecto Final de Graduación de Licenciatura'),0,1,'C');

    // Contenido
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(0,10,"Estudiante: $nombre (ID: $id_estudiante)",0,1);
    $pdf->Cell(0,10,utf8_decode("Calificación: $nota"),0,1);
    $pdf->MultiCell(0,10,"Tribunal: $tribunal");
    $pdf->Cell(0,10,"Fecha: $fecha",0,1);
    $pdf->Ln(20);
    $pdf->Cell(0,10,'Acta No: UNA-AS-EI-ACUE-001-2025',0,1,'R');

    $timestamp = date("Ymd_His");
    $filename_pdf = $dir . "Acta_" . preg_replace("/[^a-zA-Z0-9]/", "_", $nombre) . "_$timestamp.pdf";
    $pdf->Output('F', $filename_pdf);

   // ===================== GENERAR ARCHIVO CSV (tipo Excel) =====================
    $filename_csv = $dir . "Acta_" . preg_replace("/[^a-zA-Z0-9]/", "_", $nombre) . "_$timestamp.csv";

    // Crear y escribir el archivo
    $fp = fopen($filename_csv, 'w');
    fputcsv($fp, ['Campo', 'Valor']);
    fputcsv($fp, ['Nombre del Estudiante', $nombre]);
    fputcsv($fp, ['ID del Estudiante', $id_estudiante]);
    fputcsv($fp, ['Calificación', $nota]);
    fputcsv($fp, ['Tribunal Evaluador', $tribunal]);
    fputcsv($fp, ['Fecha del Examen', $fecha]);
    fputcsv($fp, ['Acta No', 'UNA-AS-EI-ACUE-001-2025']);
    fclose($fp);

    // ===================== MOSTRAR PDF =====================
    $pdf->Output('I', 'Acta.pdf');
?>
