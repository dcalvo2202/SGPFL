<?php
/**
 * Obtener Miembros de Proyecto - FRONTEND
 * Sistema SGPFL - Proyecto UNA/ESCINF 2025-07 v3.0
 * USANDO SOLO FRAMEWORK estilo.css EXISTENTE
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Incluir archivos necesarios (snake_case según estándares)
include("../../../functions.php");
include_once(__DIR__ . "/../../../inc/db/bdcommon.inc");
include("ProjectGroup.php");

// Validar parámetros de entrada (snake_case)
$project_id = $_GET['project_id'] ?? 0;

if (!$project_id || !is_numeric($project_id)) {
    echo '<div class="alert-una alert-una-warning">
            <i class="bi bi-exclamation-triangle-fill"></i>
            ID de proyecto no válido
          </div>';
    exit;
}

// Obtener datos usando backend (snake_case)
$members = ProjectGroup::get_project_members($project_id);
$project_info = ProjectGroup::get_project_by_id($project_id);

if (empty($members)) {
    echo '<div class="alert-una alert-una-info">
            <i class="bi bi-info-circle"></i>
            No se encontraron integrantes para este proyecto
          </div>';
    exit;
}
?>

<!-- Header con información del proyecto usando clases UNA existentes -->
<?php if ($project_info): ?>
<div class="card-una mb-una-lg">
    <div class="card-una-header">
        <h6 style="margin: 0; color: var(--blanco-una);">
            <i class="bi bi-folder-fill"></i>
            <?= htmlspecialchars($project_info['project_title']) ?>
        </h6>
    </div>
    <div style="padding: var(--spacing-md);">
        <div class="row text-center small">
            <div class="col-4">
                <i class="bi bi-tag-fill text-azul-una"></i><br>
                <strong>Tipo:</strong><br>
                <?= htmlspecialchars($project_info['type_name']) ?>
            </div>
            <div class="col-4">
                <i class="bi bi-people-fill text-azul-una"></i><br>
                <strong>Integrantes:</strong><br>
                <?= count($members) ?> / <?= $project_info['max_members'] ?>
            </div>
            <div class="col-4">
                <i class="bi bi-flag-fill text-azul-una"></i><br>
                <strong>Estado:</strong><br>
                <?= htmlspecialchars($project_info['status']) ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Listado de integrantes usando clases UNA existentes -->
<div class="row">
    <?php foreach ($members as $index => $member): ?>
        <?php
        // Variables usando snake_case según estándares
        $is_leader = $member['role'] === 'Líder';
        $icon_class = $is_leader ? 'bi-star-fill' : 'bi-person-fill';
        $border_style = $is_leader ? 'border-left: 4px solid var(--rojo-una);' : 'border-left: 4px solid var(--gris-una);';
        ?>
        
        <div class="col-md-6 mb-una-md">
            <div class="card-una" style="<?= $border_style ?>">
                <div class="d-flex align-items-center">
                    <!-- Icono de rol -->
                    <div class="me-3 text-center" style="min-width: 60px;">
                        <i class="<?= $icon_class ?> <?= $is_leader ? 'text-rojo-una' : 'text-azul-una' ?>" 
                           style="font-size: 1.5rem; margin-bottom: 0.25rem; display: block;"></i>
                        <div class="badge-una <?= $is_leader ? 'badge-una-primary' : 'badge-una-neutral' ?>">
                            <?= $member['role'] ?>
                        </div>
                    </div>
                    
                    <!-- Información del miembro -->
                    <div class="flex-grow-1">
                        <h6 class="text-azul-una mb-una-xs" style="font-family: var(--font-titulos); font-weight: 600;">
                            <?= htmlspecialchars($member['nombre']) ?>
                        </h6>
                        <div class="text-gris-una mb-una-xs" style="font-size: 0.875rem;">
                            <i class="bi bi-envelope"></i> 
                            <?= htmlspecialchars($member['email']) ?>
                        </div>
                        <div class="text-gris-una mb-una-xs" style="font-size: 0.875rem;">
                            <i class="bi bi-hash"></i> 
                            ID: <?= htmlspecialchars($member['user_id']) ?>
                        </div>
                        <?php if (isset($member['joined_at'])): ?>
                            <div class="text-gris-una" style="font-size: 0.875rem;">
                                <i class="bi bi-calendar-check"></i>
                                Unido: <?= date('d/m/Y', strtotime($member['joined_at'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Badge de líder -->
                    <?php if ($is_leader): ?>
                        <div class="text-end">
                            <div class="badge-una badge-una-primary" 
                                 style="padding: 0.5rem; border-radius: 20px; font-weight: 600;">
                                <i class="bi bi-crown"></i>
                                Líder
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Estadísticas del equipo usando clases UNA existentes -->
<div class="card-una mt-una-lg" 
     style="background: linear-gradient(135deg, rgba(3, 73, 145, 0.05) 0%, rgba(205, 23, 25, 0.05) 100%);">
    <h6 class="text-azul-una mb-una-md" style="font-family: var(--font-titulos); display: flex; align-items: center; gap: 0.5rem;">
        <i class="bi bi-bar-chart-fill"></i>
        Estadísticas del Equipo
    </h6>
    
    <div class="row text-center">
        <div class="col-3">
            <div class="stat-card" style="padding: var(--spacing-md); margin: 0; min-height: auto;">
                <h3 style="font-size: 1.5rem; margin-bottom: 0.25rem;"><?= count($members) ?></h3>
                <p style="font-size: 0.7rem; margin: 0; text-transform: uppercase; letter-spacing: 0.5px;">Total</p>
            </div>
        </div>
        <div class="col-3">
            <div class="stat-card" style="padding: var(--spacing-md); margin: 0; min-height: auto;">
                <h3 style="font-size: 1.5rem; margin-bottom: 0.25rem;">
                    <?= count(array_filter($members, function($m) { return $m['role'] === 'Líder'; })) ?>
                </h3>
                <p style="font-size: 0.7rem; margin: 0; text-transform: uppercase; letter-spacing: 0.5px;">Líderes</p>
            </div>
        </div>
        <div class="col-3">
            <div class="stat-card" style="padding: var(--spacing-md); margin: 0; min-height: auto;">
                <h3 style="font-size: 1.5rem; margin-bottom: 0.25rem;">
                    <?= count(array_filter($members, function($m) { return $m['role'] === 'Miembro'; })) ?>
                </h3>
                <p style="font-size: 0.7rem; margin: 0; text-transform: uppercase; letter-spacing: 0.5px;">Miembros</p>
            </div>
        </div>
        <div class="col-3">
            <div class="stat-card" style="padding: var(--spacing-md); margin: 0; min-height: auto;">
                <h3 style="font-size: 1.5rem; margin-bottom: 0.25rem;">
                    <?= $project_info['max_members'] - count($members) ?>
                </h3>
                <p style="font-size: 0.7rem; margin: 0; text-transform: uppercase; letter-spacing: 0.5px;">Libres</p>
            </div>
        </div>
    </div>
</div>

<!-- Información adicional usando clases UNA existentes -->
<div class="alert-una alert-una-info mt-una-md">
    <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
        <i class="bi bi-info-circle-fill text-azul-una" style="font-size: 1.25rem; margin-top: 0.125rem;"></i>
        <div>
            <strong>Información del Proyecto:</strong><br>
            Este proyecto permite un máximo de <span class="text-rojo-una" style="font-weight: 600;"><?= $project_info['max_members'] ?> integrantes</span>. 
            Actualmente tiene <span class="text-rojo-una" style="font-weight: 600;"><?= count($members) ?> miembros</span> registrados.
            <?php if (count($members) < $project_info['max_members']): ?>
                Aún pueden unirse <span class="text-rojo-una" style="font-weight: 600;"><?= $project_info['max_members'] - count($members) ?> personas</span> más.
            <?php else: ?>
                El proyecto está completo.
            <?php endif; ?>
        </div>
    </div>
</div>