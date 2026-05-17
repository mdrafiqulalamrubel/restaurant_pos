<?php
$page_title = 'User Management';
$page_icon = 'users-cog';
require_once 'config.php';
require_once 'header.php';

// Check if current user is admin
if ($_SESSION['role'] !== 'admin') {
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

$users = $pdo->query("SELECT * FROM users ORDER BY role, username")->fetchAll(PDO::FETCH_ASSOC);

$role_counts = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role")->fetchAll(PDO::FETCH_ASSOC);
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
    .role-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .role-admin { background: #dc3545; color: white; }
    .role-manager { background: #ffc107; color: #333; }
    .role-staff { background: #17a2b8; color: white; }
    .role-cashier { background: #28a745; color: white; }
    .status-active { color: #28a745; }
    .status-inactive { color: #dc3545; }
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
            <h3><?= array_sum(array_column(array_filter($role_counts, function($r) { return $r['role'] == 'admin'; }), 'count')) ?></h3>
            <div>Admins</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <i class="fas fa-user-tie"></i>
            <h3><?= array_sum(array_column(array_filter($role_counts, function($r) { return $r['role'] == 'manager'; }), 'count')) ?></h3>
            <div>Managers</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <i class="fas fa-user"></i>
            <h3><?= array_sum(array_column(array_filter($role_counts, function($r) { return in_array($r['role'], ['staff', 'cashier']); }), 'count')) ?></h3>
            <div>Staff</div>
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
                        <th>ID</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
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
                        <td>
                            <span class="role-badge role-<?= $user['role'] ?>">
                                <?= ucfirst($user['role']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($user['is_active']): ?>
                                <span class="status-active"><i class="fas fa-check-circle"></i> Active</span>
                            <?php else: ?>
                                <span class="status-inactive"><i class="fas fa-ban"></i> Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : '-' ?></td>
                        <td>
                            <a href="user_edit.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <a href="user_permissions.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-key"></i>
                                </a>
                                <a href="?toggle_status=1&id=<?= $user['id'] ?>" class="btn btn-sm btn-secondary" onclick="return confirm('Toggle user status?')">
                                    <i class="fas fa-power-off"></i>
                                </a>
                                <a href="?delete=<?= $user['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete user? This cannot be undone.')">
                                    <i class="fas fa-trash"></i>
                                </a>
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