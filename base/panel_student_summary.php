<?php
include("mod/login/check.php");
include('lang/lang.es');

$current_user_id = $mySessionController->getVar("usuario");
$current_user_name = $mySessionController->getVar("nombre");
$current_user_rol = $mySessionController->getVar("rol");
$base_url = $mySessionController->getVar("cds_domain") . $mySessionController->getVar("cds_locate");

if ($current_user_rol != 2 && $current_user_rol != 1) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<?php include 'head.php'; ?>
<body class="fondo-una d-flex flex-column min-vh-100">

<?php include 'header.php'; ?>

<main class="flex-fill">
    <div class="container my-5">

        <div class="dashboard-header text-center mb-5">
            <h1 style="font-size: 2.5rem; font-weight: 700; color: #034991;">Resumen consolidado por estudiante</h1>
            <p class="lead">Desde este panel puede buscar estudiantes y descargar el resumen consolidado de su proceso de TFG en formato PDF.</p>
        </div>

        <div class="card document-table mb-4">
            <div class="card-body p-4">
                <h2 class="student-summary-section-title mb-4">
                    <i class="bi bi-search"></i> Buscar estudiante
                </h2>

                <div class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label for="student-search-term" class="form-label fw-semibold">Nombre, correo o identificación</label>
                        <input
                            type="text"
                            id="student-search-term"
                            class="form-control"
                            placeholder="Escriba al menos 2 caracteres para buscar"
                            autocomplete="off"
                        >
                    </div>
                    <div class="col-md-4 d-grid student-summary-search-col">
                        <button type="button" id="btn-search-student" class="btn btn-danger student-summary-search-btn">
                            Buscar estudiante
                        </button>
                    </div>
                </div>

                <small class="text-muted d-block mt-2">
                    Puede buscar por número de identificación, nombre completo o correo institucional del estudiante.
                </small>

                <div id="search-message" class="alert mt-4 mb-0" style="display:none;"></div>
            </div>
        </div>

        <div id="results-section" style="display:none;">
            <div class="card document-table mb-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Identificación</th>
                                    <th>Nombre</th>
                                    <th>Correo</th>
                                    <th class="text-center" style="width: 140px;">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="results-body"></tbody>
                        </table>
                    </div>

                    <div id="results-empty" class="student-summary-empty-state" style="display:none;">
                        No se encontraron estudiantes con el criterio ingresado.
                    </div>
                </div>
            </div>
        </div>

        <div id="selected-student-section" style="display:none;">
            <div class="card document-table mb-4">
                <div class="card-body p-4">
                    <h2 class="student-summary-section-title mb-4">
                        <i class="bi bi-person-badge"></i> Estudiante seleccionado
                    </h2>

                    <div class="row g-4 align-items-start">
                        <div class="col-md-8">
                            <div class="student-summary-detail-row">
                                <span class="student-summary-detail-label">Identificación:</span>
                                <span id="selected-student-id">-</span>
                            </div>
                            <div class="student-summary-detail-row">
                                <span class="student-summary-detail-label">Nombre:</span>
                                <span id="selected-student-name">-</span>
                            </div>
                            <div class="student-summary-detail-row mb-0">
                                <span class="student-summary-detail-label">Correo:</span>
                                <span id="selected-student-email">-</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-grid gap-2">
                                <a id="download-summary-link" href="#" class="btn btn-danger">
                                    <i class="bi bi-download me-2"></i> Descargar resumen PDF
                                </a>
                                <button type="button" id="btn-clear-selection" class="btn btn-outline-secondary">
                                    Limpiar selección
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mt-4 mb-0">
                        La descarga abrirá o descargará directamente el resumen consolidado del estudiante en formato PDF.
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="dashboard.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left-circle"></i> Volver al panel principal
            </a>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>

