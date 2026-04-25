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

// Get all payment records
$stmt = $conn->prepare("SELECT p.* FROM payments p WHERE p.student_id = ? ORDER BY p.created_at DESC");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$transactions = $stmt->get_result();

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
    <title>Payment History - RHUB Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/fees.css">
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
                    <h1 class="page-title">Payment History</h1>
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
                <div class="content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-history"></i> All Payment Transactions</h3>
                        <a href="fees.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Make Payment
                        </a>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <?php if ($transactions->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Reference</th>
                                            <th>Description</th>
                                            <th>Amount</th>
                                            <th>Method</th>
                                            <th>Phone</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($trans = $transactions->fetch_assoc()): ?>
                                            <tr>
                                                <td><code style="font-size: 0.8rem;"><?php echo htmlspecialchars($trans['transaction_ref']); ?></code></td>
                                                <td><?php echo htmlspecialchars($trans['description']); ?></td>
                                                <td><strong><?php echo formatCurrency($trans['amount']); ?></strong></td>
                                                <td>
                                                    <span class="payment-method <?php echo strtolower($trans['payment_method']); ?>">
                                                        <?php echo $trans['payment_method']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($trans['phone_number']); ?></td>
                                                <td>
                                                    <?php echo date('M d, Y', strtotime($trans['created_at'])); ?>
                                                    <br>
                                                    <small style="color: var(--text-light);"><?php echo date('h:i A', strtotime($trans['created_at'])); ?></small>
                                                </td>
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
                                <h4>No Payment History</h4>
                                <p>You haven't made any payments yet.</p>
                                <a href="fees.php" class="btn btn-primary" style="margin-top: 1rem;">Make Your First Payment</a>
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
