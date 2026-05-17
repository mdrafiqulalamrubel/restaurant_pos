<?php
session_start();
require_once 'config.php';

// Log logout activity
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    try {
        // Check if user_activity_log table exists
        $checkTable = $pdo->query("SHOW TABLES LIKE 'user_activity_log'");
        if ($checkTable->rowCount() > 0) {
            $stmt = $pdo->prepare("INSERT INTO user_activity_log (user_id, action, details, ip_address) VALUES (?, 'logout', 'User logged out', ?)");
            $stmt->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR']]);
        }
    } catch (Exception $e) {
        // Ignore errors if table doesn't exist
    }
}

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit;
?>