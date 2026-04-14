<?php

declare(strict_types=1);

namespace Tests\functional;

use mysqli;
use RuntimeException;
use Tests\Support\FunctionalTester;

final class HU033NotificationFlowsCest
{
    private const TEST_STUDENT_PASSWORD = 'TmpHu033Student#2026';
    private const TEST_REVIEWER_PASSWORD = 'TmpHu033Reviewer#2026';
    private const BASE_URL = 'http://localhost/base';

    private string $marker = '';
    private string $studentId = '';
    private string $studentOriginalPass = '';
    private string $reviewerId = '';
    private string $reviewerOriginalPass = '';
    private string $fixtureFileName = '';
    private string $fixtureAbsolutePath = '';

    /** @var int[] */
    private array $createdProposalIds = [];

    /** @var int[] */
    private array $createdDocumentIds = [];

    public function _before(FunctionalTester $I): void
    {
        $this->marker = 'HU033_' . date('YmdHis') . '_' . (string) random_int(1000, 9999);
        $this->preparePdfFixture();

        $db = $this->connectDb();

        [$this->studentId, $this->studentOriginalPass] = $this->grabStudentWithoutApprovedProposal($db);
        $this->setUserPassword($db, $this->studentId, self::TEST_STUDENT_PASSWORD);

        [$this->reviewerId, $this->reviewerOriginalPass] = $this->grabReviewerCredentials($db);
        $this->setUserPassword($db, $this->reviewerId, self::TEST_REVIEWER_PASSWORD);

        $db->close();
    }

    public function _after(FunctionalTester $I): void
    {
    $db = $this->connectDb();

    // Primero restaurar contraseñas, para no dejar usuarios de prueba alterados
    if ($this->studentId !== '' && $this->studentOriginalPass !== '') {
        $this->restoreUserPassword($db, $this->studentId, $this->studentOriginalPass);
    }

    if ($this->reviewerId !== '' && $this->reviewerOriginalPass !== '') {
        $this->restoreUserPassword($db, $this->reviewerId, $this->reviewerOriginalPass);
    }

    // El cleanup no debe botar la prueba si falla
    try {
        $this->cleanupArtifacts($db);
    } catch (\Throwable $e) {
        error_log('HU033 cleanup warning: ' . $e->getMessage());
    }

    $db->close();

    if ($this->fixtureAbsolutePath !== '' && file_exists($this->fixtureAbsolutePath)) {
        @unlink($this->fixtureAbsolutePath);
    }
    }

    public function subirDocumentoFinalGeneraAlertasInternasParaGestoresYCTFG(FunctionalTester $I): void
    {
        $db = $this->connectDb();

        $proposalId = $this->seedApprovedProposalForStudent(
            $db,
            $this->studentId,
            'HU033 Documento Final ' . $this->marker
        );

        $beforeAlertId = $this->grabMaxUserAlertId($db);
        $role2And3Ids = $this->grabUserIdsByRoles($db, [2, 3]);

        $db->close();

        $this->loginAs($I, $this->studentId, self::TEST_STUDENT_PASSWORD);

        $I->amOnPage('/mod/admin/users/tfg_upload_final_document.php');
        $I->see('Subir Documento Final del TFG');
        $I->attachFile('input[name="documents[]"]', $this->fixtureFileName);
        $I->fillField('textarea[name="notes"]', 'Entrega funcional ' . $this->marker);
        $I->click('#btnSubmit');

        $I->seeInSource('"success":true');
        $I->seeInSource('Documento(s) final(es) subido(s) exitosamente');

        $response = json_decode($I->grabPageSource(), true);
        $I->assertIsArray($response);
        $I->assertTrue($response['success'] ?? false);

        $db = $this->connectDb();

        $recipients = $this->grabNewAlertRecipientsBySubjectAndTitle(
            $db,
            $beforeAlertId,
            'Nuevo Documento Final TFG Recibido',
            $this->marker
        );

        foreach ($role2And3Ids as $expectedUserId) {
            $I->assertTrue(
                in_array($expectedUserId, $recipients, true),
                'No se encontró alerta interna para el usuario de rol 2/3 con ID ' . $expectedUserId
            );
        }

        $db->close();
    }

    public function revisionDocumentoFinalConCorreccionesGeneraAlertaAlEstudiante(FunctionalTester $I): void
    {
        $db = $this->connectDb();

        $proposalId = $this->seedApprovedProposalForStudent(
            $db,
            $this->studentId,
            'HU033 Revision Final ' . $this->marker
        );

        $documentId = $this->seedPendingFinalDocument(
            $db,
            $proposalId,
            $this->studentId
        );

        $beforeAlertId = $this->grabMaxUserAlertId($db);
        $db->close();

        $this->loginAs($I, $this->reviewerId, self::TEST_REVIEWER_PASSWORD);

        $I->sendAjaxPostRequest('/mod/admin/users/process_final_document_review.php', [
            'document_id' => $documentId,
            'status' => 'Correcciones Requeridas',
            'comments' => 'Observaciones funcionales ' . $this->marker,
        ]);

        $I->seeInSource('"success":true');

        $source = $I->grabPageSource();
        $I->assertStringNotContainsString('Fatal error', $source);
        $I->assertStringNotContainsString('Parse error', $source);

        $db = $this->connectDb();

        $studentAlerts = $this->countStudentAlertsBySubjectAndTitle(
            $db,
            $beforeAlertId,
            $this->studentId,
            'Se Solicitan Correcciones a tu Documento Final',
            $this->marker
        );

        $I->assertGreaterThan(
            0,
            $studentAlerts,
            'No se registró la alerta interna para el estudiante después de la revisión del documento final.'
        );

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
            throw new RuntimeException('Error de conexión BD en test funcional HU-033: ' . $dbConn->connect_error);
        }

