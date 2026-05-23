<?php
// complete_fix.php - Complete system fix for admin access
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$dbname = 'restaurant_pos_bookings';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

echo "<h2>Complete System Fix</h2>";

// ============================================
// 1. CREATE ALL NECESSARY TABLES
// ============================================

echo "<h3>1. Creating Tables...</h3>";

// Users table
$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    full_name VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','manager','staff','cashier') DEFAULT 'staff',
    branch_id INT NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL,
    last_ip VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
echo "<p>✓ Users table ready</p>";

// Branches table
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
echo "<p>✓ Branches table ready</p>";

// User branch access table
$pdo->exec("CREATE TABLE IF NOT EXISTS user_branch_access (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    branch_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_branch (user_id, branch_id)
)");
echo "<p>✓ User branch access table ready</p>";

// ============================================
// 2. CREATE DEFAULT BRANCH
// ============================================

echo "<h3>2. Creating Default Branch...</h3>";

$branch_count = $pdo->query("SELECT COUNT(*) FROM branches")->fetchColumn();
if ($branch_count == 0) {
    $pdo->exec("INSERT INTO branches (name, code, status) VALUES ('Main Branch', 'MB001', 'active')");
    echo "<p>✓ Default branch created</p>";
} else {
    echo "<p>✓ Branch already exists</p>";
}

// Get branch ID
$branch_id = $pdo->query("SELECT id FROM branches LIMIT 1")->fetchColumn();
echo "<p>Branch ID: $branch_id</p>";

// ============================================
// 3. CREATE OR RESET ADMIN USER
// ============================================

echo "<h3>3. Setting up Admin User...</h3>";

// Delete existing admin
$pdo->prepare("DELETE FROM users WHERE username = 'admin'")->execute();
echo "<p>✓ Removed existing admin user</p>";

// Create new admin
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO users (username, full_name, password_hash, role, branch_id, is_active) VALUES ('admin', 'System Administrator', ?, 'admin', ?, 1)");
$stmt->execute([$hash, $branch_id]);
$admin_id = $pdo->lastInsertId();
echo "<p>✓ Admin user created (ID: $admin_id)</p>";
echo "<p>Username: <strong>admin</strong></p>";
echo "<p>Password: <strong>admin123</strong></p>";

// ============================================
// 4. SET ADMIN BRANCH ACCESS
// ============================================

echo "<h3>4. Setting Admin Branch Access...</h3>";

// Clear existing access
$pdo->prepare("DELETE FROM user_branch_access WHERE user_id = ?")->execute([$admin_id]);
echo "<p>✓ Cleared existing branch access</p>";

// Give access to all branches
$pdo->exec("INSERT INTO user_branch_access (user_id, branch_id) SELECT $admin_id, id FROM branches WHERE status = 'active'");
echo "<p>✓ Admin granted access to all branches</p>";

// ============================================
// 5. VERIFY EVERYTHING
// ============================================

echo "<h3>5. Verification...</h3>";

// Check admin user
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = 'admin'");
$stmt->execute();
$admin = $stmt->fetch();
if ($admin) {
    echo "<p style='color:green'>✅ Admin user exists</p>";
    echo "<p>Role: " . $admin['role'] . "</p>";
    echo "<p>Active: " . ($admin['is_active'] ? 'Yes' : 'No') . "</p>";
    echo "<p>Branch ID: " . ($admin['branch_id'] ?? 'Not set') . "</p>";
    
    // Test password
    if (password_verify('admin123', $admin['password_hash'])) {
        echo "<p style='color:green'>✅ Password 'admin123' is CORRECT!</p>";
    } else {
        echo "<p style='color:red'>❌ Password verification failed!</p>";
        // Force update password
        $new_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$new_hash, $admin['id']]);
        echo "<p>✅ Password forcibly reset to 'admin123'</p>";
    }
} else {
    echo "<p style='color:red'>❌ Admin user not found!</p>";
}

// Check branches
$branches = $pdo->query("SELECT * FROM branches")->fetchAll();
echo "<p>Branches found: " . count($branches) . "</p>";
foreach ($branches as $b) {
    echo "<li>" . $b['name'] . " (" . $b['code'] . ") - " . $b['status'] . "</li>";
}

// Check branch access
$access_count = $pdo->prepare("SELECT COUNT(*) FROM user_branch_access WHERE user_id = ?");
$access_count->execute([$admin_id]);
echo "<p>Branch access records: " . $access_count->fetchColumn() . "</p>";

// ============================================
// 6. CREATE SESSION FIX FILE
// ============================================

echo "<h3>6. Creating session fix...</h3>";

$session_fix = '<?php
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
    echo "<br><a href=\'pos.php\'>Go to POS</a>";
} else {
    echo "<p style=\'color:red\'>Admin not found!</p>";
}
?>';

file_put_contents('force_session.php', $session_fix);
echo "<p>✓ Created force_session.php</p>";

// ============================================
// 7. CREATE SIMPLE LOGIN PAGE
// ============================================

echo "<h3>7. Creating simple login page...</h3>";

$simple_login = '<?php
// simple_login_test.php - Simple login page
session_start();
require_once "config.php";

if (isset($_SESSION["user_id"])) {
    header("Location: pos.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user["password_hash"])) {
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["username"] = $user["username"];
        $_SESSION["role"] = $user["role"];
        $_SESSION["branch_id"] = $user["branch_id"] ?? 1;
        header("Location: pos.php");
        exit;
    } else {
        $error = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <style>
        body { font-family: Arial; background: #667eea; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .login-box { background: white; padding: 30px; border-radius: 10px; width: 350px; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; }
        button { width: 100%; padding: 10px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Restaurant POS Login</h2>
        <?php if ($error): ?>
            <p class="error"><?= $error ?></p>
        <?php endif; ?>
        <form method="post">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <p style="text-align:center; margin-top:15px;">admin / admin123</p>
    </div>
</body>
</html>';

file_put_contents('simple_login_test.php', $simple_login);
echo "<p>✓ Created simple_login_test.php</p>";

// ============================================
// FINAL INSTRUCTIONS
// ============================================

echo "<h3>✅ Fix Complete!</h3>";
echo "<div style='background: #d4edda; padding: 15px; border-radius: 10px; margin-top: 20px;'>";
echo "<p><strong>Login Credentials:</strong></p>";
echo "<ul>";
echo "<li><strong>Username:</strong> admin</li>";
echo "<li><strong>Password:</strong> admin123</li>";
echo "</ul>";
echo "<p><strong>Try these URLs:</strong></p>";
echo "<ul>";
echo "<li><a href='simple_login_test.php'>Simple Login Page</a></li>";
echo "<li><a href='force_session.php'>Force Session (Skip Login)</a></li>";
echo "<li><a href='login.php'>Original Login Page</a></li>";
echo "</ul>";
echo "</div>";
?>