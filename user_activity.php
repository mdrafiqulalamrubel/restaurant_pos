<?php
$page_title = 'User Activity Log';
$page_icon = 'history';
require_once 'config.php';
require_once 'header.php';

// Check if current user is admin
if ($_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$logs = $pdo->query("
    SELECT l.*, u.username, u.full_name 
    FROM user_activity_log l 
    JOIN users u ON l.user_id = u.id 
    ORDER BY l.created_at DESC 
    LIMIT 100
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-history"></i> User Activity Log</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($log['username']) ?></strong>
                            <?php if ($log['full_name']): ?>
                                <br><small><?= htmlspecialchars($log['full_name']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-secondary"><?= htmlspecialchars($log['action']) ?></span>
                        </td>
                        <td><?= htmlspecialchars($log['details'] ?? '-') ?></td>
                        <td><?= $log['ip_address'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>