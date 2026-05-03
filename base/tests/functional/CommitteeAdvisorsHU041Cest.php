<?php

declare(strict_types=1);

namespace Tests\functional;

use mysqli;
use RuntimeException;
use Tests\Support\FunctionalTester;

final class CommitteeAdvisorsHU041Cest
{
    private const REVIEW_PANEL_URL = '/panel_revisar_asesor_externo.php';
    private const COMMITTEE_PANEL_URL = '/panel_comites_asesores.php';
    private const DECISION_ENDPOINT = '/procesar_decision_asesor.php';

    private const MANAGER_USER_ID = '111710169';
    private const MANAGER_USER_PASS = 'secret123';

    private const TEST_PREFIX = 'HU041FT';

    // Estudiante semilla con proyecto/grupo en base.sql.
    private const PRIMARY_STUDENT_ID = '504410118';

    // Otros estudiantes semilla para aislar pruebas de rechazo/listado.
    private const SECONDARY_STUDENT_ID = '503550224';
    private const THIRD_STUDENT_ID = '402290345';

    public function _before(FunctionalTester $I): void
    {
        $db = $this->connectDb();
        $this->cleanupTestData($db);
        $db->close();
    }

    public function _after(FunctionalTester $I): void
    {
        $db = $this->connectDb();
        $this->cleanupTestData($db);
        $db->close();
    }

    /**
     * CA base: el panel de revisión muestra solicitudes pendientes
     * con sus documentos, estudiante vinculado y acciones.
     */
    public function panelRevisionMuestraSolicitudPendiente(FunctionalTester $I): void
    {
        $db = $this->connectDb();

        $requestId = $this->insertAdvisorRequest(
            $db,
            self::TEST_PREFIX . 'REV1',
            'HU041 FUNCIONAL REVISION UNO',
            'Asesor 1',
            self::PRIMARY_STUDENT_ID,
            'En Revision'
        );

        $db->close();

        $this->loginAs($I, self::MANAGER_USER_ID, self::MANAGER_USER_PASS);

        $I->amOnPage(self::REVIEW_PANEL_URL . '?status=Pendiente');

        $I->see('Revisión de Solicitudes - Comité Asesor');
        $I->see('HU041 FUNCIONAL REVISION UNO');
        $I->see(self::TEST_PREFIX . 'REV1');
        $I->see('Pendiente');
        $I->see('Estudiante a asesorar');
        $I->see('Currículum');
        $I->see('Cédula');
        $I->see('Carta');

        $I->seeElement('.btn-aprobar', ['data-id' => (string)$requestId]);
        $I->seeElement('.btn-rechazar', ['data-id' => (string)$requestId]);
    }

    /**
     * Flujo de aprobación de solicitud individual:
     * la solicitud pasa de En Revision a Aprobado y se crea/reutiliza el usuario asesor.
     */
    public function apruebaSolicitudIndividualDeAsesor(FunctionalTester $I): void
    {
        $db = $this->connectDb();

        $requestId = $this->insertAdvisorRequest(
            $db,
            self::TEST_PREFIX . 'APR1',
            'HU041 FUNCIONAL APROBADO UNO',
            'Tutor',
            self::PRIMARY_STUDENT_ID,
            'En Revision',
            'Tutor'
        );

        $db->close();

        $this->loginAs($I, self::MANAGER_USER_ID, self::MANAGER_USER_PASS);

        $I->sendAjaxPostRequest(self::DECISION_ENDPOINT, [
            'id' => $requestId,
            'decision' => 'Aprobado',
            'comentarios' => 'Aprobación funcional HU-041.',
        ]);

        $I->see('"success":true');
        $I->see('Solicitud aprobada');

        $db = $this->connectDb();
        $I->assertSame('Aprobado', $this->getRequestStatusById($db, $requestId));
        $I->assertTrue(
            $this->userExists($db, self::TEST_PREFIX . 'APR1'),
            'El usuario asesor aprobado no fue creado/reutilizado en sis_user.'
        );
        $db->close();
    }

