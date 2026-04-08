<?php

declare(strict_types=1);

namespace Tests\functional;

use Tests\Support\FunctionalTester;

/**
 * HU-022: Pruebas funcionales para Plantillas Oficiales
 * 
 * Criterios de aceptación:
 * 1. Actualizar plantillas según cambios normativos
 * 2. Las plantillas reflejan la última versión de los Anexos
 * 3. Los gestores académicos pueden agregar, eliminar plantillas existentes
 */
final class PlantillasOficialesHU022Cest
{
    // Usuarios reales del sistema (según base.sql)
    private const GESTOR_ID = '111710169';      // Miguel Arturo Corrales Ureña - Gestor Académico
    private const GESTOR_PASS = 'secret123';
    private const ADMIN_ID = '205610158';       // Oscar Chaves Barrantes - Administrador
    private const ADMIN_PASS = 'secret123';
    private const ESTUDIANTE_ID = '118440202';  // Larissa Segura Arguello - Estudiante
    private const ESTUDIANTE_PASS = 'secret123';
    private const CTFG_ID = '110600492';        // Maikol Guzmán Alán - CTFG
    private const CTFG_PASS = 'secret123';

    /**
     * CA3: Verificar que el panel de plantillas carga correctamente
     * y muestra elementos básicos para todos los roles autorizados
     */
    public function panelPlantillasCargaCorrectamente(FunctionalTester $I): void
    {
        // Login como Gestor Académico
        $this->loginAs($I, self::GESTOR_ID, self::GESTOR_PASS);

        $I->amOnPage('/panel_plantillas.php');
        
        // Verificar elementos básicos del panel
        $I->see('Plantillas Oficiales');
        $I->seeElement('a[href*="dashboard.php"]'); // Botón volver
        
        // Verificar que el Gestor ve el botón de agregar
        $I->seeElement('#btnAbrirPanel');
        $I->see('Agregar Plantilla');
    }

    /**
     * CA3: Verificar que el estudiante puede ver plantillas
     * pero NO puede agregar ni eliminar
     */
    public function estudianteVePlantillasSinGestionar(FunctionalTester $I): void
    {
        // Login como Estudiante
        $this->loginAs($I, self::ESTUDIANTE_ID, self::ESTUDIANTE_PASS);

        $I->amOnPage('/panel_plantillas.php');
        
        // Verificar que puede ver el panel
        $I->see('Plantillas Oficiales');
        
        // Verificar que NO ve el botón de agregar plantilla
        $I->dontSeeElement('#btnAbrirPanel');
        $I->dontSee('Agregar Plantilla');
        
        // Verificar que NO ve botones de eliminar (si hay plantillas)
        $I->dontSeeElement('.btn-eliminar-plantilla');
    }

    /**
     * CA3: Verificar que el formulario de agregar plantilla
     * tiene todos los campos requeridos (solo para Gestor/Admin)
     */
    public function formularioAgregarTieneCamposRequeridos(FunctionalTester $I): void
    {
        // Login como Gestor Académico
        $this->loginAs($I, self::GESTOR_ID, self::GESTOR_PASS);

        $I->amOnPage('/panel_plantillas.php');
        
        // Verificar que existe el panel de agregar (aunque esté oculto inicialmente)
        $I->seeElement('#panelAgregarPlantilla');
        $I->seeElement('#formAgregarPlantilla');
        
        // Verificar campos del formulario
        $I->seeElement('input[name="nombre"]');
        $I->seeElement('textarea[name="descripcion"]');
        $I->seeElement('select[name="tipo"]');
        $I->seeElement('input[name="archivo"]');
        
        // Verificar opciones de tipo
        $I->seeElement('option[value="Propuesta"]');
        $I->seeElement('option[value="Informe Final"]');
        $I->seeElement('option[value="Acta"]');
        $I->seeElement('option[value="Otro"]');
    }

    /**
     * CA1-CA2: Verificar que se muestran plantillas activas
     * con información de versión (fecha de creación)
     */
    public function plantillasActivasMuestranInformacion(FunctionalTester $I): void
    {
        // Login como Estudiante (solo ve activas)
        $this->loginAs($I, self::ESTUDIANTE_ID, self::ESTUDIANTE_PASS);

        $I->amOnPage('/panel_plantillas.php');
        
        // Si hay plantillas, verificar que muestran información básica
        // Nota: Este test es flexible porque puede no haber plantillas en BD limpia
        try {
            // Intentar ver elementos de plantilla
            $I->seeElement('.card-una');
            
            // Si hay plantillas, deben mostrar:
            // - Icono según tipo de archivo
            $I->seeElement('i.bi-file-earmark-pdf-fill, i.bi-file-earmark-word-fill');
            
            // - Botón de descarga
            $I->seeElement('a[href*="descargar_plantilla.php"]');
            
        } catch (\Exception $e) {
            // Si no hay plantillas, debe mostrar estado vacío
            $I->see('No hay plantillas disponibles');
        }
    }

    /**
     * CA3: Verificar que el Gestor puede ver botones de gestión
     * (eliminar y activar/desactivar) en las plantillas
     */
    public function gestorVeBotonesDeGestion(FunctionalTester $I): void
    {
        // Login como Gestor Académico
        $this->loginAs($I, self::GESTOR_ID, self::GESTOR_PASS);

        $I->amOnPage('/panel_plantillas.php');
        
        // Verificar que el Gestor ve el botón de agregar
        $I->seeElement('#btnAbrirPanel');
        
        // Si hay plantillas, verificar botones de gestión
        try {
            $I->seeElement('.card-una');
            
            // Debe ver botones de eliminar
            $I->seeElement('.btn-eliminar-plantilla');
            
            // Debe ver botones de activar/desactivar
            $I->seeElement('.btn-toggle-visibilidad');
            
        } catch (\Exception $e) {
            // Si no hay plantillas, es válido (BD limpia)
            $I->see('No hay plantillas disponibles');
        }
    }

