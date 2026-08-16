<?php
require 'config.php';

try {
    // Create tables
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `acc_accounts` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `code` VARCHAR(20) NOT NULL,
            `name` VARCHAR(100) NOT NULL,
            `type` ENUM('Asset', 'Liability', 'Equity', 'Revenue', 'Expense') NOT NULL,
            `parent_id` INT DEFAULT NULL,
            `is_default` TINYINT(1) DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `acc_journals` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `branch_id` INT NOT NULL DEFAULT 1,
            `date` DATE NOT NULL,
            `reference` VARCHAR(100) DEFAULT NULL,
            `description` TEXT,
            `created_by` INT NOT NULL,
            `contact_type` VARCHAR(50) DEFAULT NULL,
            `contact_id` INT DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `acc_journal_items` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `journal_id` INT NOT NULL,
            `account_id` INT NOT NULL,
            `debit` DECIMAL(15,2) DEFAULT 0.00,
            `credit` DECIMAL(15,2) DEFAULT 0.00,
            FOREIGN KEY (`journal_id`) REFERENCES `acc_journals`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`account_id`) REFERENCES `acc_accounts`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Default chart of accounts
    $default_accounts = [
        ['code' => '1000', 'name' => 'Cash', 'type' => 'Asset'],
        ['code' => '1100', 'name' => 'Bank', 'type' => 'Asset'],
        ['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'Asset'],
        ['code' => '1300', 'name' => 'Inventory', 'type' => 'Asset'],
        ['code' => '1400', 'name' => 'Fixed Assets', 'type' => 'Asset'],
        
        ['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'Liability'],
        ['code' => '2100', 'name' => 'Short Term Loans', 'type' => 'Liability'],
        ['code' => '2300', 'name' => 'Tax Payable', 'type' => 'Liability'],
        
        ['code' => '3000', 'name' => 'Owner Equity', 'type' => 'Equity'],
        ['code' => '3100', 'name' => 'Retained Earnings', 'type' => 'Equity'],
        
        ['code' => '4000', 'name' => 'Sales Revenue', 'type' => 'Revenue'],
        ['code' => '4100', 'name' => 'Service Revenue', 'type' => 'Revenue'],
        ['code' => '4200', 'name' => 'Other Income', 'type' => 'Revenue'],
        
        ['code' => '5000', 'name' => 'Cost of Goods Sold (COGS)', 'type' => 'Expense'],
        ['code' => '5100', 'name' => 'Salary Expense', 'type' => 'Expense'],
        ['code' => '5200', 'name' => 'Rent Expense', 'type' => 'Expense'],
        ['code' => '5300', 'name' => 'Utility Expense', 'type' => 'Expense'],
        ['code' => '5400', 'name' => 'General Expenses', 'type' => 'Expense'],
    ];

    $check = $pdo->query("SELECT COUNT(*) FROM acc_accounts");
    if ($check->fetchColumn() == 0) {
        $insertStmt = $pdo->prepare("INSERT INTO acc_accounts (code, name, type, is_default) VALUES (?, ?, ?, 1)");
        foreach ($default_accounts as $acc) {
            $insertStmt->execute([$acc['code'], $acc['name'], $acc['type']]);
        }
    }

    echo "Database setup completed successfully.\n";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
