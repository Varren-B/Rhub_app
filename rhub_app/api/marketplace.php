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
    case 'get_items':
        getItems($conn, $user_id);
        break;
    case 'get_item':
        getItem($conn, $user_id);
        break;
    case 'add_item':
        addItem($conn, $user_id);
        break;
    case 'update_item':
        updateItem($conn, $user_id);
        break;
    case 'delete_item':
        deleteItem($conn, $user_id);
        break;
    case 'mark_sold':
        markAsSold($conn, $user_id);
        break;
    case 'request_to_buy':
        requestToBuy($conn, $user_id);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function getItems($conn, $user_id) {
    $category = isset($_GET['category']) ? $_GET['category'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $my_items = isset($_GET['my_items']) && $_GET['my_items'] == '1';
    
    try {
        $sql = "
            SELECT 
                i.*,
                u.full_name as seller_name,
                u.department as seller_department
            FROM marketplace_items i
            JOIN users u ON i.seller_id = u.id
            WHERE i.status = 'available'
        ";
        $params = [];
        
        if ($my_items) {
            $sql = "
                SELECT 
                    i.*,
                    u.full_name as seller_name,
                    u.department as seller_department
                FROM marketplace_items i
                JOIN users u ON i.seller_id = u.id
                WHERE i.seller_id = ?
            ";
            $params[] = $user_id;
        }
        
        if (!empty($category)) {
            $sql .= " AND i.category = ?";
            $params[] = $category;
        }
        
        if (!empty($search)) {
            $sql .= " AND (i.title LIKE ? OR i.description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $sql .= " ORDER BY i.created_at DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'items' => $items]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching items']);
    }
}

function getItem($conn, $user_id) {
    $item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                i.*,
                u.full_name as seller_name,
                u.department as seller_department,
                u.email as seller_email
            FROM marketplace_items i
            JOIN users u ON i.seller_id = u.id
            WHERE i.id = ?
        ");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($item) {
            $item['is_owner'] = ($item['seller_id'] == $user_id);
            echo json_encode(['success' => true, 'item' => $item]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Item not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching item']);
    }
}

function addItem($conn, $user_id) {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    $category = isset($_POST['category']) ? $_POST['category'] : '';
    $condition = isset($_POST['condition']) ? $_POST['condition'] : 'used';
    
    // Validate inputs
    if (empty($title) || empty($description) || $price <= 0 || empty($category)) {
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
        return;
    }
    
    try {
        // Handle image upload
        $image_url = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $new_filename = 'item_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                $upload_dir = '../uploads/items/';
                
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_filename)) {
                    $image_url = 'uploads/items/' . $new_filename;
                }
            }
        }
        
        $stmt = $conn->prepare("
            INSERT INTO marketplace_items (seller_id, title, description, price, category, item_condition, image_url, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'available', NOW())
        ");
        $stmt->execute([$user_id, $title, $description, $price, $category, $condition, $image_url]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Item listed successfully!',
            'item_id' => $conn->lastInsertId()
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error adding item: ' . $e->getMessage()]);
    }
}

function updateItem($conn, $user_id) {
    $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    $category = isset($_POST['category']) ? $_POST['category'] : '';
    
    // Verify ownership
    try {
        $stmt = $conn->prepare("SELECT * FROM marketplace_items WHERE id = ? AND seller_id = ?");
        $stmt->execute([$item_id, $user_id]);
        
        if ($stmt->rowCount() == 0) {
            echo json_encode(['success' => false, 'message' => 'Item not found or access denied']);
            return;
        }
        
        $stmt = $conn->prepare("
            UPDATE marketplace_items 
            SET title = ?, description = ?, price = ?, category = ?, updated_at = NOW()
            WHERE id = ? AND seller_id = ?
        ");
        $stmt->execute([$title, $description, $price, $category, $item_id, $user_id]);
        
        echo json_encode(['success' => true, 'message' => 'Item updated successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error updating item']);
    }
}

function deleteItem($conn, $user_id) {
    $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
    
    try {
        // Verify ownership
        $stmt = $conn->prepare("SELECT * FROM marketplace_items WHERE id = ? AND seller_id = ?");
        $stmt->execute([$item_id, $user_id]);
        
        if ($stmt->rowCount() == 0) {
            echo json_encode(['success' => false, 'message' => 'Item not found or access denied']);
            return;
        }
        
        // Delete related conversations and messages first
        $stmt = $conn->prepare("DELETE FROM messages WHERE conversation_id IN (SELECT id FROM conversations WHERE item_id = ?)");
        $stmt->execute([$item_id]);
        
        $stmt = $conn->prepare("DELETE FROM conversations WHERE item_id = ?");
        $stmt->execute([$item_id]);
        
        // Delete the item
        $stmt = $conn->prepare("DELETE FROM marketplace_items WHERE id = ?");
        $stmt->execute([$item_id]);
        
        echo json_encode(['success' => true, 'message' => 'Item deleted successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error deleting item']);
    }
}

function markAsSold($conn, $user_id) {
    $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
    $buyer_id = isset($_POST['buyer_id']) ? intval($_POST['buyer_id']) : null;
    
    try {
        // Verify ownership
        $stmt = $conn->prepare("SELECT * FROM marketplace_items WHERE id = ? AND seller_id = ?");
        $stmt->execute([$item_id, $user_id]);
        
        if ($stmt->rowCount() == 0) {
            echo json_encode(['success' => false, 'message' => 'Item not found or access denied']);
            return;
        }
        
        // Mark as sold
        $stmt = $conn->prepare("
            UPDATE marketplace_items 
            SET status = 'sold', buyer_id = ?, sold_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$buyer_id, $item_id]);
        
        // Auto-delete sold items after marking (as per requirements)
        // We'll keep a record but mark it as sold
        $stmt = $conn->prepare("DELETE FROM marketplace_items WHERE id = ? AND status = 'sold'");
        $stmt->execute([$item_id]);
        
        echo json_encode(['success' => true, 'message' => 'Item marked as sold and removed from marketplace']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error marking item as sold']);
    }
}

function requestToBuy($conn, $user_id) {
    $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
    $message = isset($_POST['message']) ? trim($_POST['message']) : "Hi, I'm interested in buying this item.";
    
    try {
        // Get item details
        $stmt = $conn->prepare("SELECT * FROM marketplace_items WHERE id = ? AND status = 'available'");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$item) {
            echo json_encode(['success' => false, 'message' => 'Item not available']);
            return;
        }
        
        if ($item['seller_id'] == $user_id) {
            echo json_encode(['success' => false, 'message' => 'You cannot buy your own item']);
            return;
        }
        
        // Check if conversation already exists
        $stmt = $conn->prepare("
            SELECT id FROM conversations 
            WHERE item_id = ? AND buyer_id = ? AND seller_id = ?
        ");
        $stmt->execute([$item_id, $user_id, $item['seller_id']]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            $conversation_id = $existing['id'];
        } else {
            // Create conversation
            $stmt = $conn->prepare("
                INSERT INTO conversations (buyer_id, seller_id, item_id, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$user_id, $item['seller_id'], $item_id]);
            $conversation_id = $conn->lastInsertId();
        }
        
        // Send message
        $stmt = $conn->prepare("
            INSERT INTO messages (conversation_id, sender_id, receiver_id, message, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$conversation_id, $user_id, $item['seller_id'], $message]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Request sent! Check your messages to continue the conversation.',
            'conversation_id' => $conversation_id
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error sending request: ' . $e->getMessage()]);
    }
}
?>
