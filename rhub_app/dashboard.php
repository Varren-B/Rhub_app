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

// Get student fee info
$stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Check if user exists
if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit();
}

// Use the student record for fee summary (if exists, otherwise create default)
if (!$student) {
    $student = [
        'total_fees' => 350000.00,
        'amount_paid' => 0.00,
        'fee_status' => 'unpaid',
        'academic_year' => '2025-2026'
    ];
}

$fee = $student;
$fee['balance'] = $fee['total_fees'] - $fee['amount_paid'];
$fee['total_fee'] = $fee['total_fees'];

// Count marketplace items
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM marketplace_items WHERE seller_id = ? AND status = 'available'");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$my_items = $stmt->get_result()->fetch_assoc()['count'];

// Count unread messages
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$unread_messages = $stmt->get_result()->fetch_assoc()['count'];

// Count items sold
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM marketplace_items WHERE seller_id = ? AND status = 'sold'");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$items_sold = $stmt->get_result()->fetch_assoc()['count'];

// Recent transactions
$stmt = $conn->prepare("SELECT * FROM payments WHERE student_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$transactions = $stmt->get_result();

$conn->close();

// Calculate progress
$progress = 0;
if ($fee && $fee['total_fees'] > 0) {
    $progress = ($fee['amount_paid'] / $fee['total_fees']) * 100;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - RHUB Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Header -->
            <header class="top-header">
                <div class="header-left">
                    <button class="menu-toggle" id="menuToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="page-title">Dashboard</h1>
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
            
            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Welcome Message -->
                <div class="content-card" style="margin-bottom: 2rem;"> 
                    <div class="card-body" style="background: linear-gradient(135deg, var(--brick-red) 0%, var(--brick-red-dark) 100%); color: var(--cream-white);">
                        <h2 style="margin-bottom: 0.5rem;">Welcome back, <?php echo htmlspecialchars(explode(' ', $user['full_name'])[0]); ?>!</h2>
                        <p style="opacity: 0.9;">Department: <?php echo htmlspecialchars($user['department'] ?? ''); ?> | Level: <?php echo htmlspecialchars($user['level'] ?? ''); ?></p>
                    </div>
                </div>

                
                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon fees">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $fee ? formatCurrency($fee['balance']) : '0 FCFA'; ?></h3>
                            <p>Outstanding Balance</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon market">
                            <i class="fas fa-store"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $my_items; ?></h3>
                            <p>My Listed Items</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon messages">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $unread_messages; ?></h3>
                            <p>Unread Messages</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon sold">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $items_sold; ?></h3>
                            <p>Items Sold</p>
                        </div>
                    </div>
                </div>
                
                <!-- Fee Summary -->
                <div class="content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-file-invoice-dollar"></i> Fee Summary (<?php echo $fee ? $fee['academic_year'] : '2024-2025'; ?>)</h3>
                        <a href="fees.php" class="btn btn-primary btn-sm">View Details</a>
                    </div>
                    <div class="card-body">
                        <?php if ($fee): ?>
                            <div class="fee-summary">
                                <div class="fee-item">
                                    <h4><?php echo formatCurrency($fee['total_fee']); ?></h4>
                                    <p>Total Fee</p>
                                </div>
                                <div class="fee-item paid">
                                    <h4><?php echo formatCurrency($fee['amount_paid']); ?></h4>
                                    <p>Amount Paid</p>
                                </div>
                                <div class="fee-item balance">
                                    <h4><?php echo formatCurrency($fee['balance']); ?></h4>
                                    <p>Balance Due</p>
                                </div>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                            </div>
                            <p style="text-align: center; margin-top: 0.75rem; color: var(--text-light);">
                                <?php echo round($progress, 1); ?>% of fees paid
                            </p>
                        <?php else: ?>
                            <p style="text-align: center; color: var(--text-light);">No fee information available.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <a href="fees.php" class="action-btn">
                                <i class="fas fa-credit-card"></i>
                                <span>Pay Fees</span>
                            </a>
                            <a href="marketplace.php" class="action-btn">
                                <i class="fas fa-shopping-bag"></i>
                                <span>Browse Market</span>
                            </a>
                            <a href="sell-item.php" class="action-btn">
                                <i class="fas fa-plus-circle"></i>
                                <span>Sell Item</span>
                            </a>
                            <a href="messages.php" class="action-btn">
                                <i class="fas fa-comments"></i>
                                <span>Messages</span>
                            </a>
                            <a href="my-items.php" class="action-btn">
                                <i class="fas fa-box"></i>
                                <span>My Items</span>
                            </a>
                            <a href="profile.php" class="action-btn">
                                <i class="fas fa-user-cog"></i>
                                <span>Profile</span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Transactions -->
                <div class="content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-history"></i> Recent Transactions</h3>
                        <a href="fees.php" class="btn btn-primary btn-sm">View All</a>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <?php if ($transactions->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Reference</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($trans = $transactions->fetch_assoc()): ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($trans['transaction_ref']); ?></code></td>
                                            <td><?php echo formatCurrency($trans['amount']); ?></td>
                                            <td>
                                                <span class="payment-method <?php echo strtolower($trans['payment_method']); ?>">
                                                    <?php echo $trans['payment_method']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($trans['created_at'])); ?></td>
                                            <td>
                                                <span class="status-badge <?php echo $trans['status']; ?>">
                                                    <?php echo ucfirst($trans['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-receipt"></i>
                                <h4>No Transactions Yet</h4>
                                <p>Your payment transactions will appear here.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/dashboard.js"></script>
</body>
</html>
