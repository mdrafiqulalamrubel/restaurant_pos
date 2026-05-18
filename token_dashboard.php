<?php
// token_dashboard.php - Token Management Dashboard for Kitchen
$page_title = 'Token Dashboard';
$page_icon = 'ticket-alt';
require_once 'config.php';
require_once 'header.php';

// Get today's tokens
$today = date('Y-m-d');
$tokens = $pdo->prepare("
    SELECT t.*, s.total, s.payment_method,
           GROUP_CONCAT(DISTINCT i.name SEPARATOR ', ') as items
    FROM order_tokens t
    JOIN sales s ON t.sale_id = s.id
    JOIN sale_items si ON s.id = si.sale_id
    JOIN items i ON si.item_id = i.id
    WHERE t.token_date = ?
    GROUP BY t.id
    ORDER BY t.token_number ASC
");
$tokens->execute([$today]);
$token_list = $tokens->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$total_tokens = count($token_list);
$pending_tokens = count(array_filter($token_list, function($t) { return $t['status'] == 'pending'; }));
$printed_tokens = count(array_filter($token_list, function($t) { return $t['status'] == 'printed'; }));
$completed_tokens = count(array_filter($token_list, function($t) { return $t['status'] == 'completed'; }));
?>

<style>
    .token-stats {
        background: white;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .token-stats h2 { font-size: 2rem; margin: 10px 0; }
    .token-card {
        background: white;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 15px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        transition: all 0.3s;
    }
    .token-card.pending { border-left: 5px solid #ff9800; }
    .token-card.printed { border-left: 5px solid #2196f3; }
    .token-card.completed { border-left: 5px solid #4caf50; opacity: 0.7; }
    .token-number { font-size: 1.5rem; font-weight: bold; }
    .status-badge {
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: bold;
    }
    .status-pending { background: #ff9800; color: white; }
    .status-printed { background: #2196f3; color: white; }
    .status-completed { background: #4caf50; color: white; }
</style>

<div class="row">
    <div class="col-md-3">
        <div class="token-stats">
            <i class="fas fa-ticket-alt fa-2x text-primary"></i>
            <h2><?= $total_tokens ?></h2>
            <div>Total Tokens Today</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="token-stats">
            <i class="fas fa-clock fa-2x text-warning"></i>
            <h2><?= $pending_tokens ?></h2>
            <div>Pending Orders</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="token-stats">
            <i class="fas fa-print fa-2x text-info"></i>
            <h2><?= $printed_tokens ?></h2>
            <div>Printed Tokens</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="token-stats">
            <i class="fas fa-check-circle fa-2x text-success"></i>
            <h2><?= $completed_tokens ?></h2>
            <div>Completed Orders</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-list"></i> Today's Order Tokens (<?= date('d-m-Y') ?>)</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <?php foreach ($token_list as $token): ?>
            <div class="col-md-6 col-lg-4">
                <div class="token-card <?= $token['status'] ?>">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="token-number">#<?= str_pad($token['token_number'], 3, '0', STR_PAD_LEFT) ?></span>
                        <span class="status-badge status-<?= $token['status'] ?>">
                            <?= ucfirst($token['status']) ?>
                        </span>
                    </div>
                    <hr>
                    <div class="small">
                        <strong>Items:</strong> <?= htmlspecialchars(substr($token['items'], 0, 50)) ?>...
                    </div>
                    <div class="small mt-1">
                        <strong>Total:</strong> €<?= number_format($token['total'], 2) ?>
                    </div>
                    <div class="small">
                        <strong>Payment:</strong> <?= ucfirst($token['payment_method']) ?>
                    </div>
                    <?php if ($token['printed_at']): ?>
                    <div class="small text-muted">
                        Printed: <?= date('H:i', strtotime($token['printed_at'])) ?>
                    </div>
                    <?php endif; ?>
                    <div class="mt-2">
                        <a href="token_print.php?sale_id=<?= $token['sale_id'] ?>&type=kitchen" class="btn btn-sm btn-warning">🍳 Kitchen</a>
                        <a href="token_print.php?sale_id=<?= $token['sale_id'] ?>&type=customer" class="btn btn-sm btn-success">🎫 Customer</a>
                        <a href="invoice.php?id=<?= $token['sale_id'] ?>" class="btn btn-sm btn-info">📄 Invoice</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($token_list)): ?>
            <div class="col-12">
                <p class="text-center text-muted">No tokens generated today.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>