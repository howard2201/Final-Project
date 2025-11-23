<?php
session_start();
require_once __DIR__ . "/../../config/Database.php";
require_once __DIR__ . "/../Messages.php";

// Check if user is logged in
if (!isset($_SESSION['resident_id'])) {
    header("Location: ../../residents/Login.php");
    exit;
}

$messages = new Messages();
$error = '';
$success = '';

// Get selected admin (default to first admin if available)
$selectedAdminId = isset($_GET['admin_id']) ? intval($_GET['admin_id']) : null;
$allAdmins = $messages->getAllAdmins();

if (empty($allAdmins)) {
    $selectedAdminId = null;
} elseif ($selectedAdminId === null) {
    $selectedAdminId = $allAdmins[0]['id'];
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
    } elseif ($selectedAdminId) {
        $residentName = $_SESSION['resident_name'] ?? 'Resident';
        $sent = $messages->sendMessage(
            $_SESSION['resident_id'],
            'resident',
            $residentName,
            $receiverId,
            'admin',
            $message,
            $filePath,
            $fileType
        );
        
        if ($sent) {
            header("Location: Messages.php?admin_id=" . $receiverId);
            exit;
        } else {
            $error = "Failed to send message. Please try again.";
        }
    }
}

// Get conversations list
$conversations = $messages->getConversations($_SESSION['resident_id'], 'resident');

