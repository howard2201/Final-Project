<?php
session_start();

// Check if rejected resident session exists
if (!isset($_SESSION['rejected_resident_id'])) {
    header('Location: Login.php');
    exit;
}

require_once '../config/Database.php';
require_once '../chatting/Messages.php';

// Get rejection details from database using stored procedure
$pdo = Database::getInstance()->getConnection();

try {
    $stmt = $pdo->prepare("CALL getResidentById(?)");
    $stmt->execute([$_SESSION['rejected_resident_id']]);
    $resident = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
} catch (PDOException $e) {
    error_log("Rejected page error: " . $e->getMessage());
    die("Database error. Please contact the administrator.");
}

// If resident doesn't exist or is no longer rejected, redirect
if (!$resident || $resident['approval_status'] !== 'Rejected') {
    session_destroy();
    header('Location: Login.php');
    exit;
}

// Calculate days remaining until deletion
$rejectionDate = new DateTime($resident['rejection_date']);
$deletionDate = clone $rejectionDate;
$deletionDate->modify('+10 days');
$now = new DateTime();
$daysRemaining = $now->diff($deletionDate)->days;

// If already past deletion date
if ($now >= $deletionDate) {
    $daysRemaining = 0;
    $deletionMessage = "Your account is scheduled for deletion today.";
} else {
    $deletionMessage = "Your account will be permanently deleted in <strong>{$daysRemaining} day(s)</strong>.";
}

$residentName = htmlspecialchars($resident['full_name']);
$residentEmail = htmlspecialchars($resident['email']);
$rejectionDateFormatted = $rejectionDate->format('F j, Y \a\t g:i A');
$deletionDateFormatted = $deletionDate->format('F j, Y');
$residentId = $resident['id'];

// Initialize messaging
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
            $mimeType = mime_content_type(__DIR__ . '/../chatting/' . $filePath);
            $fileType = strpos($mimeType, 'image/') === 0 ? 'image' : 'video';
        }
    }
    
    if (empty($message) && !$filePath) {
        $error = "Please enter a message or attach a file.";
    } elseif ($selectedAdminId) {
        $sent = $messages->sendMessage(
            $residentId,
            'resident',
            $residentName,
            $receiverId,
            'admin',
            $message,
            $filePath,
            $fileType
        );
        
        if ($sent) {
            header("Location: Rejected_Page.php?admin_id=" . $receiverId);
            exit;
        } else {
            $error = "Failed to send message. Please try again.";
        }
    }
}

// Get conversations list
$conversations = $messages->getConversations($residentId, 'resident');

