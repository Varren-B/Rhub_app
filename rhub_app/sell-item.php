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

// Get unread messages
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$unread_messages = $stmt->get_result()->fetch_assoc()['count'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $price = floatval($_POST['price']);
    $category = sanitize($_POST['category']);
    
    if (empty($title) || empty($description) || empty($category)) {
        $error = 'Please fill in all required fields';
    } elseif ($price <= 0) {
        $error = 'Please enter a valid price';
    } else {
        $image = 'no-image.png';
        
        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($_FILES['image']['type'], $allowed)) {
                $error = 'Only JPG, PNG, GIF, and WebP images are allowed';
            } elseif ($_FILES['image']['size'] > $max_size) {
                $error = 'Image size must be less than 5MB';
            } else {
                $upload_dir = 'uploads/items/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $image = 'item_' . $student_id . '_' . time() . '.' . $ext;
                $target = $upload_dir . $image;
                
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                    $error = 'Failed to upload image. Please try again.';
                    $image = 'no-image.png';
                }
            }
        }
        
        if (empty($error)) {
            $stmt = $conn->prepare("INSERT INTO marketplace_items (seller_id, title, description, price, category, image_url) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issdss", $student_id, $title, $description, $price, $category, $image);
            
            if ($stmt->execute()) {
                $success = 'Item listed successfully! It is now visible in the marketplace.';
                // Clear form
                $_POST = [];
            } else {
                $error = 'Failed to list item. Please try again.';
            }
        }
    }
}

$conn->close();

$categories = ['Books', 'Electronics', 'Clothing', 'Furniture', 'Services', 'Other'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sell Item - RHUB Portal</title>
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
                    <h1 class="page-title">Sell an Item</h1>
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
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $success; ?>
                        <a href="my-items.php" style="margin-left: 1rem; color: inherit; text-decoration: underline;">View My Items</a>
                    </div>
                <?php endif; ?>
                
                <div class="content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-tag"></i> Item Details</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" enctype="multipart/form-data" class="sell-form">
                            <div class="form-group">
                                <label><i class="fas fa-heading"></i> Item Title *</label>
                                <input type="text" name="title" class="form-control" 
                                       placeholder="e.g., Introduction to Computer Science Textbook" required
                                       value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label><i class="fas fa-list"></i> Category *</label>
                                    <select name="category" class="form-control" required>
                                        <option value="">Select a category</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat; ?>" 
                                                <?php echo (isset($_POST['category']) && $_POST['category'] === $cat) ? 'selected' : ''; ?>>
                                                <?php echo $cat; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-money-bill"></i> Price (FCFA) *</label>
                                    <input type="number" name="price" class="form-control" 
                                           placeholder="e.g., 5000" required min="100" step="100"
                                           value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-align-left"></i> Description *</label>
                                <textarea name="description" class="form-control" 
                                          placeholder="Describe your item in detail. Include condition, features, and any other relevant information." 
                                          required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-camera"></i> Item Photo (Optional)</label>
                                <label class="image-upload" id="imageUpload">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p>Click to upload an image</p>
                                    <small>JPG, PNG, GIF, or WebP (max 5MB)</small>
                                    <input type="file" name="image" accept="image/*" id="imageInput">
                                    <div class="image-preview" id="imagePreview">
                                        <img src="" alt="Preview" id="previewImg">
                                    </div>
                                </label>
                            </div>
                            
                            <div class="form-group" style="display: flex; gap: 1rem; flex-wrap: wrap;">
                                <button type="submit" class="btn btn-primary" style="flex: 1; min-width: 200px;">
                                    <i class="fas fa-check"></i> List Item for Sale
                                </button>
                                <a href="marketplace.php" class="btn btn-secondary" style="flex: 1; min-width: 200px; text-align: center;">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Tips Card -->
                <div class="content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-lightbulb"></i> Selling Tips</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-boxes" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                            <div class="info-box" style="background: var(--cream-light); padding: 1.5rem; border-radius: 10px;">
                                <h4 style="color: var(--brick-red); margin-bottom: 0.5rem;"><i class="fas fa-camera"></i> Good Photos</h4>
                                <p style="color: var(--text-light); font-size: 0.9rem;">Clear, well-lit photos help sell items faster.</p>
                            </div>
                            <div class="info-box" style="background: var(--cream-light); padding: 1.5rem; border-radius: 10px;">
                                <h4 style="color: var(--brick-red); margin-bottom: 0.5rem;"><i class="fas fa-tag"></i> Fair Pricing</h4>
                                <p style="color: var(--text-light); font-size: 0.9rem;">Research similar items to set competitive prices.</p>
                            </div>
                            <div class="info-box" style="background: var(--cream-light); padding: 1.5rem; border-radius: 10px;">
                                <h4 style="color: var(--brick-red); margin-bottom: 0.5rem;"><i class="fas fa-info-circle"></i> Be Honest</h4>
                                <p style="color: var(--text-light); font-size: 0.9rem;">Accurately describe condition and any defects.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/dashboard.js"></script>
    <script>
        // Image preview
        document.getElementById('imageInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImg').src = e.target.result;
                    document.getElementById('imagePreview').style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
