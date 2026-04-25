<?php
/**
 * RHUB App - Helper Functions
 */

// Format date time
function formatDateTime($date) {
    return date('d M Y, H:i', strtotime($date));
}

// Time ago function
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return formatDate($datetime);
    }
}

// Generate random string
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

// Validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate matricule (customize based on your format)
function isValidMatricule($matricule) {
    // Example format: RHIMBS/20/0001 or similar
    return preg_match('/^[A-Z0-9\/\-]+$/i', $matricule);
}

// Get user avatar
function getUserAvatar($user) {
    if (!empty($user['profile_image']) && file_exists($user['profile_image'])) {
        return $user['profile_image'];
    }
    // Return initials-based placeholder
    return null;
}

// Get user initials
function getUserInitials($fullName) {
    $names = explode(' ', $fullName);
    $initials = '';
    foreach ($names as $name) {
        $initials .= strtoupper(substr($name, 0, 1));
        if (strlen($initials) >= 2) break;
    }
    return $initials;
}

// Flash messages
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Calculate fee percentage paid
function calculateFeePercentage($paid, $total) {
    if ($total <= 0) return 0;
    return min(100, round(($paid / $total) * 100));
}

// Get payment status badge class
function getPaymentStatusClass($status) {
    switch (strtolower($status)) {
        case 'completed':
            return 'badge-success';
        case 'pending':
            return 'badge-warning';
        case 'failed':
            return 'badge-danger';
        default:
            return 'badge-secondary';
    }
}

// Get item status badge class
function getItemStatusClass($status) {
    switch (strtolower($status)) {
        case 'available':
            return 'badge-success';
        case 'sold':
            return 'badge-danger';
        case 'reserved':
            return 'badge-warning';
        default:
            return 'badge-secondary';
    }
}

// Departments list
function getDepartments() {
    return [
        'Computer Science',
        'Engineering',
        'Business Administration',
        'Accounting',
        'Marketing',
        'Human Resource Management',
        'Banking and Finance',
        'Secretarial Studies',
        'Journalism',
        'Law'
    ];
}

// Levels list
function getLevels() {
    return [
        'Level 100',
        'Level 200',
        'Level 300',
        'Level 400',
        'Level 500',
        'Masters 1',
        'Masters 2'
    ];
}

// Item categories
function getItemCategories() {
    return [
        'books' => 'Books & Notes',
        'electronics' => 'Electronics',
        'clothing' => 'Clothing',
        'accessories' => 'Accessories',
        'furniture' => 'Furniture',
        'sports' => 'Sports & Fitness',
        'services' => 'Services',
        'other' => 'Other'
    ];
}

// Item conditions
function getItemConditions() {
    return [
        'new' => 'Brand New',
        'like_new' => 'Like New',
        'good' => 'Good',
        'fair' => 'Fair',
        'used' => 'Used'
    ];
}

// Truncate text
function truncateText($text, $length = 100) {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

// Format file size
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

// Log error
function logError($message, $file = 'error.log') {
    $logDir = __DIR__ . '/../logs/';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logDir . $file, $logMessage, FILE_APPEND);
}
?>

