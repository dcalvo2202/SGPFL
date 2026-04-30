<?php

declare(strict_types=1);

namespace Tests\functional;

use mysqli;
use RuntimeException;
use Tests\Support\FunctionalTester;

final class HUCancelacionProyectosCest
{
    private const TEST_REVIEWER_PASSWORD = 'TmpCancelacion#2026';

    private string $marker = '';
    private string $reviewerId = '';
    private string $reviewerOriginalPass = '';
    private string $studentId = '';

    /** @var int[] */
    private array $createdProjectIds = [];

    /** @var int[] */
    private array $createdProposalIds = [];

    public function _before(FunctionalTester $I): void
    {
        $this->marker = 'CANCEL_' . date('YmdHis') . '_' . (string) random_int(1000, 9999);

        $db = $this->connectDb();

        [$this->reviewerId, $this->reviewerOriginalPass] = $this->grabReviewerCredentials($db);
        $this->setUserPassword($db, $this->reviewerId, self::TEST_REVIEWER_PASSWORD);

        $this->studentId = $this->grabStudentWithoutApprovedProposal($db);

        $db->close();
    }

    public function _after(FunctionalTester $I): void
    {
        $db = $this->connectDb();

        if ($this->reviewerId !== '' && $this->reviewerOriginalPass !== '') {
            $this->restoreUserPassword($db, $this->reviewerId, $this->reviewerOriginalPass);
        }

        try {
            $this->cleanupArtifacts($db);
        } catch (\Throwable $e) {
            error_log('HU Cancelacion cleanup warning: ' . $e->getMessage());
        }

        $db->close();
    }

    public function cancelacionExitosaActualizaProyectoYPropuesta(FunctionalTester $I): void
    {
        $db = $this->connectDb();

        $proposalId = $this->seedApprovedProposalForStudent(
            $db,
            $this->studentId,
            'Propuesta cancelación exitosa ' . $this->marker
        );

        $projectId = $this->seedApprovedProject(
            $db,
            $proposalId,
            'Proyecto cancelación exitosa ' . $this->marker,
            '-7 months'
        );

        $db->close();

        $this->loginAs($I, $this->reviewerId, self::TEST_REVIEWER_PASSWORD);

        $I->sendAjaxPostRequest('/cancelar_proyecto_aprobado.php', [
            'proyecto_id'   => $projectId,
            'motivo'        => 'Prueba funcional de cancelación ' . $this->marker,
            'observaciones' => 'Observación funcional ' . $this->marker,
        ]);

        $source = $I->grabPageSource();
        $I->assertStringNotContainsString('Fatal error', $source);
        $I->assertStringNotContainsString('Parse error', $source);

        $db = $this->connectDb();

        $project = $this->grabProjectState($db, $projectId);
        $proposal = $this->grabProposalState($db, $proposalId);
        $cancelCount = $this->countCancellationAgreements($db, $projectId);

        $I->assertSame('CANCELADO', (string) $project['estado']);
        $I->assertSame(4, (int) $project['aprobado']);
        $I->assertSame('Cancelado', (string) $proposal['status']);
        $I->assertSame(1, $cancelCount);

        $db->close();
    }

    public function cancelacionNoProcedeSiProyectoNoCumpleSeisMeses(FunctionalTester $I): void
    {
        $db = $this->connectDb();

        $proposalId = $this->seedApprovedProposalForStudent(
            $db,
            $this->studentId,
            'Propuesta cancelación rechazada ' . $this->marker
        );

        $projectId = $this->seedApprovedProject(
            $db,
            $proposalId,
            'Proyecto cancelación rechazada ' . $this->marker,
            '-2 months'
        );

        $db->close();

        $this->loginAs($I, $this->reviewerId, self::TEST_REVIEWER_PASSWORD);

        $I->sendAjaxPostRequest('/cancelar_proyecto_aprobado.php', [
            'proyecto_id'   => $projectId,
            'motivo'        => 'Prueba funcional rechazo ' . $this->marker,
            'observaciones' => '',
        ]);

        $source = $I->grabPageSource();
        $I->assertStringNotContainsString('Fatal error', $source);
        $I->assertStringNotContainsString('Parse error', $source);

        $db = $this->connectDb();

        $project = $this->grabProjectState($db, $projectId);
        $proposal = $this->grabProposalState($db, $proposalId);
        $cancelCount = $this->countCancellationAgreements($db, $projectId);

        $I->assertNotSame('CANCELADO', (string) $project['estado']);
        $I->assertNotSame(4, (int) $project['aprobado']);
        $I->assertNotSame('Cancelado', (string) $proposal['status']);
        $I->assertSame(0, $cancelCount);

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
            throw new RuntimeException('Error de conexión BD en test funcional de cancelación: ' . $dbConn->connect_error);
        }

