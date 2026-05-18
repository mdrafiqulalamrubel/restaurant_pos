<?php
$page_title = 'User Management';
$page_icon = 'users-cog';
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Check if current user is admin - with debug output
if ($_SESSION['role'] !== 'admin') {
    // Instead of silent redirect, show error message for debugging
    if (isset($_GET['debug'])) {
        echo "Your role is: " . ($_SESSION['role'] ?? 'NOT SET') . "<br>";
        echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "<br>";
        echo "<a href='debug_session.php'>Run Debug</a>";
        exit;
    }
    header('Location: index.php');
    exit;
}

// Handle user deletion
if (isset($_GET['delete']) && $_GET['delete'] != $_SESSION['user_id']) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header('Location: users.php');
    exit;
}

// Handle user status toggle
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    header('Location: users.php');
    exit;
}

require_once 'header.php';

$users = $pdo->query("SELECT * FROM users ORDER BY role, username")->fetchAll(PDO::FETCH_ASSOC);

// Calculate role counts properly
$admin_count = 0;
$manager_count = 0;
$staff_count = 0;
foreach ($users as $user) {
    if ($user['role'] == 'admin') $admin_count++;
    elseif ($user['role'] == 'manager') $manager_count++;
    else $staff_count++;
}

$company = $pdo->query("SELECT * FROM company_settings WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
$currency = $company['currency'] ?? '€';
?>

<style>
    .stats-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        text-align: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .stats-card i {
        font-size: 2rem;
        color: #667eea;
        margin-bottom: 10px;
    }
    .stats-card h3 {
        font-size: 2rem;
        margin: 10px 0;
    }
    .role-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-block;
    }
    .role-admin { background: #dc3545; color: white; }
    .role-manager { background: #ffc107; color: #333; }
    .role-staff { background: #17a2b8; color: white; }
    .role-cashier { background: #28a745; color: white; }
</style>

<div class="row">
    <div class="col-md-3">
        <div class="stats-card">
            <i class="fas fa-users"></i>
            <h3><?= count($users) ?></h3>
            <div>Total Users</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <i class="fas fa-user-shield"></i>
            <h3><?= $admin_count ?></h3>
            <div>Administrators</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <i class="fas fa-user-tie"></i>
            <h3><?= $manager_count ?></h3>
            <div>Managers</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <i class="fas fa-user"></i>
            <h3><?= $staff_count ?></h3>
            <div>Staff & Cashiers</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5><i class="fas fa-list"></i> System Users</h5>
        <a href="user_add.php" class="btn btn-primary btn-sm">
            <i class="fas fa-user-plus"></i> Add New User
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th><th>Last Login</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                        <td><?= htmlspecialchars($user['full_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($user['email'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($user['phone'] ?? '-') ?></td>
                        <td><span class="role-badge role-<?= $user['role'] ?>"><?= ucfirst($user['role']) ?></span></td>
                        <td><?= $user['is_active'] ? '<span class="text-success">Active</span>' : '<span class="text-danger">Inactive</span>' ?></td>
                        <td><?= $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : '-' ?></td>
                        <td>
                            <a href="user_edit.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <a href="user_permissions.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-info"><i class="fas fa-key"></i></a>
                                <a href="?toggle_status=1&id=<?= $user['id'] ?>" class="btn btn-sm btn-secondary" onclick="return confirm('Toggle user status?')"><i class="fas fa-power-off"></i></a>
                                <a href="?delete=<?= $user['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete user?')"><i class="fas fa-trash"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>