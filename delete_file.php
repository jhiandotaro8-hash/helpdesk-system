<?php
// php/delete_file.php
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$data = json_decode(file_get_contents('php://input'), true);
$fileId = $data['file_id'] ?? null;

if (!$fileId) {
    die(json_encode(['success' => false, 'message' => 'Invalid ticket ID']));
}

$fileStmt = $pdo->prepare("SELECT file_path, user_id FROM files WHERE id = ?");
$fileStmt->execute([$fileId]);
$file = $fileStmt->fetch();

if (!$file) {
    die(json_encode(['success' => false, 'message' => 'Ticket not found']));
}

if ($_SESSION['role'] == 'user' && $file['user_id'] != $_SESSION['user_id']) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

if (file_exists($file['file_path'])) {
    unlink($file['file_path']);
}

$stmt = $pdo->prepare("DELETE FROM files WHERE id = ?");
if ($stmt->execute([$fileId])) {
    echo json_encode(['success' => true, 'message' => 'Ticket deleted']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error deleting ticket']);
}
?>