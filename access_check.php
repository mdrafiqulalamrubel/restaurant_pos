<?php
// access_check.php - Check if user is admin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$is_admin = ($_SESSION['role'] ?? '') === 'admin';
$access_denied = !$is_admin;
?>