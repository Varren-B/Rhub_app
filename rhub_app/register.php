<?php
require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDBConnection();
    
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $department = sanitize($_POST['department']);
    $level = sanitize($_POST['level']);
    $matricule = sanitize($_POST['matricule']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($full_name) || empty($email) || empty($department) || empty($level) || empty($matricule) || empty($password)) {
        $error = 'Please fill in all fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Email is already registered';
        } else {
            // Check if matricule exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE matricule = ?");
            $stmt->bind_param("s", $matricule);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error = 'Matricule number is already registered';
            } else {
                // Insert new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, department, level, matricule) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $full_name, $email, $hashed_password, $department, $level, $matricule);

                
                // Get the inserted student_id
                $student_id = $conn->insert_id;
                
                if ($stmt->execute()) {
                    $student_id = $conn->insert_id;
                    $stmt->close();  // Close the users statement

                    // NOW prepare the students INSERT with valid $student_id
                    $amount_paid = 0;
                    $academic_year = '2025-2026';
                    $created_at = date('Y-m-d H:i:s');
                    $updated_at = date('Y-m-d H:i:s');

                    $stmt = $conn->prepare("INSERT INTO students (student_id, total_fees, amount_paid, last_payment_date, academic_year, fee_status, created_at, updated_at) VALUES (?, 350000.00, ?, NULL, ?, 'unpaid', ?, ?)");
                    $stmt->bind_param("idsss", $student_id, $amount_paid, $academic_year, $created_at, $updated_at);
                    $stmt->execute();
                    $stmt->close();  // Close the students statement
                    
                    // Create initial fee record for the student
                    $amount = 0.0;
                    $payment_method = 'mtn';
                    $phone_number = '0000000000';
                    $transaction_ref = 'INIT-' . $student_id . '-' . time();
                    $status = 'pending';
                    $description = 'Initial fee record';
                    $created_at = date('Y-m-d H:i:s');

                    $stmt = $conn->prepare("INSERT INTO payments (student_id, amount, payment_method, phone_number, transaction_ref, status, description, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("idssssss", $student_id, $amount, $payment_method, $phone_number, $transaction_ref, $status, $description, $created_at);
                    $stmt->execute();
                    $stmt->close();  // Close the payments statement
                    
                    $conn->close();
                    header('Location: login.php');
                    exit();
                } else {
                    $error = 'Registration failed. Please try again.';
                    $stmt->close();
                }
            }
        }
    }
    $conn->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - RHUB Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <a href="index.php" class="back-home">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>
        
        <div class="auth-card wide">
            <div class="auth-header">
                <div class="auth-logo">RH</div>
                <h1>Create Account</h1>
                <p>Join the RHIMBS student community</p>
            </div>
            
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
            
            <form method="POST" action="" id="registerForm">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Full Name</label>
                    <input type="text" name="full_name" class="form-control" placeholder="Enter your full name" required value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="your.email@example.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-building"></i> Department</label>
                        <select name="department" class="form-control" required>
                            <option value="">Select Department</option>
                            <option value="Computer Science" <?php echo (isset($_POST['department']) && $_POST['department'] === 'Computer Science') ? 'selected' : ''; ?>>Computer Science</option>
                            <option value="Business Administration" <?php echo (isset($_POST['department']) && $_POST['department'] === 'Business Administration') ? 'selected' : ''; ?>>Business Administration</option>
                            <option value="Accounting" <?php echo (isset($_POST['department']) && $_POST['department'] === 'Accounting') ? 'selected' : ''; ?>>Accounting</option>
                            <option value="Marketing" <?php echo (isset($_POST['department']) && $_POST['department'] === 'Marketing') ? 'selected' : ''; ?>>Marketing</option>
                            <option value="Banking and Finance" <?php echo (isset($_POST['department']) && $_POST['department'] === 'Banking and Finance') ? 'selected' : ''; ?>>Banking and Finance</option>
                            <option value="Human Resource Management" <?php echo (isset($_POST['department']) && $_POST['department'] === 'Human Resource Management') ? 'selected' : ''; ?>>Human Resource Management</option>
                            <option value="Logistics" <?php echo (isset($_POST['department']) && $_POST['department'] === 'Logistics') ? 'selected' : ''; ?>>Logistics</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-layer-group"></i> Level</label>
                        <select name="level" class="form-control" required>
                            <option value="">Select Level</option>
                            <option value="100" <?php echo (isset($_POST['level']) && $_POST['level'] === '100') ? 'selected' : ''; ?>>Level 100</option>
                            <option value="200" <?php echo (isset($_POST['level']) && $_POST['level'] === '200') ? 'selected' : ''; ?>>Level 200</option>
                            <option value="300" <?php echo (isset($_POST['level']) && $_POST['level'] === '300') ? 'selected' : ''; ?>>Level 300</option>
                            <option value="400" <?php echo (isset($_POST['level']) && $_POST['level'] === '400') ? 'selected' : ''; ?>>Level 400</option>
                            <option value="500" <?php echo (isset($_POST['level']) && $_POST['level'] === '500') ? 'selected' : ''; ?>>Level 500 (Masters)</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-id-card"></i> Matricule Number</label>
                    <input type="text" name="matricule" class="form-control" placeholder="e.g., RHIMBS/2024/001" required value="<?php echo isset($_POST['matricule']) ? htmlspecialchars($_POST['matricule']) : ''; ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Password</label>
                        <div class="password-toggle">
                            <input type="password" name="password" id="password" class="form-control" placeholder="Min. 6 characters" required>
                            <button type="button" class="toggle-btn" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Confirm Password</label>
                        <div class="password-toggle">
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm password" required>
                            <button type="button" class="toggle-btn" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn-auth">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>
            
            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Sign in here</a></p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
