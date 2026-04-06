<?php
// php/approve_file.php
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$data = json_decode(file_get_contents('php://input'), true);
$fileId = $data['file_id'] ?? null;
$status = $data['status'] ?? null;
$reason = $data['reason'] ?? null;

if (!$fileId || !$status) {
    die(json_encode(['success' => false, 'message' => 'Invalid parameters']));
}

$fileStmt = $pdo->prepare("SELECT user_id, file_name FROM files WHERE id = ?");
$fileStmt->execute([$fileId]);
$file = $fileStmt->fetch();

if (!$file) {
    die(json_encode(['success' => false, 'message' => 'Ticket not found']));
}

$stmt = $pdo->prepare("
    UPDATE files 
    SET status = ?, approved_at = NOW(), approved_by = ?, rejection_reason = ?
    WHERE id = ?
");

if ($stmt->execute([$status, $_SESSION['user_id'], $reason, $fileId])) {
    $message = ($status == 'approved') 
        ? 'Your ticket "' . $file['file_name'] . '" has been resolved! ✅' 
        : 'Your ticket "' . $file['file_name'] . '" has been closed. Reason: ' . $reason . ' ❌';
    
    $notifStmt = $pdo->prepare("
        INSERT INTO notifications (user_id, message, type) 
        VALUES (?, ?, ?)
    ");
    $notifStmt->execute([$file['user_id'], $message, 'approval']);

    $logStmt = $pdo->prepare("
        INSERT INTO activity_log (user_id, action, details) 
        VALUES (?, ?, ?)
    ");
    $logStmt->execute([$_SESSION['user_id'], 'ticket_' . $status, 'Ticket ID: ' . $fileId]);

    echo json_encode(['success' => true, 'message' => 'Ticket ' . $status]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating ticket']);
}
?>