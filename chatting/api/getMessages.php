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

$messages = new Messages();
$otherId = isset($_GET['other_id']) ? intval($_GET['other_id']) : null;
$otherType = isset($_GET['other_type']) ? $_GET['other_type'] : null;

if (!$otherId || !$otherType) {
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

// Get messages
$chatMessages = $messages->getMessages($userId, $userType, $otherId, $otherType);

// Get conversations for unread counts
$conversations = $messages->getConversations($userId, $userType);

echo json_encode([
    'success' => true,
    'messages' => $chatMessages,
    'conversations' => $conversations
]);

