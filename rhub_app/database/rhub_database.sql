-- ============================================
-- RHUB Application Database Schema
-- RHIMBS Higher Institute Portal
-- ============================================
-- Instructions:
-- 1. Open phpMyAdmin (http://localhost/phpmyadmin)
-- 2. Create a new database named "rhub_database"
-- 3. Select the database and click "Import"
-- 4. Choose this file and click "Go"
-- ============================================

-- Create database (run this manually if not using Import)
-- CREATE DATABASE IF NOT EXISTS rhub_database;
-- USE rhub_database;

-- ============================================
-- USERS TABLE
-- Stores student information and credentials
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    department VARCHAR(100) NOT NULL,
    level VARCHAR(50) NOT NULL,
    matricule VARCHAR(50) NOT NULL UNIQUE,
    phone VARCHAR(20) DEFAULT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_matricule (matricule)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- STUDENTS TABLE
-- Extended student information for fee tracking
-- ============================================
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL UNIQUE,
    total_fees DECIMAL(12, 2) DEFAULT 350000.00,
    amount_paid DECIMAL(12, 2) DEFAULT 0.00,
    last_payment_date TIMESTAMP NULL,
    academic_year VARCHAR(20) DEFAULT '2024-2025',
    fee_status ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_student_id (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- PAYMENTS TABLE
-- Records all payment transactions
-- ============================================
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    payment_method ENUM('mtn', 'orange') NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    transaction_ref VARCHAR(50) NOT NULL UNIQUE,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    description VARCHAR(255) DEFAULT 'Fee Payment',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_transaction_ref (transaction_ref),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- MARKETPLACE ITEMS TABLE
-- Stores items listed for sale
-- ============================================
CREATE TABLE IF NOT EXISTS marketplace_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    buyer_id INT DEFAULT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(12, 2) NOT NULL,
    category VARCHAR(50) NOT NULL,
    item_condition ENUM('new', 'like_new', 'good', 'fair', 'used') DEFAULT 'used',
    image_url VARCHAR(255) DEFAULT NULL,
    status ENUM('available', 'reserved', 'sold') DEFAULT 'available',
    views INT DEFAULT 0,
    sold_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_seller_id (seller_id),
    INDEX idx_status (status),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- CONVERSATIONS TABLE
-- Manages buyer-seller conversations
-- ============================================
CREATE TABLE IF NOT EXISTS conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id INT NOT NULL,
    seller_id INT NOT NULL,
    item_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES marketplace_items(id) ON DELETE SET NULL,
    INDEX idx_buyer_id (buyer_id),
    INDEX idx_seller_id (seller_id),
    INDEX idx_item_id (item_id),
    UNIQUE KEY unique_conversation (buyer_id, seller_id, item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- MESSAGES TABLE
-- Stores peer-to-peer messages
-- ============================================
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_conversation_id (conversation_id),
    INDEX idx_receiver_id (receiver_id),
    INDEX idx_is_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- NOTIFICATIONS TABLE
-- Stores user notifications
-- ============================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255) DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_is_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SAMPLE DATA (Optional - for testing)
-- ============================================

-- Create a test user (password is: password123)
-- The password hash is generated using PHP's password_hash function
INSERT INTO users (full_name, email, password, department, level, matricule, phone) VALUES
('John Doe', 'john.doe@rhimbs.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Computer Science', 'Level 300', 'RHIMBS/CS/2022/001', '670123456'),
('Jane Smith', 'jane.smith@rhimbs.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Business Administration', 'Level 200', 'RHIMBS/BA/2023/015', '690456789');

-- Create student fee records for test users
INSERT INTO students (student_id, total_fees, amount_paid, academic_year, fee_status) VALUES
(1, 350000.00, 150000.00, '2024-2025', 'partial'),
(2, 350000.00, 0.00, '2024-2025', 'unpaid');

-- Sample payment history
INSERT INTO payments (student_id, amount, payment_method, phone_number, transaction_ref, status, description) VALUES
(1, 100000.00, 'mtn', '670123456', 'MTN-20240915100000-1234', 'completed', 'First Installment'),
(1, 50000.00, 'orange', '670123456', 'ORANGE-20241001143000-5678', 'completed', 'Second Installment');

-- Sample marketplace items
INSERT INTO marketplace_items (seller_id, title, description, price, category, item_condition, status) VALUES
(1, 'Introduction to Programming Textbook', 'Like new condition. Used for one semester only. All pages intact.', 15000.00, 'books', 'like_new', 'available'),
(1, 'Scientific Calculator (Casio fx-991ES)', 'Barely used calculator. Perfect for engineering students.', 8000.00, 'electronics', 'good', 'available'),
(2, 'Business Statistics Notes', 'Complete handwritten notes for BUS 201. Very detailed.', 5000.00, 'books', 'good', 'available');

-- ============================================
-- TRIGGERS
-- ============================================

-- Trigger to update fee_status when amount_paid changes
DELIMITER //
CREATE TRIGGER update_fee_status BEFORE UPDATE ON students
FOR EACH ROW
BEGIN
    IF NEW.amount_paid >= NEW.total_fees THEN
        SET NEW.fee_status = 'paid';
    ELSEIF NEW.amount_paid > 0 THEN
        SET NEW.fee_status = 'partial';
    ELSE
        SET NEW.fee_status = 'unpaid';
    END IF;
END//
DELIMITER ;

-- ============================================
-- VIEWS (Optional - for reporting)
-- ============================================

-- View for student fee summary
CREATE OR REPLACE VIEW student_fee_summary AS
SELECT 
    u.id as student_id,
    u.full_name,
    u.matricule,
    u.department,
    u.level,
    s.total_fees,
    s.amount_paid,
    (s.total_fees - s.amount_paid) as balance,
    s.fee_status,
    s.last_payment_date
FROM users u
JOIN students s ON u.id = s.student_id;

-- View for marketplace statistics
CREATE OR REPLACE VIEW marketplace_stats AS
SELECT 
    u.id as seller_id,
    u.full_name as seller_name,
    COUNT(mi.id) as total_items,
    SUM(CASE WHEN mi.status = 'available' THEN 1 ELSE 0 END) as available_items,
    SUM(CASE WHEN mi.status = 'sold' THEN 1 ELSE 0 END) as sold_items
FROM users u
LEFT JOIN marketplace_items mi ON u.id = mi.seller_id
GROUP BY u.id, u.full_name;
