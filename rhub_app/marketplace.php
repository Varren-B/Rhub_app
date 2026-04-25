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

// Get filter parameters
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$category = isset($_GET['category']) ? sanitize($_GET['category']) : '';

// Build query
$query = "SELECT mi.*, u.full_name as seller_name, u.department as seller_department 
          FROM marketplace_items mi 
          JOIN users u ON mi.seller_id = u.id 
          WHERE mi.status = 'available'";
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (mi.title LIKE ? OR mi.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if (!empty($category)) {
    $query .= " AND mi.category = ?";
    $params[] = $category;
    $types .= "s";
}

$query .= " ORDER BY mi.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$items = $stmt->get_result();

// Get categories with counts
$categories = $conn->query("SELECT category, COUNT(*) as count FROM marketplace_items WHERE status = 'available' GROUP BY category");
$cat_counts = [];
while ($cat = $categories->fetch_assoc()) {
    $cat_counts[$cat['category']] = $cat['count'];
}

// Get unread messages
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$unread_messages = $stmt->get_result()->fetch_assoc()['count'];

$conn->close();

$all_categories = ['Books', 'Electronics', 'Clothing', 'Furniture', 'Services', 'Other'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketplace - RHUB Portal</title>
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
                    <h1 class="page-title">Marketplace</h1>
                </div>
                <div class="header-right">
                    <a href="sell-item.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Sell Item
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
                <!-- Search and Filters -->
                <form method="GET" action="" class="search-filters">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Search items..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <select name="category" class="filter-select" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php foreach ($all_categories as $cat): ?>
                            <option value="<?php echo $cat; ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                                <?php echo $cat; ?> (<?php echo $cat_counts[$cat] ?? 0; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
                
                <!-- Categories Quick Filter -->
                <div class="categories-grid">
                    <a href="marketplace.php" class="category-btn <?php echo empty($category) ? 'active' : ''; ?>">
                        <i class="fas fa-th-large"></i>
                        <span>All Items</span>
                    </a>
                    <a href="?category=Books" class="category-btn <?php echo $category === 'Books' ? 'active' : ''; ?>">
                        <i class="fas fa-book"></i>
                        <span>Books</span>
                    </a>
                    <a href="?category=Electronics" class="category-btn <?php echo $category === 'Electronics' ? 'active' : ''; ?>">
                        <i class="fas fa-laptop"></i>
                        <span>Electronics</span>
                    </a>
                    <a href="?category=Clothing" class="category-btn <?php echo $category === 'Clothing' ? 'active' : ''; ?>">
                        <i class="fas fa-tshirt"></i>
                        <span>Clothing</span>
                    </a>
                    <a href="?category=Furniture" class="category-btn <?php echo $category === 'Furniture' ? 'active' : ''; ?>">
                        <i class="fas fa-couch"></i>
                        <span>Furniture</span>
                    </a>
                    <a href="?category=Services" class="category-btn <?php echo $category === 'Services' ? 'active' : ''; ?>">
                        <i class="fas fa-hands-helping"></i>
                        <span>Services</span>
                    </a>
                    <a href="sell-item.php" class="btn btn-primary" style="margin-top: 1rem; font-weight: bold;">
                                    <i class="fas fa-plus"></i> Sell Something
                                </a>
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
                                    <?php 
                                    $created = strtotime($item['created_at']);
                                    $now = time();
                                    $diff = $now - $created;
                                    if ($diff < 86400): // Less than 24 hours
                                    ?>
                                        <span class="item-badge new">New</span>
                                    <?php endif; ?>
                                </div>
                                <div class="item-details">
                                    <span class="item-category"><?php echo htmlspecialchars($item['category']); ?></span>
                                    <h4><?php echo htmlspecialchars($item['title']); ?></h4>
                                    <p class="item-description"><?php echo htmlspecialchars($item['description']); ?></p>
                                    <div class="item-price"><?php echo formatCurrency($item['price']); ?></div>
                                    <div class="item-meta">
                                        <div class="seller-info">
                                            <div class="seller-avatar">
                                                <?php echo strtoupper(substr($item['seller_name'], 0, 2)); ?>
                                            </div>
                                            <span class="seller-name"><?php echo htmlspecialchars(explode(' ', $item['seller_name'])[0]); ?></span>
                                        </div>
                                        <div class="item-views">
                                            <i class="fas fa-eye"></i>
                                            <?php echo $item['views']; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="item-actions">
                                    <a href="item-detail.php?id=<?php echo $item['id']; ?>" class="btn btn-outline">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <?php if ($item['seller_id'] != $student_id): ?>
                                        <a href="messages.php?to=<?php echo $item['seller_id']; ?>&item=<?php echo $item['id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-comment"></i> Contact
                                        </a>
                                    <?php else: ?>
                                        <span class="btn btn-outline" style="opacity: 0.5; cursor: default;">Your Item</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="content-card">
                        <div class="card-body">
                            <div class="empty-state">
                                <i class="fas fa-store"></i>
                                <h4>No Items Found</h4>
                                <p>
                                    <?php if (!empty($search) || !empty($category)): ?>
                                        No items match your search criteria. Try different filters.
                                    <?php else: ?>
                                        The marketplace is empty. Be the first to list an item!
                                    <?php endif; ?>
                                </p>
                                <a href="sell-item.php" class="btn btn-primary" style="margin-top: 1rem;">
                                    <i class="fas fa-plus"></i> Sell Something
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
