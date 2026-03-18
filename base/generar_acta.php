<?php
include("mod/login/check.php");
include('lang/lang.es');

$current_user_name = $mySessionController->getVar("nombre");
$current_user_rol  = $mySessionController->getVar("rol");
$cds_domain = $mySessionController->getVar("cds_domain");
$cds_locate = $mySessionController->getVar("cds_locate");
$base_url   = $cds_domain . $cds_locate;

if (!in_array($current_user_rol, [2, 3])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<?php include('head.php'); ?>
<body class="fondo-una d-flex flex-column min-vh-100">

<?php include('header.php'); ?>

<main class="flex-fill">
    <div class="container my-5">

        <div class="dashboard-header text-center mb-5">
            <h1 style="font-size: 2.3rem; font-weight: 700;">Generar Acta de Presentación Pública</h1>
            <p class="lead">Complete el formulario para crear el documento PDF oficial.</p>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body text-dark">
                <form action="procesar_acta.php" method="POST" target="_blank" class="needs-validation" novalidate>

                    <!-- DATOS GENERALES DEL ACTA -->
                    <h4 class="mb-3">Datos generales del acta</h4>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="numero_acta" class="form-label fw-bold">Número de acta:</label>
                            <input type="text" name="numero_acta" id="numero_acta" class="form-control"
                                   placeholder="Ejemplo: 023-2026" required>
                        </div>

                        <div class="col-md-4">
                            <label for="fecha" class="form-label fw-bold">Fecha de la presentación:</label>
                            <input type="date" name="fecha" id="fecha" class="form-control" required>
                        </div>

                        <div class="col-md-2">
                            <label for="hora_inicio" class="form-label fw-bold">Hora de inicio:</label>
                            <input type="time" name="hora_inicio" id="hora_inicio" class="form-control" required>
                        </div>

                        <div class="col-md-2">
                            <label for="hora_cierre" class="form-label fw-bold">Hora de cierre:</label>
                            <input type="time" name="hora_cierre" id="hora_cierre" class="form-control" required>
                        </div>

                        <div class="col-md-4">
                            <label for="modalidad_sesion" class="form-label fw-bold">Modalidad de la sesión:</label>
                            <select name="modalidad_sesion" id="modalidad_sesion" class="form-select" required>
                                <option value="">Seleccione...</option>
                                <option value="virtual">Virtual</option>
                                <option value="presencial">Presencial</option>
                            </select>
                        </div>

                        <div class="col-md-8">
                            <label for="plataforma" class="form-label fw-bold">Plataforma / lugar:</label>
                            <input type="text" name="plataforma" id="plataforma" class="form-control"
                                   placeholder="Ejemplo: Microsoft Teams / Aula 205" required>
                        </div>
                    </div>

                    <!-- DATOS DEL TFG -->
                    <h4 class="mb-3">Datos del trabajo final de graduación</h4>
                    <div class="row g-3 mb-4">
                        <div class="col-md-12">
                            <label for="titulo_tfg" class="form-label fw-bold">Título del TFG:</label>
                            <input type="text" name="titulo_tfg" id="titulo_tfg" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label for="modalidad_tfg" class="form-label fw-bold">Modalidad del TFG:</label>
                            <input type="text" name="modalidad_tfg" id="modalidad_tfg" class="form-control"
                                   value="Proyecto de Graduación" required>
                        </div>

                        <div class="col-md-6">
                            <label for="grado" class="form-label fw-bold">Grado al que opta:</label>
                            <input type="text" name="grado" id="grado" class="form-control"
                                   value="Licenciatura en Informática con énfasis en Sistema de Información" required>
                        </div>
                    </div>

                    <!-- ESTUDIANTE 1 -->
                    <h4 class="mb-3">Postulante 1</h4>
                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label for="nombre_estudiante_1" class="form-label fw-bold">Nombre completo:</label>
                            <input type="text" name="nombre_estudiante_1" id="nombre_estudiante_1" class="form-control" required>
                        </div>

                        <div class="col-md-4">
                            <label for="cedula_estudiante_1" class="form-label fw-bold">Cédula:</label>
                            <input type="text" name="cedula_estudiante_1" id="cedula_estudiante_1" class="form-control" required>
                        </div>
                    </div>

                    <!-- ESTUDIANTE 2 (OPCIONAL) -->
                    <h4 class="mb-3">Postulante 2 (opcional)</h4>
                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label for="nombre_estudiante_2" class="form-label fw-bold">Nombre completo:</label>
                            <input type="text" name="nombre_estudiante_2" id="nombre_estudiante_2" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label for="cedula_estudiante_2" class="form-label fw-bold">Cédula:</label>
                            <input type="text" name="cedula_estudiante_2" id="cedula_estudiante_2" class="form-control">
                        </div>
                    </div>

                    <!-- TRIBUNAL -->
                    <h4 class="mb-3">Tribunal evaluador</h4>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="presidente_nombre" class="form-label fw-bold">Presidente(a) del tribunal:</label>
                            <input type="text" name="presidente_nombre" id="presidente_nombre" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label for="presidente_cargo" class="form-label fw-bold">Cargo del/de la presidente(a):</label>
                            <input type="text" name="presidente_cargo" id="presidente_cargo" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label for="director_nombre" class="form-label fw-bold">Director(a) de Escuela:</label>
                            <input type="text" name="director_nombre" id="director_nombre" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label for="director_cargo" class="form-label fw-bold">Cargo:</label>
                            <input type="text" name="director_cargo" id="director_cargo" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label for="tutor_nombre" class="form-label fw-bold">Tutor(a):</label>
                            <input type="text" name="tutor_nombre" id="tutor_nombre" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label for="tutor_cargo" class="form-label fw-bold">Cargo:</label>
                            <input type="text" name="tutor_cargo" id="tutor_cargo" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label for="asesor_nombre" class="form-label fw-bold">Asesor(a):</label>
                            <input type="text" name="asesor_nombre" id="asesor_nombre" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label for="asesor_cargo" class="form-label fw-bold">Cargo:</label>
                            <input type="text" name="asesor_cargo" id="asesor_cargo" class="form-control" required>
                        </div>
                    </div>

                    <!-- RESULTADO -->
                    <h4 class="mb-3">Resultado del tribunal</h4>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="resultado" class="form-label fw-bold">Resultado:</label>
                            <select name="resultado" id="resultado" class="form-select" required>
                                <option value="">Seleccione...</option>
                                <option value="Aprobado">Aprobado</option>
                                <option value="Reprobado">Reprobado</option>
                                <option value="Con Observaciones">Con Observaciones</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="nota" class="form-label fw-bold">Calificación:</label>
                            <input type="number" step="0.01" name="nota" id="nota" class="form-control" min="0" max="10" required>
                        </div>

                        <div class="col-md-4">
                            <label for="tipo_observaciones" class="form-label fw-bold">Observaciones:</label>
                            <select name="tipo_observaciones" id="tipo_observaciones" class="form-select" required>
                                <option value="">Seleccione...</option>
                                <option value="Sin Observaciones">Sin Observaciones</option>
                                <option value="Con Observaciones">Con Observaciones</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="mencion" class="form-label fw-bold">Mención honorífica:</label>
                            <select name="mencion" id="mencion" class="form-select">
                                <option value="">Sin mención</option>
                                <option value="Cum Laude">Cum Laude</option>
                                <option value="Magna Cum Laude">Magna Cum Laude</option>
                                <option value="Summa Cum Laude">Summa Cum Laude</option>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label for="observaciones_detalle" class="form-label fw-bold">Detalle de observaciones:</label>
                            <textarea name="observaciones_detalle" id="observaciones_detalle" class="form-control" rows="5"
                                      placeholder="Escriba aquí las observaciones del tribunal, si aplica."></textarea>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-file-earmark-pdf-fill"></i> Generar Acta en PDF
                        </button>

                        <a href="dashboard.php" class="btn btn-secondary btn-lg ms-3">
                            <i class="bi bi-arrow-left-circle"></i> Volver al panel principal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<footer class="footer-una mt-auto">
    <div class="container">
        <p class="mb-1">&copy; <?= date('Y') ?> Universidad Nacional de Costa Rica</p>
        <small>Escuela de Informática - Proyecto SGPFL v3.0</small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
(() => {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');

    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

</body>
</html>