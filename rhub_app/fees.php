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

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit();
}

if (!$student) {
    $student = [
        'academic_year' => date('Y') . '-' . (date('Y') + 1),
        'total_fees' => 350000.00,
        'amount_paid' => 0.00,
        'fee_status' => 'unpaid'
    ];
}

// Get current fee summary from the students table
$current_fee = [
    'academic_year' => $student['academic_year'] ?? date('Y') . '-' . (date('Y') + 1),
    'semester' => 'Current',
    'total_fee' => $student['total_fees'],
    'amount_paid' => $student['amount_paid'],
    'balance' => max(0, $student['total_fees'] - $student['amount_paid']),
    'status' => $student['fee_status']
];

// Get unread messages
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$unread_messages = $stmt->get_result()->fetch_assoc()['count'];

$error = '';
$success = '';

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount']);
    $payment_method = sanitize($_POST['payment_method']);
    $phone_number = sanitize($_POST['phone_number']);
    
    if ($amount <= 0) {
        $error = 'Please enter a valid amount';
    } elseif ($current_fee && $amount > $current_fee['balance']) {
        $error = 'Amount cannot exceed your balance of ' . formatCurrency($current_fee['balance']);
    } elseif (!in_array($payment_method, ['MTN', 'ORANGE'])) {
        $error = 'Please select a valid payment method';
    } elseif (empty($phone_number) || strlen(preg_replace('/\s/', '', $phone_number)) < 9) {
        $error = 'Please enter a valid phone number';
    } else {
        // Create transaction record
        $transaction_ref = generateTransactionRef();
        $status = 'completed';
        $description = 'Fee payment';

        $stmt = $conn->prepare("INSERT INTO payments (student_id, amount, payment_method, phone_number, transaction_ref, status, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("idsssss", $student_id, $amount, $payment_method, $phone_number, $transaction_ref, $status, $description);
        
        if ($stmt->execute()) {
            $new_paid = $student['amount_paid'] + $amount;
            $new_status = $new_paid >= $student['total_fees'] ? 'paid' : ($new_paid > 0 ? 'partial' : 'unpaid');
            $now = date('Y-m-d H:i:s');
            
            $stmt = $conn->prepare("UPDATE students SET amount_paid = ?, fee_status = ?, last_payment_date = ? WHERE student_id = ?");
            $stmt->bind_param("dssi", $new_paid, $new_status, $now, $student_id);
            $stmt->execute();
            
            $success = 'Payment of ' . formatCurrency($amount) . ' successful! Reference: ' . $transaction_ref;
            
            $student['amount_paid'] = $new_paid;
            $student['fee_status'] = $new_status;
            $current_fee['amount_paid'] = $new_paid;
            $current_fee['balance'] = max(0, $student['total_fees'] - $new_paid);
            $current_fee['status'] = $new_status;
        } else {
            $error = 'Payment failed. Please try again.';
        }
    }
}

$conn->close();

