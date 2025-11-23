<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . "/../Messages.php";

// Check authentication
$userId = null;
$userType = null;

if (isset($_SESSION['admin_id'])) {
    $userId = $_SESSION['admin_id'];
    $userType = 'admin';
} elseif (isset($_SESSION['resident_id'])) {
    $userId = $_SESSION['resident_id'];
    $userType = 'resident';
} elseif (isset($_SESSION['pending_resident_id'])) {
    $userId = $_SESSION['pending_resident_id'];
    $userType = 'resident';
} elseif (isset($_SESSION['rejected_resident_id'])) {
    $userId = $_SESSION['rejected_resident_id'];
    $userType = 'resident';
}

if (!$userId) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

if (!isset($_FILES['file'])) {
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$messages = new Messages();
$filePath = $messages->uploadFile($_FILES['file']);

if ($filePath) {
    // Determine file type
    $mimeType = mime_content_type(__DIR__ . '/../' . $filePath);
    $fileType = strpos($mimeType, 'image/') === 0 ? 'image' : 'video';
    
    echo json_encode([
        'success' => true,
        'file_path' => $filePath,
        'file_type' => $fileType
    ]);
} else {
    echo json_encode(['error' => 'File upload failed']);
}

