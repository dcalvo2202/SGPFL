<?php
include("../../login/check.php");
include("../../../functions.php");
include("../../../inc/db/db.php");

$vocab = $mySessionController->getVar("vocab");
$user_rol = (int)$mySessionController->getVar("rol");

if ($user_rol !== 1 || !check_permiso($mod3, $act2, $user_rol)) {
    echo '<div class="alert alert-danger" style="margin:20px;">Acceso denegado: no tiene permisos para visualizar la auditoría de accesos.</div>';
    exit();
}

$sql = "SELECT
            l.id_bi,
            l.date_bi,
            l.id_user,
            u.nombre,
            l.action_type,
            l.action_result,
            l.ip_address,
            l.device_info,
            l.detail
        FROM sis_log l
        LEFT JOIN sis_user u ON u.id = l.id_user
        ORDER BY l.date_bi DESC
        LIMIT 500";

$logs = seleccion($sql);
if (!is_array($logs)) {
    $logs = [];
}
?>

<div class="well well-sm"><h1>Auditoría de Accesos al Sistema</h1></div>

<div class="panel panel-default">
    <div class="panel-body">
        <p>
            <strong>Resumen:</strong> Mostrando los últimos <strong>500</strong> registros de acceso y eventos de seguridad
            (incluye intentos fallidos y alertas de múltiples intentos por IP).
        </p>
        <div class="table-responsive">
            <table id="audit_log_table" class="table table-striped table-bordered dataTable" style="width:100%">
                <thead>
                    <tr>
                        <th>Fecha/Hora</th>
                        <th>Usuario</th>
                        <th>Nombre</th>
                        <th>Acción</th>
                        <th>Resultado</th>
                        <th>IP</th>
                        <th>Dispositivo</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$row['date_bi']) ?></td>
                            <td><?= htmlspecialchars((string)$row['id_user']) ?></td>
                            <td><?= htmlspecialchars((string)$row['nombre']) ?></td>
                            <td><?= htmlspecialchars((string)$row['action_type']) ?></td>
                            <td>
                                <?php $result = strtoupper((string)$row['action_result']); ?>
                                <?php if ($result === 'SUCCESS'): ?>
                                    <span class="label label-success">SUCCESS</span>
                                <?php elseif ($result === 'FAIL'): ?>
                                    <span class="label label-danger">FAIL</span>
                                <?php elseif ($result === 'ALERT'): ?>
                                    <span class="label label-warning">ALERT</span>
                                <?php else: ?>
                                    <span class="label label-default"><?= htmlspecialchars($result) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars((string)$row['ip_address']) ?></td>
                            <td><?= htmlspecialchars((string)$row['device_info']) ?></td>
                            <td><?= htmlspecialchars((string)$row['detail']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script type="text/javascript">
    jQuery(document).ready(function () {
        jQuery('#audit_log_table').dataTable({
            "aaSorting": [[0, 'desc']],
            "iDisplayLength": 25,
            "aLengthMenu": [[10, 25, 50, 100], [10, 25, 50, 100]],
            "language": {
                "sSearch": "Buscar:",
                "sLengthMenu": "Mostrar _MENU_ registros",
                "sInfo": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                "sInfoEmpty": "Mostrando 0 a 0 de 0 registros",
                "sZeroRecords": "No se encontraron registros",
                "oPaginate": {
                    "sFirst": "Primero",
                    "sLast": "Último",
                    "sNext": "Siguiente",
                    "sPrevious": "Anterior"
                }
            }
        });
    });
</script>