<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .dashboard-header h1 {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        font-size: 2.5rem;
        font-weight: 700;
        color: #034991;
        margin-bottom: 0.5rem;
    }

    .dashboard-header .lead {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #6c757d;
        max-width: 900px;
        margin: 0 auto;
    }

    .document-table {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    .table thead th {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        font-weight: 600;
        font-size: 0.95rem;
        color: #034991;
        border-bottom: 2px solid #dee2e6;
    }

    .table tbody td {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        vertical-align: middle;
    }

    .student-summary-section-title {
        color: #c8151a;
        font-size: 1.35rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .student-summary-empty-state {
        padding: 2.5rem 1.5rem;
        text-align: center;
        color: #6c757d;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .student-summary-detail-row {
        margin-bottom: 1rem;
        font-size: 1rem;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .student-summary-detail-label {
        font-weight: 700;
        color: #034991;
        display: inline-block;
        min-width: 130px;
    }

    .student-summary-search-col {
        align-self: end;
    }

    .student-summary-search-btn {
        min-height: 38px;
        font-weight: 600;
    }

    #results-body tr {
        cursor: pointer;
    }

    @media (max-width: 767px) {
        .student-summary-detail-label {
            min-width: auto;
            display: block;
            margin-bottom: 0.2rem;
        }
    }
</style>

<script>
    (function ($) {
        const baseUrl = <?= json_encode($base_url) ?>;
        const searchEndpoint = baseUrl + 'mod/admin/users/search_users.php';
        const downloadEndpoint = baseUrl + 'mod/admin/users/download_student_summary_pdf.php';

        const $searchInput = $('#student-search-term');
        const $searchButton = $('#btn-search-student');
        const $searchMessage = $('#search-message');
        const $resultsSection = $('#results-section');
        const $resultsBody = $('#results-body');
        const $resultsEmpty = $('#results-empty');
        const $selectedSection = $('#selected-student-section');
        const $selectedStudentId = $('#selected-student-id');
        const $selectedStudentName = $('#selected-student-name');
        const $selectedStudentEmail = $('#selected-student-email');
        const $downloadLink = $('#download-summary-link');
        const $clearButton = $('#btn-clear-selection');

        function escapeHtml(value) {
            return $('<div>').text(value || '').html();
        }

        function showMessage(message, type) {
            const classMap = {
                info: 'alert-info',
                warning: 'alert-warning',
                success: 'alert-success',
                danger: 'alert-danger'
            };

            $searchMessage
                .removeClass('alert-info alert-warning alert-success alert-danger')
                .addClass(classMap[type] || 'alert-info')
                .html(message)
                .show();
        }

        function clearMessage() {
            $searchMessage.hide().html('');
        }

        function clearResults() {
            $resultsBody.empty();
            $resultsEmpty.hide();
            $resultsSection.hide();
        }

        function clearSelection() {
            $selectedStudentId.text('-');
            $selectedStudentName.text('-');
            $selectedStudentEmail.text('-');
            $downloadLink.attr('href', '#');
            $selectedSection.hide();
        }

        function renderResults(users) {
            $resultsBody.empty();

            if (!Array.isArray(users) || users.length === 0) {
                $resultsSection.show();
                $resultsEmpty.show();
                return;
            }

            users.forEach(function (user) {
                const row = `
                    <tr data-id="${escapeHtml(user.id)}" data-name="${escapeHtml(user.nombre)}" data-email="${escapeHtml(user.email)}">
                        <td>${escapeHtml(user.id)}</td>
                        <td>${escapeHtml(user.nombre)}</td>
                        <td>${escapeHtml(user.email)}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-primary btn-select-student">
                                Seleccionar
                            </button>
                        </td>
                    </tr>
                `;
                $resultsBody.append(row);
            });

            $resultsEmpty.hide();
            $resultsSection.show();
        }

        function selectStudent(student) {
            $selectedStudentId.text(student.id || '-');
            $selectedStudentName.text(student.nombre || '-');
            $selectedStudentEmail.text(student.email || '-');
            $downloadLink.attr('href', downloadEndpoint + '?student_id=' + encodeURIComponent(student.id));
            $selectedSection.show();

            $('html, body').animate({
                scrollTop: $selectedSection.offset().top - 80
            }, 250);
        }

        function performSearch() {
            const term = $.trim($searchInput.val());

            clearMessage();
            clearResults();
            clearSelection();

            if (term.length < 2) {
                showMessage('Debe ingresar al menos 2 caracteres para realizar la búsqueda.', 'warning');
                return;
            }

            $searchButton.prop('disabled', true).text('Buscando...');

            $.ajax({
                url: searchEndpoint,
                method: 'GET',
                dataType: 'json',
                data: { term: term }
            }).done(function (response) {
                if (response && response.error) {
                    showMessage('Ocurrió un problema al buscar estudiantes. Intente nuevamente.', 'warning');
                    return;
                }

                renderResults(response || []);

                if (Array.isArray(response) && response.length > 0) {
                    showMessage('Seleccione el estudiante correspondiente para descargar su resumen consolidado.', 'info');
                } else {
                    showMessage('No se encontraron coincidencias para el criterio ingresado.', 'warning');
                }
            }).fail(function () {
                showMessage('No fue posible completar la búsqueda en este momento.', 'warning');
            }).always(function () {
                $searchButton.prop('disabled', false).text('Buscar estudiante');
            });
        }

        $searchButton.on('click', performSearch);

        $searchInput.on('keypress', function (event) {
            if (event.which === 13) {
                event.preventDefault();
                performSearch();
            }
        });

        $resultsBody.on('click', '.btn-select-student, tr', function (event) {
            const $row = $(event.target).closest('tr');
            const student = {
                id: $row.data('id'),
                nombre: $row.data('name'),
                email: $row.data('email')
            };
            selectStudent(student);
        });

        $clearButton.on('click', function () {
            clearSelection();
            $searchInput.val('').focus();
            clearResults();
            clearMessage();
        });
    })(jQuery);
</script>

</body>
</html>
