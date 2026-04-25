<?php
require_once 'config/database.php';
requireLogin();

$conn = getDBConnection();
$student_id = getCurrentStudentId();

// Get student info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

$error = '';
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $full_name = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $department = sanitize($_POST['department']);
        $level = sanitize($_POST['level']);
        
        if (empty($full_name) || empty($email)) {
            $error = 'Name and email are required';
        } else {
            // Check email uniqueness
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->bind_param("si", $email, $student_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error = 'Email is already in use by another account';
            } else {
                $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, department = ?, level = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $full_name, $email, $department, $level, $student_id);
                if ($stmt->execute()) {
                    $_SESSION['student_name'] = $full_name;
                    $_SESSION['student_email'] = $email;
                    $_SESSION['student_department'] = $department;
                    $_SESSION['student_level'] = $level;
                    $success = 'Profile updated successfully!';
                    
                    // Refresh student data
                    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->bind_param("i", $student_id);
                    $stmt->execute();
                    $student = $stmt->get_result()->fetch_assoc();
                } else {
                    $error = 'Failed to update profile';
                }
            }
        }
    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'All password fields are required';
        } elseif (strlen($new_password) < 6) {
            $error = 'New password must be at least 6 characters';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match';
        } elseif (!password_verify($current_password, $student['password'])) {
            $error = 'Current password is incorrect';
        } else {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed, $student_id);
            if ($stmt->execute()) {
                $success = 'Password changed successfully!';
            } else {
                $error = 'Failed to change password';
            }
        }
    } elseif ($action === 'upload_profile_picture') {
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_image'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($file['type'], $allowed_types)) {
                $error = 'Only JPEG, PNG, GIF, and WebP images are allowed';
            } elseif ($file['size'] > $max_size) {
                $error = 'Image size must be less than 5MB';
            } else {
                $upload_dir = 'uploads/profiles/';
                $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $new_filename = 'profile_' . $student_id . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    // Delete old profile image if exists
                    if (!empty($student['profile_image']) && file_exists($student['profile_image'])) {
                        unlink($student['profile_image']);
                    }
                    
                    // Update database
                    $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                    $stmt->bind_param("si", $upload_path, $student_id);
                    if ($stmt->execute()) {
                        $success = 'Profile picture updated successfully!';
                        
                        // Refresh student data
                        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                        $stmt->bind_param("i", $student_id);
                        $stmt->execute();
                        $student = $stmt->get_result()->fetch_assoc();
                    } else {
                        $error = 'Failed to update profile picture in database';
                        // Delete uploaded file if database update failed
                        if (file_exists($upload_path)) {
                            unlink($upload_path);
                        }
                    }
                } else {
                    $error = 'Failed to upload image';
                }
            }
        } else {
            $error = 'Please select an image to upload';
        }
    }
}

