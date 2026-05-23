<?php
// fix_admin_branch.php - Fix admin branch access
require_once 'config.php';

echo "<h2>Fixing Admin Branch Access</h2>";

// Check if branches table exists and has data
$tables = $pdo->query("SHOW TABLES LIKE 'branches'");
if ($tables->rowCount() == 0) {
    echo "<p style='color:red'>Branches table doesn't exist! Creating...</p>";
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS branches (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        code VARCHAR(20) UNIQUE NOT NULL,
        address TEXT,
        phone VARCHAR(50),
        email VARCHAR(100),
        manager_name VARCHAR(100),
        opening_time TIME DEFAULT '09:00:00',
        closing_time TIME DEFAULT '22:00:00',
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "<p style='color:green'>✓ Branches table created</p>";
}

// Check if user_branch_access table exists
$tables = $pdo->query("SHOW TABLES LIKE 'user_branch_access'");
if ($tables->rowCount() == 0) {
    echo "<p style='color:red'>user_branch_access table doesn't exist! Creating...</p>";
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_branch_access (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        branch_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_branch (user_id, branch_id)
    )");
    echo "<p style='color:green'>✓ user_branch_access table created</p>";
}

// Insert default branch if none exists
$count = $pdo->query("SELECT COUNT(*) FROM branches")->fetchColumn();
if ($count == 0) {
    echo "<p>No branches found. Creating default branch...</p>";
    $pdo->exec("INSERT INTO branches (name, code, status) VALUES ('Main Branch', 'MB001', 'active')");
    echo "<p style='color:green'>✓ Default branch created</p>";
}

// Get admin user
$stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE username = 'admin'");
$stmt->execute();
$admin = $stmt->fetch();

if (!$admin) {
    echo "<p style='color:red'>Admin user not found! Creating...</p>";
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, is_active) VALUES ('admin', ?, 'admin', 1)");
    $stmt->execute([$hash]);
    $admin_id = $pdo->lastInsertId();
    echo "<p style='color:green'>✓ Admin user created with password 'admin123'</p>";
} else {
    $admin_id = $admin['id'];
    echo "<p>Admin user found: {$admin['username']} (ID: $admin_id)</p>";
}

// Ensure admin role is set
$stmt = $pdo->prepare("UPDATE users SET role = 'admin', is_active = 1 WHERE id = ?");
$stmt->execute([$admin_id]);
echo "<p style='color:green'>✓ Admin role confirmed</p>";

// Delete existing branch access for admin
$stmt = $pdo->prepare("DELETE FROM user_branch_access WHERE user_id = ?");
$stmt->execute([$admin_id]);
echo "<p>✓ Removed existing branch access</p>";

// Give admin access to all branches
$stmt = $pdo->prepare("
    INSERT INTO user_branch_access (user_id, branch_id)
    SELECT ?, id FROM branches WHERE status = 'active'
");
$stmt->execute([$admin_id]);
echo "<p style='color:green'>✓ Admin granted access to all active branches</p>";

// Set admin's primary branch
$stmt = $pdo->prepare("UPDATE users SET branch_id = (SELECT id FROM branches WHERE status = 'active' LIMIT 1) WHERE id = ?");
$stmt->execute([$admin_id]);
echo "<p style='color:green'>✓ Admin primary branch set</p>";

// Display current branches
$branches = $pdo->query("SELECT * FROM branches WHERE status = 'active'")->fetchAll();
echo "<h3>Active Branches:</h3>";
echo "<ul>";
foreach ($branches as $b) {
    echo "<li>{$b['name']} (Code: {$b['code']})</li>";
}
echo "</ul>";

// Display admin's branch access
$access = $pdo->prepare("
    SELECT b.name FROM user_branch_access uba 
    JOIN branches b ON uba.branch_id = b.id 
    WHERE uba.user_id = ?
");
$access->execute([$admin_id]);
$user_branches = $access->fetchAll();

echo "<h3>Admin's Branch Access:</h3>";
echo "<ul>";
foreach ($user_branches as $ub) {
    echo "<li>{$ub['name']}</li>";
}
echo "</ul>";

echo "<br><a href='login.php' class='btn btn-primary'>Go to Login</a>";
echo " | <a href='pos.php' class='btn btn-success'>Go to POS</a>";
?>