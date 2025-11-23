<?php
require_once __DIR__ . "/../config/Database.php";

class Messages {
    private $messagesDir;
    
    private $uploadsDir;
    
    public function __construct() {
        // Create messages directory if it doesn't exist
        $this->messagesDir = __DIR__ . '/messages/';
        if (!file_exists($this->messagesDir)) {
            mkdir($this->messagesDir, 0777, true);
        }
        
        // Create uploads directory for chat files
        $this->uploadsDir = __DIR__ . '/uploads/';
        if (!file_exists($this->uploadsDir)) {
            mkdir($this->uploadsDir, 0777, true);
        }
    }
    
    /**
     * Get messages file path for a conversation
     */
    private function getMessagesFile($senderId, $senderType, $receiverId, $receiverType) {
        // Create a consistent file name regardless of who sent first
        $ids = [$senderId . '_' . $senderType, $receiverId . '_' . $receiverType];
        sort($ids);
        $filename = implode('_', $ids) . '.json';
        return $this->messagesDir . $filename;
    }
    
    /**
     * Upload a file and return the file path
     */
    public function uploadFile($file, $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/quicktime', 'video/x-msvideo']) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return null;
        }
        
        // Check file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            return null;
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $filepath = $this->uploadsDir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Return relative path from chatting directory
            return 'uploads/' . $filename;
        }
        
        return null;
    }
    
    /**
     * Send a message
     */
    public function sendMessage($senderId, $senderType, $senderName, $receiverId, $receiverType, $message, $filePath = null, $fileType = null) {
        $file = $this->getMessagesFile($senderId, $senderType, $receiverId, $receiverType);
        
        // Load existing messages
        $messages = [];
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $messages = json_decode($content, true) ?: [];
        }
        
        // Add new message
        $messageData = [
            'id' => uniqid(),
            'sender_id' => $senderId,
            'sender_type' => $senderType, // 'admin' or 'resident'
            'sender_name' => $senderName,
            'receiver_id' => $receiverId,
            'receiver_type' => $receiverType,
            'message' => trim($message),
            'timestamp' => date('Y-m-d H:i:s'),
            'read' => false
        ];
        
        // Add file info if present
        if ($filePath) {
            $messageData['file_path'] = $filePath;
            $messageData['file_type'] = $fileType; // 'image' or 'video'
        }
        
        $messages[] = $messageData;
        
        // Save messages
        file_put_contents($file, json_encode($messages, JSON_PRETTY_PRINT));
        
        return true;
    }
    
    /**
     * Get messages between two users
     */
    public function getMessages($userId, $userType, $otherId, $otherType) {
        $file = $this->getMessagesFile($userId, $userType, $otherId, $otherType);
        
        if (!file_exists($file)) {
            return [];
        }
        
        $content = file_get_contents($file);
        $messages = json_decode($content, true) ?: [];
        
        // Mark messages as read if they're for the current user
        $updated = false;
        foreach ($messages as &$msg) {
            if ($msg['receiver_id'] == $userId && $msg['receiver_type'] == $userType && !$msg['read']) {
                $msg['read'] = true;
                $updated = true;
            }
        }
        
        if ($updated) {
            file_put_contents($file, json_encode($messages, JSON_PRETTY_PRINT));
        }
        
        return $messages;
    }
    
    /**
     * Get all conversations for a user
     */
    public function getConversations($userId, $userType) {
        $conversations = [];
        $files = glob($this->messagesDir . '*.json');
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $messages = json_decode($content, true) ?: [];
            
            if (empty($messages)) continue;
            
            // Check if this conversation involves the current user
            $lastMessage = end($messages);
            $otherId = null;
            $otherType = null;
            
            if ($lastMessage['sender_id'] == $userId && $lastMessage['sender_type'] == $userType) {
                $otherId = $lastMessage['receiver_id'];
                $otherType = $lastMessage['receiver_type'];
            } elseif ($lastMessage['receiver_id'] == $userId && $lastMessage['receiver_type'] == $userType) {
                $otherId = $lastMessage['sender_id'];
                $otherType = $lastMessage['sender_type'];
            }
            
            if ($otherId) {
                // Get other user's name from database
                $otherName = $this->getUserName($otherId, $otherType);
                
                // Count unread messages
                $unreadCount = 0;
                foreach ($messages as $msg) {
                    if ($msg['receiver_id'] == $userId && $msg['receiver_type'] == $userType && !$msg['read']) {
                        $unreadCount++;
                    }
                }
                
                $conversations[] = [
                    'other_id' => $otherId,
                    'other_type' => $otherType,
                    'other_name' => $otherName,
                    'last_message' => $lastMessage['message'],
                    'last_timestamp' => $lastMessage['timestamp'],
                    'unread_count' => $unreadCount
                ];
            }
        }
        
        // Sort by last message timestamp (newest first)
        usort($conversations, function($a, $b) {
            return strtotime($b['last_timestamp']) - strtotime($a['last_timestamp']);
        });
        
        return $conversations;
    }
    
    /**
     * Get user name from database
     */
    private function getUserName($userId, $userType) {
        try {
            $pdo = Database::getInstance()->getConnection();
            
            if ($userType === 'admin') {
                $stmt = $pdo->prepare("CALL getAdminById(?)");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                $stmt->closeCursor();
                return $user ? $user['full_name'] : 'Admin';
            } else {
                $stmt = $pdo->prepare("CALL getResidentById(?)");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                $stmt->closeCursor();
                return $user ? $user['full_name'] : 'Resident';
            }
        } catch (PDOException $e) {
            error_log("Error getting user name: " . $e->getMessage());
            return 'Unknown';
        }
    }
    
    /**
     * Get all residents for admin
     */
    public function getAllResidents() {
        try {
            $pdo = Database::getInstance()->getConnection();
            $stmt = $pdo->prepare("SELECT id, full_name, username, phone_number FROM residents ORDER BY full_name");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting residents: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all admins for resident
     */
    public function getAllAdmins() {
        try {
            $pdo = Database::getInstance()->getConnection();
            $stmt = $pdo->prepare("SELECT id, full_name, username FROM admins ORDER BY full_name");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting admins: " . $e->getMessage());
            return [];
        }
    }
}
?>