// Get stats
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM marketplace_items WHERE seller_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$total_items = $stmt->get_result()->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM marketplace_items WHERE seller_id = ? AND status = 'sold'");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$items_sold = $stmt->get_result()->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT SUM(amount) as total FROM payments WHERE student_id = ? AND status = 'completed'");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$total_paid = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

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
    <title>My Profile - RHUB Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, var(--brick-red) 0%, var(--brick-red-dark) 100%);
            color: var(--cream-white);
            padding: 3rem 2rem;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: var(--cream-white);
            color: var(--brick-red);
            font-size: 2.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            border: 4px solid rgba(255, 253, 208, 0.3);
        }
        
        .profile-header h2 {
            margin-bottom: 0.25rem;
        }
        
        .profile-header p {
            opacity: 0.9;
        }
        
        .profile-stats {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }
        
        .profile-stat {
            text-align: center;
        }
        
        .profile-stat h4 {
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
        }
        
        .profile-stat p {
            font-size: 0.85rem;
            opacity: 0.8;
        }
        
        .profile-form .form-group {
            margin-bottom: 1.5rem;
        }
        
        .profile-form label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }
        
        .profile-form label i {
            color: var(--brick-red);
            margin-right: 0.5rem;
        }
        
        .profile-form .form-control {
            width: 100%;
            padding: 1rem;
            border: 2px solid var(--gray-200);
            border-radius: 10px;
            font-size: 1rem;
        }
        
        .profile-form .form-control:focus {
            outline: none;
            border-color: var(--brick-red);
        }
        
        .profile-form .form-control:disabled {
            background: var(--gray-100);
            cursor: not-allowed;
        }
        
        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-error {
            background: #FEE2E2;
            color: #DC2626;
        }
        
        .profile-form input[type="file"] {
            padding: 0.75rem;
            border: 2px dashed var(--gray-300);
            border-radius: 10px;
            background: var(--gray-50);
            cursor: pointer;
        }
        
        .profile-form input[type="file"]:hover {
            border-color: var(--brick-red);
            background: rgba(178, 34, 52, 0.05);
        }
        
        .profile-form input[type="file"]:focus {
            outline: none;
            border-color: var(--brick-red);
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
                    <h1 class="page-title">My Profile</h1>
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
                            <?php if (!empty($student['profile_image']) && file_exists($student['profile_image'])): ?>
                                <img src="<?php echo htmlspecialchars($student['profile_image']); ?>" alt="Profile" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                            <?php else: ?>
                                <?php echo strtoupper(substr($student['full_name'], 0, 2)); ?>
                            <?php endif; ?>
                        </div>
                        <div class="user-info">
                            <h4><?php echo htmlspecialchars($student['full_name']); ?></h4>
                            <span><?php echo htmlspecialchars($student['matricule']); ?></span>
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
                
                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php if (!empty($student['profile_image']) && file_exists($student['profile_image'])): ?>
                            <img src="<?php echo htmlspecialchars($student['profile_image']); ?>" alt="Profile Picture" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                        <?php else: ?>
                            <?php echo strtoupper(substr($student['full_name'], 0, 2)); ?>
                        <?php endif; ?>
                    </div>
                    <h2><?php echo htmlspecialchars($student['full_name']); ?></h2>
                    <p><?php echo htmlspecialchars($student['matricule']); ?></p>
                    <p><?php echo htmlspecialchars($student['department']); ?> - Level <?php echo htmlspecialchars($student['level']); ?></p>
                    
                    <!-- <div class="profile-stats">
                        <div class="profile-stat">
                            <h4><?php echo $total_items; ?></h4>
                            <p>Items Listed</p>
                        </div>
                        <div class="profile-stat">
                            <h4><?php echo $items_sold; ?></h4>
                            <p>Items Sold</p>
                        </div>
                        <div class="profile-stat">
                            <h4><?php echo formatCurrency($total_paid); ?></h4>
                            <p>Fees Paid</p>
                        </div>
                    </div> -->
                </div>
                
                <div class="profile-grid">
                    <!-- Update Profile -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-user-edit"></i> Update Profile</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" class="profile-form">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="form-group">
                                    <label><i class="fas fa-user"></i> Full Name</label>
                                    <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($student['full_name']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-envelope"></i> Email Address</label>
                                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-id-card"></i> Matricule Number</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['matricule']); ?>" disabled>
                                    <small style="color: var(--text-light);">Matricule cannot be changed</small>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-building"></i> Department</label>
                                    <select name="department" class="form-control">
                                        <?php 
                                        $departments = ['Computer Science', 'Engineering', 'Business Administration', 'Accounting', 'Marketing', 'Banking and Finance', 'Human Resource Management', 'Logistics'];
                                        foreach ($departments as $dept): 
                                        ?>
                                            <option value="<?php echo $dept; ?>" <?php echo $student['department'] === $dept ? 'selected' : ''; ?>>
                                                <?php echo $dept; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-layer-group"></i> Level</label>
                                    <select name="level" class="form-control">
                                        <?php foreach (['100', '200', '300', '400', '500'] as $lvl): ?>
                                            <option value="<?php echo $lvl; ?>" <?php echo $student['level'] === $lvl ? 'selected' : ''; ?>>
                                                Level <?php echo $lvl; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <button type="submit" class="btn btn-primary" style="width: 100%;">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Profile Picture Upload -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-camera"></i> Profile Picture</h3>
                        </div>
                        <div class="card-body">
                            <div style="text-align: center; margin-bottom: 1.5rem;">
                                <div class="profile-avatar" style="width: 120px; height: 120px; margin: 0 auto 1rem;">
                                    <?php if (!empty($student['profile_image']) && file_exists($student['profile_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($student['profile_image']); ?>" alt="Current Profile Picture" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($student['full_name'], 0, 2)); ?>
                                    <?php endif; ?>
                                </div>
                                <p style="color: var(--text-light); font-size: 0.9rem;">Upload a new profile picture</p>
                            </div>
                            
                            <form method="POST" action="" enctype="multipart/form-data" class="profile-form">
                                <input type="hidden" name="action" value="upload_profile_picture">
                                
                                <div class="form-group">
                                    <label><i class="fas fa-image"></i> Choose Image</label>
                                    <input type="file" name="profile_image" class="form-control" accept="image/*" required>
                                    <small style="color: var(--text-light);">Supported formats: JPEG, PNG, GIF, WebP (Max 5MB)</small>
                                </div>
                                
                                <button type="submit" class="btn btn-primary" style="width: 100%;">
                                    <i class="fas fa-upload"></i> Upload Picture
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Change Password -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-lock"></i> Change Password</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" class="profile-form">
                                <input type="hidden" name="action" value="change_password">
                                
                                <div class="form-group">
                                    <label><i class="fas fa-key"></i> Current Password</label>
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-lock"></i> New Password</label>
                                    <input type="password" name="new_password" class="form-control" required minlength="6">
                                    <small style="color: var(--text-light);">Minimum 6 characters</small>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-lock"></i> Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="form-control" required>
                                </div>
                                
                                <button type="submit" class="btn btn-primary" style="width: 100%;">
                                    <i class="fas fa-key"></i> Change Password
                                </button>
                            </form>
                        </div>
                        
                        <!-- Account Info -->
                        <div class="card-body" style="border-top: 1px solid var(--gray-200);">
                            <h4 style="margin-bottom: 1rem; color: var(--text-dark);"><i class="fas fa-info-circle" style="color: var(--brick-red);"></i> Account Information</h4>
                            <p style="color: var(--text-light); margin-bottom: 0.5rem;">
                                <strong>Member Since:</strong> <?php echo date('F d, Y', strtotime($student['created_at'])); ?>
                            </p>
                            <p style="color: var(--text-light);">
                                <strong>Last Updated:</strong> <?php echo date('F d, Y', strtotime($student['updated_at'])); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/dashboard.js"></script>
</body>
</html>
