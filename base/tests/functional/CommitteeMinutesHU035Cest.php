<?php

declare(strict_types=1);

namespace Tests\functional;

use mysqli;
use RuntimeException;
use Tests\Support\FunctionalTester;

final class CommitteeMinutesHU035Cest
{
    private const PANEL_URL = '/panel_committee_minutes.php';

    // Usuario real del comité del proyecto semilla 12
    private const COMITE_USER_ID = '800870458'; // Darinka Grbic Grbic - Tutor
    private const COMITE_USER_PASS = 'secret123';

    private const PROJECT_ID = 12;

    /**
     * CA base: el panel carga, muestra selección de proyecto
     * y permite cargar los asistentes del proyecto.
     */
    public function panelCargaYMuestraAsistentesDelProyecto(FunctionalTester $I): void
    {
        $this->loginAs($I, self::COMITE_USER_ID, self::COMITE_USER_PASS);

        $I->amOnPage(self::PANEL_URL);

        $I->see('Registro de minutas del Comité Asesor');
        $I->seeElement('select[name="project_id"]');
        $I->see('Seleccione un proyecto');

        $I->selectOption('select[name="project_id"]', (string) self::PROJECT_ID);
        $I->click('Cargar asistentes');

        $I->see('Proyecto seleccionado:');
        $I->seeElement('input[name="session_date"]');
        $I->seeElement('input[name="minute_pdf"]');

        // Participantes esperados del proyecto 12 según la semilla
        $I->see('DARINKA GRBIC GRBIC');
        $I->see('CARLOS LUIS CHANTO ESPINOZA');
        $I->see('EDDIER LÓPEZ LÓPEZ');
        $I->see('CARLOS DANIEL LÓPEZ CHÉVEZ');
        $I->see('JOSE DOMINGO MOLINA SALAS');
        $I->see('LARISSA SEGURA ARGUELLO');
    }

    /**
     * Flujo principal: subir una minuta válida, confirmar mensaje de éxito
     * y validar en BD que quedó registrada.
     */
    public function subeMinutaValidaYSeListaEnElProyecto(FunctionalTester $I): void
    {
        $db = $this->connectDb();
        $sessionDate = $this->findUnusedSessionDate($db, self::PROJECT_ID);
        $beforeCount = $this->countMinutesByProjectAndDate($db, self::PROJECT_ID, $sessionDate);

        $fixturePath = $this->ensurePdfFixtureExists('hu035_minuta_valida.pdf');
        $fixtureName = basename($fixturePath);

        $this->loginAs($I, self::COMITE_USER_ID, self::COMITE_USER_PASS);

        $I->amOnPage(self::PANEL_URL);
        $I->selectOption('select[name="project_id"]', (string) self::PROJECT_ID);
        $I->click('Cargar asistentes');

        $I->fillField('input[name="session_date"]', $sessionDate);
        $I->attachFile('input[name="minute_pdf"]', $fixturePath);

        // Marcar al menos un asistente
        $I->checkOption('input[name="attendees[]"][value="800870458"]');
        $I->checkOption('input[name="attendees[]"][value="504410118"]');

        $I->click('Guardar minuta');

        $I->see('La minuta se registró correctamente');

        // El flujo actual vuelve al panel limpio, así que volvemos a cargar el proyecto
        // para verificar que la minuta quedó listada correctamente.
        $I->amOnPage(self::PANEL_URL . '?project_id=' . self::PROJECT_ID);

        $I->see('Minutas registradas del proyecto');

        $afterCount = $this->countMinutesByProjectAndDate($db, self::PROJECT_ID, $sessionDate);
        $I->assertSame(
            $beforeCount + 1,
            $afterCount,
            'La minuta no se registró en BD para la fecha esperada.'
        );

        $latestMinute = $this->grabLatestMinuteByProjectAndDate($db, self::PROJECT_ID, $sessionDate);
        $I->assertNotNull($latestMinute, 'No se pudo recuperar la minuta recién registrada desde BD.');

        $I->see($sessionDate);
        $I->see($fixtureName);
        $I->see('Descargar');

        $db->close();
    }

    /**
     * Regla crítica: no permitir una segunda minuta para el mismo proyecto
     * y la misma fecha de sesión.
     */
    public function bloqueaMinutaDuplicadaPorProyectoYFecha(FunctionalTester $I): void
    {
        $db = $this->connectDb();
        $sessionDate = $this->findUnusedSessionDate($db, self::PROJECT_ID);

        $fixtureFirst = $this->ensurePdfFixtureExists('hu035_minuta_duplicado_1.pdf');
        $fixtureSecond = $this->ensurePdfFixtureExists('hu035_minuta_duplicado_2.pdf');

        // Primera carga válida
        $this->loginAs($I, self::COMITE_USER_ID, self::COMITE_USER_PASS);

        $I->amOnPage(self::PANEL_URL);
        $I->selectOption('select[name="project_id"]', (string) self::PROJECT_ID);
        $I->click('Cargar asistentes');

        $I->fillField('input[name="session_date"]', $sessionDate);
        $I->attachFile('input[name="minute_pdf"]', $fixtureFirst);
        $I->checkOption('input[name="attendees[]"][value="800870458"]');
        $I->click('Guardar minuta');

        $I->see('La minuta se registró correctamente');

        $countAfterFirst = $this->countMinutesByProjectAndDate($db, self::PROJECT_ID, $sessionDate);
        $I->assertSame(
            1,
            $countAfterFirst,
            'La primera minuta de la prueba de duplicado no quedó registrada como se esperaba.'
        );

        // Segundo intento con misma fecha => debe bloquear
        $I->amOnPage(self::PANEL_URL);
        $I->selectOption('select[name="project_id"]', (string) self::PROJECT_ID);
        $I->click('Cargar asistentes');

        $I->fillField('input[name="session_date"]', $sessionDate);
        $I->attachFile('input[name="minute_pdf"]', $fixtureSecond);
        $I->checkOption('input[name="attendees[]"][value="800870458"]');
        $I->click('Guardar minuta');

        $I->see('Ya existe una minuta registrada para este proyecto en esa fecha de sesión.');

        $countAfterSecond = $this->countMinutesByProjectAndDate($db, self::PROJECT_ID, $sessionDate);
        $I->assertSame(
            1,
            $countAfterSecond,
            'Se registró una minuta duplicada cuando debió bloquearse.'
        );

        $db->close();
    }