// Get messages with selected admin
$chatMessages = [];
if ($selectedAdminId) {
    $chatMessages = $messages->getMessages($residentId, 'resident', $selectedAdminId, 'admin');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Rejected — Smart Brgy System</title>
    <link rel="stylesheet" href="../css/residents.css">
    <link rel="stylesheet" href="../css/include.css">
    <link rel="stylesheet" href="../css/temppage.css">
    <link rel="stylesheet" href="../chatting/css/Messages.css">
    <script src="../js/alerts.js"></script>
</head>
<body>
<?php include '../includes/headerinner.php'; ?>

<main class="container rejection-container">
    <div class="rejection-card">
            <div class="rejection-header">
                <div class="rejection-icon">⚠️</div>
                <h1>Account Registration Rejected</h1>
            </div>

            <div class="rejection-body">
                <div class="info-box">
                    <h3>Dear <?php echo $residentName; ?>,</h3>
                    <p>We regret to inform you that your account registration has been rejected by the barangay administrator.</p>
                    <p>Your account and all associated data will be permanently deleted after 10 days from the rejection date.</p>
                </div>

                <div class="countdown-box">
                    <div class="label">Days Until Deletion</div>
                    <div class="days"><?php echo $daysRemaining; ?></div>
                    <p style="margin: 0.5rem 0 0 0; color: #6c757d;">
                        <?php echo $deletionMessage; ?>
                    </p>
                </div>

                <ul class="details-list">
                    <li>
                        <span class="label">Account Name:</span>
                        <span class="value"><?php echo $residentName; ?></span>
                    </li>
                    <li>
                        <span class="label">Email:</span>
                        <span class="value"><?php echo $residentEmail; ?></span>
                    </li>
                    <li>
                        <span class="label">Rejection Date:</span>
                        <span class="value"><?php echo $rejectionDateFormatted; ?></span>
                    </li>
                    <li>
                        <span class="label">Deletion Date:</span>
                        <span class="value"><?php echo $deletionDateFormatted; ?></span>
                    </li>
                </ul>

                <div class="warning-text">
                    <strong>⚠️ Important:</strong> If you believe this rejection was made in error, please contact the barangay office immediately before your account is deleted. You can use the chat below to message administrators.
                </div>

                <!-- Messaging Section -->
                <div class="messages-section" style="margin-top: 2rem;">
                    <h2 style="color: #dc3545; margin-bottom: 1rem; border-bottom: 2px solid #ffc107; padding-bottom: 0.5rem;">💬 Chat with Administrators</h2>
                    
                    <div class="messages-container" style="height: 500px; min-height: 500px;">
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
                                        <a href="Rejected_Page.php?admin_id=<?php echo $admin['id']; ?>" 
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
                                            <div class="message <?php echo $msg['sender_id'] == $residentId && $msg['sender_type'] == 'resident' ? 'sent' : 'received'; ?>">
                                                <div class="message-content">
                                                    <?php if (!empty($msg['message'])): ?>
                                                        <div class="message-text"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                                                    <?php endif; ?>
                                                    <?php if (isset($msg['file_path'])): ?>
                                                        <div class="message-file">
                                                            <?php if ($msg['file_type'] === 'image'): ?>
                                                                <img src="../chatting/<?php echo htmlspecialchars($msg['file_path']); ?>" alt="Image" class="message-image" onclick="window.open('../chatting/<?php echo htmlspecialchars($msg['file_path']); ?>', '_blank')">
                                                            <?php elseif ($msg['file_type'] === 'video'): ?>
                                                                <video controls class="message-video">
                                                                    <source src="../chatting/<?php echo htmlspecialchars($msg['file_path']); ?>" type="video/mp4">
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
                                        <div class="chat-input-wrapper">
                                            <label for="chat_file_rejected_<?php echo $selectedAdminId; ?>" class="file-upload-btn" title="Upload image or video">
                                                📎
                                            </label>
                                            <input type="file" id="chat_file_rejected_<?php echo $selectedAdminId; ?>" name="chat_file" accept="image/*,video/*" style="display: none;">
                                            <textarea name="message" class="chat-input" placeholder="Type your message..." rows="2"></textarea>
                                            <button type="submit" name="send_message" class="send-button">Send</button>
                                        </div>
                                        <div id="file-preview-rejected-<?php echo $selectedAdminId; ?>" class="file-preview"></div>
                                    </form>
                                </div>
                            <?php else: ?>
                                <div class="no-selection">
                                    <p>Select an admin to start chatting</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if (!empty($error)): ?>
                    <div data-error-message="<?php echo htmlspecialchars($error); ?>"></div>
                <?php endif; ?>

                <div class="action-buttons" style="margin-top: 2rem;">
                    <a href="Login.php" class="logout-link">Back to Login</a>
                    <a href="../index.php" class="logout-link">Go to Home</a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>

<script>
    var selectedAdminId = <?php echo $selectedAdminId ? $selectedAdminId : 'null'; ?>;
    var residentId = <?php echo $residentId; ?>;
    var lastMessageCount = <?php echo count($chatMessages); ?>;
    var isUserScrolledUp = false;

    function scrollToBottom() {
        var chatMessages = document.getElementById('chatMessages');
        if (chatMessages && !isUserScrolledUp) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    }

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

    function updateMessages() {
        if (!selectedAdminId) return;

        fetch('../chatting/api/getMessages.php?other_id=' + selectedAdminId + '&other_type=admin')
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
                                    var isSent = msg.sender_id == residentId && msg.sender_type == 'resident';
                                    var messageClass = isSent ? 'sent' : 'received';
                                    var messageTime = new Date(msg.timestamp).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
                                    
                                    var fileHtml = '';
                                    if (msg.file_path) {
                                        var fileUrl = '../chatting/' + msg.file_path;
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
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                var fileInput = chatForm.querySelector('input[type="file"]');
                if (this.value.trim().length > 0 || (fileInput && fileInput.files.length > 0)) {
                    chatForm.submit();
                }
            }
        });
    }

    // Handle file upload preview
    var fileInput = document.querySelector('input[type="file"]');
    var filePreview = document.getElementById('file-preview-rejected-' + selectedAdminId);
    
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

