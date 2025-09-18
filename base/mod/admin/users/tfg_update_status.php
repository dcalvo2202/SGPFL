<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
include __DIR__ . '/../../../inc/db/bdcommon.inc';

header('Content-Type: application/json');

if (!isset($_POST['id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$conn = new mysqli($db_host, $usuario, $clave, $db);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

try {
    $conn->begin_transaction();

    // 1. Get current proposal data
    $stmt = $conn->prepare("SELECT document, file_name, mime_type, file_size FROM tfg_proposals WHERE id = ?");
    $stmt->bind_param("i", $_POST['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $proposal = $result->fetch_assoc();

    // 2. Insert into history
    $sql = "INSERT INTO tfg_proposal_history 
            (proposal_id, document, file_name, mime_type, file_size, status, reviewed_by, comments) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $reviewer_id = $_SESSION['id'] ?? '112170040'; // Update with actual reviewer ID
    
    $stmt->bind_param("ibssisss", 
        $_POST['id'], 
        $proposal['document'], 
        $proposal['file_name'],
        $proposal['mime_type'],
        $proposal['file_size'],
        $_POST['status'],
        $reviewer_id,
        $_POST['comments']
    );
    $stmt->send_long_data(1, $proposal['document']);
    $stmt->execute();

    // 3. Update main table
    $stmt = $conn->prepare("UPDATE tfg_proposals SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $_POST['status'], $_POST['id']);
    $stmt->execute();

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    $conn->close();
}