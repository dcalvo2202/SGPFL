<?php   
// Normaliza $base_url si no viene definido por la página
if (!isset($base_url) || !$base_url) {
    $cds_domain = isset($mySessionController) ? ($mySessionController->getVar("cds_domain") ?? '') : '';
    $cds_locate = isset($mySessionController) ? ($mySessionController->getVar("cds_locate") ?? '/base/') : '/base/';
    $base_url = rtrim($cds_domain, '/') . '/' . trim($cds_locate, '/') . '/';
}
?>
<!-- JQuery -->
<script src="<?= $base_url ?>lib/jquery-3.1.0.min.js" type="text/javascript"></script>
<!-- SweetAlert2 -->
<script src="<?= $base_url ?>lib/sweetalert2/sweetalert2-v11-23-0.js" type="text/javascript"></script>
<!-- JQuery Alerts-->
<link href="<?= $base_url ?>lib/jquery-alerts/jquery.alerts.css" rel="stylesheet" type="text/css"/>
<script src="<?= $base_url ?>lib/jquery-alerts/jquery.alerts.js" type="text/javascript"></script>

<!-- JQuery DataTables-->
<script src="<?= $base_url ?>lib/DataTables/media/js/jquery.dataTables.min.js" type="text/javascript"></script>
<script src="<?= $base_url ?>lib/DataTables/media/js/dataTables.bootstrap.min.js" type="text/javascript"></script>
<link href="<?= $base_url ?>lib/DataTables/media/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css"/>
<link href="<?= $base_url ?>lib/DataTables/media/css/dataTables.bootstrap.min.css" rel="stylesheet" type="text/css"/>

<!-- Prototype EvalScript -->
<?php if (empty($disable_prototype_js)): ?>
<script src="<?= $base_url ?>inc/js/prototype.js" type="text/javascript"></script>
<?php endif; ?>

<!-- Main  -->
<?php if (empty($disable_prototype_js)): ?>
<script src="<?= $base_url ?>inc/js/main.js" type="text/javascript"></script>
<script src="<?= $base_url ?>inc/js/validator.js" type="text/javascript"></script>

<!-- Modulos -->
<script src="<?= $base_url ?>inc/js/login.js" type="text/javascript"></script>
<script src="<?= $base_url ?>inc/js/permit.js" type="text/javascript"></script>
<script src="<?= $base_url ?>inc/js/roll.js" type="text/javascript"></script>
<script src="<?= $base_url ?>inc/js/user.js" type="text/javascript"></script>
<?php endif; ?>

<!-- Bootstrap + Font-Awesome + Estilo -->
<link href="<?= $base_url ?>lib/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css"/>  
<link href="<?= $base_url ?>lib/bootstrap/css/bootstrap-theme.min.css" rel="stylesheet" type="text/css"/>
<script src="<?= $base_url ?>lib/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
<script src="<?= $base_url ?>lib/bootstrap/js/bootstrap-filestyle.min.js" type="text/javascript"></script>
<link href="<?= $base_url ?>lib/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css"/>
<script src="<?= $base_url ?>lib/calendar/calendar.js" type="text/javascript"></script>
<link href="<?= $base_url ?>lib/calendar/calendar.css" rel="stylesheet" type="text/css"/>
<link href="<?= $base_url ?>inc/css/estilo.css" rel="stylesheet" type="text/css"/>
<link href="<?= $base_url ?>inc/css/panel_estudiante.css" rel="stylesheet" type="text/css"/>
<link href="<?= $base_url ?>inc/css/tfg_upload.css" rel="stylesheet" type="text/css"/>

<?php require_once 'config.inc';?>