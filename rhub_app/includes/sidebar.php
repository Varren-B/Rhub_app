<?php
require_once 'helpers.php';

// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);

// Get unread message count
$conn_sidebar = getDBConnection();
$stmt_sidebar = $conn_sidebar->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0");
$stmt_sidebar->bind_param("i", $_SESSION['student_id']);
$stmt_sidebar->execute();
$unread_count = $stmt_sidebar->get_result()->fetch_assoc()['count'];
$conn_sidebar->close();
?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">RH</div>
        <div class="sidebar-brand">
            <h2>RHUB</h2>
            <span>Student Portal</span>
        </div>
        <div class="sidebar-user">
            <?php
            $user = [
                'id' => $_SESSION['student_id'],
                'full_name' => $_SESSION['student_name'],
                'profile_image' => $_SESSION['student_image'] ?? null,
                'matricule' => $_SESSION['student_matricule'] ?? null,
                'department' => $_SESSION['student_department'] ?? null,
                'level' => $_SESSION['student_level'] ?? null,
            ];
            $currentAvatar = getUserAvatar($user);
            ?>
            <?php if ($currentAvatar): ?>
                <img src="<?php echo htmlspecialchars($currentAvatar); ?>" alt="Profile" class="sidebar-profile-img">
            <?php else: ?>
                <div class="sidebar-profile-placeholder"><?php echo getUserInitials($user['full_name']); ?></div>
            <?php endif; ?>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <div class="nav-section">
            <span class="nav-section-title">Main Menu</span>
        </div>
        
        <a href="dashboard.php" class="nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        
        <div class="nav-section">
            <span class="nav-section-title">Fee Payment</span>
        </div>
        
        <a href="fees.php" class="nav-item <?php echo $current_page == 'fees.php' ? 'active' : ''; ?>">
            <i class="fas fa-credit-card"></i>
            <span>Pay Fees</span>
        </a>
        
        <a href="payment-history.php" class="nav-item <?php echo $current_page == 'payment-history.php' ? 'active' : ''; ?>">
            <i class="fas fa-history"></i>
            <span>Payment History</span>
        </a>
        
        <div class="nav-section">
            <span class="nav-section-title">Marketplace</span>
        </div>
        
        <a href="marketplace.php" class="nav-item <?php echo $current_page == 'marketplace.php' ? 'active' : ''; ?>">
            <i class="fas fa-store"></i>
            <span>Browse Items</span>
        </a>
        
        <!-- <a href="sell-item.php" class="nav-item <?php echo $current_page == 'sell-item.php' ? 'active' : ''; ?>">
            <i class="fas fa-plus-circle"></i>
            <span>Sell Item</span>
        </a> -->
        
        <a href="my-items.php" class="nav-item <?php echo $current_page == 'my-items.php' ? 'active' : ''; ?>">
            <i class="fas fa-box"></i>
            <span>My Items</span>
        </a>
        
        <div class="nav-section">
            <span class="nav-section-title">Communication</span>
        </div>
        
        <a href="messages.php" class="nav-item <?php echo $current_page == 'messages.php' ? 'active' : ''; ?>">
            <i class="fas fa-comments"></i>
            <span>Messages</span>
            <?php if ($unread_count > 0): ?>
                <span class="badge"><?php echo $unread_count; ?></span>
            <?php endif; ?>
        </a>
        
        <div class="nav-section">
            <span class="nav-section-title">Account</span>
        </div>
        
        <a href="profile.php" class="nav-item <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-user"></i>
            <span>My Profile</span>
        </a>
        
        <a href="logout.php" class="nav-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </nav>
</aside>