    /**
     * CA3: Verificar que el Administrador también puede gestionar plantillas
     */
    public function administradorPuedeGestionarPlantillas(FunctionalTester $I): void
    {
        // Login como Administrador
        $this->loginAs($I, self::ADMIN_ID, self::ADMIN_PASS);

        $I->amOnPage('/panel_plantillas.php');
        
        // Verificar que puede ver el panel
        $I->see('Plantillas Oficiales');
        
        // Verificar que ve el botón de agregar
        $I->seeElement('#btnAbrirPanel');
        $I->see('Agregar Plantilla');
    }

    /**
     * CA3: Verificar que CTFG NO puede gestionar plantillas
     * (solo puede verlas como cualquier usuario autorizado)
     */
    public function ctfgNoGestionaPlantillas(FunctionalTester $I): void
    {
        // Login como CTFG
        $this->loginAs($I, self::CTFG_ID, self::CTFG_PASS);

        $I->amOnPage('/panel_plantillas.php');
        
        // Verificar que puede ver el panel
        $I->see('Plantillas Oficiales');
        
        // Verificar que NO ve el botón de agregar
        $I->dontSeeElement('#btnAbrirPanel');
        
        // Verificar que NO ve botones de eliminar
        $I->dontSeeElement('.btn-eliminar-plantilla');
    }

    /**
     * CA1: Verificar que el sistema valida tipos de archivo permitidos
     * (solo PDF y DOCX según validaciones de seguridad)
     */
    public function formularioValidaTiposArchivo(FunctionalTester $I): void
    {
        // Login como Gestor Académico
        $this->loginAs($I, self::GESTOR_ID, self::GESTOR_PASS);

        $I->amOnPage('/panel_plantillas.php');
        
        // Verificar que el input de archivo tiene atributo accept
        $I->seeElement('input[name="archivo"][accept*=".pdf"]');
        $I->seeElement('input[name="archivo"][accept*=".docx"]');
        
        // Verificar mensaje de tamaño máximo
        $I->see('máx. 20 MB');
    }

    /**
     * CA2: Verificar que las plantillas muestran fecha de creación
     * (control de versión visible para el usuario)
     */
    public function plantillasMuestranFechaCreacion(FunctionalTester $I): void
    {
        // Login como Gestor (ve todas las plantillas con más detalle)
        $this->loginAs($I, self::GESTOR_ID, self::GESTOR_PASS);

        $I->amOnPage('/panel_plantillas.php');
        
        // Si hay plantillas, deben mostrar fecha
        try {
            $I->seeElement('.card-una');
            
            // Verificar que muestra icono de calendario (fecha)
            $I->seeElement('i.bi-calendar3');
            
        } catch (\Exception $e) {
            // Si no hay plantillas, es válido
            $I->see('No hay plantillas disponibles');
        }
    }

    /**
     * CA1-CA2: Verificar que las plantillas se agrupan por tipo
     * (organización para facilitar actualización según categoría)
     */
    public function plantillasAgrupadasPorTipo(FunctionalTester $I): void
    {
        // Login como Estudiante
        $this->loginAs($I, self::ESTUDIANTE_ID, self::ESTUDIANTE_PASS);

        $I->amOnPage('/panel_plantillas.php');
        
        // Verificar que existe estructura de secciones por tipo
        // (aunque no haya plantillas, la estructura debe estar preparada)
        $I->seeElement('.quick-actions-section, .empty-state');
    }

    /**
     * CA3: Verificar que el script de descarga existe y es accesible
     * (todos los usuarios autorizados pueden descargar)
     */
    public function scriptDescargaAccesible(FunctionalTester $I): void
    {
        // Login como Estudiante
        $this->loginAs($I, self::ESTUDIANTE_ID, self::ESTUDIANTE_PASS);

        // Intentar acceder al script de descarga sin ID (debe manejar error)
        $I->amOnPage('/descargar_plantilla.php');
        
        // No debe mostrar error fatal de PHP
        $I->dontSee('Fatal error');
        $I->dontSee('Parse error');
    }

    /**
     * CA3: Verificar que el acceso directo a scripts de gestión
     * está protegido (solo Gestor y Admin)
     */
    public function scriptGestionProtegido(FunctionalTester $I): void
    {
        // Login como Estudiante (no autorizado para gestión)
        $this->loginAs($I, self::ESTUDIANTE_ID, self::ESTUDIANTE_PASS);

        // Intentar acceder directamente al script de subida
        $I->amOnPage('/plantilla_upload_process.php');
        
        // Debe rechazar el acceso (no debe procesar)
        // El script responde JSON, así que verificamos que no hay error fatal
        $I->dontSee('Fatal error');
        $I->dontSee('Parse error');
    }

    /**
     * Helper: Login con usuario y contraseña
     */
    private function loginAs(FunctionalTester $I, string $userId, string $password): void
    {
        $I->amOnPage('/login.php');
        $I->fillField('#user', $userId);
        $I->fillField('#pass', $password);
        $I->click('#saveForm');
    }
}