// Get messages with selected admin
$chatMessages = [];
if ($selectedAdminId) {
    $chatMessages = $messages->getMessages($_SESSION['resident_id'], 'resident', $selectedAdminId, 'admin');
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
    <title>Messages — Resident</title>
    <link rel="stylesheet" href="../../css/residents.css">
    <link rel="stylesheet" href="../../css/include.css">
    <link rel="stylesheet" href="../css/Messages.css">
    <script src="../../js/alerts.js"></script>
    <script src="../../js/hamburger.js"></script>
</head>
<body>
    <?php 
    $current_page = basename($_SERVER['PHP_SELF']);
    $base_path = '../../';
    ?>
    <header class="site-header">
        <div class="container header-inner">
            <div class="brand">Bagong Pook Community Service Request</div>

            <!-- Original nav links -->
            <nav class="nav original-nav">
                <a href="<?php echo $base_path; ?>index.php">Home</a>

                <?php if ($current_page !== 'AnnouncementsList.php') : ?>
                    <a href="<?php echo $base_path; ?>announcements/AnnouncementsList.php">Announcements</a>
                <?php endif; ?>

                <?php
                if (isset($_SESSION['resident_id']) || isset($_SESSION['admin_id'])) {
                    echo '<a href="' . $base_path . 'logout.php" class="btn small outline">Logout</a>';
                } else {
                    if (!in_array($current_page, ['Login.php','Register.php','AdminLogin.php'])) {
                        echo '<a href="' . $base_path . 'residents/Login.php" class="btn small">Login</a>';
                    }
                }
                ?>
            </nav>

            <!-- Hamburger icon -->
            <div class="hamburger" id="hamburger2">
                <span></span>
                <span></span>
                <span></span>
            </div>

            <!-- Dropdown nav for hamburger -->
            <nav class="nav dropdown-nav" id="dropdown2">
                <a href="<?php echo $base_path; ?>index.php">Home</a>

                <?php if ($current_page !== 'AnnouncementsList.php') : ?>
                    <a href="<?php echo $base_path; ?>announcements/AnnouncementsList.php">Announcements</a>
                <?php endif; ?>

                <?php
                if (isset($_SESSION['resident_id']) || isset($_SESSION['admin_id'])) {
                    echo '<a href="' . $base_path . 'logout.php" class="btn small outline">Logout</a>';
                } else {
                    if (!in_array($current_page, ['Login.php','Register.php','AdminLogin.php'])) {
                        echo '<a href="' . $base_path . 'residents/Login.php" class="btn small">Login</a>';
                    }
                }
                ?>
            </nav>
        </div>
    </header>

    <?php if (!empty($success)): ?>
        <div data-success-message="<?php echo htmlspecialchars($success); ?>"></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div data-error-message="<?php echo htmlspecialchars($error); ?>"></div>
    <?php endif; ?>

    <main class="container messages-page">
        <h1>Messages</h1>

        <div class="messages-container">
            <!-- Conversations List -->
            <div class="conversations-sidebar">
                <h3>Admins</h3>
                <div class="conversations-list">
                    <?php if (empty($allAdmins)): ?>
                        <p class="empty-state">No admins found.</p>
                    <?php else: ?>
                        <?php foreach ($allAdmins as $admin): ?>
                            <?php
                            // Get conversation info
                            $convInfo = null;
                            foreach ($conversations as $conv) {
                                if ($conv['other_id'] == $admin['id'] && $conv['other_type'] == 'admin') {
                                    $convInfo = $conv;
                                    break;
                                }
                            }
                            $isActive = $selectedAdminId == $admin['id'];
                            $unreadCount = $convInfo ? $convInfo['unread_count'] : 0;
                            ?>
                            <a href="Messages.php?admin_id=<?php echo $admin['id']; ?>" 
                               class="conversation-item <?php echo $isActive ? 'active' : ''; ?>">
                                <div class="conversation-avatar"><?php echo strtoupper(substr($admin['full_name'], 0, 1)); ?></div>
                                <div class="conversation-info">
                                    <div class="conversation-name"><?php echo htmlspecialchars($admin['full_name']); ?></div>
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
                <?php if ($selectedAdminId): ?>
                    <?php
                    $selectedAdmin = null;
                    foreach ($allAdmins as $a) {
                        if ($a['id'] == $selectedAdminId) {
                            $selectedAdmin = $a;
                            break;
                        }
                    }
                    ?>
                    <div class="chat-header">
                        <div class="chat-header-info">
                            <div class="chat-avatar"><?php echo strtoupper(substr($selectedAdmin['full_name'], 0, 1)); ?></div>
                            <div>
                                <h3><?php echo htmlspecialchars($selectedAdmin['full_name']); ?></h3>
                                <p class="chat-subtitle">Administrator</p>
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
                                <div class="message <?php echo $msg['sender_id'] == $_SESSION['resident_id'] && $msg['sender_type'] == 'resident' ? 'sent' : 'received'; ?>">
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
                            <input type="hidden" name="receiver_id" value="<?php echo $selectedAdminId; ?>">
                            <div id="file-preview-<?php echo $selectedAdminId; ?>" class="file-preview"></div>
                            <div class="chat-input-wrapper">
                                <label for="chat_file_<?php echo $selectedAdminId; ?>" class="file-upload-btn" title="Upload image or video (max 5 files)">
                                    📎
                                </label>
                                <input type="file" id="chat_file_<?php echo $selectedAdminId; ?>" name="chat_file[]" accept="image/*,video/*" multiple style="display: none;">
                                <textarea name="message" class="chat-input" placeholder="Type your message..." rows="2"></textarea>
                                <button type="submit" name="send_message" class="send-button">Send</button>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="no-selection">
                        <p>Select an admin to start chatting</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <h4>Bagong Pook Community Service Request</h4>
                    <p>Lipa City, Batangas</p>
                </div>
                <div>
                    <h4>Contact</h4>
                    <p>Email: barangay@example.ph</p>
                    <p>Phone: (+63) 912-345-6789</p>
                </div>
            </div>
            <p class="copyright">© <?php echo date('Y'); ?> Prototype — All rights reserved.</p>
        </div>
    </footer>

    <script>
        var selectedAdminId = <?php echo $selectedAdminId ? $selectedAdminId : 'null'; ?>;
        var residentId = <?php echo $_SESSION['resident_id']; ?>;
        var lastMessageCount = <?php echo count($chatMessages); ?>;
        var isUserScrolledUp = false;
        var lastScrollHeight = 0;

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
                
                // If user is near bottom (within 100px), auto-scroll
                if (scrollHeight - scrollTop - clientHeight < 100) {
                    isUserScrolledUp = false;
                } else {
                    isUserScrolledUp = true;
                }
            }
        }

        // Initial scroll
        scrollToBottom();

        // Listen for scroll events
        var chatMessagesEl = document.getElementById('chatMessages');
        if (chatMessagesEl) {
            chatMessagesEl.addEventListener('scroll', checkScrollPosition);
        }

        // Function to update messages via AJAX
        function updateMessages() {
            if (!selectedAdminId) return;

            fetch('../chatting/api/getMessages.php?other_id=' + selectedAdminId + '&other_type=admin')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        var chatMessagesEl = document.getElementById('chatMessages');
                        var currentMessageCount = data.messages.length;
                        
                        // Only update if there are new messages
                        if (currentMessageCount !== lastMessageCount) {
                            // Save current scroll position
                            var wasAtBottom = !isUserScrolledUp;
                            
                            // Update messages
                            var messagesHtml = '';
                            if (data.messages.length === 0) {
                                messagesHtml = '<div class="empty-chat"><p>No messages yet. Start the conversation!</p></div>';
                            } else {
                                data.messages.forEach(function(msg) {
                                    var isSent = msg.sender_id == residentId && msg.sender_type == 'resident';
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
                            
                            // Scroll to bottom if user was at bottom before update
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

        // Escape HTML to prevent XSS
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

        // Auto-refresh messages every 3 seconds
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
        var filePreview = document.getElementById('file-preview-' + selectedAdminId);
        
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