    /**
     * Flujo de rechazo de solicitud individual:
     * valida que se actualice el estado y el contador de rechazos.
     */
    public function rechazaSolicitudIndividualDeAsesorConMotivo(FunctionalTester $I): void
    {
        $db = $this->connectDb();

        $requestId = $this->insertAdvisorRequest(
            $db,
            self::TEST_PREFIX . 'REJ1',
            'HU041 FUNCIONAL RECHAZADO UNO',
            'Asesor 2',
            self::PRIMARY_STUDENT_ID,
            'En Revision'
        );

        $db->close();

        $this->loginAs($I, self::MANAGER_USER_ID, self::MANAGER_USER_PASS);

        $I->sendAjaxPostRequest(self::DECISION_ENDPOINT, [
            'id' => $requestId,
            'decision' => 'Rechazado',
            'comentarios' => 'Motivo funcional suficiente para rechazar la solicitud.',
        ]);

        $I->see('"success":true');
        $I->see('Solicitud rechazada');

        $db = $this->connectDb();
        $I->assertSame('Rechazado', $this->getRequestStatusById($db, $requestId));
        $I->assertSame(1, $this->getRequestRejectionCountById($db, $requestId));
        $db->close();
    }

    /**
     * El panel de comités debe mostrar como inválido un comité incompleto.
     */
    public function panelComitesMuestraComiteIncompletoComoInvalido(FunctionalTester $I): void
    {
        $db = $this->connectDb();

        $this->ensureTestUser($db, self::TEST_PREFIX . 'INC_T', 'HU041 FUNCIONAL INCOMPLETO TUTOR');
        $this->ensureTestUser($db, self::TEST_PREFIX . 'INC_A1', 'HU041 FUNCIONAL INCOMPLETO ASESOR UNO');

        $this->insertAdvisorRequest(
            $db,
            self::TEST_PREFIX . 'INC_T',
            'HU041 FUNCIONAL INCOMPLETO TUTOR',
            'Tutor',
            self::SECONDARY_STUDENT_ID,
            'Aprobado',
            'Tutor'
        );

        $this->insertAdvisorRequest(
            $db,
            self::TEST_PREFIX . 'INC_A1',
            'HU041 FUNCIONAL INCOMPLETO ASESOR UNO',
            'Asesor 1',
            self::SECONDARY_STUDENT_ID,
            'Aprobado'
        );

        $db->close();

        $this->loginAs($I, self::MANAGER_USER_ID, self::MANAGER_USER_PASS);

        $I->amOnPage(self::COMMITTEE_PANEL_URL);

        $I->see('Solicitudes de comite pendientes');
        $I->see('HU041 FUNCIONAL INCOMPLETO TUTOR');
        $I->see('HU041 FUNCIONAL INCOMPLETO ASESOR UNO');
        $I->see('Invalido');
        $I->see('Falta completar el comité: debe tener un Tutor, un Asesor 1 y un Asesor 2.');
    }

    /**
     * El panel permite rechazar un grupo de solicitudes de comité.
     */
    public function rechazaSolicitudDeComiteDesdePanel(FunctionalTester $I): void
    {
        $db = $this->connectDb();

        $this->ensureTestUser($db, self::TEST_PREFIX . 'PC_REJ_T', 'HU041 PANEL RECHAZO TUTOR');
        $this->ensureTestUser($db, self::TEST_PREFIX . 'PC_REJ_A1', 'HU041 PANEL RECHAZO ASESOR UNO');

        $this->insertAdvisorRequest(
            $db,
            self::TEST_PREFIX . 'PC_REJ_T',
            'HU041 PANEL RECHAZO TUTOR',
            'Tutor',
            self::THIRD_STUDENT_ID,
            'Aprobado',
            'Tutor'
        );

        $this->insertAdvisorRequest(
            $db,
            self::TEST_PREFIX . 'PC_REJ_A1',
            'HU041 PANEL RECHAZO ASESOR UNO',
            'Asesor 1',
            self::THIRD_STUDENT_ID,
            'Aprobado'
        );

        $beforeRejected = $this->countTestRequestsByStudentAndStatus($db, self::THIRD_STUDENT_ID, 'Rechazado');
        $db->close();

        $this->loginAs($I, self::MANAGER_USER_ID, self::MANAGER_USER_PASS);

        $I->amOnPage(self::COMMITTEE_PANEL_URL);

        $I->submitForm('#formRechazoComite', [
            'action' => 'reject_committee_request',
            'request_student_id' => self::THIRD_STUDENT_ID,
            'rejection_reason' => 'Motivo funcional suficiente para rechazar el comité.',
        ]);

        $I->see('Solicitud de comite rechazada correctamente.');

        $db = $this->connectDb();
        $afterRejected = $this->countTestRequestsByStudentAndStatus($db, self::THIRD_STUDENT_ID, 'Rechazado');

        $I->assertSame(
            $beforeRejected + 2,
            $afterRejected,
            'No se rechazaron las solicitudes esperadas del comité.'
        );

        $db->close();
    }

