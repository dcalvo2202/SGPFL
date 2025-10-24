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

// Crear PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->Image('img/Logo-UNA-Rojo_FondoTransparente.png', 10, 10, 30);
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 40, utf8_decode('Acta de Proyecto Final de Graduación de Licenciatura'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, "Estudiante: $nombre (ID: $id_estudiante)", 0, 1);
$pdf->Cell(0, 10, utf8_decode("Calificación: $nota"), 0, 1);
$pdf->MultiCell(0, 10, "Tribunal: $tribunal");
$pdf->Cell(0, 10, "Fecha: $fecha", 0, 1);
$pdf->Ln(20);
$pdf->Cell(0, 10, 'Acta No: UNA-AS-EI-ACUE-001-2025', 0, 1, 'R');

// Guardar y mostrar
$filename = $dir . "Acta_" . preg_replace("/[^a-zA-Z0-9]/", "_", $nombre) . "_" . date("Ymd_His") . ".pdf";
$pdf->Output('F', $filename);
$pdf->Output('I', 'Acta.pdf');
?>
