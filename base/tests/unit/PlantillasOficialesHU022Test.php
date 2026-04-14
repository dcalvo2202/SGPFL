<?php
/**
 * HU-022: Pruebas unitarias para Plantillas Oficiales
 * 
 * Criterios de aceptación:
 * 1. Actualizar plantillas según cambios normativos
 * 2. Las plantillas reflejan la última versión de los Anexos
 * 3. Los gestores académicos pueden agregar, eliminar plantillas existentes
 */

use PHPUnit\Framework\TestCase;

class PlantillasOficialesHU022Test extends TestCase
{
    private function readSource(string $relativePath): string
    {
        $path = __DIR__ . '/../../' . $relativePath;
        $this->assertFileExists($path, "No existe el archivo esperado: {$relativePath}");

        $content = file_get_contents($path);
        $this->assertNotFalse($content, "No se pudo leer: {$relativePath}");

        return (string) $content;
    }

    /**
     * CA1: Verificar que existe la tabla plantillas_oficiales en base.sql
     * para almacenar las plantillas actualizables según cambios normativos
     */
    public function testExisteTablaDePlantillasOficialesEnBaseDatos(): void
    {
        $sql = $this->readSource('base.sql');

        // Verificar estructura de la tabla
        $this->assertStringContainsString(
            "CREATE TABLE `plantillas_oficiales`",
            $sql,
            "Debe existir la tabla plantillas_oficiales para almacenar plantillas"
        );

        // Verificar campos clave para versionado y actualización (con espacios)
        $this->assertStringContainsString("`nombre`", $sql);
        $this->assertStringContainsString("`tipo`", $sql);
        $this->assertStringContainsString("`archivo`", $sql);
        $this->assertStringContainsString("`file_name`", $sql);
        $this->assertStringContainsString("`mime_type`", $sql);
        $this->assertStringContainsString("`activo`", $sql);
        $this->assertStringContainsString("`subido_por`", $sql);
        $this->assertStringContainsString("`created_at`", $sql);
        $this->assertStringContainsString("`updated_at`", $sql);
        $this->assertStringContainsString("enum('Propuesta','Informe Final','Acta','Otro')", $sql);
    }

    /**
     * CA2: Verificar que las plantillas reflejan la última versión
     * mediante campos de fecha y control de versión
     */
    public function testPlantillasTienenCamposDeVersionadoYFecha(): void
    {
        $sql = $this->readSource('base.sql');

        // Campos para rastrear versión y actualización (sin \r\n)
        $this->assertStringContainsString(
            "`created_at`    datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP",
            $sql,
            "Debe existir campo created_at para rastrear fecha de creación"
        );

        $this->assertStringContainsString(
            "ON UPDATE CURRENT_TIMESTAMP",
            $sql,
            "Debe existir campo updated_at para rastrear última actualización"
        );

        $this->assertStringContainsString(
            "`subido_por`    varchar(50)  NOT NULL",
            $sql,
            "Debe existir campo subido_por para rastrear quién actualizó la plantilla"
        );
    }

    /**
     * CA3: Verificar que los gestores académicos tienen permisos
     * para agregar y eliminar plantillas
     */
    public function testGestoresAcademicosTienenPermisosAgregarEliminar(): void
    {
        $sql = $this->readSource('base.sql');

        // Gestor Académico (rol 2) debe tener permisos de Agregar (3) y Eliminar (5)
        // sobre módulo 5 (Documentación y versionado)
        $this->assertStringContainsString(
            "(5, 3, 2)",
            $sql,
            "Gestor Académico debe tener permiso de Agregar (3) en módulo 5"
        );

        $this->assertStringContainsString(
            "(5, 5, 2)",
            $sql,
            "Gestor Académico debe tener permiso de Eliminar (5) en módulo 5"
        );
    }

