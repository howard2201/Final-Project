<?php
session_start();
require_once '../config/Database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: AdminLogin.php');
    exit;
}

// Get resident ID from URL
$resident_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$file_type = isset($_GET['type']) ? $_GET['type'] : '';

if ($resident_id <= 0 || !in_array($file_type, ['id', 'proof'])) {
    die('Invalid request');
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $column = ($file_type === 'id') ? 'id_file' : 'proof_file';

    // Get the file from database using stored procedure
    $stmt = $conn->prepare("CALL getResidentFile(?, ?)");
    $stmt->execute([$resident_id, $file_type]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (!$result || !$result[$column]) {
        die('File not found');
    }

    // Get file content
    $fileContent = $result[$column];
    $fileName = $result['full_name'] . '_' . $file_type;
    
    // Detect file type from content
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->buffer($fileContent);
    
    // Set appropriate headers
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: inline; filename="' . $fileName . '"');
    header('Content-Length: ' . strlen($fileContent));
    
    // Output file content
    echo $fileContent;
    
} catch (PDOException $e) {
    die('Error retrieving file: ' . $e->getMessage());
}
?>

