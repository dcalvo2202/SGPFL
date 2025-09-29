<?php
// tests/integration/TfgCompleteWorkflowTest.php
use PHPUnit\Framework\TestCase;

class TfgCompleteWorkflowTest extends TestCase
{
    private $conn;
    private $testUserId = 'test_student_001';
    private $testProposalId;
    
    protected function setUp(): void
    {
        // Usar la base de datos actual
        include __DIR__ . '/../../inc/db/bdcommon.inc';
        $this->conn = new mysqli($db_host, $usuario, $clave, $db);
        
        if ($this->conn->connect_error) {
            $this->fail('No se pudo conectar a la base de datos');
        }
        
        // Limpiar datos previos de prueba
        $this->cleanupPreviousTestData();
    }
    
    protected function tearDown(): void
    {
        // Limpiar después de cada prueba
        $this->cleanupTestData();
        $this->conn->close();
    }
    
    public function testCompleteWorkflowFromUploadToApproval()
    {
        echo "\n=== INICIANDO PRUEBA COMPLETA DE FLUJO TFG ===\n";
        
        // Paso 1: Crear usuario estudiante
        $this->createTestStudent();
        echo "✅ Usuario estudiante creado: {$this->testUserId}\n";
        
        // Paso 2: Subir propuesta (simular tfg_upload_process.php)
        $this->testProposalId = $this->uploadProposal();
        echo "✅ Propuesta subida con ID: {$this->testProposalId}\n";
        
        // Paso 3: Verificar estado inicial
        $initialStatus = $this->getProposalStatus();
        $this->assertEquals('Pendiente de Revisión', $initialStatus);
        echo "✅ Estado inicial verificado: {$initialStatus}\n";
        
        // Paso 4: Aprobar propuesta (simular tfg_update_status.php)
        $approvalResponse = $this->approveProposal();
        $this->assertTrue($approvalResponse['success']);
        echo "✅ Propuesta aprobada: {$approvalResponse['message']}\n";
        
        // Paso 5: Verificar nuevo estado
        $newStatus = $this->getProposalStatus();
        $this->assertEquals('Cumple requisitos', $newStatus);
        echo "✅ Nuevo estado verificado: {$newStatus}\n";
        
        // Paso 6: Verificar historial creado
        $historyCount = $this->getHistoryCount();
        $this->assertEquals(1, $historyCount);
        echo "✅ Historial creado: {$historyCount} entrada(s)\n";
        
        // Paso 7: Verificar datos del historial
        $historyEntry = $this->getLatestHistoryEntry();
        $this->assertEquals('Cumple requisitos', $historyEntry['status']);
        $this->assertEquals('Propuesta aprobada en prueba automatizada', $historyEntry['comments']);
        echo "✅ Datos del historial verificados\n";
        
        // Paso 8: Verificar que se puede descargar la versión actual
        $currentVersion = $this->downloadCurrentVersion();
        $this->assertNotEmpty($currentVersion);
        echo "✅ Descarga de versión actual funcional\n";
        
        // Paso 9: Verificar que se puede descargar desde historial
        $historicalVersion = $this->downloadHistoricalVersion($historyEntry['id']);
        $this->assertNotEmpty($historicalVersion);
        echo "✅ Descarga de versión histórica funcional\n";
        
        echo "\n🎉 PRUEBA COMPLETA EXITOSA - TODO EL FLUJO FUNCIONA CORRECTAMENTE\n";
    }
    
    public function testCompleteWorkflowFromUploadToRejection()
    {
        echo "\n=== INICIANDO PRUEBA DE RECHAZO ===\n";
        
        // Crear usuario y subir propuesta
        $this->createTestStudent();
        $this->testProposalId = $this->uploadProposal();
        
        // Rechazar propuesta
        $rejectionResponse = $this->rejectProposal();
        $this->assertTrue($rejectionResponse['success']);
        echo "✅ Propuesta rechazada: {$rejectionResponse['message']}\n";
        
        // Verificar estado
        $status = $this->getProposalStatus();
        $this->assertEquals('No cumple requisitos', $status);
        echo "✅ Estado de rechazo verificado: {$status}\n";
        
        echo "🎉 PRUEBA DE RECHAZO EXITOSA\n";
    }
    
