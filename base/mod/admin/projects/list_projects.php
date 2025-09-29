<?php
/**
 * Listado de Proyectos del Usuario
 * Sistema SGPFL - Proyecto UNA/ESCINF 2025-07
 * Siguiendo estándares oficiales UNA
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

include_once(__DIR__ . "/../../../inc/db/bdcommon.inc");
include_once("ProjectGroup.php");

// COMENTAR ESTAS LÍNEAS TEMPORALMENTE:
// include("../../login/check.php");
// $vocab = $mySessionController->getVar("vocab");
// $user_rol = $mySessionController->getVar("rol");
// $base_url = $mySessionController->getVar("cds_domain") . $mySessionController->getVar("cds_locate");

// VARIABLES TEMPORALES PARA PRUEBAS (snake_case según estándares UNA):
$base_url = "/SGPFL/Sistema-Gestor-de-Proyectos-Finales-de-Licenciatura/base/";
$current_user_id = '112170040'; // Usuario temporal para pruebas

// Obtener proyectos del usuario usando método estándar UNA
$projects = ProjectGroup::get_projects_by_user($current_user_id);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Proyectos - SGPFL</title>
    <!-- CSS Framework oficial UNA -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- CSS personalizado UNA -->
    <link rel="stylesheet" href="../../../inc/css/estilo.css">
    <!-- Tipografías oficiales UNA -->
    <link href="https://fonts.googleapis.com/css2?family=Goudy+Old+Style:wght@400;700&family=Frutiger:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navbar oficial UNA -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-una">
        <div class="container">
            <!-- Logo UNA en esquina superior izquierda -->
            <div class="logo-una">
                UNA
            </div>
            <a class="navbar-brand" href="../../../panel_estudiante.php" id="nav-brand-sgpfl">
                <i class="bi bi-mortarboard-fill"></i> SGPFL - ESCINF
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav-main-menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="nav-main-menu">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../../../panel_estudiante.php" id="nav-link-dashboard">
                            <i class="bi bi-house-fill"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#" id="nav-link-projects">
                            <i class="bi bi-kanban"></i> Mis Proyectos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register_group.php" id="nav-link-register">
                            <i class="bi bi-plus-circle-fill"></i> Nuevo Proyecto
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Header de la página -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 id="page-title-projects">
                    <i class="bi bi-kanban text-primary"></i> Mis Proyectos
                </h1>
                <p class="lead text-muted" id="page-subtitle-projects">
                    Administre todos sus proyectos de trabajo final de graduación
                </p>
            </div>
            <a href="register_group.php" class="btn-una-primary" id="btn-new-project">
                <i class="bi bi-plus-circle-fill"></i> Nuevo Proyecto
            </a>
        </div>

        <?php if(empty($projects)): ?>
            <!-- Estado vacío con diseño UNA -->
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="card-una text-center">
                        <div class="py-5">
                            <i class="bi bi-folder-x" style="font-size: 4rem; color: var(--gris-una);"></i>
                            <h3 class="mt-3 mb-3">No tienes proyectos registrados</h3>
                            <p class="text-muted mb-4">
                                Comienza creando tu primer proyecto de trabajo final de graduación. 
                                Podrás colaborar con otros estudiantes y hacer seguimiento de tu progreso.
                            </p>
                            <a href="register_group.php" class="btn-una-primary" id="btn-first-project">
                                <i class="bi bi-plus-circle-fill"></i> Crear mi primer proyecto
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Listado de proyectos con diseño UNA -->
            <div class="row">
                <?php foreach($projects as $project): ?>
                    <div class="col-lg-6 col-xl-4 mb-4">
                        <div class="card-una">
                            <!-- Header del proyecto -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <h5 class="mb-1" id="project-title-<?= $project['id'] ?>">
                                        <?= htmlspecialchars($project['project_title']) ?>
                                    </h5>
                                    <small class="text-muted">
                                        Tipo: <?= htmlspecialchars($project['type_name'] ?? 'No especificado') ?>
                                    </small>
                                </div>
                                <div class="dropdown">
                                    <button class="btn-una-secondary btn-sm dropdown-toggle" type="button" 
                                            id="dropdown-project-<?= $project['id'] ?>" 
                                            data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="#" 
                                               onclick="viewProjectMembers(<?= $project['id'] ?>)">
                                                <i class="bi bi-people"></i> Ver Integrantes
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="#">
                                                <i class="bi bi-pencil"></i> Editar
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="#">
                                                <i class="bi bi-trash"></i> Eliminar
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Descripción del proyecto -->
                            <p class="text-muted mb-3" id="project-description-<?= $project['id'] ?>">
                                <?= htmlspecialchars(substr($project['description'] ?? 'Sin descripción', 0, 120)) ?>
                                <?= strlen($project['description'] ?? '') > 120 ? '...' : '' ?>
                            </p>

                            <!-- Estado y metadatos -->
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <?php
                                $status_class = '';
                                switch($project['status']) {
                                    case 'Aprobado':
                                        $status_class = 'badge-una-secondary';
                                        break;
                                    case 'Registrado':
                                        $status_class = 'badge-una-primary';
                                        break;
                                    case 'Rechazado':
                                        $status_class = 'badge-una-warning';
                                        break;
                                    default:
                                        $status_class = 'badge-una-neutral';
                                }
                                ?>
                                <span class="badge-una <?= $status_class ?>">
                                    <?= htmlspecialchars($project['status'] ?? 'Borrador') ?>
                                </span>
                                
                                <?php if (strpos($project['user_roles'] ?? '', 'Líder') !== false): ?>
                                    <span class="badge-una badge-una-primary">
                                        <i class="bi bi-star-fill"></i> Líder
                                    </span>
                                <?php else: ?>
                                    <span class="badge-una badge-una-neutral">
                                        <i class="bi bi-person"></i> Miembro
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Información adicional -->
                            <div class="row text-center text-muted small">
                                <div class="col-6">
                                    <i class="bi bi-calendar3"></i>
                                    <br>
                                    <?= date('d/m/Y', strtotime($project['created_at'] ?? 'now')) ?>
                                </div>
                                <div class="col-6">
                                    <i class="bi bi-people"></i>
                                    <br>
                                    Máx. <?= $project['max_members'] ?? 'N/A' ?> miembros
                                </div>
                            </div>

                            <!-- Acciones principales -->
                            <div class="d-flex gap-2 mt-3">
                                <button type="button" class="btn-una-secondary flex-fill" 
                                        onclick="viewProjectMembers(<?= $project['id'] ?>)"
                                        id="btn-view-members-<?= $project['id'] ?>">
                                    <i class="bi bi-people"></i> Integrantes
                                </button>
                                <a href="#" class="btn-una-primary flex-fill" 
                                   id="btn-manage-project-<?= $project['id'] ?>">
                                    <i class="bi bi-gear"></i> Gestionar
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Paginación (si es necesaria) -->
            <?php if(count($projects) >= 10): ?>
                <div class="d-flex justify-content-center mt-4">
                    <nav aria-label="Navegación de proyectos">
                        <ul class="pagination">
                            <li class="page-item">
                                <a class="page-link btn-una-secondary" href="#" id="btn-pagination-prev">
                                    <i class="bi bi-chevron-left"></i> Anterior
                                </a>
                            </li>
                            <li class="page-item active">
                                <span class="page-link btn-una-primary">1</span>
                            </li>
                            <li class="page-item">
                                <a class="page-link btn-una-secondary" href="#" id="btn-pagination-next">
                                    Siguiente <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Modal para ver integrantes con diseño UNA -->
    <div class="modal fade" id="modal-members" tabindex="-1" aria-labelledby="modal-members-label" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--azul-una) 0%, var(--rojo-una) 100%); color: var(--blanco-una);">
                    <h5 class="modal-title" id="modal-members-label">
                        <i class="bi bi-people-fill"></i> Integrantes del Proyecto
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="modal-members-content">
                    <div class="text-center">
                        <div class="spinner-border" style="color: var(--azul-una);" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2">Cargando integrantes...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-una-secondary" data-bs-dismiss="modal" id="btn-close-members-modal">
                        <i class="bi bi-x-circle"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer oficial UNA/ESCINF -->
    <footer class="footer-una">
        <div class="container">
            <p>
                Copyright © 2025. Todos los derechos reservados. 
                USTDS-Escuela de Informática-UNA<br>
                Contacto: escinf@una.ac.cr | Tel: +506 2562-4000 ext. 2200
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // JavaScript siguiendo estándares UNA (camelCase para variables)
        document.addEventListener('DOMContentLoaded', function() {
            // Animaciones de entrada para las tarjetas
            const projectCards = document.querySelectorAll('.card-una');
            projectCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });

        // Función para ver integrantes del proyecto
        function viewProjectMembers(projectId) {
            const modal = new bootstrap.Modal(document.getElementById('modal-members'));
            const modalContent = document.getElementById('modal-members-content');
            
            // Mostrar modal
            modal.show();
            
            // Cargar integrantes usando método estándar UNA
            fetch(`get_project_members.php?project_id=${projectId}`)
                .then(response => response.text())
                .then(data => {
                    modalContent.innerHTML = data;
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalContent.innerHTML = `
                        <div class="alert alert-una alert-una-warning">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            Error al cargar los integrantes del proyecto
                        </div>
                    `;
                });
        }

        // Función para confirmar eliminación de proyecto
        function confirmDeleteProject(projectId, projectTitle) {
            if (confirm(`¿Está seguro de que desea eliminar el proyecto "${projectTitle}"?`)) {
                // Aquí iría la lógica para eliminar el proyecto
                window.location.href = `delete_project.php?id=${projectId}`;
            }
        }

        // Manejar tooltips para información adicional
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>