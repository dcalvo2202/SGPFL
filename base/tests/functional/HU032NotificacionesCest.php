<?php

declare(strict_types=1);

namespace Tests\functional;

use mysqli;
use RuntimeException;
use Tests\Support\FunctionalTester;

final class HU032NotificacionesCest
{
    private const TEST_STUDENT_PASSWORD = 'TmpHU032Student#2026';

    private string $marker = '';
    private string $studentId = '';
    private string $studentOriginalPass = '';
    private string $fixtureFileName = '';
    private string $fixtureAbsolutePath = '';

    /** @var int[] */
    private array $createdProposalIds = [];

    public function _before(FunctionalTester $I): void
    {
        $this->marker = 'HU032_' . date('YmdHis') . '_' . (string) random_int(1000, 9999);
        $this->preparePdfFixture();

        $db = $this->connectDb();

        [$this->studentId, $this->studentOriginalPass] = $this->grabStudentWithoutApprovedProposal($db);
        $this->setUserPassword($db, $this->studentId, self::TEST_STUDENT_PASSWORD);

        $db->close();
    }

    public function _after(FunctionalTester $I): void
    {
        $db = $this->connectDb();

        if ($this->studentId !== '' && $this->studentOriginalPass !== '') {
            $this->restoreUserPassword($db, $this->studentId, $this->studentOriginalPass);
        }

        try {
            $this->cleanupArtifacts($db);
        } catch (\Throwable $e) {
            error_log('HU032 cleanup warning: ' . $e->getMessage());
        }

        $db->close();

        if ($this->fixtureAbsolutePath !== '' && file_exists($this->fixtureAbsolutePath)) {
            @unlink($this->fixtureAbsolutePath);
        }
    }

    public function estudianteSubeDocumentoYSeNotificaARoles2y3(FunctionalTester $I): void
    {
        $db = $this->connectDb();

        $this->seedApprovedProposalForStudent(
            $db,
            $this->studentId,
            'HU032 Documento Final ' . $this->marker
        );

        $beforeAlertId = $this->grabMaxUserAlertId($db);
        $expectedRecipients = $this->grabUserIdsByRoles($db, [2, 3]);

        $db->close();

        $this->loginAs($I, $this->studentId, self::TEST_STUDENT_PASSWORD);

        $I->amOnPage('/mod/admin/users/tfg_upload_final_document.php');
        $I->see('Subir Documento Final del TFG');
        $I->attachFile('input[name="documents[]"]', $this->fixtureFileName);
        $I->fillField('textarea[name="notes"]', 'Prueba funcional HU032 ' . $this->marker);
        $I->click('#btnSubmit');

        $I->seeInSource('"success":true');
        $I->seeInSource('Documento(s) final(es) subido(s) exitosamente');

        $response = json_decode($I->grabPageSource(), true);
        $I->assertIsArray($response);
        $I->assertTrue($response['success'] ?? false);

        $db = $this->connectDb();

        $recipients = $this->grabNewAlertRecipientsBySubjectAndMarker(
            $db,
            $beforeAlertId,
            'Nuevo Documento Final TFG Recibido',
            $this->marker
        );

        foreach ($expectedRecipients as $expectedUserId) {
            $I->assertTrue(
                in_array($expectedUserId, $recipients, true),
                'No se encontró alerta para el usuario esperado: ' . $expectedUserId
            );
        }

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

        $dbConn = new mysqli($db_host, $usuario, $clave, $db);
        if ($dbConn->connect_error) {
            throw new RuntimeException('Error de conexión BD en test funcional HU-032: ' . $dbConn->connect_error);
        }

        $dbConn->set_charset('utf8');
        return $dbConn;
    }

