<?php
require_once 'config.php';

// Method 1: Update admin password to 'admin'
$new_password = 'admin';
$hash = password_hash($new_password, PASSWORD_DEFAULT);

// Check if admin exists
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = 'admin'");
$stmt->execute();
$admin = $stmt->fetch();

if ($admin) {
    // Update existing admin
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = 'admin'");
    $stmt->execute([$hash]);
    echo "✅ Admin password updated successfully!<br>";
    echo "Username: admin<br>";
    echo "Password: admin<br>";
} else {
    // Create new admin
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, is_active) VALUES ('admin', ?, 'admin', 1)");
    $stmt->execute([$hash]);
    echo "✅ Admin user created successfully!<br>";
    echo "Username: admin<br>";
    echo "Password: admin<br>";
}

echo "<br><a href='login.php'>Go to Login Page</a>";
?>