<?php
/**
 * Reorganiza la sección de firmas en la plantilla DOCX
 * Patrón final:
 * - Presidente (centrado)
 * - Director + Tutor (línea)
 * - Asesor (centrado)
 * - Postulantes (línea)
 * - [Espacio en blanco para escribir]
 * - Líneas decorativas (firmas)
 */

$docx_path = 'c:\\xampp\\htdocs\\base\\templates\\ACTA PRES.PUB-BORRADOR.docx';
$extract_path = 'c:\\xampp\\htdocs\\base\\templates\\temp_reorg_final';

// Cleanup
if (file_exists($extract_path)) {
    system("rmdir /s /q $extract_path 2>nul");
}
mkdir($extract_path, 0777, true);

// Extract
$zip = new ZipArchive();
if (!$zip->open($docx_path)) {
    die("ERROR: Can't open DOCX\n");
}
$zip->extractTo($extract_path);
$zip->close();

// Load XML
$xml_path = $extract_path . '\\word\\document.xml';
$dom = new DOMDocument();
$dom->preserveWhiteSpace = false;
$dom->load($xml_path);

$xpath = new DOMXPath($dom);
$xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

$body = $xpath->query('//w:body')->item(0);

// Helper function
function get_text($para, $xpath) {
    $texts = $xpath->query('.//w:t', $para);
    $res = '';
    foreach ($texts as $t) {
        $res .= $t->nodeValue;
    }
    return $res;
}

// Find all paragraphs
$all_paras = [];
foreach ($xpath->query('//w:p') as $p) {
    $all_paras[] = $p;
}

// Collect elements
$elements = [
    'table' => $xpath->query('//w:tbl')->item(0),
    'pres_name' => null,
    'dir_name' => null,
    'dir_cargo' => null,
    'tut_name' => null,
    'tut_cargo' => null,
    'asesor_name' => null,
    'asesor_cargo' => null,
    'post1' => null,
    'obs' => null,
    'picture_paras' => [],
];

foreach ($all_paras as $p) {
    $txt = get_text($p, $xpath);
    
    if (!$elements['pres_name'] && strpos($txt, '{{presidente_nombre}}') !== false)
        $elements['pres_name'] = $p;
    elseif (!$elements['dir_name'] && strpos($txt, '{{director_nombre}}') !== false)
        $elements['dir_name'] = $p;
    elseif (!$elements['dir_cargo'] && strpos($txt, '{{director_cargo}}') !== false)
        $elements['dir_cargo'] = $p;
    elseif (!$elements['tut_name'] && strpos($txt, '{{tutor_nombre}}') !== false)
        $elements['tut_name'] = $p;
    elseif (!$elements['tut_cargo'] && strpos($txt, '{{tutor_cargo}}') !== false)
        $elements['tut_cargo'] = $p;
    elseif (!$elements['asesor_name'] && strpos($txt, '{{asesor_nombre}}') !== false)
        $elements['asesor_name'] = $p;
    elseif (!$elements['asesor_cargo'] && strpos($txt, '{{asesor_cargo}}') !== false)
        $elements['asesor_cargo'] = $p;
    elseif (!$elements['post1'] && strpos($txt, '{{postulante1_nombre}}') !== false)
        $elements['post1'] = $p;
    elseif (!$elements['obs'] && strpos($txt, 'OBSERVACIONES') !== false)
        $elements['obs'] = $p;
    
    // Find picture paragraphs
    if ($xpath->query('.//w:pict', $p)->length > 0) {
        $elements['picture_paras'][] = $p;
    }
}

// Count what we found
$count_found = 0;
foreach (['pres_name', 'dir_name', 'dir_cargo', 'tut_name', 'tut_cargo', 'asesor_name', 'asesor_cargo', 'post1', 'obs'] as $k) {
    if ($elements[$k]) $count_found++;
}

echo "Found elements: $count_found/9\n";
echo "Picture paragraphs: " . count($elements['picture_paras']) . "\n";

if ($count_found < 9) {
    die("ERROR: Missing elements\n");
}

// Plan: Reorder by moving paragraphs to new positions
// Order should be: presidente_name, director_name, director_cargo, tutor_name, tutor_cargo,
// asesor_name, asesor_cargo, postulante1, postulante2, observaciones, [blank], [pictures]

$reorder = [
    'pres_name',
    'dir_name',
    'dir_cargo',
    'tut_name',
    'tut_cargo',
    'asesor_name',
    'asesor_cargo',
    'post1',
    'obs'
];

// Find insertion point (after table)
$table = $elements['table'];
$insert_after = $table;

// Move elements in correct order
foreach ($reorder as $key) {
    $para = $elements[$key];
    if (!$para) continue;
    
    // Remove from current position
    $para->parentNode->removeChild($para);
    
    // Insert after current insert point
    if ($insert_after->nextSibling) {
        $insert_after->parentNode->insertBefore($para, $insert_after->nextSibling);
    } else {
        $insert_after->parentNode->appendChild($para);
    }
    
    // Update insertion point
    $insert_after = $para;
}

echo "Reordering done\n";

// Save
$dom->save($xml_path);

// Repack
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

// Cleanup
system("rmdir /s /q $extract_path 2>nul");

echo "✓ DOCX reorganizado exitosamente\n";
echo "Archivo: $docx_path\n";
?>
