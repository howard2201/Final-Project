<?php
session_start();
require_once '../config/Database.php';

if (!isset($_SESSION['admin_id'])) {
  http_response_code(403);
  exit('Unauthorized');
}

$requestId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$fileType = isset($_GET['type']) ? $_GET['type'] : '';

$validTypes = ['id', 'residency'];
if ($requestId <= 0 || !in_array($fileType, $validTypes, true)) {
  http_response_code(400);
  exit('Invalid parameters.');
}

try {
  $db = Database::getInstance()->getConnection();
  $column = $fileType === 'id' ? 'id_file' : 'residency_file';

  $stmt = $db->prepare("
    SELECT r.{$column} AS file_name, res.full_name
    FROM requests r
    JOIN residents res ON res.id = r.resident_id
    WHERE r.id = ?
  ");
  $stmt->execute([$requestId]);
  $fileData = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$fileData || empty($fileData['file_name'])) {
    http_response_code(404);
    exit('File not found.');
  }

  $uploadDir = realpath(__DIR__ . '/../uploads');
  $requestedPath = realpath($uploadDir . DIRECTORY_SEPARATOR . $fileData['file_name']);

  if (!$requestedPath || strpos($requestedPath, $uploadDir) !== 0 || !file_exists($requestedPath)) {
    http_response_code(404);
    exit('File is missing on the server.');
  }

  $mimeType = mime_content_type($requestedPath) ?: 'application/octet-stream';
  $extension = pathinfo($requestedPath, PATHINFO_EXTENSION);
  $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $fileData['full_name']);
  $downloadName = "{$safeName}_{$fileType}." . ($extension ?: 'dat');

  header('Content-Type: ' . $mimeType);
  header('Content-Disposition: inline; filename="' . $downloadName . '"');
  header('Content-Length: ' . filesize($requestedPath));

  readfile($requestedPath);
  exit;
} catch (PDOException $e) {
  error_log('ViewRequestFile error: ' . $e->getMessage());
  http_response_code(500);
  exit('Unable to load file.');
}