    private function preparePdfFixture(): void
    {
        $candidatePaths = [
            __DIR__ . '/../_data/sample_cv.pdf',
            __DIR__ . '/../_data/sample_id.pdf',
            __DIR__ . '/_data/sample_cv.pdf',
            __DIR__ . '/_data/sample_id.pdf',
        ];

        $source = null;
        foreach ($candidatePaths as $candidate) {
            if (file_exists($candidate)) {
                $source = realpath($candidate);
                break;
            }
        }

        if ($source === false || $source === null || !file_exists($source)) {
            throw new RuntimeException(
                'No se encontró un PDF fixture en tests/_data (sample_cv.pdf o sample_id.pdf).'
            );
        }

        $dataDir = realpath(__DIR__ . '/../_data');
        if ($dataDir === false) {
            throw new RuntimeException('No se encontró la carpeta tests/_data para crear el fixture de HU-032.');
        }

        $this->fixtureFileName = $this->marker . '.pdf';
        $this->fixtureAbsolutePath = $dataDir . DIRECTORY_SEPARATOR . $this->fixtureFileName;

        $contents = file_get_contents($source);
        if ($contents === false) {
            throw new RuntimeException('No se pudo leer el PDF base para HU-032.');
        }

        $minBytes = 150 * 1024;
        if (strlen($contents) < $minBytes) {
            $padding = str_repeat("\n% HU032 padding", (int) ceil(($minBytes - strlen($contents)) / 15));
            $contents .= $padding;
        }

        if (file_put_contents($this->fixtureAbsolutePath, $contents) === false) {
            throw new RuntimeException('No fue posible crear el fixture PDF para HU-032.');
        }
    }

    private function grabStudentWithoutApprovedProposal(mysqli $db): array
    {
        $sql = "
            SELECT l.id, l.pass
            FROM sis_login l
            LEFT JOIN tfg_proposals tp
                ON tp.user_id = l.id
               AND tp.status = 'Aprobado'
            WHERE l.id_roll = 4
            GROUP BY l.id, l.pass
            HAVING COUNT(tp.id) = 0
            ORDER BY l.id ASC
            LIMIT 1
        ";

        $res = $db->query($sql);
        $row = $res ? $res->fetch_assoc() : null;

        if (!$row || !isset($row['id'], $row['pass'])) {
            throw new RuntimeException('No se encontró un estudiante real sin propuestas aprobadas para HU-032.');
        }

        return [(string) $row['id'], (string) $row['pass']];
    }

    private function setUserPassword(mysqli $db, string $userId, string $plainPassword): void
    {
        $hash = password_hash($plainPassword, PASSWORD_DEFAULT);

        $stmt = $db->prepare('UPDATE sis_login SET pass = ? WHERE id = ?');
        $stmt->bind_param('ss', $hash, $userId);
        $ok = $stmt->execute();
        $stmt->close();

        if (!$ok) {
            throw new RuntimeException('No se pudo asignar contraseña temporal al usuario ' . $userId);
        }
    }

    private function restoreUserPassword(mysqli $db, string $userId, string $originalHash): void
    {
        $stmt = $db->prepare('UPDATE sis_login SET pass = ? WHERE id = ?');
        $stmt->bind_param('ss', $originalHash, $userId);
        $stmt->execute();
        $stmt->close();
    }

    private function seedApprovedProposalForStudent(mysqli $db, string $studentId, string $title): int
    {
        $blob = file_get_contents($this->fixtureAbsolutePath);
        if ($blob === false) {
            throw new RuntimeException('No se pudo leer el fixture PDF para sembrar propuesta.');
        }

        $disciplines = 'HU032 prueba funcional';
        $description = 'Propuesta funcional de prueba ' . $this->marker;
        $fileName = $this->fixtureFileName;
        $mimeType = 'application/pdf';
        $fileSize = filesize($this->fixtureAbsolutePath);

        $sql = "
            INSERT INTO tfg_proposals
            (user_id, title, disciplines, project_description, document, file_name, mime_type, file_size, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Aprobado', NOW(), NOW())
        ";

        $stmt = $db->prepare($sql);
        $nullBlob = null;
        $stmt->bind_param('ssssbssi', $studentId, $title, $disciplines, $description, $nullBlob, $fileName, $mimeType, $fileSize);
        $stmt->send_long_data(4, $blob);

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new RuntimeException('No se pudo sembrar propuesta aprobada: ' . $error);
        }

        $proposalId = (int) $db->insert_id;
        $stmt->close();

        $this->createdProposalIds[] = $proposalId;

        return $proposalId;
    }

    private function grabMaxUserAlertId(mysqli $db): int
    {
        $res = $db->query('SELECT COALESCE(MAX(id), 0) AS max_id FROM user_alerts');
        $row = $res ? $res->fetch_assoc() : null;

        return isset($row['max_id']) ? (int) $row['max_id'] : 0;
    }

    private function grabUserIdsByRoles(mysqli $db, array $roles): array
    {
        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        $types = str_repeat('i', count($roles));

        $stmt = $db->prepare("SELECT id FROM sis_login WHERE id_roll IN ($placeholders)");
        $stmt->bind_param($types, ...$roles);
        $stmt->execute();
        $res = $stmt->get_result();

        $ids = [];
        while ($row = $res->fetch_assoc()) {
            $ids[] = (string) $row['id'];
        }

        $stmt->close();
        return $ids;
    }

