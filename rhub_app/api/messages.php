<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to continue']);
    exit;
}

$database = new Database();
$conn = $database->getConnection();

$user_id = $_SESSION['user_id'];
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

switch ($action) {
    case 'get_conversations':
        getConversations($conn, $user_id);
        break;
    case 'get_messages':
        getMessages($conn, $user_id);
        break;
    case 'send_message':
        sendMessage($conn, $user_id);
        break;
    case 'mark_read':
        markAsRead($conn, $user_id);
        break;
    case 'get_unread_count':
        getUnreadCount($conn, $user_id);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function getConversations($conn, $user_id) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                c.id as conversation_id,
                c.item_id,
                c.created_at as conversation_started,
                CASE 
                    WHEN c.buyer_id = ? THEN c.seller_id 
                    ELSE c.buyer_id 
                END as other_user_id,
                u.full_name as other_user_name,
                i.title as item_title,
                i.image_url as item_image,
                (SELECT message FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
                (SELECT created_at FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message_time,
                (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND sender_id != ? AND is_read = 0) as unread_count
            FROM conversations c
            JOIN users u ON (CASE WHEN c.buyer_id = ? THEN c.seller_id ELSE c.buyer_id END) = u.id
            LEFT JOIN marketplace_items i ON c.item_id = i.id
            WHERE c.buyer_id = ? OR c.seller_id = ?
            ORDER BY last_message_time DESC
        ");
        $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id]);
        $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'conversations' => $conversations]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching conversations']);
    }
}

function getMessages($conn, $user_id) {
    $conversation_id = isset($_GET['conversation_id']) ? intval($_GET['conversation_id']) : 0;
    
    if ($conversation_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid conversation']);
        return;
    }
    
    try {
        // Verify user is part of this conversation
        $stmt = $conn->prepare("SELECT * FROM conversations WHERE id = ? AND (buyer_id = ? OR seller_id = ?)");
        $stmt->execute([$conversation_id, $user_id, $user_id]);
        
        if ($stmt->rowCount() == 0) {
            echo json_encode(['success' => false, 'message' => 'Conversation not found']);
            return;
        }
        
        // Get messages
        $stmt = $conn->prepare("
            SELECT 
                m.*,
                u.full_name as sender_name
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.conversation_id = ?
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$conversation_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Mark messages as read
        $stmt = $conn->prepare("
            UPDATE messages 
            SET is_read = 1 
            WHERE conversation_id = ? AND sender_id != ? AND is_read = 0
        ");
        $stmt->execute([$conversation_id, $user_id]);
        
        echo json_encode(['success' => true, 'messages' => $messages, 'current_user_id' => $user_id]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching messages']);
    }
}

function sendMessage($conn, $user_id) {
    $conversation_id = isset($_POST['conversation_id']) ? intval($_POST['conversation_id']) : 0;
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    $receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;
    $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : null;
    
    if (empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
        return;
    }
    
    try {
        // If no conversation exists, create one
        if ($conversation_id <= 0 && $receiver_id > 0) {
            // Check if conversation already exists for this item
            if ($item_id) {
                $stmt = $conn->prepare("
                    SELECT id FROM conversations 
                    WHERE item_id = ? AND 
                    ((buyer_id = ? AND seller_id = ?) OR (buyer_id = ? AND seller_id = ?))
                ");
                $stmt->execute([$item_id, $user_id, $receiver_id, $receiver_id, $user_id]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existing) {
                    $conversation_id = $existing['id'];
                }
            }
            
            // Create new conversation if none exists
            if ($conversation_id <= 0) {
                $stmt = $conn->prepare("
                    INSERT INTO conversations (buyer_id, seller_id, item_id, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->execute([$user_id, $receiver_id, $item_id]);
                $conversation_id = $conn->lastInsertId();
            }
        }
        
        // Verify user is part of this conversation
        $stmt = $conn->prepare("SELECT * FROM conversations WHERE id = ? AND (buyer_id = ? OR seller_id = ?)");
        $stmt->execute([$conversation_id, $user_id, $user_id]);
        
        if ($stmt->rowCount() == 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid conversation']);
            return;
        }
        
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
        $receiver_id = ($conversation['buyer_id'] == $user_id) ? $conversation['seller_id'] : $conversation['buyer_id'];
        
        // Insert message
        $stmt = $conn->prepare("
            INSERT INTO messages (conversation_id, sender_id, receiver_id, message, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$conversation_id, $user_id, $receiver_id, $message]);
        
        $message_id = $conn->lastInsertId();
        
        // Get the inserted message with sender info
        $stmt = $conn->prepare("
            SELECT m.*, u.full_name as sender_name
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.id = ?
        ");
        $stmt->execute([$message_id]);
        $new_message = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Message sent',
            'data' => $new_message,
            'conversation_id' => $conversation_id
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error sending message: ' . $e->getMessage()]);
    }
}

function markAsRead($conn, $user_id) {
    $conversation_id = isset($_POST['conversation_id']) ? intval($_POST['conversation_id']) : 0;
    
    try {
        $stmt = $conn->prepare("
            UPDATE messages 
            SET is_read = 1 
            WHERE conversation_id = ? AND sender_id != ?
        ");
        $stmt->execute([$conversation_id, $user_id]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error marking as read']);
    }
}

function getUnreadCount($conn, $user_id) {
    try {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count
            FROM messages m
            JOIN conversations c ON m.conversation_id = c.id
            WHERE m.receiver_id = ? AND m.is_read = 0
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'count' => $result['count']]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'count' => 0]);
    }
}
?>
