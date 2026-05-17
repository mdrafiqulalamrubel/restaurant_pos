<?php
require_once 'config.php';

// Create company_settings table
$pdo->exec("CREATE TABLE IF NOT EXISTS company_settings (
    id INT PRIMARY KEY DEFAULT 1,
    name VARCHAR(200) DEFAULT 'Restaurant POS',
    address TEXT,
    phone VARCHAR(50),
    email VARCHAR(100),
    website VARCHAR(100),
    logo VARCHAR(255),
    currency VARCHAR(10) DEFAULT '€',
    currency_code VARCHAR(5) DEFAULT 'EUR',
    tax_rate DECIMAL(5,2) DEFAULT 10.00,
    receipt_footer TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Insert default company settings
$stmt = $pdo->prepare("INSERT INTO company_settings (id, name, currency, currency_code, tax_rate) 
    VALUES (1, 'Restaurant POS', '€', 'EUR', 10.00)
    ON DUPLICATE KEY UPDATE name = name");
$stmt->execute();

// Create expenses table
$pdo->exec("CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expense_date DATE NOT NULL,
    category VARCHAR(50) NOT NULL,
    description TEXT,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(20) DEFAULT 'cash',
    receipt VARCHAR(255),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Create dining_tables table
$pdo->exec("CREATE TABLE IF NOT EXISTS dining_tables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_number VARCHAR(10) NOT NULL,
    capacity INT DEFAULT 4,
    location VARCHAR(50),
    status ENUM('available','occupied','reserved','maintenance') DEFAULT 'available',
    current_order_id INT NULL,
    qr_code VARCHAR(100)
)");

// Create table_orders table
$pdo->exec("CREATE TABLE IF NOT EXISTS table_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_id INT NOT NULL,
    sale_id INT NOT NULL,
    order_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active','completed','cancelled') DEFAULT 'active'
)");

// Create order_tokens table
$pdo->exec("CREATE TABLE IF NOT EXISTS order_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    token_number INT NOT NULL,
    token_date DATE NOT NULL,
    status ENUM('pending','ready','served','completed') DEFAULT 'pending',
    printed_at TIMESTAMP NULL,
    UNIQUE KEY unique_token_date (token_number, token_date)
)");

// Insert sample tables
$pdo->exec("INSERT IGNORE INTO dining_tables (table_number, capacity, location) VALUES
    ('T01', 2, 'Window Side'),
    ('T02', 2, 'Window Side'),
    ('T03', 4, 'Center'),
    ('T04', 4, 'Center'),
    ('T05', 6, 'Back Area'),
    ('T06', 6, 'Back Area'),
    ('T07', 8, 'VIP Corner'),
    ('T08', 10, 'VIP Corner')");

echo "✅ All tables created successfully!<br>";
echo "<a href='pos.php'>Go to POS</a>";