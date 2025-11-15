<?php
/**
 * Chat API Endpoint
 * Handles sending, editing, deleting messages and retrieving chat history
 * Available to both residents and admins
 */

session_start();
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

try {
    // Verify authentication
    $user_id = null;
    $user_type = null;
    $recipient_id = null;

    if (isset($_SESSION['resident_id'])) {
        $user_id = $_SESSION['resident_id'];
        $user_type = 'resident';
    } elseif (isset($_SESSION['admin_id'])) {
        $user_id = $_SESSION['admin_id'];
        $user_type = 'admin';
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    require_once "../config/Database.php";
    $conn = Database::getInstance()->getConnection();

    $action = isset($_GET['action']) ? $_GET['action'] : '';

    // Get current user info
    if ($action === 'user_info' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'user_name' => ($user_type === 'resident' ? 'Resident' : 'Admin'),
            'user_id' => $user_id,
            'user_type' => $user_type
        ]);
        exit;
    }

    // Send a message
    if ($action === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['recipient_id']) || !isset($data['content']) || empty($data['content'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }

        $recipient_id = intval($data['recipient_id']);
        $content = trim($data['content']);
        $reply_to_id = isset($data['reply_to_id']) ? intval($data['reply_to_id']) : null;

        // Validate content
        if (strlen($content) > 5000) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Message too long (max 5000 characters)']);
            exit;
        }

        try {
            $stmt = $conn->prepare("CALL sendMessage(?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $user_type, $recipient_id, $content, $reply_to_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if ($result) {
                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'message' => 'Message sent',
                    'message_id' => $result['message_id']
                ]);
            } else {
                throw new Exception('Failed to send message');
            }
        } catch (PDOException $e) {
            error_log("Send message error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to send message']);
            exit;
        }
    }

    // Get messages (conversation history)
    else if ($action === 'get' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $recipient_id = isset($_GET['recipient_id']) ? intval($_GET['recipient_id']) : 0;

        if ($recipient_id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid recipient ID']);
            exit;
        }

        try {
            if ($user_type === 'resident') {
                $stmt = $conn->prepare("CALL getMessages(?, ?)");
                $stmt->execute([$user_id, $recipient_id]);
            } else {
                $stmt = $conn->prepare("CALL getMessages(?, ?)");
                $stmt->execute([$recipient_id, $user_id]);
            }

            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            // Filter out deleted messages (or mark them)
            $filtered_messages = array_map(function ($msg) {
                if ($msg['is_deleted']) {
                    $msg['content'] = '[This message was deleted]';
                }
                return $msg;
            }, $messages);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'messages' => $filtered_messages
            ]);
        } catch (PDOException $e) {
            error_log("Get messages error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to fetch messages']);
            exit;
        }
    }

    // Edit a message
    else if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['message_id']) || !isset($data['content']) || empty($data['content'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }

        $message_id = intval($data['message_id']);
        $content = trim($data['content']);

        if (strlen($content) > 5000) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Message too long']);
            exit;
        }

        try {
            $stmt = $conn->prepare("CALL editMessage(?, ?, ?, ?)");
            $stmt->execute([$message_id, $user_id, $user_type, $content]);
            $stmt->closeCursor();

            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Message updated']);
        } catch (PDOException $e) {
            error_log("Edit message error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to edit message']);
            exit;
        }
    }

    // Delete a message
    else if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['message_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing message ID']);
            exit;
        }

        $message_id = intval($data['message_id']);

        try {
            $stmt = $conn->prepare("CALL deleteMessage(?, ?, ?)");
            $stmt->execute([$message_id, $user_id, $user_type]);
            $stmt->closeCursor();

            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Message deleted']);
        } catch (PDOException $e) {
            error_log("Delete message error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to delete message']);
            exit;
        }
    }

    // Get conversation list
    else if ($action === 'conversations' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        try {
            if ($user_type === 'resident') {
                $stmt = $conn->prepare("CALL getResidentConversations(?)");
            } else {
                $stmt = $conn->prepare("CALL getAdminConversations(?)");
            }

            $stmt->execute([$user_id]);
            $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'conversations' => $conversations
            ]);
        } catch (PDOException $e) {
            error_log("Get conversations error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to fetch conversations']);
            exit;
        }
    }

    else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
    }

} catch (Exception $e) {
    error_log("Chat API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>
