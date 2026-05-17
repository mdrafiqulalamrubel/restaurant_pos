<?php
require_once 'config.php';

// Hash a new password (change 'admin123' to whatever you want)
$new_password = 'admin123';
$hash = password_hash($new_password, PASSWORD_DEFAULT);

// Update admin user
$stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = 'admin'");
$stmt->execute([$hash]);

echo "Password reset successfully!<br>";
echo "Username: admin<br>";
echo "Password: admin123<br>";
echo "<a href='login.php'>Go to Login</a>";
?>