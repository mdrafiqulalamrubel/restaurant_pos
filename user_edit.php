<?php
$page_title = 'Edit User';
$page_icon = 'user-edit';
require_once 'config.php';
require_once 'header.php';

// Check if current user is admin
if ($_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$user_id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die('User not found');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role = $_POST['role'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $password = $_POST['password'];
    
    // Update query
    if (!empty($password)) {
        if (strlen($password) < 6) {
            $error = "Password must be at least 6 characters";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET full_name=?, email=?, phone=?, role=?, is_active=?, password_hash=? WHERE id=?");
            $stmt->execute([$full_name, $email, $phone, $role, $is_active, $password_hash, $user_id]);
            $success = "User updated successfully!";
        }
    } else {
        $stmt = $pdo->prepare("UPDATE users SET full_name=?, email=?, phone=?, role=?, is_active=? WHERE id=?");
        $stmt->execute([$full_name, $email, $phone, $role, $is_active, $user_id]);
        $success = "User updated successfully!";
    }
    
    // Log activity
    if (empty($error)) {
        $stmt = $pdo->prepare("INSERT INTO user_activity_log (user_id, action, details, ip_address) VALUES (?, 'user_updated', ?, ?)");
        $stmt->execute([$_SESSION['user_id'], "Updated user: {$user['username']}", $_SERVER['REMOTE_ADDR']]);
        
        // Refresh user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
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
</style>

<div class="user-form">
    <div class="form-card">
        <h4 class="mb-4"><i class="fas fa-user-edit"></i> Edit User: <?= htmlspecialchars($user['username']) ?></h4>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        
        <form method="post">
            <div class="mb-3">
                <label>Username</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                <small class="text-muted">Username cannot be changed</small>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Full Name</label>
                    <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label>Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
            </div>
            
            <div class="mb-3">
                <label>New Password (leave blank to keep current)</label>
                <input type="password" name="password" class="form-control">
                <small class="text-muted">Minimum 6 characters</small>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Role</label>
                    <select name="role" class="form-control">
                        <option value="staff" <?= $user['role'] == 'staff' ? 'selected' : '' ?>>Staff</option>
                        <option value="cashier" <?= $user['role'] == 'cashier' ? 'selected' : '' ?>>Cashier</option>
                        <option value="manager" <?= $user['role'] == 'manager' ? 'selected' : '' ?>>Manager</option>
                        <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="is_active" class="form-check-input" value="1" <?= $user['is_active'] ? 'checked' : '' ?>>
                        <label class="form-check-label">Active Account</label>
                    </div>
                </div>
            </div>
            
            <?php if ($user['id'] == $_SESSION['user_id']): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> You are editing your own account. Be careful changing your role.
                </div>
            <?php endif; ?>
            
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-save"></i> Update User
            </button>
            
            <a href="users.php" class="btn btn-secondary w-100 mt-2">Back to Users</a>
        </form>
    </div>
</div>

<?php require_once 'footer.php'; ?>