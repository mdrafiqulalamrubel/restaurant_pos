<?php
// force_session.php - Force login session
session_start();
require_once "config.php";

// Get admin user
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute(["admin"]);
$admin = $stmt->fetch();

if ($admin) {
    $_SESSION["user_id"] = $admin["id"];
    $_SESSION["username"] = $admin["username"];
    $_SESSION["role"] = $admin["role"];
    $_SESSION["branch_id"] = $admin["branch_id"] ?? 1;
    
    echo "<h2>Session Created!</h2>";
    echo "<p>User ID: " . $_SESSION["user_id"] . "</p>";
    echo "<p>Username: " . $_SESSION["username"] . "</p>";
    echo "<p>Role: " . $_SESSION["role"] . "</p>";
    echo "<p>Branch ID: " . $_SESSION["branch_id"] . "</p>";
    echo "<br><a href='pos.php'>Go to POS</a>";
} else {
    echo "<p style='color:red'>Admin not found!</p>";
}
?>