// Calculate progress
$progress = 0;
if ($current_fee && $current_fee['total_fee'] > 0) {
    $progress = ($current_fee['amount_paid'] / $current_fee['total_fee']) * 100;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Payment - RHUB Portal</title>
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
                    <h1 class="page-title">Fee Payment</h1>
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
                    </div>
                <?php endif; ?>
                
                <!-- Student Info Card -->
                <div class="content-card student-info-card">
                    <div class="card-body">
                        <div class="student-info-grid">
                            <div class="info-item">
                                <span class="label">Student Name</span>
                                <span class="value"><?php echo htmlspecialchars($user['full_name']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Matricule</span>
                                <span class="value"><?php echo htmlspecialchars($user['matricule'] ?? ''); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Department</span>
                                <span class="value"><?php echo htmlspecialchars($user['department'] ?? ''); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Level</span>
                                <span class="value">Level <?php echo htmlspecialchars($user['level'] ?? ''); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($current_fee): ?>
                    <!-- Fee Summary -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-file-invoice-dollar"></i> Fee Summary - <?php echo $current_fee['academic_year']; ?> (<?php echo $current_fee['semester']; ?>)</h3>
                        </div>
                        <div class="card-body">
                            <div class="fee-overview">
                                <div class="fee-box total">
                                    <i class="fas fa-wallet"></i>
                                    <h4><?php echo formatCurrency($current_fee['total_fee']); ?></h4>
                                    <p>Total Fee</p>
                                </div>
                                <div class="fee-box paid">
                                    <i class="fas fa-check-double"></i>
                                    <h4><?php echo formatCurrency($current_fee['amount_paid']); ?></h4>
                                    <p>Amount Paid</p>
                                </div>
                                <div class="fee-box balance">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <h4><?php echo formatCurrency($current_fee['balance']); ?></h4>
                                    <p>Balance Due</p>
                                </div>
                            </div>
                            
                            <div class="progress-section">
                                <div class="progress-header">
                                    <span>Payment Progress</span>
                                    <span><?php echo round($progress, 1); ?>%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                                </div>
                                <p class="progress-status">
                                    Status: <span class="status-badge <?php echo $current_fee['status']; ?>"><?php echo ucfirst($current_fee['status']); ?></span>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment Form -->
                    <?php if ($current_fee['balance'] > 0): ?>
                        <div class="content-card payment-form-card">
                            <div class="card-header">
                                <h3><i class="fas fa-credit-card"></i> Make a Payment</h3>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="" id="paymentForm">
                                    <div class="payment-methods">
                                        <label class="method-card">
                                            <input type="radio" name="payment_method" value="MTN" required>
                                            <div class="method-content mtn">
                                                <div class="method-icon">
                                                    <span class="mtn-badge">MTN</span>
                                                </div>
                                                <h4>MTN Mobile Money</h4>
                                                <p>Pay with your MTN MoMo wallet</p>
                                            </div>
                                        </label>
                                        <label class="method-card">
                                            <input type="radio" name="payment_method" value="ORANGE" required>
                                            <div class="method-content orange">
                                                <div class="method-icon">
                                                    <span class="orange-badge">Orange</span>
                                                </div>
                                                <h4>Orange Money</h4>
                                                <p>Pay with your Orange Money wallet</p>
                                            </div>
                                        </label>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label><i class="fas fa-money-bill"></i> Amount (FCFA)</label>
                                            <input type="number" name="amount" class="form-control" 
                                                   placeholder="Enter amount" required 
                                                   min="1000" max="<?php echo $current_fee['balance']; ?>"
                                                   step="100">
                                            <small>Minimum: 1,000 FCFA | Maximum: <?php echo formatCurrency($current_fee['balance']); ?></small>
                                        </div>
                                        <div class="form-group">
                                            <label><i class="fas fa-phone"></i> Phone Number</label>
                                            <input type="tel" name="phone_number" class="form-control" 
                                                   placeholder="e.g., 6XX XXX XXX" required
                                                   pattern="[0-9\s]{9,}">
                                            <small>Enter the phone number linked to your mobile money account</small>
                                        </div>
                                    </div>
                                    
                                    <div class="quick-amounts">
                                        <span>Quick amounts:</span>
                                        <button type="button" class="quick-btn" data-amount="10000">10,000</button>
                                        <button type="button" class="quick-btn" data-amount="25000">25,000</button>
                                        <button type="button" class="quick-btn" data-amount="50000">50,000</button>
                                        <button type="button" class="quick-btn" data-amount="<?php echo $current_fee['balance']; ?>">Full Balance</button>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-lock"></i> Process Secure Payment
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="content-card">
                            <div class="card-body text-center" style="padding: 3rem;">
                                <i class="fas fa-check-circle" style="font-size: 4rem; color: var(--success); margin-bottom: 1rem;"></i>
                                <h3 style="color: var(--success); margin-bottom: 0.5rem;">Fees Fully Paid!</h3>
                                <p style="color: var(--text-light);">You have no outstanding balance for this semester.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="content-card">
                        <div class="card-body">
                            <div class="empty-state">
                                <i class="fas fa-file-invoice"></i>
                                <h4>No Fee Information</h4>
                                <p>Fee information for the current semester is not yet available.</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Payment Information -->
                <div class="content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-info-circle"></i> Payment Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-boxes">
                            <div class="info-box">
                                <h4><i class="fas fa-mobile-alt"></i> How to Pay</h4>
                                <ol>
                                    <li>Select your payment method (MTN or Orange)</li>
                                    <li>Enter the amount you wish to pay</li>
                                    <li>Enter your mobile money phone number</li>
                                    <li>Click "Process Secure Payment"</li>
                                    <li>Confirm the payment on your phone</li>
                                </ol>
                            </div>
                            <div class="info-box">
                                <h4><i class="fas fa-shield-alt"></i> Security</h4>
                                <p>All payments are processed securely through MTN and Orange official payment gateways. Your financial information is encrypted and protected.</p>
                            </div>
                            <div class="info-box">
                                <h4><i class="fas fa-headset"></i> Need Help?</h4>
                                <p>If you experience any issues with your payment, please contact the RHIMBS accounts office or email: accounts@rhimbs.edu</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/dashboard.js"></script>
    <script>
        // Quick amount buttons
        document.querySelectorAll('.quick-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const amount = this.dataset.amount;
                document.querySelector('input[name="amount"]').value = amount;
            });
        });
        
        // Payment method selection visual feedback
        document.querySelectorAll('.method-card input').forEach(input => {
            input.addEventListener('change', function() {
                document.querySelectorAll('.method-card').forEach(card => {
                    card.classList.remove('selected');
                });
                if (this.checked) {
                    this.closest('.method-card').classList.add('selected');
                }
            });
        });
    </script>
</body>
</html>
