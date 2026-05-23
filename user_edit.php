<?php
$page_title = 'Edit User';
$page_icon = 'user-edit';
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Check if current user is admin - Allow access for admin role only
if ($_SESSION['role'] !== 'admin') {
    // If not admin, check if editing own profile
    $edit_id = $_GET['id'] ?? 0;
    if ($edit_id != $_SESSION['user_id']) {
        header('Location: index.php');
        exit;
    }
}

$user_id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die('User not found');
}

// Get user's branch access
$user_access = [];
$stmt = $pdo->prepare("SELECT branch_id FROM user_branch_access WHERE user_id = ?");
$stmt->execute([$user_id]);
$user_access = $stmt->fetchAll(PDO::FETCH_COLUMN);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role = $_POST['role'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $branch_id = $_POST['branch_id'] ?? null;
    $branch_access = $_POST['branch_access'] ?? [];
    $password = $_POST['password'];
    
    // Update query
    if (!empty($password)) {
        if (strlen($password) < 6) {
            $error = "Password must be at least 6 characters";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET full_name=?, email=?, phone=?, role=?, is_active=?, branch_id=?, password_hash=? WHERE id=?");
            $stmt->execute([$full_name, $email, $phone, $role, $is_active, $branch_id, $password_hash, $user_id]);
            $success = "User updated successfully!";
        }
    } else {
        $stmt = $pdo->prepare("UPDATE users SET full_name=?, email=?, phone=?, role=?, is_active=?, branch_id=? WHERE id=?");
        $stmt->execute([$full_name, $email, $phone, $role, $is_active, $branch_id, $user_id]);
        $success = "User updated successfully!";
    }
    
    if (empty($error)) {
        // Update branch access permissions
        $stmt = $pdo->prepare("DELETE FROM user_branch_access WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        if ($role === 'admin') {
            // Admin gets access to all branches
            $all_branches = $pdo->query("SELECT id FROM branches WHERE status = 'active'")->fetchAll();
            foreach ($all_branches as $branch) {
                $stmt = $pdo->prepare("INSERT INTO user_branch_access (user_id, branch_id) VALUES (?, ?)");
                $stmt->execute([$user_id, $branch['id']]);
            }
        } elseif (!empty($branch_access)) {
            // Add selected branch access
            foreach ($branch_access as $ba) {
                $stmt = $pdo->prepare("INSERT INTO user_branch_access (user_id, branch_id) VALUES (?, ?)");
                $stmt->execute([$user_id, $ba]);
            }
        } else {
            // Add default branch access
            $stmt = $pdo->prepare("INSERT INTO user_branch_access (user_id, branch_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $branch_id]);
        }
        
        // Refresh user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

require_once 'header.php';

$branches = $pdo->query("SELECT id, name FROM branches WHERE status = 'active' ORDER BY name")->fetchAll();
?>

<style>
    .user-form {
        max-width: 650px;
        margin: 0 auto;
    }
    .form-card {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .password-toggle {
        cursor: pointer;
    }
    .password-toggle:hover {
        background: #f8f9fa;
    }
    .branch-select-multiple {
        min-height: 100px;
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
                <div class="input-group">
                    <input type="password" name="password" id="password" class="form-control">
                    <span class="input-group-text password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </span>
                </div>
                <small class="text-muted">Minimum 6 characters</small>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Role</label>
                    <select name="role" id="roleSelect" class="form-control" onchange="toggleBranchFields()" <?= ($user['id'] == $_SESSION['user_id'] && $_SESSION['role'] !== 'admin') ? 'disabled' : '' ?>>
                        <option value="staff" <?= $user['role'] == 'staff' ? 'selected' : '' ?>>Staff</option>
                        <option value="cashier" <?= $user['role'] == 'cashier' ? 'selected' : '' ?>>Cashier</option>
                        <option value="manager" <?= $user['role'] == 'manager' ? 'selected' : '' ?>>Manager</option>
                        <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                    <?php if ($user['id'] == $_SESSION['user_id'] && $_SESSION['role'] !== 'admin'): ?>
                        <small class="text-muted">You cannot change your own role</small>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="is_active" class="form-check-input" value="1" <?= $user['is_active'] ? 'checked' : '' ?>>
                        <label class="form-check-label">Active Account</label>
                    </div>
                </div>
            </div>
            
            <!-- Primary Branch Assignment -->
            <div class="mb-3" id="primaryBranchDiv">
                <label>Primary Branch</label>
                <select name="branch_id" class="form-control">
                    <option value="">-- Select Branch --</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= ($user['branch_id'] ?? '') == $b['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">User will be assigned to this branch by default</small>
            </div>
            
            <!-- Additional Branch Access -->
            <div class="mb-3" id="branchAccessDiv">
                <label>Additional Branch Access (Hold Ctrl to select multiple)</label>
                <select name="branch_access[]" class="form-control branch-select-multiple" multiple size="4">
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= in_array($b['id'], $user_access) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">User will have access to these branches as well. Admin has access to all branches by default.</small>
            </div>
            
            <?php if ($user['id'] == $_SESSION['user_id']): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> You are editing your own account.
                </div>
            <?php endif; ?>
            
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-save"></i> Update User
            </button>
            
            <a href="users.php" class="btn btn-secondary w-100 mt-2">Back to Users</a>
        </form>
    </div>
</div>

<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

function toggleBranchFields() {
    const role = document.getElementById('roleSelect').value;
    const primaryDiv = document.getElementById('primaryBranchDiv');
    const accessDiv = document.getElementById('branchAccessDiv');
    
    if (role === 'admin') {
        primaryDiv.style.display = 'none';
        accessDiv.style.display = 'none';
    } else {
        primaryDiv.style.display = 'block';
        accessDiv.style.display = 'block';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleBranchFields();
});
</script>

<?php require_once 'footer.php'; ?>