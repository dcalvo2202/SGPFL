<?php
/**
 * Script para reorganizar la sección de firmas en la plantilla DOCX
 * Patrón deseado:
 * 1. Presidente (solo, centrado)
 * 2. Director + Tutor (dos columnas)
 * 3. Asesor (solo, centrado)
 * 4. Postulantes 1 + 2 (dos columnas)
 * 5. Observaciones
 * 6. Espacios en blanco
 * 7. Líneas decorativas (firmas)
 */

// Rutas
$docx_path = 'c:\\xampp\\htdocs\\base\\templates\\ACTA PRES.PUB-BORRADOR.docx';
$backup_path = 'c:\\xampp\\htdocs\\base\\templates\\ACTA_PRES_BACKUP_'.date('YmdHis').'.docx';
$extract_path = 'c:\\xampp\\htdocs\\base\\templates\\temp_docx_php';

if (!file_exists($docx_path)) {
    die("ERROR: No se encontró el archivo DOCX\n");
}

// Crear backup
if (!copy($docx_path, $backup_path)) {
    die("ERROR: No se pudo crear backup\n");
}
echo "✓ Backup creado: $backup_path\n";

// Limpiar y preparar directorio temporal
if (file_exists($extract_path)) {
    system("rmdir /s /q $extract_path 2>nul");
}
mkdir($extract_path, 0777, true);

// Extraer DOCX (es un ZIP)
$zip = new ZipArchive();
if ($zip->open($docx_path) !== true) {
    die("ERROR: No se pudo abrir DOCX\n");
}
$zip->extractTo($extract_path);
$zip->close();
echo "✓ DOCX extraído a: $extract_path\n";

// Cargar el XML del documento
$xml_path = $extract_path . '\\word\\document.xml';
$dom = new DOMDocument();
$dom->preserveWhiteSpace = false;
if (!$dom->load($xml_path)) {
    die("ERROR: No se pudo cargar document.xml\n");
}

$xpath = new DOMXPath($dom);
$xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

// Buscar la tabla (primero elemento tbl)
$tables = $xpath->query('//w:tbl');
if ($tables->length === 0) {
    die("ERROR: No se encontró tabla\n");
}
$table = $tables->item(0);
echo "✓ Tabla encontrada\n";

// Buscar párrafo ancla "Se da lectura a esta acta"
$anchor_para = null;
$all_paras = $xpath->query('//w:p');
foreach ($all_paras as $p) {
    $text_nodes = $xpath->query('.//w:t', $p);
    $full_text = '';
    foreach ($text_nodes as $t) {
        $full_text .= $t->nodeValue;
    }
    if (strpos($full_text, 'Se da lectura a esta acta') !== false) {
        $anchor_para = $p;
        break;
    }
}

if (!$anchor_para) {
    die("ERROR: No se encontró párrafo ancla\n");
}
echo "✓ Párrafo ancla encontrado\n";

// Para esta versión, implementar la reorganización
// Estrategia:
// 1. Guardar referencias a los párrafos que necesitamos mover
// 2. Ubicar el punto de inserción (después de la tabla)
// 3. Crear el nuevo orden

// Guardar párrafos que necesitamos reorganizar en variables
$paras_a_reorganizar = [];

// También buscar los párrafos de cargo
$pres_cargo_para = null;
$dir_cargo_para = null;
$tut_cargo_para = null;
$asesor_cargo_para = null;
$obs_para = null;
$picture_paras = [];

foreach ($all_paras as $p) {
    $text_nodes = $xpath->query('.//w:t', $p);
    $full_text = '';
    foreach ($text_nodes as $t) {
        $full_text .= $t->nodeValue;
    }
    
    if (strpos($full_text, '{{presidente_cargo}}') !== false && !$pres_cargo_para) {
        $pres_cargo_para = $p;
    }
    if (strpos($full_text, '{{director_cargo}}') !== false && !$dir_cargo_para) {
        $dir_cargo_para = $p;
    }
    if (strpos($full_text, '{{tutor_cargo}}') !== false && !$tut_cargo_para) {
        $tut_cargo_para = $p;
    }
    if (strpos($full_text, '{{asesor_cargo}}') !== false && !$asesor_cargo_para) {
        $asesor_cargo_para = $p;
    }
    if (strpos($full_text, 'OBSERVACIONES') !== false && !$obs_para) {
        $obs_para = $p;
    }
    
    // Detectar párrafos con elementos picture (líneas de firma)
    $picts = $xpath->query('.//w:pict', $p);
    if ($picts->length > 0) {
        $picture_paras[] = $p;
    }
}

// Encontrar el punto donde insertar (después de tabla, antes del párrafo ancla)
$insert_point = $table->nextSibling;
while ($insert_point && $insert_point->nodeType == XML_TEXT_NODE) {
    $insert_point = $insert_point->nextSibling;
}

if (!$insert_point) {
    die("ERROR: No se encontró punto de inserción\n");
}

echo "Reorganizando párrafos...\n";
echo "Punto de inserción encontrado\n";

// Plan de reorganización:
// Crear tabla con estructura 1-2-1-2:
// Row 1: Presidente (solo, merged)
// Row 2: Director | Tutor
// Row 3: Asesor (solo, merged)
// Row 4: Postulante1 | Postulante2

// Por ahora, solo verificar que encontramos todo
if ($pres_cargo_para && $dir_cargo_para && $tut_cargo_para && 
    $asesor_cargo_para && $obs_para && count($picture_paras) > 0) {
    echo "✓ Todos los elementos de reorganización encontrados\n";
    echo "  Párrafos con líneas de firma: " . count($picture_paras) . "\n";
} else {
    echo "✗ Faltaron algunos elementos:\n";
    echo "  Pres cargo: " . ($pres_cargo_para ? "✓" : "✗") . "\n";
    echo "  Dir cargo: " . ($dir_cargo_para ? "✓" : "✗") . "\n";
    echo "  Tut cargo: " . ($tut_cargo_para ? "✓" : "✗") . "\n";
    echo "  Asesor cargo: " . ($asesor_cargo_para ? "✓" : "✗") . "\n";
    echo "  Observaciones: " . ($obs_para ? "✓" : "✗") . "\n";
    echo "  Picture paras: " . count($picture_paras) . "\n";
}

// Por ahora, solo guardamos sin cambios para verificar que el proceso funciona
$dom->save($xml_path);
echo "\n✓ Document.xml guardado\n";

// Reempacar el DOCX
echo "Reempacando DOCX...\n";
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
echo "✓ DOCX reempacado\n";

// Limpiar temporal
system("rmdir /s /q $extract_path 2>nul");

echo "\n✓ Proceso completado exitosamente\n";
echo "Archivo: $docx_path\n";
?>
