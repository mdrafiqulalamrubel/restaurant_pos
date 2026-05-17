<?php
$page_title = 'User Permissions';
$page_icon = 'key';
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

// Define available permissions
$permissions = [
    'pos_access' => 'Access POS System',
    'manage_sales' => 'Manage Sales/Transactions',
    'manage_items' => 'Manage Menu Items',
    'manage_customers' => 'Manage Customers',
    'manage_bookings' => 'Manage Bookings',
    'manage_tables' => 'Manage Dining Tables',
    'view_reports' => 'View Reports',
    'manage_expenses' => 'Manage Expenses',
    'manage_users' => 'Manage Users (Admin only)',
    'company_settings' => 'Company Settings',
    'view_all_sales' => 'View All Sales Reports',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Delete existing permissions
    $stmt = $pdo->prepare("DELETE FROM user_permissions WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    // Insert new permissions
    foreach ($permissions as $key => $label) {
        $value = isset($_POST[$key]) ? 1 : 0;
        $stmt = $pdo->prepare("INSERT INTO user_permissions (user_id, permission_key, permission_value) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $key, $value]);
    }
    
    $success = "Permissions updated successfully!";
}

// Get current permissions
$current_perms = [];
$stmt = $pdo->prepare("SELECT permission_key, permission_value FROM user_permissions WHERE user_id = ?");
$stmt->execute([$user_id]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $current_perms[$row['permission_key']] = $row['permission_value'];
}
?>

<style>
    .permissions-card {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .permission-group {
        border: 1px solid #e0e0e0;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 15px;
    }
    .permission-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px;
        border-bottom: 1px solid #f0f0f0;
    }
    .permission-item:last-child {
        border-bottom: none;
    }
    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
    }
    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .3s;
        border-radius: 24px;
    }
    .toggle-slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .3s;
        border-radius: 50%;
    }
    input:checked + .toggle-slider {
        background-color: #28a745;
    }
    input:checked + .toggle-slider:before {
        transform: translateX(26px);
    }
</style>

<div class="permissions-card">
    <h4 class="mb-4">
        <i class="fas fa-key"></i> Permissions for: <?= htmlspecialchars($user['username']) ?>
        <span class="role-badge role-<?= $user['role'] ?>"><?= ucfirst($user['role']) ?></span>
    </h4>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <form method="post">
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> 
            <strong>Note:</strong> Permissions override role-based access. If a permission is disabled, the user cannot access that feature regardless of role.
        </div>
        
        <?php foreach ($permissions as $key => $label): ?>
        <div class="permission-item">
            <div>
                <strong><?= $label ?></strong>
                <br>
                <small class="text-muted">Permission key: <?= $key ?></small>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" name="<?= $key ?>" value="1" 
                    <?= isset($current_perms[$key]) && $current_perms[$key] ? 'checked' : '' ?>
                    <?= $user['role'] == 'admin' && $key == 'manage_users' ? 'disabled' : '' ?>>
                <span class="toggle-slider"></span>
            </label>
        </div>
        <?php endforeach; ?>
        
        <div class="mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Permissions
            </button>
            <a href="users.php" class="btn btn-secondary">Back to Users</a>
        </div>
    </form>
</div>

<?php require_once 'footer.php'; ?>