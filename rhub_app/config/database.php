<?php
/**
 * Database Configuration for RHUB Application
 * Update these values according to your XAMPP/MySQL setup
 */

class Database {
    private $host = "localhost";
    private $db_name = "rhub_database";  // Database name
    private $username = "root";           // Default XAMPP username
    private $password = "";               // Default XAMPP password (empty)
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
            exit;
        }

        return $this->conn;
    }
}

// Legacy mysqli connection for backwards compatibility
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'rhub_database');

function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include reusable helper functions
require_once __DIR__ . '/../includes/helpers.php';

// Helper function to sanitize input
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Generate unique transaction reference
function generateTransactionRef($method = 'RHUB') {
    return strtoupper($method) . '-' . date('YmdHis') . '-' . rand(1000, 9999);
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['student_id']) && !empty($_SESSION['student_id']);
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Get current student ID
function getCurrentStudentId() {
    return $_SESSION['student_id'] ?? null;
}

// Get current user ID (alias)
function getCurrentUserId() {
    return getCurrentStudentId();
}

// Get current user data
function getCurrentUser() {
    return $_SESSION['user'] ?? null;
}

// Format currency (XAF/FCFA)
function formatCurrency($amount) {
    return number_format($amount, 0, ',', ' ') . ' FCFA';
}

// Format date
function formatDate($date, $format = 'd M Y') {
    return date($format, strtotime($date));
}

// Flash message functions
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Redirect helper
function redirect($url) {
    header("Location: $url");
    exit;
}
?>