    /**
     * CA3: Verificar que existe el script de subida de plantillas
     * con validaciones de seguridad y control de acceso
     */
    public function testExisteScriptSubidaPlantillasConValidaciones(): void
    {
        $src = $this->readSource('plantilla_upload_process.php');

        // Control de acceso: solo Gestor (2) y Administrador (1)
        $this->assertStringContainsString(
            "if (!in_array(\$current_user_rol, [1, 2], true))",
            $src,
            "Debe validar que solo Gestor y Admin pueden subir plantillas"
        );

        // Validación de tipos de archivo permitidos
        $this->assertStringContainsString(
            "\$tipos_validos = ['Propuesta', 'Informe Final', 'Acta', 'Otro']",
            $src,
            "Debe validar tipos de plantilla permitidos"
        );

        // Validación de archivo subido
        $this->assertStringContainsString(
            "is_uploaded_file",
            $src,
            "Debe validar que el archivo fue subido correctamente"
        );

        // Validación de MIME type
        $this->assertStringContainsString(
            "mime_content_type",
            $src,
            "Debe validar el tipo MIME del archivo"
        );

        // Inserción en BD usando función dedicada
        $this->assertStringContainsString(
            "insertarPlantilla",
            $src,
            "Debe usar función insertarPlantilla para guardar en BD"
        );
    }

    /**
     * CA3: Verificar que existe el script de eliminación de plantillas
     * con control de acceso
     */
    public function testExisteScriptEliminacionPlantillasConValidaciones(): void
    {
        $src = $this->readSource('plantilla_delete_process.php');

        // Control de acceso: solo Gestor (2) y Administrador (1)
        $this->assertStringContainsString(
            "if (!in_array(\$current_user_rol, [1, 2]))",
            $src,
            "Debe validar que solo Gestor y Admin pueden eliminar plantillas"
        );

        // Validación de ID
        $this->assertStringContainsString(
            "filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT)",
            $src,
            "Debe validar el ID de la plantilla a eliminar"
        );

        // Eliminación usando función dedicada
        $this->assertStringContainsString(
            "eliminarPlantilla",
            $src,
            "Debe usar función eliminarPlantilla para eliminar de BD"
        );
    }

    /**
     * CA1-CA2: Verificar que existe el archivo de funciones de plantillas
     * con operaciones CRUD completas
     */
    public function testExistenFuncionesDeNegocioParaPlantillas(): void
    {
        $src = $this->readSource('inc/plantillas_functions.php');

        // Funciones de consulta
        $this->assertStringContainsString(
            "function getPlantillasActivas",
            $src,
            "Debe existir función para obtener plantillas activas"
        );

        $this->assertStringContainsString(
            "function getTodasLasPlantillas",
            $src,
            "Debe existir función para obtener todas las plantillas (Gestor)"
        );

        $this->assertStringContainsString(
            "function getPlantillaParaDescarga",
            $src,
            "Debe existir función para descargar plantilla"
        );

        // Funciones de escritura
        $this->assertStringContainsString(
            "function insertarPlantilla",
            $src,
            "Debe existir función para insertar nueva plantilla"
        );

        $this->assertStringContainsString(
            "function eliminarPlantilla",
            $src,
            "Debe existir función para eliminar plantilla"
        );

        $this->assertStringContainsString(
            "function toggleActivoPlantilla",
            $src,
            "Debe existir función para activar/desactivar plantilla"
        );
    }

    /**
     * CA1: Verificar validaciones de seguridad en función insertarPlantilla
     */
    public function testFuncionInsertarPlantillaTieneValidacionesSeguridad(): void
    {
        $src = $this->readSource('inc/plantillas_functions.php');

        // Validación de tipos MIME permitidos
        $this->assertStringContainsString(
            "\$mimes_permitidos = [",
            $src,
            "Debe validar tipos MIME permitidos"
        );

        $this->assertStringContainsString(
            "'application/pdf'",
            $src,
            "Debe permitir archivos PDF"
        );

        $this->assertStringContainsString(
            "'application/vnd.openxmlformats-officedocument.wordprocessingml.document'",
            $src,
            "Debe permitir archivos DOCX"
        );

        // Validación de tamaño máximo
        $this->assertStringContainsString(
            "\$max_bytes = 20 * 1024 * 1024",
            $src,
            "Debe validar tamaño máximo de archivo (20 MB)"
        );

        // Uso de prepared statements
        $this->assertStringContainsString(
            "\$stmt = \$conn->prepare(",
            $src,
            "Debe usar prepared statements para prevenir SQL injection"
        );

        $this->assertStringContainsString(
            "\$stmt->bind_param(",
            $src,
            "Debe usar bind_param para parámetros seguros"
        );
    }

