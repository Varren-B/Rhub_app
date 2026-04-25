<?php
require_once 'config/database.php';
requireLogin();

$conn = getDBConnection();
$student_id = getCurrentStudentId();

// Get item ID
$item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($item_id <= 0) {
    header('Location: marketplace.php');
    exit();
}

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

// Get item details
$stmt = $conn->prepare("SELECT mi.*, u.full_name as seller_name, u.email as seller_email, u.department as seller_department, u.level as seller_level 
                        FROM marketplace_items mi 
                        JOIN users u ON mi.seller_id = u.id 
                        WHERE mi.id = ?");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: marketplace.php');
    exit();
}

$item = $result->fetch_assoc();

// Increment view count (only for other users)
if ($item['seller_id'] != $student_id) {
    $stmt = $conn->prepare("UPDATE marketplace_items SET views = views + 1 WHERE id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $item['views']++;
}

// Get unread messages
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$unread_messages = $stmt->get_result()->fetch_assoc()['count'];

// Get similar items
$stmt = $conn->prepare("SELECT mi.*, u.full_name as seller_name 
                        FROM marketplace_items mi 
                        JOIN users u ON mi.seller_id = u.id 
                        WHERE mi.category = ? AND mi.id != ? AND mi.status = 'available' 
                        ORDER BY mi.created_at DESC LIMIT 4");
$stmt->bind_param("si", $item['category'], $item_id);
$stmt->execute();
$similar_items = $stmt->get_result();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($item['title']); ?> - RHUB Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/marketplace.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .item-detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .item-image-large {
            background: var(--gray-200);
            border-radius: 15px;
            overflow: hidden;
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .item-image-large img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .item-image-large .no-image {
            font-size: 6rem;
            color: var(--gray-300);
        }
        
        .item-info h1 {
            font-size: 1.75rem;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .item-info .category {
            display: inline-block;
            background: var(--brick-red);
            color: var(--cream-white);
            padding: 0.35rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }
        
        .item-info .price {
            font-size: 2rem;
            color: var(--brick-red);
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        
        .item-info .description {
            color: var(--text-light);
            line-height: 1.8;
            margin-bottom: 1.5rem;
        }
        
        .seller-card {
            background: var(--cream-light);
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 1.5rem;
        }
        
        .seller-card h4 {
            color: var(--text-dark);
            margin-bottom: 1rem;
        }
        
        .seller-card .seller-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .seller-card .seller-avatar {
            width: 50px;
            height: 50px;
            font-size: 1.25rem;
        }
        
        .seller-card .seller-details p {
            margin: 0;
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        .seller-card .seller-details h5 {
            margin: 0 0 0.25rem;
            color: var(--text-dark);
        }
        
        .item-meta-list {
            list-style: none;
            margin-bottom: 1.5rem;
        }
        
        .item-meta-list li {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .item-meta-list li:last-child {
            border-bottom: none;
        }
        
        .item-meta-list li i {
            color: var(--brick-red);
            width: 20px;
        }
        
        .contact-actions {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .contact-actions .btn {
            padding: 1rem;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .item-detail-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
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
                    <h1 class="page-title">Item Details</h1>
                </div>
                <div class="header-right">
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
                <a href="marketplace.php" style="display: inline-flex; align-items: center; gap: 0.5rem; color: var(--brick-red); margin-bottom: 1.5rem; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Back to Marketplace
                </a>
                
                <div class="content-card">
                    <div class="card-body">
                        <div class="item-detail-grid">
                            <div class="item-image-large">
                                <?php if ($item['image_url'] && $item['image_url'] !== 'no-image.png'): ?>
                                    <img src="uploads/items/<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                <?php else: ?>
                                    <i class="fas fa-image no-image"></i>
                                <?php endif; ?>
                            </div>
                            
                            <div class="item-info">
                                <span class="category"><?php echo htmlspecialchars($item['category']); ?></span>
                                <h1><?php echo htmlspecialchars($item['title']); ?></h1>
                                <div class="price"><?php echo formatCurrency($item['price']); ?></div>
                                
                                <p class="description"><?php echo nl2br(htmlspecialchars($item['description'])); ?></p>
                                
                                <ul class="item-meta-list">
                                    <li>
                                        <i class="fas fa-calendar"></i>
                                        <span>Listed <?php echo date('F d, Y', strtotime($item['created_at'])); ?></span>
                                    </li>
                                    <li>
                                        <i class="fas fa-eye"></i>
                                        <span><?php echo $item['views']; ?> views</span>
                                    </li>
                                    <li>
                                        <i class="fas fa-info-circle"></i>
                                        <span>Status: <strong><?php echo ucfirst($item['status']); ?></strong></span>
                                    </li>
                                </ul>
                                
                                <div class="seller-card">
                                    <h4>Seller Information</h4>
                                    <div class="seller-info">
                                        <div class="seller-avatar">
                                            <?php echo strtoupper(substr($item['seller_name'], 0, 2)); ?>
                                        </div>
                                        <div class="seller-details">
                                            <h5><?php echo htmlspecialchars($item['seller_name']); ?></h5>
                                            <p><?php echo htmlspecialchars($item['seller_department']); ?> - Level <?php echo htmlspecialchars($item['seller_level']); ?></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($item['seller_id'] != $student_id && $item['status'] === 'available'): ?>
                                    <div class="contact-actions">
                                        <a href="messages.php?to=<?php echo $item['seller_id']; ?>&item=<?php echo $item['id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-comment"></i> Contact Seller
                                        </a>
                                    </div>
                                <?php elseif ($item['seller_id'] == $student_id): ?>
                                    <div class="contact-actions">
                                        <div class="alert alert-success" style="text-align: center; margin: 0;">
                                            <i class="fas fa-info-circle"></i> This is your item listing
                                        </div>
                                        <a href="my-items.php" class="btn btn-primary">
                                            <i class="fas fa-cog"></i> Manage My Items
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-error" style="text-align: center;">
                                        <i class="fas fa-times-circle"></i> This item has been sold
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Similar Items -->
                <?php if ($similar_items->num_rows > 0): ?>
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-th-large"></i> Similar Items</h3>
                        </div>
                        <div class="card-body">
                            <div class="items-grid">
                                <?php while ($sim = $similar_items->fetch_assoc()): ?>
                                    <div class="item-card">
                                        <div class="item-image">
                                            <?php if ($sim['image'] && $sim['image'] !== 'no-image.png'): ?>
                                                <img src="uploads/items/<?php echo htmlspecialchars($sim['image']); ?>" alt="<?php echo htmlspecialchars($sim['title']); ?>">
                                            <?php else: ?>
                                                <i class="fas fa-image no-image"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="item-details">
                                            <h4><?php echo htmlspecialchars($sim['title']); ?></h4>
                                            <div class="item-price"><?php echo formatCurrency($sim['price']); ?></div>
                                        </div>
                                        <div class="item-actions">
                                            <a href="item-detail.php?id=<?php echo $sim['id']; ?>" class="btn btn-outline">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
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
