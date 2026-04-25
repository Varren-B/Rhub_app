<?php
require_once 'config/database.php';
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

// Handle item actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $item_id = intval($_POST['item_id'] ?? 0);
    
    if ($item_id > 0) {
        // Verify ownership
        $stmt = $conn->prepare("SELECT id FROM marketplace_items WHERE id = ? AND seller_id = ?");
        $stmt->bind_param("ii", $item_id, $student_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            if ($action === 'mark_sold') {
                $stmt = $conn->prepare("UPDATE marketplace_items SET status = 'sold' WHERE id = ?");
                $stmt->bind_param("i", $item_id);
                $stmt->execute();
                $success_msg = 'Item marked as sold!';
            } elseif ($action === 'delete') {
                // Delete the item image if exists
                $stmt = $conn->prepare("SELECT image_url FROM marketplace_items WHERE id = ?");
                $stmt->bind_param("i", $item_id);
                $stmt->execute();
                $item = $stmt->get_result()->fetch_assoc();
                if ($item['image_url'] !== 'no-image.png' && file_exists('uploads/items/' . $item['image_url'])) {
                    unlink('uploads/items/' . $item['image_url']);
                }
                
                $stmt = $conn->prepare("DELETE FROM marketplace_items WHERE id = ?");
                $stmt->bind_param("i", $item_id);
                $stmt->execute();
                $success_msg = 'Item deleted successfully!';
            }
        }
    }
}

// Get filter
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';

// Get my items
$query = "SELECT * FROM marketplace_items WHERE seller_id = ?";
if ($status_filter === 'available') {
    $query .= " AND status = 'available'";
} elseif ($status_filter === 'sold') {
    $query .= " AND status = 'sold'";
}
$query .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$items = $stmt->get_result();

// Get counts
$stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM marketplace_items WHERE seller_id = ? GROUP BY status");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$counts = $stmt->get_result();
$status_counts = ['available' => 0, 'sold' => 0];
while ($row = $counts->fetch_assoc()) {
    $status_counts[$row['status']] = $row['count'];
}
$total_count = $status_counts['available'] + $status_counts['sold'];

// Get unread messages
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
    <title>My Items - RHUB Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/marketplace.css">
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
                    <h1 class="page-title">My Items</h1>
                </div>
                <div class="header-right">
                    <a href="sell-item.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Sell New
                    </a>
                    <a href="messages.php" class="notification-btn">
                        <i class="fas fa-envelope"></i>
                        <?php if ($unread_messages > 0): ?>
                            <span class="badge"><?php echo $unread_messages; ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="user-profile">
                        <div class="user-avatar">
                            <?php $currentAvatar = getUserAvatar($user); ?>
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
                <?php if (isset($success_msg)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $success_msg; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Filter Tabs -->
                <div class="content-card" style="margin-bottom: 1.5rem;">
                    <div class="card-body" style="padding: 1rem;">
                        <div class="my-items-header">
                            <div class="tab-buttons">
                                <a href="?status=all" class="tab-btn <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
                                    All (<?php echo $total_count; ?>)
                                </a>
                                <a href="?status=available" class="tab-btn <?php echo $status_filter === 'available' ? 'active' : ''; ?>">
                                    Available (<?php echo $status_counts['available']; ?>)
                                </a>
                                <a href="?status=sold" class="tab-btn <?php echo $status_filter === 'sold' ? 'active' : ''; ?>">
                                    Sold (<?php echo $status_counts['sold']; ?>)
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Items Grid -->
                <?php if ($items->num_rows > 0): ?>
                    <div class="items-grid">
                        <?php while ($item = $items->fetch_assoc()): ?>
                            <div class="item-card">
                                <div class="item-image">
                                    <?php if ($item['image_url'] && $item['image_url'] !== 'no-image.png'): ?>
                                        <img src="uploads/items/<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                    <?php else: ?>
                                        <i class="fas fa-image no-image"></i>
                                    <?php endif; ?>
                                    <?php if ($item['status'] === 'sold'): ?>
                                        <span class="item-badge sold">Sold</span>
                                    <?php endif; ?>
                                </div>
                                <div class="item-details">
                                    <span class="item-category"><?php echo htmlspecialchars($item['category']); ?></span>
                                    <h4><?php echo htmlspecialchars($item['title']); ?></h4>
                                    <p class="item-description"><?php echo htmlspecialchars($item['description']); ?></p>
                                    <div class="item-price"><?php echo formatCurrency($item['price']); ?></div>
                                    <div class="item-meta">
                                        <span class="status-badge <?php echo $item['status']; ?>">
                                            <?php echo ucfirst($item['status']); ?>
                                        </span>
                                        <div class="item-views">
                                            <i class="fas fa-eye"></i>
                                            <?php echo $item['views']; ?> views
                                        </div>
                                    </div>
                                </div>
                                <div class="item-actions">
                                    <?php if ($item['status'] === 'available'): ?>
                                        <form method="POST" action="" style="flex: 1;" onsubmit="return confirm('Mark this item as sold?');">
                                            <input type="hidden" name="action" value="mark_sold">
                                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" class="btn btn-success" style="width: 100%;">
                                                <i class="fas fa-check"></i> Mark Sold
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" action="" style="flex: 1;" onsubmit="return confirm('Are you sure you want to delete this item?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="btn btn-danger" style="width: 100%;">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="content-card">
                        <div class="card-body">
                            <div class="empty-state">
                                <i class="fas fa-box-open"></i>
                                <h4>No Items</h4>
                                <p>
                                    <?php if ($status_filter !== 'all'): ?>
                                        You have no <?php echo $status_filter; ?> items.
                                    <?php else: ?>
                                        You haven't listed any items yet.
                                    <?php endif; ?>
                                </p>
                                <a href="sell-item.php" class="btn btn-primary" style="margin-top: 1rem;">
                                    <i class="fas fa-plus"></i> List Your First Item
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="assets/js/dashboard.js"></script>
</body>
</html>
