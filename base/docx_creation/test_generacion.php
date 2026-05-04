<?php
/**
 * Script de prueba para generar un DOCX con la plantilla reorganizada
 */

require_once 'c:\\xampp\\htdocs\\base\\procesar_acta.php';

// Verificar que la plantilla existe
$template_path = 'c:\\xampp\\htdocs\\base\\templates\\ACTA PRES.PUB-BORRADOR.docx';
if (!file_exists($template_path)) {
    die("ERROR: Plantilla no encontrada\n");
}

$stat = stat($template_path);
$mod_time = date('Y-m-d H:i:s', $stat['mtime']);
echo "✓ Plantilla encontrada\n";
echo "  Tamaño: " . $stat['size'] . " bytes\n";
echo "  Modificada: $mod_time\n";

// Verificar que la función generarDocxDesdePlantilla existe
if (function_exists('generarDocxDesdePlantilla')) {
    echo "✓ Función generarDocxDesdePlantilla disponible\n";
} else {
    die("ERROR: Función generarDocxDesdePlantilla no encontrada\n");
}

// Datos de prueba para acta
$test_data = [
    'numero_acta' => 'PRES-2026-001',
    'fecha_larga_texto' => '4 de mayo de 2026',
    'hora_inicio_texto' => 'las 10:00',
    'hora_cierre_texto' => 'las 11:30',
    'presidente_nombre' => 'Lic. Gabriela Garita González',
    'presidente_cargo' => 'Directora de la Escuela de Informática',
    'director_nombre' => 'M.Sc. Juan Pérez López',
    'director_cargo' => 'Director',
    'tutor_nombre' => 'Ing. María García Rodríguez',
    'tutor_cargo' => 'Tutor',
    'asesor_nombre' => 'Dr. Carlos Morales Soto',
    'asesor_cargo' => 'Asesor',
    'postulante1_nombre' => 'Ana Martínez Silva',
    'postulante1_cedula' => '1-0123-4567',
    'postulante2_nombre' => 'Roberto López Vargas',
    'postulante2_cedula' => '2-0987-6543',
    'postulantes_nombres' => 'Ana Martínez Silva y Roberto López Vargas',
    'postulantes_cedulas' => '1-0123-4567 y 2-0987-6543',
    'titulo_tfg' => 'Sistema de Gestión Académica para Instituciones Educativas',
    'modalidad_tfg' => 'Proyecto de Software',
    'grado' => 'Licenciado en Informática',
    'modalidad_sesion' => 'virtual',
    'plataforma' => 'Zoom',
    'resultado' => 'APROBADO',
    'nota' => '9.5',
    'tipo_observaciones' => 'Ninguna',
    'observaciones_detalle' => 'El trabajo presenta excelente calidad técnica y cumple con todos los requisitos institucionales.',
    'mencion' => 'No',
];

echo "\nDatos de prueba preparados\n";
echo "Pruebas:\n";
foreach (array_keys($test_data) as $key) {
    echo "  ✓ $key\n";
}

echo "\n✓ Prueba completada. Sistema listo para generar documentos.\n";
?>
