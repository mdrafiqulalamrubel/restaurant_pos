<?php
$page_title = 'User Management';
$page_icon = 'users-cog';
require_once 'config.php';
require_once 'access_check.php';

// If access denied, show error and exit
if ($access_denied) {
    require_once 'header.php';
    echo '<div class="alert alert-danger text-center p-5">
            <i class="fas fa-lock fa-3x mb-3 d-block"></i>
            <h4>Access Denied!</h4>
            <p>You do not have permission to access this page.</p>
            <p>Please contact the system administrator.</p>
            <a href="index.php" class="btn btn-primary mt-3">Go to Dashboard</a>
          </div>';
    require_once 'footer.php';
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
$role_counts = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role")->fetchAll(PDO::FETCH_ASSOC);
$company = $pdo->query("SELECT * FROM company_settings WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
$currency = $company['currency'] ?? '€';
?>

<!-- Rest of your users.php HTML content... -->
<style>
    .stats-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        text-align: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
            <i class="fas fa-users fa-2x text-primary mb-2 d-block"></i>
            <h3><?= count($users) ?></h3>
            <div>Total Users</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <i class="fas fa-user-shield fa-2x text-danger mb-2 d-block"></i>
            <h3><?= array_sum(array_column(array_filter($role_counts, function($r) { return $r['role'] == 'admin'; }), 'count')) ?></h3>
            <div>Administrators</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <i class="fas fa-user-tie fa-2x text-warning mb-2 d-block"></i>
            <h3><?= array_sum(array_column(array_filter($role_counts, function($r) { return $r['role'] == 'manager'; }), 'count')) ?></h3>
            <div>Managers</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <i class="fas fa-user fa-2x text-success mb-2 d-block"></i>
            <h3><?= array_sum(array_column(array_filter($role_counts, function($r) { return in_array($r['role'], ['staff', 'cashier']); }), 'count')) ?></h3>
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
                    <tr><th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th><th>Last Login</th><th>Actions</th></tr>
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