        $dbConn->set_charset('utf8');
        return $dbConn;
    }

    private function grabReviewerCredentials(mysqli $db): array
    {
        $stmt = $db->prepare('SELECT id, pass FROM sis_login WHERE id_roll IN (2,3) ORDER BY id_roll ASC, id ASC LIMIT 1');
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row || !isset($row['id'], $row['pass'])) {
            throw new RuntimeException('No se encontró usuario con rol 2 o 3 para la prueba funcional.');
        }

        return [(string) $row['id'], (string) $row['pass']];
    }

    private function grabStudentWithoutApprovedProposal(mysqli $db): string
    {
        $sql = "
            SELECT l.id
            FROM sis_login l
            LEFT JOIN tfg_proposals tp
                ON tp.user_id = l.id
               AND tp.status = 'Aprobado'
            WHERE l.id_roll = 4
            GROUP BY l.id
            HAVING COUNT(tp.id) = 0
            ORDER BY l.id ASC
            LIMIT 1
        ";

        $res = $db->query($sql);
        $row = $res ? $res->fetch_assoc() : null;

        if (!$row || !isset($row['id'])) {
            throw new RuntimeException('No se encontró estudiante sin propuesta aprobada para la prueba funcional.');
        }

        return (string) $row['id'];
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
        $blob = '%PDF-1.4 prueba funcional cancelacion ' . $this->marker;
        $disciplines = 'HU Cancelación';
        $description = 'Propuesta funcional de prueba ' . $this->marker;
        $fileName = 'cancelacion_' . $this->marker . '.pdf';
        $mimeType = 'application/pdf';
        $fileSize = strlen($blob);

        $sql = "
            INSERT INTO tfg_proposals
            (user_id, title, disciplines, project_description, document, file_name, mime_type, file_size, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Aprobado', NOW(), NOW())
        ";

        $stmt = $db->prepare($sql);
        $stmt->bind_param('sssssssi', $studentId, $title, $disciplines, $description, $blob, $fileName, $mimeType, $fileSize);

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

    private function seedApprovedProject(mysqli $db, int $proposalId, string $projectName, string $lastAdvanceRelative): int
    {
        $comiteId = $this->grabAnyComiteId($db);
        $documento = 'Documento funcional cancelación ' . $this->marker;
        $identificador = 'UNA-TFG-' . random_int(1000, 9999) . '-' . date('Y');
        $fechaCreacion = date('Y-m-d H:i:s', strtotime('-8 months'));
        $fechaFinalizacion = date('Y-m-d H:i:s', strtotime('+4 months'));
        $fechaUltimoAvance = date('Y-m-d H:i:s', strtotime($lastAdvanceRelative));
        $estado = 'ACTIVO';
        $aprobado = 1;

        $sql = "
            INSERT INTO proyecto_aprobado
            (nombre, proposal_id, comite_id, documento, aprobado, identificador, fecha_creacion, fecha_finalizacion, estado, fecha_ultimo_avance)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $db->prepare($sql);
        $stmt->bind_param(
            'siisisssss',
            $projectName,
            $proposalId,
            $comiteId,
            $documento,
            $aprobado,
            $identificador,
            $fechaCreacion,
            $fechaFinalizacion,
            $estado,
            $fechaUltimoAvance
        );

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new RuntimeException('No se pudo sembrar proyecto_aprobado: ' . $error);
        }

        $projectId = (int) $db->insert_id;
        $stmt->close();

        $this->createdProjectIds[] = $projectId;

        return $projectId;
    }

    private function grabAnyComiteId(mysqli $db): int
    {
        $stmt = $db->prepare('SELECT Id FROM comite ORDER BY Id ASC LIMIT 1');
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row || !isset($row['Id'])) {
            throw new RuntimeException('No existe comité en base de datos para asociar el proyecto.');
        }

        return (int) $row['Id'];
    }

    private function grabProjectState(mysqli $db, int $projectId): array
    {
        $stmt = $db->prepare('SELECT estado, aprobado FROM proyecto_aprobado WHERE id_aprobado = ?');
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            throw new RuntimeException('No se encontró el proyecto sembrado: ' . $projectId);
        }

        return $row;
    }

    private function grabProposalState(mysqli $db, int $proposalId): array
    {
        $stmt = $db->prepare('SELECT status FROM tfg_proposals WHERE id = ?');
        $stmt->bind_param('i', $proposalId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            throw new RuntimeException('No se encontró la propuesta sembrada: ' . $proposalId);
        }

        return $row;
    }

    private function countCancellationAgreements(mysqli $db, int $projectId): int
    {
        $stmt = $db->prepare('SELECT COUNT(*) AS total FROM acuerdo_cancelacion WHERE proyecto_id = ?');
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return isset($row['total']) ? (int) $row['total'] : 0;
    }

    private function cleanupArtifacts(mysqli $db): void
    {
        foreach ($this->createdProjectIds as $projectId) {
            $stmt = $db->prepare('DELETE FROM proyecto_aprobado WHERE id_aprobado = ?');
            if ($stmt) {
                $stmt->bind_param('i', $projectId);
                $stmt->execute();
                $stmt->close();
            }
        }

        foreach ($this->createdProposalIds as $proposalId) {
            $stmt = $db->prepare('DELETE FROM tfg_proposals WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('i', $proposalId);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}
?>