    /**
     * Flujo principal de HU-041:
     * un comité completo se aprueba desde el panel, se crea en comite
     * y las solicitudes quedan vinculadas al nuevo comité.
     */
    public function apruebaComiteCompletoDesdePanel(FunctionalTester $I): void
    {
        $db = $this->connectDb();

        $tutorId = self::TEST_PREFIX . 'OK_T';
        $asesor1Id = self::TEST_PREFIX . 'OK_A1';
        $asesor2Id = self::TEST_PREFIX . 'OK_A2';

        $this->ensureTestUser($db, $tutorId, 'HU041 COMITE OK TUTOR');
        $this->ensureTestUser($db, $asesor1Id, 'HU041 COMITE OK ASESOR UNO');
        $this->ensureTestUser($db, $asesor2Id, 'HU041 COMITE OK ASESOR DOS');

        $this->insertAdvisorRequest(
            $db,
            $tutorId,
            'HU041 COMITE OK TUTOR',
            'Tutor',
            self::PRIMARY_STUDENT_ID,
            'Aprobado',
            'Tutor'
        );

        $this->insertAdvisorRequest(
            $db,
            $asesor1Id,
            'HU041 COMITE OK ASESOR UNO',
            'Asesor 1',
            self::PRIMARY_STUDENT_ID,
            'Aprobado'
        );

        $this->insertAdvisorRequest(
            $db,
            $asesor2Id,
            'HU041 COMITE OK ASESOR DOS',
            'Asesor 2',
            self::PRIMARY_STUDENT_ID,
            'Aprobado'
        );

        $beforeComites = $this->countComitesWithAnyTestMember($db);
        $db->close();

        $this->loginAs($I, self::MANAGER_USER_ID, self::MANAGER_USER_PASS);

        $I->amOnPage(self::COMMITTEE_PANEL_URL);

        $I->see('HU041 COMITE OK TUTOR');
        $I->see('HU041 COMITE OK ASESOR UNO');
        $I->see('HU041 COMITE OK ASESOR DOS');
        $I->see('Listo para aprobar');

        $I->submitForm(
            '//input[@name="request_student_id" and @value="' . self::PRIMARY_STUDENT_ID . '"]/ancestor::form',
            [
                'action' => 'approve_committee_request',
                'request_student_id' => self::PRIMARY_STUDENT_ID,
            ]
        );

        $I->see('Comite aprobado y creado desde solicitudes.');

        $db = $this->connectDb();

        $afterComites = $this->countComitesWithAnyTestMember($db);
        $I->assertSame(
            $beforeComites + 1,
            $afterComites,
            'No se creó el comité esperado en la tabla comite.'
        );

        $I->assertSame(
            3,
            $this->countLinkedApprovedRequestsForStudent($db, self::PRIMARY_STUDENT_ID),
            'Las tres solicitudes aprobadas no quedaron vinculadas al comité.'
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

        $db = new mysqli($db_host, $usuario, $clave, $db);
        if ($db->connect_error) {
            throw new RuntimeException('Error conectando a BD para pruebas funcionales: ' . $db->connect_error);
        }

        $db->set_charset('utf8');
        return $db;
    }

    private function cleanupTestData(mysqli $db): void
    {
        $prefixLike = self::TEST_PREFIX . '%';

        $stmt = $db->prepare(
            'DELETE FROM external_advisor_linked_students
             WHERE internal_advisor_id LIKE ?
                OR advisor_request_id IN (
                    SELECT id
                    FROM external_advisor_profile_requests
                    WHERE applicant_id LIKE ?
                )'
        );
        $stmt->bind_param('ss', $prefixLike, $prefixLike);
        $stmt->execute();
        $stmt->close();

        $stmt = $db->prepare(
            'UPDATE external_advisor_profile_requests
             SET linked_comite_id = NULL
             WHERE applicant_id LIKE ?'
        );
        $stmt->bind_param('s', $prefixLike);
        $stmt->execute();
        $stmt->close();

        $stmt = $db->prepare(
            'DELETE FROM comite
             WHERE tutor LIKE ?
                OR asesor_1 LIKE ?
                OR asesor_2 LIKE ?'
        );
        $stmt->bind_param('sss', $prefixLike, $prefixLike, $prefixLike);
        $stmt->execute();
        $stmt->close();

        $stmt = $db->prepare(
            'DELETE FROM external_advisor_profile_requests
             WHERE applicant_id LIKE ?'
        );
        $stmt->bind_param('s', $prefixLike);
        $stmt->execute();
        $stmt->close();

        $stmt = $db->prepare(
            'DELETE FROM user_alerts
             WHERE user_id LIKE ?'
        );
        $stmt->bind_param('s', $prefixLike);
        $stmt->execute();
        $stmt->close();

        $stmt = $db->prepare(
            'DELETE FROM sis_user
             WHERE id LIKE ?'
        );
        $stmt->bind_param('s', $prefixLike);
        $stmt->execute();
        $stmt->close();

        $stmt = $db->prepare(
            'DELETE FROM sis_login
             WHERE id LIKE ?'
        );
        $stmt->bind_param('s', $prefixLike);
        $stmt->execute();
        $stmt->close();
    }

    private function ensureTestUser(mysqli $db, string $id, string $name, int $roleId = 5): void
    {
        $password = md5('secret123');

        $stmtLogin = $db->prepare(
            'INSERT INTO sis_login (id, pass, id_roll)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE pass = VALUES(pass), id_roll = VALUES(id_roll)'
        );
        $stmtLogin->bind_param('ssi', $id, $password, $roleId);
        $stmtLogin->execute();
        $stmtLogin->close();

        $email = strtolower($id) . '@example.com';
        $phone = '88888888';
        $phoneType = 'M';

        $stmtUser = $db->prepare(
            'INSERT INTO sis_user (id, nombre, email, telefono, id_tipo_tel)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                nombre = VALUES(nombre),
                email = VALUES(email),
                telefono = VALUES(telefono),
                id_tipo_tel = VALUES(id_tipo_tel)'
        );
        $stmtUser->bind_param('sssss', $id, $name, $email, $phone, $phoneType);
        $stmtUser->execute();
        $stmtUser->close();
    }

    private function insertAdvisorRequest(
        mysqli $db,
        string $applicantId,
        string $fullName,
        string $committeeRole,
        string $linkedStudentId,
        string $status,
        string $postulationType = 'Asesor Externo'
    ): int {
        $safeApplicantId = $db->real_escape_string($applicantId);
        $safeFullName = $db->real_escape_string($fullName);
        $safeEmail = $db->real_escape_string(strtolower($applicantId) . '@example.com');
        $safeCommitteeRole = $db->real_escape_string($committeeRole);
        $safeStudentId = $db->real_escape_string($linkedStudentId);
        $safeStatus = $db->real_escape_string($status);
        $safePostulationType = $db->real_escape_string($postulationType);

        $committeeSubrole = $committeeRole === 'Tutor'
            ? 'NULL'
            : "'" . $db->real_escape_string($committeeRole) . "'";

        $pdfHex = bin2hex($this->minimalPdfContent());
        $idCopyHex = bin2hex("\x89PNG\r\n\x1a\nHU041 functional test png");

        $sql = "
            INSERT INTO external_advisor_profile_requests (
                applicant_id,
                full_name,
                email,
                telefono,
                id_tipo_tel,
                institution,
                specialization,
                postulation_type,
                committee_subrole,
                committee_role,
                cv_document,
                cv_file_name,
                cv_mime_type,
                cv_file_size,
                id_copy_document,
                id_copy_file_name,
                id_copy_mime_type,
                id_copy_file_size,
                cover_letter_document,
                cover_letter_file_name,
                cover_letter_mime_type,
                cover_letter_file_size,
                status,
                linked_student_id,
                created_at
            ) VALUES (
                '{$safeApplicantId}',
                '{$safeFullName}',
                '{$safeEmail}',
                '88888888',
                'M',
                'Institucion funcional HU041',
                'Especializacion funcional HU041',
                '{$safePostulationType}',
                {$committeeSubrole},
                '{$safeCommitteeRole}',
                UNHEX('{$pdfHex}'),
                'cv_hu041.pdf',
                'application/pdf',
                LENGTH(UNHEX('{$pdfHex}')),
                UNHEX('{$idCopyHex}'),
                'cedula_hu041.png',
                'image/png',
                LENGTH(UNHEX('{$idCopyHex}')),
                UNHEX('{$pdfHex}'),
                'carta_hu041.pdf',
                'application/pdf',
                LENGTH(UNHEX('{$pdfHex}')),
                '{$safeStatus}',
                '{$safeStudentId}',
                NOW()
            )
        ";

        if (!$db->query($sql)) {
            throw new RuntimeException('No se pudo insertar solicitud HU-041 funcional: ' . $db->error);
        }

        return (int)$db->insert_id;
    }

    private function minimalPdfContent(): string
    {
        return "%PDF-1.4\n"
            . "1 0 obj\n"
            . "<< /Type /Catalog >>\n"
            . "endobj\n"
            . "%%EOF";
    }

    private function getRequestStatusById(mysqli $db, int $requestId): ?string
    {
        $stmt = $db->prepare(
            'SELECT status
             FROM external_advisor_profile_requests
             WHERE id = ?'
        );
        $stmt->bind_param('i', $requestId);
        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return isset($row['status']) ? (string)$row['status'] : null;
    }

    private function getRequestRejectionCountById(mysqli $db, int $requestId): int
    {
        $stmt = $db->prepare(
            'SELECT rejection_count
             FROM external_advisor_profile_requests
             WHERE id = ?'
        );
        $stmt->bind_param('i', $requestId);
        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return isset($row['rejection_count']) ? (int)$row['rejection_count'] : 0;
    }

    private function userExists(mysqli $db, string $userId): bool
    {
        $stmt = $db->prepare(
            'SELECT COUNT(*) AS total
             FROM sis_user
             WHERE id = ?'
        );
        $stmt->bind_param('s', $userId);
        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return isset($row['total']) && (int)$row['total'] > 0;
    }

    private function countTestRequestsByStudentAndStatus(mysqli $db, string $studentId, string $status): int
    {
        $prefixLike = self::TEST_PREFIX . '%';

        $stmt = $db->prepare(
            'SELECT COUNT(*) AS total
             FROM external_advisor_profile_requests
             WHERE applicant_id LIKE ?
               AND linked_student_id = ?
               AND status = ?'
        );
        $stmt->bind_param('sss', $prefixLike, $studentId, $status);
        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return isset($row['total']) ? (int)$row['total'] : 0;
    }

    private function countComitesWithAnyTestMember(mysqli $db): int
    {
        $prefixLike = self::TEST_PREFIX . '%';

        $stmt = $db->prepare(
            'SELECT COUNT(*) AS total
             FROM comite
             WHERE tutor LIKE ?
                OR asesor_1 LIKE ?
                OR asesor_2 LIKE ?'
        );
        $stmt->bind_param('sss', $prefixLike, $prefixLike, $prefixLike);
        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return isset($row['total']) ? (int)$row['total'] : 0;
    }

    private function countLinkedApprovedRequestsForStudent(mysqli $db, string $studentId): int
    {
        $prefixLike = self::TEST_PREFIX . '%';

        $stmt = $db->prepare(
            'SELECT COUNT(*) AS total
             FROM external_advisor_profile_requests
             WHERE applicant_id LIKE ?
               AND linked_student_id = ?
               AND status = "Aprobado"
               AND linked_comite_id IS NOT NULL'
        );
        $stmt->bind_param('ss', $prefixLike, $studentId);
        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return isset($row['total']) ? (int)$row['total'] : 0;
    }
}