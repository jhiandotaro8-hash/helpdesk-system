<?php
// php/upload.php
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$response = ['success' => false, 'message' => 'Unknown error'];
$uploadDir = '../uploads/';

if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (isset($_FILES['files'])) {
    $uploadedCount = 0;
    
    foreach ($_FILES['files']['name'] as $key => $fileName) {
        if ($_FILES['files']['error'][$key] === UPLOAD_ERR_OK) {
            $fileSize = $_FILES['files']['size'][$key];
            $tmpName = $_FILES['files']['tmp_name'][$key];
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            
            $allowedExt = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'txt', 'zip'];
            if (!in_array(strtolower($fileExtension), $allowedExt)) {
                continue;
            }

            if ($fileSize > 10 * 1024 * 1024) {
                continue;
            }

            $newFileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $fileName);
            $filePath = $uploadDir . $newFileName;

            if (move_uploaded_file($tmpName, $filePath)) {
                $stmt = $pdo->prepare("
                    INSERT INTO files (user_id, file_name, file_path, file_size, status) 
                    VALUES (?, ?, ?, ?, 'pending')
                ");
                
                if ($stmt->execute([$_SESSION['user_id'], $fileName, $filePath, $fileSize])) {
                    $notifStmt = $pdo->prepare("
                        INSERT INTO notifications (user_id, message, type) 
                        VALUES (?, ?, 'upload')
                    ");
                    $notifStmt->execute([$_SESSION['user_id'], "Your ticket '$fileName' has been submitted and is waiting for support response."]);
                    
                    $uploadedCount++;
                }
            }
        }
    }
    
    if ($uploadedCount > 0) {
        $response = ['success' => true, 'message' => $uploadedCount . ' ticket(s) submitted successfully'];
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>