    /**
     * El listado debe exponer acción de descarga para las minutas ya registradas.
     */
    public function listadoMuestraAccionDeDescarga(FunctionalTester $I): void
    {
        $db = $this->connectDb();
        $existingMinuteId = $this->grabAnyMinuteIdByProject($db, self::PROJECT_ID);

        if ($existingMinuteId === null) {
            $db->close();
            throw new RuntimeException(
                'No existe ninguna minuta para el proyecto 12. ' .
                'Ejecute primero subeMinutaValidaYSeListaEnElProyecto o registre una minuta manualmente.'
            );
        }

        $this->loginAs($I, self::COMITE_USER_ID, self::COMITE_USER_PASS);

        $I->amOnPage(self::PANEL_URL . '?project_id=' . self::PROJECT_ID);

        $I->see('Minutas registradas del proyecto');
        $I->seeElement('a[href*="mod/admin/users/minute_download.php?minute_id="]');
        $I->see('Descargar');

        $db->close();
    }

    private function loginAs(FunctionalTester $I, string $userId, string $password): void
    {
        $I->amOnPage('/login.php');
        $I->fillField('#user', $userId);
        $I->fillField('#pass', $password);
        $I->click('#saveForm');
    }

    private function connectDb(): mysqli
    {
        require __DIR__ . '/../../inc/db/bdcommon.inc';

        $db = new mysqli($db_host, $usuario, $clave, $db);
        if ($db->connect_error) {
            throw new RuntimeException('Error conectando a BD para pruebas funcionales: ' . $db->connect_error);
        }

        $db->set_charset('utf8');
        return $db;
    }

    private function countMinutesByProjectAndDate(mysqli $db, int $projectId, string $sessionDate): int
    {
        $stmt = $db->prepare(
            'SELECT COUNT(*) AS total
             FROM project_minutes
             WHERE project_id = ?
               AND session_date = ?'
        );
        $stmt->bind_param('is', $projectId, $sessionDate);
        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return isset($row['total']) ? (int) $row['total'] : 0;
    }

    private function grabLatestMinuteByProjectAndDate(mysqli $db, int $projectId, string $sessionDate): ?array
    {
        $stmt = $db->prepare(
            'SELECT id, file_name, session_date
             FROM project_minutes
             WHERE project_id = ?
               AND session_date = ?
             ORDER BY id DESC
             LIMIT 1'
        );
        $stmt->bind_param('is', $projectId, $sessionDate);
        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return is_array($row) ? $row : null;
    }

    private function grabAnyMinuteIdByProject(mysqli $db, int $projectId): ?int
    {
        $stmt = $db->prepare(
            'SELECT id
             FROM project_minutes
             WHERE project_id = ?
             ORDER BY session_date DESC, id DESC
             LIMIT 1'
        );
        $stmt->bind_param('i', $projectId);
        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return isset($row['id']) ? (int) $row['id'] : null;
    }

    private function findUnusedSessionDate(mysqli $db, int $projectId): string
    {
        $base = new \DateTimeImmutable('today');

        for ($i = 0; $i < 365; $i++) {
            $candidate = $base->modify('+' . $i . ' day')->format('Y-m-d');
            if ($this->countMinutesByProjectAndDate($db, $projectId, $candidate) === 0) {
                return $candidate;
            }
        }

        throw new RuntimeException('No se encontró una fecha libre para registrar la minuta de prueba.');
    }

private function ensurePdfFixtureExists(string $fileName): string
    {
        $path = __DIR__ . '/../_data/' . $fileName;

        if (!is_file($path)) {
            $minimalPdf = <<<PDF
    %PDF-1.4
    1 0 obj
    << /Type /Catalog /Pages 2 0 R >>
    endobj
    2 0 obj
    << /Type /Pages /Count 1 /Kids [3 0 R] >>
    endobj
    3 0 obj
    << /Type /Page /Parent 2 0 R /MediaBox [0 0 300 144] /Contents 4 0 R >>
    endobj
    4 0 obj
    << /Length 44 >>
    stream
    BT
    /F1 12 Tf
    72 72 Td
    (HU-035 Minuta de prueba) Tj
    ET
    endstream
    endobj
    xref
    0 5
    0000000000 65535 f
    0000000010 00000 n
    0000000063 00000 n
    0000000122 00000 n
    0000000208 00000 n
    trailer
    << /Root 1 0 R /Size 5 >>
    startxref
    302
    %%EOF
    PDF;
            file_put_contents($path, $minimalPdf);
        }

        if (!is_file($path)) {
            throw new RuntimeException('No se pudo crear el fixture PDF para la prueba funcional.');
        }

        return $fileName;
    }
}