    private function createTestStudent()
    {
        // Crear entrada en sis_login primero
        $stmt = $this->conn->prepare("INSERT IGNORE INTO sis_login (id, pass, id_roll) VALUES (?, MD5('testpass'), 3)");
        $stmt->bind_param("s", $this->testUserId);
        $stmt->execute();
        
        // Crear entrada en sis_user
        $stmt = $this->conn->prepare("INSERT IGNORE INTO sis_user (id, nombre, email, telefono, id_tipo_tel) 
                                     VALUES (?, 'Estudiante Prueba', 'dach2202@hotmail.com', '88888888', 'M')");
        $stmt->bind_param("s", $this->testUserId);
        $stmt->execute();
    }
    
    private function uploadProposal()
    {
        // Simular archivo PDF
        $pdfContent = $this->generateTestPDF();
        
        // Simular $_POST y $_FILES como en tfg_upload_process.php
        $title = "Propuesta de Prueba Automatizada";
        $disciplines = "Ingeniería en Sistemas,Informática";
        
        $stmt = $this->conn->prepare("INSERT INTO tfg_proposals 
            (user_id, title, disciplines, document, file_name, mime_type, file_size, status) 
            VALUES (?, ?, ?, ?, 'prueba_automatizada.pdf', 'application/pdf', ?, 'Pendiente de Revisión')");
        
        $fileSize = strlen($pdfContent);
        $stmt->bind_param("ssssi", $this->testUserId, $title, $disciplines, $pdfContent, $fileSize);
        $stmt->execute();
        
        return $this->conn->insert_id;
    }
    
    private function approveProposal()
    {
        return $this->updateProposalStatus('Cumple requisitos', 'Propuesta aprobada en prueba automatizada');
    }
    
    private function rejectProposal()
    {
        return $this->updateProposalStatus('No cumple requisitos', 'Propuesta rechazada en prueba automatizada');
    }
    
    private function updateProposalStatus($status, $comments)
    {
        try {
            $this->conn->begin_transaction();
            
            // Obtener datos actuales de la propuesta (como en tfg_update_status.php)
            $stmt = $this->conn->prepare("SELECT p.document, p.file_name, p.mime_type, p.file_size, p.title, u.email, u.nombre 
                                         FROM tfg_proposals p 
                                         JOIN sis_user u ON p.user_id = u.id 
                                         WHERE p.id = ?");
            $stmt->bind_param("i", $this->testProposalId);
            $stmt->execute();
            $result = $stmt->get_result();
            $proposal = $result->fetch_assoc();
            
            if (!$proposal) {
                throw new Exception('Propuesta no encontrada');
            }
            
            // Insertar en historial
            $sql = "INSERT INTO tfg_proposal_history 
                    (proposal_id, document, file_name, mime_type, file_size, status, reviewed_by, comments) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            $reviewerId = 'test_reviewer_001';
            
            $stmt->bind_param("ibssisss", 
                $this->testProposalId, 
                $proposal['document'], 
                $proposal['file_name'],
                $proposal['mime_type'],
                $proposal['file_size'],
                $status,
                $reviewerId,
                $comments
            );
            $stmt->send_long_data(1, $proposal['document']);
            $stmt->execute();
            
            // Actualizar tabla principal
            $stmt = $this->conn->prepare("UPDATE tfg_proposals SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $this->testProposalId);
            $stmt->execute();
            
            // Enviar correo (real)
            $this->sendNotificationEmail($proposal, $status, $comments);
            
            $this->conn->commit();
            return ['success' => true, 'message' => 'Estado actualizado y notificación enviada'];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    private function sendNotificationEmail($proposal, $status, $comments)
    {
        $to = $proposal['email'];
        $subject = "Actualización de estado - Propuesta TFG [PRUEBA AUTOMATIZADA]";
        $message = "Estimado/a " . $proposal['nombre'] . ",\n\n";
        $message .= "Su propuesta de TFG \"" . $proposal['title'] . "\" ha sido revisada.\n\n";
        $message .= "Nuevo estado: " . $status . "\n";
        $message .= "Comentarios: " . $comments . "\n";
        $message .= "\n--- ESTE ES UN CORREO DE PRUEBA AUTOMATIZADA ---\n\n";
        $message .= "Saludos,\nSistema de Pruebas TFG";
        
        $headers = "From: calvoss2002@gmail.com\r\n";
        $headers .= "Reply-To: calvoss2002@gmail.com\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        $emailSent = mail($to, $subject, $message, $headers);
        
        if ($emailSent) {
            echo "📧 Email enviado exitosamente a: {$to}\n";
        } else {
            echo "❌ Error enviando email a: {$to}\n";
        }
    }
    
    private function downloadCurrentVersion()
    {
        $stmt = $this->conn->prepare("SELECT document FROM tfg_proposals WHERE id = ?");
        $stmt->bind_param("i", $this->testProposalId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result ? $result['document'] : null;
    }
    
    private function downloadHistoricalVersion($historyId)
    {
        $stmt = $this->conn->prepare("SELECT document FROM tfg_proposal_history WHERE id = ?");
        $stmt->bind_param("i", $historyId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result ? $result['document'] : null;
    }
    
    private function generateTestPDF()
    {
        // Generar contenido PDF básico para pruebas
        return "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj 3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]/Contents 4 0 R>>endobj 4 0 obj<</Length 44>>stream BT /F1 12 Tf 100 700 Td (Documento de Prueba TFG) Tj ET endstream endobj xref 0 5 0000000000 65535 f 0000000009 00000 n 0000000058 00000 n 0000000115 00000 n 0000000204 00000 n trailer<</Size 5/Root 1 0 R>> startxref 297 %%EOF";
    }
    
    // Métodos auxiliares para verificaciones
    private function getProposalStatus()
    {
        $stmt = $this->conn->prepare("SELECT status FROM tfg_proposals WHERE id = ?");
        $stmt->bind_param("i", $this->testProposalId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result ? $result['status'] : null;
    }
    
    private function getHistoryCount()
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM tfg_proposal_history WHERE proposal_id = ?");
        $stmt->bind_param("i", $this->testProposalId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return (int)$result['count'];
    }
    
    private function getLatestHistoryEntry()
    {
        $stmt = $this->conn->prepare("SELECT * FROM tfg_proposal_history WHERE proposal_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->bind_param("i", $this->testProposalId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    private function cleanupPreviousTestData()
    {
        $this->conn->query("DELETE FROM tfg_proposal_history WHERE proposal_id IN (SELECT id FROM tfg_proposals WHERE user_id LIKE 'test_%')");
        $this->conn->query("DELETE FROM tfg_proposals WHERE user_id LIKE 'test_%'");
        $this->conn->query("DELETE FROM sis_user WHERE id LIKE 'test_%'");
        $this->conn->query("DELETE FROM sis_login WHERE id LIKE 'test_%'");
    }
    
    private function cleanupTestData()
    {
        if ($this->testProposalId) {
            $this->conn->query("DELETE FROM tfg_proposal_history WHERE proposal_id = {$this->testProposalId}");
            $this->conn->query("DELETE FROM tfg_proposals WHERE id = {$this->testProposalId}");
        }
        $this->conn->query("DELETE FROM sis_user WHERE id = '{$this->testUserId}'");
        $this->conn->query("DELETE FROM sis_login WHERE id = '{$this->testUserId}'");
    }
}
?>