<?php
// includes.php comentado porque causa problemas con rutas relativas en subdirectorios
// include('includes.php');
include('config.inc');
include('lang/lang.es');

// Verificar y definir $base_url si no está definido
if (!isset($base_url) || !$base_url) {
    $cds_domain = isset($mySessionController) ? ($mySessionController->getVar("cds_domain") ?? '') : '';
    $cds_locate = isset($mySessionController) ? ($mySessionController->getVar("cds_locate") ?? '/base/') : '/base/';
    $base_url = rtrim($cds_domain, '/') . '/' . trim($cds_locate, '/') . '/';
}

$favicon_url = $base_url . "img/logo.webp";

?>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title><?= $page_title ?></title>
    <!-- Logo de la escuela -->
    <link rel="icon" type="image/webp" href="<?= $favicon_url ?>">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Estilos personalizados -->
    <link href="<?= htmlspecialchars($base_url . 'inc/css/estilo.css') ?>" rel="stylesheet">
    <link href="<?= htmlspecialchars($base_url . 'inc/css/panel_estudiante.css') ?>" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <?php 
    // Cargar CSS adicionales si están definidos
    if (isset($additional_css) && is_array($additional_css)) {
        foreach ($additional_css as $css_file) {
            echo '<link href="' . htmlspecialchars($base_url . $css_file) . '" rel="stylesheet">' . "\n    ";
        }
    }
    ?>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <?php if (!empty($extra_head)) echo $extra_head; ?>
    <?php if (!empty($inlineStyles)) echo "<style>\n{$inlineStyles}\n</style>"; ?>
</head>