<?php
/**
 * Registro de Proyectos Grupales
 * Sistema SGPFL - Proyecto UNA/ESCINF 2025-07
 * Siguiendo estándares oficiales UNA
 */

// VERIFICAR AUTENTICACIÓN USANDO EL SISTEMA ESTÁNDAR
include("../../login/check.php");

// Incluir archivos necesarios
include_once(__DIR__ . "/../../../inc/db/bdcommon.inc");
include_once(__DIR__ . "/../../../inc/db/db.php");
include_once(__DIR__ . "/../../../inc/student_functions.php");
include_once("ProjectGroup.php");

// Obtener variables de sesión
$vocab = $mySessionController->getVar("vocab");
$user_rol = $mySessionController->getVar("rol");
$current_user_id = $mySessionController->getVar("usuario");
$current_user_name = $mySessionController->getVar("nombre");
$base_url = $mySessionController->getVar("cds_domain") . $mySessionController->getVar("cds_locate");

// Verificar permisos: Solo estudiantes (rol 4) pueden crear grupos
if ($user_rol != 4) {
    header('Location: ../../../dashboard.php');
    exit;
}

$message = '';
$error = '';

if ($_POST) {
    // Validar título único usando método estándar UNA
    if (ProjectGroup::title_exists($_POST['project_title'])) {
        $error = "Ya existe un proyecto con ese título";
    } else {
        $project_data = [
            'project_title' => $_POST['project_title'],
            'description' => $_POST['description'],
            'project_type_id' => $_POST['project_type_id'],
            'members' => []
        ];
        
        // Agregar líder como primer miembro
        $project_data['members'][] = [
            'user_id' => $current_user_id,
            'role' => 'Líder'
        ];
        
        // Agregar otros miembros si los hay
        if (isset($_POST['members']) && !empty($_POST['members'])) {
            foreach ($_POST['members'] as $member_id) {
                if (!empty($member_id) && $member_id !== $current_user_id) {
                    $project_data['members'][] = [
                        'user_id' => $member_id,
                        'role' => 'Miembro'
                    ];
                }
            }
        }
        
        // Crear proyecto usando método estándar UNA
        $result = ProjectGroup::create_project($project_data);
        
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

// Obtener tipos de proyecto usando método estándar UNA
$project_types = ProjectGroup::get_project_types();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Proyecto Grupal - SGPFL</title>
    <!-- CSS Framework oficial UNA -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- CSS personalizado UNA -->
    <link rel="stylesheet" href="../../../inc/css/estilo.css">
    <!-- Tipografías oficiales UNA -->
    <link href="https://fonts.googleapis.com/css2?family=Goudy+Old+Style:wght@400;700&family=Frutiger:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                        <a class="nav-link" href="list_projects.php" id="nav-link-projects">
                            <i class="bi bi-kanban"></i> Mis Proyectos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#" id="nav-link-register">
                            <i class="bi bi-plus-circle-fill"></i> Registrar Proyecto
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Header de la página -->
        <div class="row mb-4">
            <div class="col-12">
                <h1 id="page-title-register-project">
                    <i class="bi bi-plus-circle-fill"></i> Registrar Proyecto Grupal
                </h1>
                <p class="lead text-muted" id="page-subtitle-register">
                    Complete la información para registrar un nuevo proyecto de trabajo final de graduación
                </p>
            </div>
        </div>

        <!-- Alertas de resultado -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-una alert-una-success" id="alert-success-message">
                <i class="bi bi-check-circle-fill"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-una alert-una-warning" id="alert-error-message">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Información de estudiantes disponibles -->
        <?php 
        try {
            $total_students = countStudents();
            $available_students = getAllStudents();
        } catch (Exception $e) {
            $total_students = 0;
            $available_students = [];
        }
        ?>
        
        <div class="row mb-4">
            <div class="col-12">
                <div class="card-una-info">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-1">
                                <i class="bi bi-people-fill text-primary"></i>
                                Estudiantes Disponibles
                            </h6>
                            <p class="mb-0 text-muted">
                                Total: <?= $total_students ?> estudiantes registrados
                            </p>
                        </div>
                        <button type="button" class="btn-una-outline" data-bs-toggle="collapse" data-bs-target="#div-all-students">
                            <i class="bi bi-list-ul"></i> Ver Lista
                        </button>
                    </div>
                    
                    <!-- Lista colapsable de todos los estudiantes -->
                    <div class="collapse mt-3" id="div-all-students">
                        <hr>
                        <div class="row">
                            <?php if (!empty($available_students)): ?>
                                <div class="col-12">
                                    <div style="max-height: 300px; overflow-y: auto;">
                                        <div class="row g-2">
                                            <?php foreach (array_slice($available_students, 0, 20) as $student): ?>
                                                <div class="col-md-6">
                                                    <div class="border rounded p-2 bg-light">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div class="flex-grow-1">
                                                                <strong class="text-primary"><?= htmlspecialchars($student['nombre']) ?></strong><br>
                                                                <small class="text-muted">
                                                                    <?= htmlspecialchars($student['id']) ?> | <?= htmlspecialchars($student['email']) ?>
                                                                </small>
                                                            </div>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-primary" 
                                                                    onclick="quickAddMember('<?= $student['id'] ?>', '<?= htmlspecialchars($student['nombre']) ?>', '<?= htmlspecialchars($student['email']) ?>')">
                                                                <i class="bi bi-plus"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php if (count($available_students) > 20): ?>
                                            <div class="text-center mt-2">
                                                <small class="text-muted">
                                                    Mostrando primeros 20 de <?= count($available_students) ?> estudiantes. 
                                                    Use la búsqueda para encontrar estudiantes específicos.
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="col-12">
                                    <p class="text-warning">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        No hay estudiantes registrados en el sistema.
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario de registro con estilos UNA -->
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card-una">
                    <div class="card-una-header">
                        <h3 class="mb-0" id="form-header-title">
                            <i class="bi bi-file-earmark-plus-fill"></i> 
                            Información del Proyecto
                        </h3>
                    </div>
                    
                    <form method="POST" class="form-una" id="frm-register-project">
                        <!-- Título del proyecto -->
                        <div class="form-group-una">
                            <label for="inp-project-title" class="form-label-una">
                                <i class="bi bi-card-heading"></i> Título del Proyecto *
                            </label>
                            <input type="text" 
                                   class="form-control-una" 
                                   id="inp-project-title" 
                                   name="project_title" 
                                   maxlength="100" 
                                   required
                                   placeholder="Ingrese el título del proyecto (máximo 100 caracteres)">
                            <small class="text-muted">El título debe ser único y descriptivo</small>
                        </div>

                        <!-- Tipo de proyecto -->
                        <div class="form-group-una">
                            <label for="sel-project-type" class="form-label-una">
                                <i class="bi bi-tags-fill"></i> Tipo de Proyecto *
                            </label>
                            <select class="form-control-una" id="sel-project-type" name="project_type_id" required>
                                <option value="">Seleccione el tipo de proyecto</option>
                                <?php foreach ($project_types as $type): ?>
                                    <option value="<?= $type['id'] ?>" data-max-members="<?= $type['max_members'] ?>">
                                        <?= htmlspecialchars($type['type_name']) ?> 
                                        (Máximo <?= $type['max_members'] ?> miembros)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Descripción -->
                        <div class="form-group-una">
                            <label for="txt-description" class="form-label-una">
                                <i class="bi bi-file-text-fill"></i> Descripción del Proyecto *
                            </label>
                            <textarea class="form-control-una" 
                                      id="txt-description" 
                                      name="description" 
                                      rows="4" 
                                      maxlength="500" 
                                      required
                                      placeholder="Describa brevemente el proyecto (máximo 500 caracteres)"></textarea>
                            <small class="text-muted">Proporcione una descripción clara y concisa del proyecto</small>
                        </div>

                        <!-- Integrantes del grupo -->
                        <div class="form-group-una">
                            <label class="form-label-una">
                                <i class="bi bi-people-fill"></i> Integrantes del Grupo
                            </label>
                            
                            <!-- Líder del proyecto (usuario actual) -->
                            <div class="card-una mb-3">
                                <div class="d-flex align-items-center p-3">
                                    <i class="bi bi-star-fill text-warning me-3" style="font-size: 1.5rem;"></i>
                                    <div>
                                        <strong>Líder del Proyecto (Usted)</strong><br>
                                        <small class="text-muted">ID: <?= htmlspecialchars($current_user_id) ?></small>
                                    </div>
                                </div>
                            </div>

                            <!-- Búsqueda de miembros adicionales -->
                            <div class="mb-3">
                                <label for="inp-search-members" class="form-label-una">
                                    Agregar Miembros (Opcional)
                                </label>
                                <div class="input-group">
                                    <input type="text" 
                                           class="form-control-una" 
                                           id="inp-search-members" 
                                           placeholder="Buscar por nombre, email o ID de usuario">
                                    <button type="button" class="btn-una-secondary" id="btn-search-members">
                                        <i class="bi bi-search"></i> Buscar
                                    </button>
                                </div>
                            </div>

                            <!-- Resultados de búsqueda -->
                            <div id="div-search-results" class="mb-3" style="display: none;"></div>

                            <!-- Miembros seleccionados -->
                            <div id="div-selected-members">
                                <h6>Miembros Seleccionados:</h6>
                                <div id="list-selected-members"></div>
                            </div>
                        </div>

                        <!-- Botones de acción -->
                        <div class="d-flex gap-3 justify-content-end">
                            <a href="list_projects.php" class="btn-una-secondary" id="btn-cancel-register">
                                <i class="bi bi-x-circle"></i> Cancelar
                            </a>
                            <button type="submit" class="btn-una-primary" id="btn-submit-register">
                                <i class="bi bi-check-circle-fill"></i> Registrar Proyecto
                            </button>
                        </div>
                    </form>
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
            const searchInput = document.getElementById('inp-search-members');
            const searchButton = document.getElementById('btn-search-members');
            const searchResults = document.getElementById('div-search-results');
            const selectedMembersList = document.getElementById('list-selected-members');
            const projectTypeSelect = document.getElementById('sel-project-type');
            
            let selectedMembers = [];
            let maxMembers = 1; // Por defecto solo el líder
            
            // Actualizar límite de miembros según tipo de proyecto
            projectTypeSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                maxMembers = parseInt(selectedOption.getAttribute('data-max-members')) || 1;
                updateMembersDisplay();
            });
            
            // Función para buscar usuarios
            function searchUsers(searchTerm) {
                if (searchTerm.length < 2) {
                    searchResults.style.display = 'none';
                    return;
                }
                
                // Mostrar indicador de carga
                searchResults.innerHTML = '<div class="text-center"><i class="bi bi-spinner-border"></i> Buscando...</div>';
                searchResults.style.display = 'block';
                
                // Petición AJAX real usando nuestras funciones de estudiantes
                const formData = new FormData();
                formData.append('search_term', searchTerm);
                
                fetch('ajax_search_students.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(users => {
                    displaySearchResults(users);
                })
                .catch(error => {
                    console.error('Error en búsqueda:', error);
                    searchResults.innerHTML = '<div class="alert alert-danger">Error al buscar usuarios</div>';
                });
            }
            
            // Mostrar resultados de búsqueda
            function displaySearchResults(users) {
                if (users.length === 0) {
                    searchResults.innerHTML = '<div class="alert alert-una-info">No se encontraron usuarios</div>';
                } else {
                    let html = '<div class="card-una"><h6>Resultados de búsqueda:</h6>';
                    users.forEach(user => {
                        const isAlreadySelected = selectedMembers.some(m => m.id === user.id);
                        const isCurrentUser = user.id === '<?= $current_user_id ?>';
                        
                        if (!isAlreadySelected && !isCurrentUser) {
                            html += `
                                <div class="d-flex justify-content-between align-items-center p-2 border-bottom">
                                    <div>
                                        <strong>${user.nombre}</strong><br>
                                        <small class="text-muted">${user.email} | ID: ${user.id}</small>
                                    </div>
                                    <button type="button" class="btn-una-secondary btn-sm" onclick="addMember('${user.id}', '${user.nombre}', '${user.email}')">
                                        <i class="bi bi-plus"></i> Agregar
                                    </button>
                                </div>
                            `;
                        }
                    });
                    html += '</div>';
                    searchResults.innerHTML = html;
                }
                searchResults.style.display = 'block';
            }
            
            // Agregar miembro al grupo
            window.addMember = function(userId, userName, userEmail) {
                if (selectedMembers.length >= (maxMembers - 1)) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Límite alcanzado',
                        text: `Solo se permiten ${maxMembers} miembros para este tipo de proyecto`,
                        confirmButtonColor: '#CD1719'
                    });
                    return;
                }
                
                selectedMembers.push({
                    id: userId,
                    nombre: userName,
                    email: userEmail
                });
                
                updateMembersDisplay();
                searchResults.style.display = 'none';
                searchInput.value = '';
            };

            // Función rápida para agregar desde la lista completa
            window.quickAddMember = function(userId, userName, userEmail) {
                // Verificar si ya está seleccionado
                const isAlreadySelected = selectedMembers.some(m => m.id === userId);
                if (isAlreadySelected) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Ya seleccionado',
                        text: `${userName} ya está en el grupo`,
                        confirmButtonColor: '#007bff'
                    });
                    return;
                }
                
                // Usar la misma lógica de addMember
                addMember(userId, userName, userEmail);
                
                // Mostrar confirmación
                Swal.fire({
                    icon: 'success',
                    title: 'Agregado',
                    text: `${userName} fue agregado al grupo`,
                    timer: 1500,
                    showConfirmButton: false
                });
            };
            
            // Remover miembro del grupo
            window.removeMember = function(userId) {
                selectedMembers = selectedMembers.filter(m => m.id !== userId);
                updateMembersDisplay();
            };
            
            // Actualizar visualización de miembros
            function updateMembersDisplay() {
                let html = '';
                selectedMembers.forEach(member => {
                    html += `
                        <div class="card-una mb-2">
                            <div class="d-flex justify-content-between align-items-center p-3">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-person-fill text-primary me-3"></i>
                                    <div>
                                        <strong>${member.nombre}</strong><br>
                                        <small class="text-muted">${member.email} | ID: ${member.id}</small>
                                    </div>
                                </div>
                                <button type="button" class="btn-una-secondary btn-sm" onclick="removeMember('${member.id}')">
                                    <i class="bi bi-x"></i> Remover
                                </button>
                            </div>
                            <input type="hidden" name="members[]" value="${member.id}">
                        </div>
                    `;
                });
                
                if (html === '') {
                    html = '<p class="text-muted">No hay miembros adicionales seleccionados</p>';
                }
                
                selectedMembersList.innerHTML = html;
            }
            
            // Event listeners
            searchButton.addEventListener('click', () => {
                searchUsers(searchInput.value.trim());
            });
            
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchUsers(searchInput.value.trim());
                }
            });
            
            // Validaciones del formulario
            document.getElementById('frm-register-project').addEventListener('submit', function(e) {
                const projectTitle = document.getElementById('inp-project-title').value.trim();
                const projectType = document.getElementById('sel-project-type').value;
                const description = document.getElementById('txt-description').value.trim();
                
                if (!projectTitle || !projectType || !description) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Campos requeridos',
                        text: 'Por favor complete todos los campos obligatorios',
                        confirmButtonColor: '#CD1719'
                    });
                    return;
                }
                
                if (projectTitle.length > 100) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Título muy largo',
                        text: 'El título no puede exceder los 100 caracteres',
                        confirmButtonColor: '#CD1719'
                    });
                    return;
                }
                
                if (description.length > 500) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Descripción muy larga',
                        text: 'La descripción no puede exceder los 500 caracteres',
                        confirmButtonColor: '#CD1719'
                    });
                    return;
                }
            });
            
            // Inicializar display de miembros
            updateMembersDisplay();
        });
    </script>
</body>
</html>