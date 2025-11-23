<?php
// Check admin authentication
require_once __DIR__ . "/../../admin/auth_check.php";
require_once __DIR__ . "/../Messages.php";

$messages = new Messages();
$error = '';
$success = '';

// Get selected resident (default to first resident if available)
$selectedResidentId = isset($_GET['resident_id']) ? intval($_GET['resident_id']) : null;
$allResidents = $messages->getAllResidents();

if (empty($allResidents)) {
    $selectedResidentId = null;
} elseif ($selectedResidentId === null) {
    $selectedResidentId = $allResidents[0]['id'];
}

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiverId = intval($_POST['receiver_id']);
    $message = trim($_POST['message'] ?? '');
    $filePath = null;
    $fileType = null;
    
    // Handle file upload if present
    if (isset($_FILES['chat_file']) && $_FILES['chat_file']['error'] === UPLOAD_ERR_OK) {
        $filePath = $messages->uploadFile($_FILES['chat_file']);
        if ($filePath) {
            $mimeType = mime_content_type(__DIR__ . '/../' . $filePath);
            $fileType = strpos($mimeType, 'image/') === 0 ? 'image' : 'video';
        }
    }
    
    if (empty($message) && !$filePath) {
        $error = "Please enter a message or attach a file.";
    } elseif ($selectedResidentId) {
        $adminName = $_SESSION['admin_name'] ?? 'Admin';
        $sent = $messages->sendMessage(
            $_SESSION['admin_id'],
            'admin',
            $adminName,
            $receiverId,
            'resident',
            $message,
            $filePath,
            $fileType
        );
        
        if ($sent) {
            header("Location: Messages.php?resident_id=" . $receiverId);
            exit;
        } else {
            $error = "Failed to send message. Please try again.";
        }
    }
}

// Get conversations list
$conversations = $messages->getConversations($_SESSION['admin_id'], 'admin');

// Get messages with selected resident
$chatMessages = [];
if ($selectedResidentId) {
    $chatMessages = $messages->getMessages($_SESSION['admin_id'], 'admin', $selectedResidentId, 'resident');
}

// Get success message if any
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages — Admin</title>
    <link rel="stylesheet" href="../../css/admin.css">
    <link rel="stylesheet" href="../css/Messages.css">
    <script src="../../js/alerts.js"></script>