        $dbConn->set_charset('utf8');
        return $dbConn;
    }

    private function preparePdfFixture(): void {
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
        throw new RuntimeException('No se encontró la carpeta tests/_data para crear el fixture de HU-033.');
    }

    $this->fixtureFileName = $this->marker . '.pdf';
    $this->fixtureAbsolutePath = $dataDir . DIRECTORY_SEPARATOR . $this->fixtureFileName;

    $contents = file_get_contents($source);
    if ($contents === false) {
        throw new RuntimeException('No se pudo leer el PDF base para HU-033.');
    }

    // Aumentar tamaño para pasar validaciones de "documento completo"
    $minBytes = 150 * 1024; // 150 KB
    if (strlen($contents) < $minBytes) {
        $padding = str_repeat("\n% HU033 padding", (int) ceil(($minBytes - strlen($contents)) / 15));
        $contents .= $padding;
    }

    if (file_put_contents($this->fixtureAbsolutePath, $contents) === false) {
        throw new RuntimeException('No fue posible crear el fixture PDF para HU-033.');
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
            throw new RuntimeException('No se encontró un estudiante real sin propuestas aprobadas para HU-033.');
        }

        return [(string) $row['id'], (string) $row['pass']];
    }

    private function grabReviewerCredentials(mysqli $db): array
    {
        $stmt = $db->prepare('SELECT id, pass FROM sis_login WHERE id_roll IN (2,3) ORDER BY id_roll ASC, id ASC LIMIT 1');
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row || !isset($row['id'], $row['pass'])) {
            throw new RuntimeException('No se encontró revisor con rol 2 o 3 para la prueba funcional HU-033.');
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

        $disciplines = 'HU033 prueba funcional';
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

    private function seedPendingFinalDocument(mysqli $db, int $proposalId, string $studentId): int
    {
        $blob = file_get_contents($this->fixtureAbsolutePath);
        if ($blob === false) {
            throw new RuntimeException('No se pudo leer el fixture PDF para sembrar documento final.');
        }

        $fileName = $this->fixtureFileName;
        $mimeType = 'application/pdf';
        $fileSize = filesize($this->fixtureAbsolutePath);
        $version = 1.0;
        $documentType = 'Documento Final TFG';

        $stmtFile = $db->prepare("
            INSERT INTO tfg_files
            (file_name, mime_type, file_size, file_data, storage_path, uploaded_by, version, document_type)
            VALUES (?, ?, ?, ?, NULL, ?, ?, ?)
        ");

        $nullBlob = null;
        $stmtFile->bind_param('ssibsds', $fileName, $mimeType, $fileSize, $nullBlob, $studentId, $version, $documentType);
        $stmtFile->send_long_data(3, $blob);

        if (!$stmtFile->execute()) {
            $error = $stmtFile->error;
            $stmtFile->close();
            throw new RuntimeException('No se pudo sembrar archivo de documento final: ' . $error);
        }

        $fileId = (int) $db->insert_id;
        $stmtFile->close();

        $projectStatus = 'Vigente';
        $status = 'Pendiente de Revision';

        $stmtDoc = $db->prepare("
            INSERT INTO tfg_final_documents
            (proposal_id, file_id, status, project_status, submitted_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmtDoc->bind_param('iisss', $proposalId, $fileId, $status, $projectStatus, $studentId);

        if (!$stmtDoc->execute()) {
            $error = $stmtDoc->error;
            $stmtDoc->close();
            throw new RuntimeException('No se pudo sembrar documento final pendiente: ' . $error);
        }

        $documentId = (int) $db->insert_id;
        $stmtDoc->close();

        $stmtLink = $db->prepare('UPDATE tfg_files SET final_document_id = ? WHERE id = ?');
        $stmtLink->bind_param('ii', $documentId, $fileId);
        $stmtLink->execute();
        $stmtLink->close();

        $this->createdDocumentIds[] = $documentId;

        return $documentId;
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

    private function grabNewAlertRecipientsBySubjectAndTitle(mysqli $db, int $beforeAlertId, string $subject, string $marker): array
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

    private function countStudentAlertsBySubjectAndTitle(mysqli $db, int $beforeAlertId, string $studentId, string $subject, string $marker): int
    {
        $like = '%' . $marker . '%';

        $stmt = $db->prepare("
            SELECT COUNT(*) AS total
            FROM user_alerts
            WHERE id > ?
              AND user_id = ?
              AND subject = ?
              AND message LIKE ?
        ");
        $stmt->bind_param('isss', $beforeAlertId, $studentId, $subject, $like);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return isset($row['total']) ? (int) $row['total'] : 0;
    }

    private function cleanupArtifacts(mysqli $db): void
{
    $like = '%' . $this->marker . '%';

    // 1. Alertas internas generadas por la prueba
    $stmtAlerts = $db->prepare('DELETE FROM user_alerts WHERE message LIKE ?');
    if ($stmtAlerts) {
        $stmtAlerts->bind_param('s', $like);
        $stmtAlerts->execute();
        $stmtAlerts->close();
    }

    // 2. Notificaciones funcionales ligadas a propuestas de prueba
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

    // 3. Revisiones ligadas a documentos finales de propuestas de prueba
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

    // 4. Timelines ligados a propuestas de prueba
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

    // 5. Proyecto aprobado ligado a propuestas de prueba
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

    // 6. Borrar propuestas de prueba
    // Esto debería eliminar automáticamente tfg_final_documents por la FK proposal_id -> tfg_proposals
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

    // 7. Solo después borrar archivos huérfanos de prueba
    // Nunca borrar archivos que sigan referenciados por tfg_final_documents
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
}
}