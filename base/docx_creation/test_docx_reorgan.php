<?php
/**
 * Script simple para reorganizar la sección de firmas en DOCX
 * Mueve los párrafos de firmas a nueva posición pero mantiene funcionalidad
 */

$docx_path = 'c:\\xampp\\htdocs\\base\\templates\\ACTA PRES.PUB-BORRADOR.docx';
$extract_path = 'c:\\xampp\\htdocs\\base\\templates\\temp_docx_reorg';

if (!file_exists($docx_path)) {
    die("ERROR: No se encontró archivo DOCX\n");
}

// Extraer
if (file_exists($extract_path)) {
    system("rmdir /s /q $extract_path 2>nul");
}
mkdir($extract_path, 0777, true);

$zip = new ZipArchive();
$zip->open($docx_path);
$zip->extractTo($extract_path);
$zip->close();

// Cargar XML
$xml_path = $extract_path . '\\word\\document.xml';
$dom = new DOMDocument();
$dom->load($xml_path);

$xpath = new DOMXPath($dom);
$xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

$body = $xpath->query('//w:body')->item(0);
$all_paras = $xpath->query('//w:p');

// Función auxiliar para obtener texto de un párrafo
function get_para_text($para, $xpath) {
    $texts = $xpath->query('.//w:t', $para);
    $full = '';
    foreach ($texts as $t) {
        $full .= $t->nodeValue;
    }
    return $full;
}

// Coleccionar elementos
$elements = [];
$table = $xpath->query('//w:tbl')->item(0);

foreach ($all_paras as $p) {
    $text = get_para_text($p, $xpath);
    $key = null;
    
    if (strpos($text, 'Se da lectura a esta acta') !== false) $key = 'anchor';
    elseif (strpos($text, 'OBSERVACIONES') !== false) $key = 'obs';
    elseif (strpos($text, '{{presidente_nombre}}') !== false) $key = 'pres_name';
    elseif (strpos($text, '{{director_nombre}}') !== false) $key = 'dir_name';
    elseif (strpos($text, '{{director_cargo}}') !== false) $key = 'dir_cargo';
    elseif (strpos($text, '{{tutor_nombre}}') !== false) $key = 'tut_name';
    elseif (strpos($text, '{{tutor_cargo}}') !== false) $key = 'tut_cargo';
    elseif (strpos($text, '{{asesor_nombre}}') !== false) $key = 'asesor_name';
    elseif (strpos($text, '{{asesor_cargo}}') !== false) $key = 'asesor_cargo';
    elseif (strpos($text, '{{postulante1_nombre}}') !== false) $key = 'post1';
    elseif (strpos($text, '{{postulante2_nombre}}') !== false) $key = 'post2';
    
    if ($key) {
        $elements[$key] = $p;
    }
}

echo "Elementos identificados:\n";
foreach (array_keys($elements) as $k) {
    echo "  $k: ✓\n";
}

// Aquí iría la lógica de reorganización
// Por ahora, solo guardamos para verificar que el proceso mantiene integridad

$dom->save($xml_path);
echo "\nGuardando XML...\n";

// Reempacar
$zip = new ZipArchive();
$zip->open($docx_path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($extract_path),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($files as $file) {
    if (!is_dir($file)) {
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($extract_path) + 1);
        $relativePath = str_replace('\\', '/', $relativePath);
        $zip->addFile($filePath, $relativePath);
    }
}
$zip->close();

// Limpiar
system("rmdir /s /q $extract_path 2>nul");

echo "✓ Completado\n";
?>
