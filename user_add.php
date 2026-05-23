<?php
$page_title = 'Add New User';
$page_icon = 'user-plus';
require_once 'config.php';

// Check if current user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
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
    $branch_id = $_POST['branch_id'] ?? null;
    $branch_access = $_POST['branch_access'] ?? [];
    
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
            $stmt = $pdo->prepare("INSERT INTO users (username, full_name, email, phone, password_hash, role, is_active, branch_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $full_name, $email, $phone, $password_hash, $role, $is_active, $branch_id]);
            $user_id = $pdo->lastInsertId();
            
            // Add branch access permissions
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
            
            $success = "User created successfully!";
            // Clear form
            $_POST = [];
        }
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
                    <div class="input-group">
                        <input type="password" name="password" id="password" class="form-control" required>
                        <span class="input-group-text password-toggle" onclick="togglePassword('password', 'toggleIcon1')">
                            <i class="fas fa-eye" id="toggleIcon1"></i>
                        </span>
                    </div>
                    <small class="text-muted">Minimum 6 characters</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Confirm Password *</label>
                    <div class="input-group">
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                        <span class="input-group-text password-toggle" onclick="togglePassword('confirm_password', 'toggleIcon2')">
                            <i class="fas fa-eye" id="toggleIcon2"></i>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Role</label>
                    <select name="role" id="roleSelect" class="form-control" onchange="toggleBranchFields()">
                        <option value="staff" <?= ($_POST['role'] ?? '') == 'staff' ? 'selected' : '' ?>>Staff</option>
                        <option value="cashier" <?= ($_POST['role'] ?? '') == 'cashier' ? 'selected' : '' ?>>Cashier</option>
                        <option value="manager" <?= ($_POST['role'] ?? '') == 'manager' ? 'selected' : '' ?>>Manager</option>
                        <option value="admin" <?= ($_POST['role'] ?? '') == 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="is_active" class="form-check-input" value="1" checked>
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
                        <option value="<?= $b['id'] ?>" <?= ($_POST['branch_id'] ?? '') == $b['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">User will be assigned to this branch by default</small>
            </div>
            
            <!-- Additional Branch Access (for non-admin users) -->
            <div class="mb-3" id="branchAccessDiv">
                <label>Additional Branch Access (Hold Ctrl to select multiple)</label>
                <select name="branch_access[]" class="form-control branch-select-multiple" multiple size="4">
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= $b['id'] ?>">
                            <?= htmlspecialchars($b['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">User will have access to these branches as well. Admin has access to all branches by default.</small>
            </div>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                <strong>Role Permissions:</strong><br>
                - <strong>Admin:</strong> Full access to everything, all branches<br>
                - <strong>Manager:</strong> Can manage inventory, view reports for assigned branches<br>
                - <strong>Cashier:</strong> Can process sales, view customers for assigned branch<br>
                - <strong>Staff:</strong> Basic POS access only for assigned branch
            </div>
            
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-save"></i> Create User
            </button>
            
            <a href="users.php" class="btn btn-secondary w-100 mt-2">Back to Users</a>
        </form>
    </div>
</div>

<script>
function togglePassword(fieldId, iconId) {
    const passwordInput = document.getElementById(fieldId);
    const toggleIcon = document.getElementById(iconId);
    
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