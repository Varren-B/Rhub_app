<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to continue']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$database = new Database();
$conn = $database->getConnection();

$user_id = $_SESSION['user_id'];
$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
$payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
$phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : 'Fee Payment';

// Validate inputs
if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid amount']);
    exit;
}

if (!in_array($payment_method, ['mtn', 'orange'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment method']);
    exit;
}

if (empty($phone_number) || !preg_match('/^6[0-9]{8}$/', $phone_number)) {
    echo json_encode(['success' => false, 'message' => 'Invalid phone number format']);
    exit;
}

// Generate unique transaction reference
$transaction_ref = strtoupper($payment_method) . '-' . date('YmdHis') . '-' . rand(1000, 9999);

try {
    // In a real application, you would integrate with MTN MoMo or Orange Money API here
    // For demonstration, we'll simulate a successful payment
    
    // Simulate API call delay
    usleep(500000); // 0.5 seconds
    
    // Simulate payment success (in production, this would be the API response)
    $payment_successful = true; // Set to true for demo purposes
    
    if ($payment_successful) {
        // Record the payment in database
        $stmt = $conn->prepare("
            INSERT INTO payments (student_id, amount, payment_method, phone_number, transaction_ref, status, description, created_at)
            VALUES (?, ?, ?, ?, ?, 'completed', ?, NOW())
        ");
        $stmt->execute([$student_id, $amount, $payment_method, $phone_number, $transaction_ref, $description]);
        
        // Update user's fee balance
        $stmt = $conn->prepare("
            UPDATE students 
            SET amount_paid = amount_paid + ?,
                last_payment_date = NOW()
            WHERE student_id = ?
        ");
        $stmt->execute([$amount, $student_id]);
        
        // Get updated balance
        $stmt = $conn->prepare("SELECT total_fees, amount_paid FROM students WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $remaining_balance = $student['total_fees'] - $student['amount_paid'];
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment successful!',
            'transaction_ref' => $transaction_ref,
            'amount_paid' => $amount,
            'new_balance' => $remaining_balance,
            'total_paid' => $student['amount_paid']
        ]);
    } else {
        // Record failed payment
        $stmt = $conn->prepare("
            INSERT INTO payments (user_id, amount, payment_method, phone_number, transaction_ref, status, description, created_at)
            VALUES (?, ?, ?, ?, ?, 'failed', ?, NOW())
        ");
        $stmt->execute([$user_id, $amount, $payment_method, $phone_number, $transaction_ref, $description]);
        
        echo json_encode([
            'success' => false,
            'message' => 'Payment failed. Please try again.'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