    /**
     * CA3: Verificar que existe interfaz de gestión para Gestor Académico
     */
    public function testExistePanelGestionPlantillasParaGestor(): void
    {
        $this->assertFileExists(
            __DIR__ . '/../../panel_plantillas.php',
            "Debe existir panel_plantillas.php para gestión de plantillas"
        );

        $src = $this->readSource('panel_plantillas.php');

        // Control de acceso mediante verificación de roles
        $this->assertStringContainsString(
            "\$puede_gestionar = in_array(\$current_user_rol, [1, 2])",
            $src,
            "Debe verificar permisos de acceso para gestionar plantillas"
        );

        // Funcionalidad de agregar
        $this->assertStringContainsString(
            "Agregar Plantilla",
            $src,
            "Debe tener opción para agregar plantilla"
        );

        // Funcionalidad de eliminar
        $this->assertStringContainsString(
            "btn-eliminar-plantilla",
            $src,
            "Debe tener opción para eliminar plantilla"
        );

        // Funcionalidad de activar/desactivar
        $this->assertStringContainsString(
            "btn-toggle-visibilidad",
            $src,
            "Debe tener opción para activar/desactivar plantilla"
        );
    }

    /**
     * CA2: Verificar que existe script de descarga con validaciones
     */
    public function testExisteScriptDescargaPlantillasConValidaciones(): void
    {
        $src = $this->readSource('descargar_plantilla.php');

        // Validación de ID
        $this->assertStringContainsString(
            "filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT)",
            $src,
            "Debe validar el ID de la plantilla a descargar"
        );

        // Uso de función de negocio
        $this->assertStringContainsString(
            "getPlantillaParaDescarga",
            $src,
            "Debe usar función getPlantillaParaDescarga"
        );

        // Headers de seguridad para descarga
        $this->assertStringContainsString(
            "Content-Type",
            $src,
            "Debe establecer Content-Type correcto"
        );

        $this->assertStringContainsString(
            "Content-Disposition",
            $src,
            "Debe establecer Content-Disposition para descarga"
        );
    }

    /**
     * CA1-CA3: Verificar que el sistema cumple con principio de responsabilidad única
     * separando lógica de negocio, controladores y vistas
     */
    public function testArquitecturaSeparaResponsabilidades(): void
    {
        // Funciones de negocio en archivo dedicado
        $this->assertFileExists(
            __DIR__ . '/../../inc/plantillas_functions.php',
            "Debe existir archivo de funciones de negocio separado"
        );

        // Controladores de procesamiento separados
        $this->assertFileExists(
            __DIR__ . '/../../plantilla_upload_process.php',
            "Debe existir controlador de subida separado"
        );

        $this->assertFileExists(
            __DIR__ . '/../../plantilla_delete_process.php',
            "Debe existir controlador de eliminación separado"
        );

        $this->assertFileExists(
            __DIR__ . '/../../descargar_plantilla.php',
            "Debe existir controlador de descarga separado"
        );

        // Vista de gestión separada
        $this->assertFileExists(
            __DIR__ . '/../../panel_plantillas.php',
            "Debe existir vista de gestión separada"
        );
    }

    /**
     * CA1: Verificar que las plantillas soportan actualización
     * mediante campo updated_at y posibilidad de reemplazo
     */
    public function testPlantillasSoportanActualizacionDeVersiones(): void
    {
        $sql = $this->readSource('base.sql');
        $functions = $this->readSource('inc/plantillas_functions.php');

        // Campo updated_at con auto-actualización
        $this->assertStringContainsString(
            "ON UPDATE CURRENT_TIMESTAMP",
            $sql,
            "Campo updated_at debe actualizarse automáticamente"
        );

        // Función para insertar permite reemplazar versiones antiguas
        $this->assertStringContainsString(
            "function insertarPlantilla",
            $functions,
            "Debe existir función para insertar nuevas versiones"
        );

        // Función para eliminar permite remover versiones obsoletas
        $this->assertStringContainsString(
            "function eliminarPlantilla",
            $functions,
            "Debe existir función para eliminar versiones obsoletas"
        );
    }
}
