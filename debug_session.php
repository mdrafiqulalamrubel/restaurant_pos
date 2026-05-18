<?php
// debug_session.php - Check what role you have
require_once 'config.php';

echo "<h2>Session Debug Information</h2>";

echo "<h3>Session Variables:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>User from Database:</h3>";
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT id, username, role, is_active, email FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    echo "<pre>";
    print_r($user);
    echo "</pre>";
    
    if ($user) {
        echo "<p>Your role in database: <strong>" . $user['role'] . "</strong></p>";
        if ($user['role'] !== 'admin') {
            echo "<p style='color:red'>⚠️ Your role is '{$user['role']}', not 'admin'. User Management requires admin role.</p>";
            echo "<h3>Fix: Run this SQL in phpMyAdmin:</h3>";
            echo "<code>UPDATE users SET role = 'admin' WHERE id = " . $user['id'] . ";</code>";
        } else {
            echo "<p style='color:green'>✅ Your role is 'admin'. User Management should work.</p>";
        }
    }
} else {
    echo "<p style='color:red'>❌ No user logged in. <a href='login.php'>Login first</a></p>";
}

echo "<br><a href='users.php' class='btn btn-primary'>Try Users Page</a>";
echo " | <a href='logout.php' class='btn btn-danger'>Logout and Login Again</a>";
?>