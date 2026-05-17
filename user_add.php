<?php
$page_title = 'Add New User';
$page_icon = 'user-plus';
require_once 'config.php';
require_once 'header.php';

// Check if current user is admin
if ($_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validation
    if (empty($username) || empty($password)) {
        $error = "Username and password are required";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } else {
        // Check if username exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = "Username already exists";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, full_name, email, phone, password_hash, role, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $full_name, $email, $phone, $password_hash, $role, $is_active]);
            $user_id = $pdo->lastInsertId();
            
            // Log activity
            $stmt = $pdo->prepare("INSERT INTO user_activity_log (user_id, action, details, ip_address) VALUES (?, 'user_created', ?, ?)");
            $stmt->execute([$_SESSION['user_id'], "Created user: $username", $_SERVER['REMOTE_ADDR']]);
            
            $success = "User created successfully!";
            // Clear form
            $_POST = [];
        }
    }
}
?>

<style>
    .user-form {
        max-width: 600px;
        margin: 0 auto;
    }
    .form-card {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .password-hint {
        font-size: 0.8rem;
        color: #6c757d;
    }
</style>

<div class="user-form">
    <div class="form-card">
        <h4 class="mb-4"><i class="fas fa-user-plus"></i> Create New User</h4>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        
        <form method="post">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Username *</label>
                    <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Full Name</label>
                    <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label>Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Password *</label>
                    <input type="password" name="password" class="form-control" required>
                    <small class="password-hint">Minimum 6 characters</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Confirm Password *</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Role</label>
                    <select name="role" class="form-control">
                        <option value="staff" <?= ($_POST['role'] ?? '') == 'staff' ? 'selected' : '' ?>>Staff</option>
                        <option value="cashier" <?= ($_POST['role'] ?? '') == 'cashier' ? 'selected' : '' ?>>Cashier</option>
                        <option value="manager" <?= ($_POST['role'] ?? '') == 'manager' ? 'selected' : '' ?>>Manager</option>
                        <option value="admin" <?= ($_POST['role'] ?? '') == 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label>&nbsp;</label>
                    <div class="form-check mt-2">
                        <input type="checkbox" name="is_active" class="form-check-input" value="1" checked>
                        <label class="form-check-label">Active Account</label>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                <strong>Role Permissions:</strong><br>
                - <strong>Admin:</strong> Full access to everything<br>
                - <strong>Manager:</strong> Can manage inventory, view reports<br>
                - <strong>Cashier:</strong> Can process sales, view customers<br>
                - <strong>Staff:</strong> Basic POS access only
            </div>
            
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-save"></i> Create User
            </button>
            
            <a href="users.php" class="btn btn-secondary w-100 mt-2">Back to Users</a>
        </form>
    </div>
</div>

<?php require_once 'footer.php'; ?>