</head>
<body>
    <?php if (!empty($success)): ?>
        <div data-success-message="<?php echo htmlspecialchars($success); ?>"></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div data-error-message="<?php echo htmlspecialchars($error); ?>"></div>
    <?php endif; ?>

    <div class="admin-layout">
        <?php include __DIR__ . '/../../admin/sidebar.php'; ?>

        <main class="admin-main">
            <header class="admin-top">
                <div>
                    <p class="muted">Communication</p>
                    <h1>Messages</h1>
                </div>
            </header>

            <div class="messages-container">
                <!-- Conversations List -->
                <div class="conversations-sidebar">
                    <h3>Residents</h3>
                    <div class="conversations-list">
                        <?php if (empty($allResidents)): ?>
                            <p class="empty-state">No residents found.</p>
                        <?php else: ?>
                            <?php foreach ($allResidents as $resident): ?>
                                <?php
                                // Get conversation info
                                $convInfo = null;
                                foreach ($conversations as $conv) {
                                    if ($conv['other_id'] == $resident['id'] && $conv['other_type'] == 'resident') {
                                        $convInfo = $conv;
                                        break;
                                    }
                                }
                                $isActive = $selectedResidentId == $resident['id'];
                                $unreadCount = $convInfo ? $convInfo['unread_count'] : 0;
                                ?>
                                <a href="Messages.php?resident_id=<?php echo $resident['id']; ?>" 
                                   class="conversation-item <?php echo $isActive ? 'active' : ''; ?>">
                                    <div class="conversation-avatar"><?php echo strtoupper(substr($resident['full_name'], 0, 1)); ?></div>
                                    <div class="conversation-info">
                                        <div class="conversation-name"><?php echo htmlspecialchars($resident['full_name']); ?></div>
                                        <?php if ($convInfo): ?>
                                            <div class="conversation-preview"><?php echo htmlspecialchars(substr($convInfo['last_message'], 0, 30)); ?>...</div>
                                        <?php else: ?>
                                            <div class="conversation-preview">No messages yet</div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($unreadCount > 0): ?>
                                        <span class="unread-badge"><?php echo $unreadCount; ?></span>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Chat Area -->
                <div class="chat-area">
                    <?php if ($selectedResidentId): ?>
                        <?php
                        $selectedResident = null;
                        foreach ($allResidents as $r) {
                            if ($r['id'] == $selectedResidentId) {
                                $selectedResident = $r;
                                break;
                            }
                        }
                        ?>
                        <div class="chat-header">
                            <div class="chat-header-info">
                                <div class="chat-avatar"><?php echo strtoupper(substr($selectedResident['full_name'], 0, 1)); ?></div>
                                <div>
                                    <h3><?php echo htmlspecialchars($selectedResident['full_name']); ?></h3>
                                    <p class="chat-subtitle"><?php echo htmlspecialchars($selectedResident['username'] ?? 'N/A'); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="chat-messages" id="chatMessages">
                            <?php if (empty($chatMessages)): ?>
                                <div class="empty-chat">
                                    <p>No messages yet. Start the conversation!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($chatMessages as $msg): ?>
                                    <div class="message <?php echo $msg['sender_id'] == $_SESSION['admin_id'] && $msg['sender_type'] == 'admin' ? 'sent' : 'received'; ?>">
                                        <div class="message-content">
                                            <?php if (!empty($msg['message'])): ?>
                                                <div class="message-text"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                                            <?php endif; ?>
                                            <?php if (isset($msg['file_path'])): ?>
                                            <div class="message-file">
                                                <?php if ($msg['file_type'] === 'image'): ?>
                                                    <img src="../<?php echo htmlspecialchars($msg['file_path']); ?>" alt="Image" class="message-image" onclick="window.open('../<?php echo htmlspecialchars($msg['file_path']); ?>', '_blank')">
                                                <?php elseif ($msg['file_type'] === 'video'): ?>
                                                    <video controls class="message-video">
                                                        <source src="../<?php echo htmlspecialchars($msg['file_path']); ?>" type="video/mp4">
                                                        Your browser does not support the video tag.
                                                    </video>
                                                <?php endif; ?>
                                            </div>
                                            <?php endif; ?>
                                            <div class="message-time"><?php echo date('g:i A', strtotime($msg['timestamp'])); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div class="chat-input-area">
                            <form method="POST" class="chat-form" enctype="multipart/form-data">
                                <input type="hidden" name="receiver_id" value="<?php echo $selectedResidentId; ?>">
                                <div class="chat-input-wrapper">
                                    <label for="chat_file_<?php echo $selectedResidentId; ?>" class="file-upload-btn" title="Upload image or video">
                                        📎
                                    </label>
                                    <input type="file" id="chat_file_<?php echo $selectedResidentId; ?>" name="chat_file" accept="image/*,video/*" style="display: none;">
                                    <textarea name="message" class="chat-input" placeholder="Type your message..." rows="2"></textarea>
                                    <button type="submit" name="send_message" class="send-button">Send</button>
                                </div>
                                <div id="file-preview-<?php echo $selectedResidentId; ?>" class="file-preview"></div>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="no-selection">
                            <p>Select a resident to start chatting</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        var selectedResidentId = <?php echo $selectedResidentId ? $selectedResidentId : 'null'; ?>;
        var adminId = <?php echo $_SESSION['admin_id']; ?>;
        var lastMessageCount = <?php echo count($chatMessages); ?>;
        var isUserScrolledUp = false;

        // Auto-scroll to bottom of chat
        function scrollToBottom() {
            var chatMessages = document.getElementById('chatMessages');
            if (chatMessages && !isUserScrolledUp) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        }

        // Check if user has scrolled up
        function checkScrollPosition() {
            var chatMessages = document.getElementById('chatMessages');
            if (chatMessages) {
                var scrollTop = chatMessages.scrollTop;
                var scrollHeight = chatMessages.scrollHeight;
                var clientHeight = chatMessages.clientHeight;
                
                if (scrollHeight - scrollTop - clientHeight < 100) {
                    isUserScrolledUp = false;
                } else {
                    isUserScrolledUp = true;
                }
            }
        }

        scrollToBottom();

        var chatMessagesEl = document.getElementById('chatMessages');
        if (chatMessagesEl) {
            chatMessagesEl.addEventListener('scroll', checkScrollPosition);
        }

        // Function to update messages via AJAX
        function updateMessages() {
            if (!selectedResidentId) return;

            fetch('../chatting/api/getMessages.php?other_id=' + selectedResidentId + '&other_type=resident')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        var chatMessagesEl = document.getElementById('chatMessages');
                        var currentMessageCount = data.messages.length;
                        
                        if (currentMessageCount !== lastMessageCount) {
                            var wasAtBottom = !isUserScrolledUp;
                            
                            var messagesHtml = '';
                            if (data.messages.length === 0) {
                                messagesHtml = '<div class="empty-chat"><p>No messages yet. Start the conversation!</p></div>';
                            } else {
                                data.messages.forEach(function(msg) {
                                    var isSent = msg.sender_id == adminId && msg.sender_type == 'admin';
                                    var messageClass = isSent ? 'sent' : 'received';
                                    var messageTime = new Date(msg.timestamp).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
                                    
                                    var fileHtml = '';
                                    if (msg.file_path) {
                                        var fileUrl = '../' + msg.file_path;
                                        if (msg.file_type === 'image') {
                                            fileHtml = '<div class="message-file"><img src="' + fileUrl + '" alt="Image" class="message-image" onclick="window.open(\'' + fileUrl + '\', \'_blank\')"></div>';
                                        } else if (msg.file_type === 'video') {
                                            fileHtml = '<div class="message-file"><video controls class="message-video"><source src="' + fileUrl + '" type="video/mp4">Your browser does not support the video tag.</video></div>';
                                        }
                                    }
                                    
                                    messagesHtml += '<div class="message ' + messageClass + '">' +
                                        '<div class="message-content">' +
                                        (msg.message ? '<div class="message-text">' + escapeHtml(msg.message).replace(/\n/g, '<br>') + '</div>' : '') +
                                        fileHtml +
                                        '<div class="message-time">' + messageTime + '</div>' +
                                        '</div>' +
                                        '</div>';
                                });
                            }
                            
                            chatMessagesEl.innerHTML = messagesHtml;
                            lastMessageCount = currentMessageCount;
                            
                            if (wasAtBottom) {
                                setTimeout(scrollToBottom, 100);
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Error updating messages:', error);
                });
        }

        function escapeHtml(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        setInterval(updateMessages, 3000);

        // Handle Enter key to send message, Shift+Enter for new line
        var messageTextarea = document.querySelector('.chat-input');
        var chatForm = document.querySelector('.chat-form');
        
        if (messageTextarea && chatForm) {
            messageTextarea.addEventListener('keydown', function(e) {
                // Check if Enter key is pressed
                if (e.key === 'Enter') {
                    if (e.shiftKey) {
                        // Shift+Enter: Allow default behavior (new line)
                        return true;
                    } else {
                        // Enter alone: Send message
                        e.preventDefault();
                        // Only submit if there's text or file
                        var fileInput = chatForm.querySelector('input[type="file"]');
                        if (this.value.trim().length > 0 || (fileInput && fileInput.files.length > 0)) {
                            chatForm.submit();
                        }
                        return false;
                    }
                }
            });
        }

        // Handle file upload preview
        var fileInput = document.querySelector('input[type="file"]');
        var filePreview = document.getElementById('file-preview-' + selectedResidentId);
        
        if (fileInput && filePreview) {
            fileInput.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    var file = this.files[0];
                    var reader = new FileReader();
                    
                    if (file.type.startsWith('image/')) {
                        reader.onload = function(e) {
                            filePreview.innerHTML = '<div class="file-preview-item"><img src="' + e.target.result + '" style="max-width: 200px; max-height: 200px; border-radius: 4px;"><span class="file-preview-remove" onclick="this.parentElement.remove(); document.querySelector(\'input[type=\\\'file\\\']\').value=\\\'\\\';" style="cursor: pointer; margin-left: 10px; color: red;">✕</span></div>';
                        };
                        reader.readAsDataURL(file);
                    } else if (file.type.startsWith('video/')) {
                        filePreview.innerHTML = '<div class="file-preview-item"><span>Video: ' + file.name + '</span><span class="file-preview-remove" onclick="this.parentElement.remove(); document.querySelector(\'input[type=\\\'file\\\']\').value=\\\'\\\';" style="cursor: pointer; margin-left: 10px; color: red;">✕</span></div>';
                    }
                }
            });
        }
    </script>
</body>
</html>
