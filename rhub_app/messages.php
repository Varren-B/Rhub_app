<?php
require_once 'config/database.php';
require_once 'includes/helpers.php';
requireLogin();

$conn = getDBConnection();
$student_id = getCurrentStudentId();

// Get user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit();
}

$currentAvatar = getUserAvatar($user);

// Get recipient from URL (for new conversation)
$to_student_id = isset($_GET['to']) ? intval($_GET['to']) : 0;
$item_id = isset($_GET['item']) ? intval($_GET['item']) : 0;
$active_chat = isset($_GET['chat']) ? intval($_GET['chat']) : $to_student_id;

// Handle sending message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiver_id = intval($_POST['receiver_id']);
    $message = sanitize($_POST['message']);
    $msg_item_id = intval($_POST['item_id'] ?? 0);
    
    if (!empty($message) && $receiver_id > 0) {
        // Get or create conversation
        $stmt = $conn->prepare("SELECT id FROM conversations WHERE (buyer_id = ? AND seller_id = ?) OR (buyer_id = ? AND seller_id = ?)");
        $stmt->bind_param("iiii", $student_id, $receiver_id, $receiver_id, $student_id);
        $stmt->execute();
        $conv_result = $stmt->get_result();
        
        if ($conv_result->num_rows > 0) {
            $conv_id = $conv_result->fetch_assoc()['id'];
        } else {
            // Create new conversation
            $item_val = $msg_item_id > 0 ? $msg_item_id : null;
            $stmt = $conn->prepare("INSERT INTO conversations (buyer_id, seller_id, item_id) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $student_id, $receiver_id, $item_val);
            $stmt->execute();
            $conv_id = $stmt->insert_id;
        }
        
        // Insert message
        $stmt = $conn->prepare("INSERT INTO messages (conversation_id, sender_id, receiver_id, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $conv_id, $student_id, $receiver_id, $message);
        $stmt->execute();
        
        // Redirect to avoid form resubmission
        header("Location: messages.php?chat=$receiver_id" . ($msg_item_id ? "&item=$msg_item_id" : ""));
        exit();
    }
}

// Get all conversations (unique users)
$stmt = $conn->prepare("
    SELECT DISTINCT 
        CASE 
            WHEN sender_id = ? THEN receiver_id 
            ELSE sender_id 
        END as other_user_id,
        (SELECT full_name FROM users WHERE id = other_user_id) as user_name,
        (SELECT profile_image FROM users WHERE id = other_user_id) as profile_image,
        (SELECT message FROM messages WHERE (sender_id = ? AND receiver_id = other_user_id) OR (sender_id = other_user_id AND receiver_id = ?) ORDER BY created_at DESC LIMIT 1) as last_message,
        (SELECT created_at FROM messages WHERE (sender_id = ? AND receiver_id = other_user_id) OR (sender_id = other_user_id AND receiver_id = ?) ORDER BY created_at DESC LIMIT 1) as last_time,
        (SELECT COUNT(*) FROM messages WHERE sender_id = other_user_id AND receiver_id = ? AND is_read = 0) as unread_count
    FROM messages 
    WHERE sender_id = ? OR receiver_id = ?
    ORDER BY last_time DESC
");
$stmt->bind_param("iiiiiiii", $student_id, $student_id, $student_id, $student_id, $student_id, $student_id, $student_id, $student_id);
$stmt->execute();
$conversations = $stmt->get_result();

// If starting a new conversation with someone not in the list
$other_user = null;
$chat_item = null;
if ($active_chat > 0) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $active_chat);
    $stmt->execute();
    $other_user = $stmt->get_result()->fetch_assoc();
    
    // Get item info if present
    if ($item_id > 0) {
        $stmt = $conn->prepare("SELECT * FROM marketplace_items WHERE id = ?");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $chat_item = $stmt->get_result()->fetch_assoc();
    }
    
    // Get messages in this conversation
    $stmt = $conn->prepare("
        SELECT m.*, u.full_name as sender_name 
        FROM messages m 
        JOIN users u ON m.sender_id = u.id 
        WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.created_at ASC
    ");
    $stmt->bind_param("iiii", $student_id, $active_chat, $active_chat, $student_id);
    $stmt->execute();
    $messages = $stmt->get_result();
    
    // Mark messages as read
    $stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?");
    $stmt->bind_param("ii", $active_chat, $student_id);
    $stmt->execute();
}

// Get unread messages total
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$unread_messages = $stmt->get_result()->fetch_assoc()['count'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - RHUB Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/messages.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        
        <div class="main-content">
            <header class="top-header">
                <div class="header-left">
                    <button class="menu-toggle" id="menuToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="page-title">Messages</h1>
                </div>
                <div class="header-right">
                    <div class="user-profile">
                        <div class="user-avatar">
                            <?php if ($currentAvatar): ?>
                                <img src="<?php echo htmlspecialchars($currentAvatar); ?>" alt="Profile" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                            <?php else: ?>
                                <?php echo getUserInitials($user['full_name']); ?>
                            <?php endif; ?>
                        </div>
                        <div class="user-info">
                            <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                            <span><?php echo htmlspecialchars($user['matricule'] ?? ''); ?></span>
                        </div>
                    </div>
                </div>
            </header>
            
            <div class="dashboard-content">
                <div class="messages-container">
                    <!-- Conversations List -->
                    <div class="conversations-list <?php echo $active_chat > 0 ? 'hidden-mobile' : ''; ?>" id="conversationsList">
                        <div class="conversations-header">
                            <h3><i class="fas fa-comments"></i> Conversations</h3>
                            <div class="search-conversations">
                                <i class="fas fa-search"></i>
                                <input type="text" placeholder="Search conversations..." id="searchConversations">
                            </div>
                        </div>
                        <div class="conversations-body">
                            <?php if ($conversations->num_rows > 0): ?>
                                <?php while ($conv = $conversations->fetch_assoc()): ?>
                                    <a href="?chat=<?php echo $conv['other_user_id']; ?>" 
                                       class="conversation-item <?php echo $active_chat == $conv['other_user_id'] ? 'active' : ''; ?>">
                                        <div class="conversation-avatar">
                                            <?php if (!empty($conv['profile_image']) && file_exists($conv['profile_image'])): ?>
                                                <img src="<?php echo htmlspecialchars($conv['profile_image']); ?>" alt="<?php echo htmlspecialchars($conv['user_name']); ?>" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                                            <?php else: ?>
                                                <?php echo getUserInitials($conv['user_name'] ?? ''); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="conversation-info">
                                            <h4><?php echo htmlspecialchars($conv['user_name'] ?? 'Unknown'); ?></h4>
                                            <p><?php echo htmlspecialchars(substr($conv['last_message'], 0, 40)); ?>...</p>
                                        </div>
                                        <div class="conversation-meta">
                                            <div class="time"><?php echo date('H:i', strtotime($conv['last_time'])); ?></div>
                                            <?php if ($conv['unread_count'] > 0): ?>
                                                <span class="unread-badge"><?php echo $conv['unread_count']; ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="no-conversation" style="padding: 3rem 1rem;">
                                    <i class="fas fa-inbox"></i>
                                    <h4>No Conversations</h4>
                                    <p>Start a conversation from the marketplace</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Chat Area -->
                    <div class="chat-area <?php echo $active_chat > 0 ? 'active' : ''; ?>" id="chatArea">
                        <?php if ($other_user): ?>
                            <div class="chat-header">
                                <button class="back-btn" onclick="showConversations()">
                                    <i class="fas fa-arrow-left"></i>
                                </button>
                                <div class="chat-user">
                                    <div class="avatar">
                                        <?php $otherAvatar = getUserAvatar($other_user); ?>
                                        <?php if ($otherAvatar): ?>
                                            <img src="<?php echo htmlspecialchars($otherAvatar); ?>" alt="<?php echo htmlspecialchars($other_user['full_name']); ?>" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                                        <?php else: ?>
                                            <?php echo getUserInitials($other_user['full_name']); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="user-details">
                                        <h4><?php echo htmlspecialchars($other_user['full_name']); ?></h4>
                                        <p><?php echo htmlspecialchars($other_user['department']); ?> - Level <?php echo htmlspecialchars($other_user['level']); ?></p>
                                    </div>
                                </div>
                                <?php if ($chat_item): ?>
                                    <div class="chat-item-info">
                                        <i class="fas fa-tag"></i>
                                        <?php echo htmlspecialchars($chat_item['title']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="chat-messages" id="chatMessages">
                                <?php if (isset($messages) && $messages->num_rows > 0): ?>
                                    <?php 
                                    $current_date = '';
                                    while ($msg = $messages->fetch_assoc()): 
                                        $msg_date = date('Y-m-d', strtotime($msg['created_at']));
                                        if ($msg_date !== $current_date):
                                            $current_date = $msg_date;
                                    ?>
                                        <div class="date-separator">
                                            <span><?php echo date('F d, Y', strtotime($msg['created_at'])); ?></span>
                                        </div>
                                    <?php endif; ?>
                                        <div class="message <?php echo $msg['sender_id'] == $student_id ? 'sent' : 'received'; ?>">
                                            <div class="avatar">
                                                <?php $senderAvatar = $msg['sender_id'] == $student_id ? $currentAvatar : $otherAvatar; ?>
                                                <?php if ($senderAvatar): ?>
                                                    <img src="<?php echo htmlspecialchars($senderAvatar); ?>" alt="<?php echo htmlspecialchars($msg['sender_name']); ?>" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                                                <?php else: ?>
                                                    <?php echo getUserInitials($msg['sender_name']); ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="message-content">
                                                <p><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                                                <span class="time"><?php echo date('h:i A', strtotime($msg['created_at'])); ?></span>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="no-conversation">
                                        <i class="fas fa-comment-dots"></i>
                                        <h4>Start the Conversation</h4>
                                        <p>Send a message to <?php echo htmlspecialchars(explode(' ', $other_user['full_name'])[0]); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <form method="POST" action="" class="chat-input">
                                <input type="hidden" name="receiver_id" value="<?php echo $other_user['id']; ?>">
                                <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                                <textarea name="message" placeholder="Type your message..." rows="1" required id="messageInput"></textarea>
                                <button type="submit">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="no-conversation">
                                <i class="fas fa-comments"></i>
                                <h4>Select a Conversation</h4>
                                <p>Choose a conversation from the list or start a new one from the marketplace</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/dashboard.js"></script>
    <script>
        // Auto-scroll to bottom of messages
        const chatMessages = document.getElementById('chatMessages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        // Auto-resize textarea
        const messageInput = document.getElementById('messageInput');
        if (messageInput) {
            messageInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 120) + 'px';
            });
        }
        
        // Mobile view functions
        function showConversations() {
            document.getElementById('conversationsList').classList.remove('hidden-mobile');
            document.getElementById('chatArea').classList.remove('active');
            window.history.pushState({}, '', 'messages.php');
        }
        
        // Search conversations
        document.getElementById('searchConversations')?.addEventListener('input', function(e) {
            const search = e.target.value.toLowerCase();
            document.querySelectorAll('.conversation-item').forEach(item => {
                const name = item.querySelector('h4').textContent.toLowerCase();
                item.style.display = name.includes(search) ? '' : 'none';
            });
        });
    </script>
</body>
</html>