    private function grabNewAlertRecipientsBySubjectAndMarker(mysqli $db, int $beforeAlertId, string $subject, string $marker): array
    {
        $like = '%' . $marker . '%';

        $stmt = $db->prepare("
            SELECT DISTINCT user_id
            FROM user_alerts
            WHERE id > ?
              AND subject = ?
              AND message LIKE ?
        ");
        $stmt->bind_param('iss', $beforeAlertId, $subject, $like);
        $stmt->execute();
        $res = $stmt->get_result();

        $ids = [];
        while ($row = $res->fetch_assoc()) {
            $ids[] = (string) $row['user_id'];
        }

        $stmt->close();
        return $ids;
    }

    private function cleanupArtifacts(mysqli $db): void
    {
        $like = '%' . $this->marker . '%';

        $stmtAlerts = $db->prepare('DELETE FROM user_alerts WHERE message LIKE ?');
        if ($stmtAlerts) {
            $stmtAlerts->bind_param('s', $like);
            $stmtAlerts->execute();
            $stmtAlerts->close();
        }

        $stmtNotifications = $db->prepare("
            DELETE tn
            FROM tfg_notifications tn
            INNER JOIN tfg_proposals tp ON tp.id = tn.proposal_id
            WHERE tp.user_id = ?
              AND tp.title LIKE ?
        ");
        if ($stmtNotifications) {
            $stmtNotifications->bind_param('ss', $this->studentId, $like);
            $stmtNotifications->execute();
            $stmtNotifications->close();
        }

        $stmtReviews = $db->prepare("
            DELETE dr
            FROM tfg_document_reviews dr
            INNER JOIN tfg_final_documents fd ON dr.document_id = fd.id
            INNER JOIN tfg_proposals tp ON fd.proposal_id = tp.id
            WHERE tp.user_id = ?
              AND tp.title LIKE ?
        ");
        if ($stmtReviews) {
            $stmtReviews->bind_param('ss', $this->studentId, $like);
            $stmtReviews->execute();
            $stmtReviews->close();
        }

        $stmtTimeline = $db->prepare("
            DELETE tt
            FROM tfg_project_timeline tt
            INNER JOIN tfg_proposals tp ON tt.proposal_id = tp.id
            WHERE tp.user_id = ?
              AND tp.title LIKE ?
        ");
        if ($stmtTimeline) {
            $stmtTimeline->bind_param('ss', $this->studentId, $like);
            $stmtTimeline->execute();
            $stmtTimeline->close();
        }

        $stmtProyectoAprobado = $db->prepare("
            DELETE pa
            FROM proyecto_aprobado pa
            INNER JOIN tfg_proposals tp ON pa.proposal_id = tp.id
            WHERE tp.user_id = ?
              AND tp.title LIKE ?
        ");
        if ($stmtProyectoAprobado) {
            $stmtProyectoAprobado->bind_param('ss', $this->studentId, $like);
            $stmtProyectoAprobado->execute();
            $stmtProyectoAprobado->close();
        }

        $stmtFinalDocs = $db->prepare("
            DELETE fd
            FROM tfg_final_documents fd
            INNER JOIN tfg_proposals tp ON fd.proposal_id = tp.id
            WHERE tp.user_id = ?
              AND tp.title LIKE ?
        ");
        if ($stmtFinalDocs) {
            $stmtFinalDocs->bind_param('ss', $this->studentId, $like);
            $stmtFinalDocs->execute();
            $stmtFinalDocs->close();
        }

        $stmtFiles = $db->prepare("
            DELETE FROM tfg_files
            WHERE uploaded_by = ?
              AND file_name LIKE ?
              AND id NOT IN (SELECT file_id FROM tfg_final_documents)
        ");
        if ($stmtFiles) {
            $stmtFiles->bind_param('ss', $this->studentId, $like);
            $stmtFiles->execute();
            $stmtFiles->close();
        }

        $stmtProposals = $db->prepare("
            DELETE FROM tfg_proposals
            WHERE user_id = ?
              AND title LIKE ?
        ");
        if ($stmtProposals) {
            $stmtProposals->bind_param('ss', $this->studentId, $like);
            $stmtProposals->execute();
            $stmtProposals->close();
        }
    }
}
?>