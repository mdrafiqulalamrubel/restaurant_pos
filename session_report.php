<?php
$page_title = 'Session Report';
$page_icon = 'file-invoice';
require_once 'config.php';
require_once 'header.php';

$session_id = $_GET['id'] ?? 0;

// Get session details
$stmt = $pdo->prepare("
    SELECT s.*, u.username as user_name, u2.username as closed_by_name, b.name as branch_name
    FROM pos_sessions s
    LEFT JOIN users u ON s.user_id = u.id
    LEFT JOIN users u2 ON s.closed_by = u2.id
    LEFT JOIN branches b ON s.branch_id = b.id
    WHERE s.id = ?
");
$stmt->execute([$session_id]);
$session = $stmt->fetch();

if (!$session) {
    die("Session not found");
}

// Get sales during session
$sales = $pdo->prepare("
    SELECT * FROM sales WHERE session_id = ? ORDER BY id DESC
");
$sales->execute([$session_id]);
$sales_data = $sales->fetchAll();

// Get expenses during session
$expenses = $pdo->prepare("
    SELECT * FROM expenses WHERE session_id = ? ORDER BY id DESC
");
$expenses->execute([$session_id]);
$expenses_data = $expenses->fetchAll();
?>

<style>
    .report-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 20px;
    }
    .summary-card {
        background: white;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 15px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .amount-positive { color: #28a745; }
    .amount-negative { color: #dc3545; }
    @media print {
        .no-print { display: none; }
        .report-header { background: #333; }
    }
</style>

<div class="report-header">
    <h3><i class="fas fa-chart-line"></i> POS Session Closing Report</h3>
    <p>Session #<?= $session['id'] ?> | Branch: <?= htmlspecialchars($session['branch_name']) ?></p>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="summary-card">
            <h5><i class="fas fa-info-circle"></i> Session Information</h5>
            <table class="table table-sm">
                <tr><td width="40%">Session ID:</td><td><strong>#<?= $session['id'] ?></strong></td></tr>
                <tr><td>Opened By:</td><td><?= htmlspecialchars($session['user_name']) ?></td></tr>
                <tr><td>Opening Time:</td><td><?= date('d/m/Y H:i:s', strtotime($session['opening_time'])) ?></td></tr>
                <tr><td>Closing Time:</td><td><?= $session['closing_time'] ? date('d/m/Y H:i:s', strtotime($session['closing_time'])) : '-' ?></td></tr>
                <tr><td>Closed By:</td><td><?= htmlspecialchars($session['closed_by_name'] ?? '-') ?></td></tr>
                <tr><td>Opening Balance:</td><td class="fw-bold"><?= number_format($session['opening_balance'], 2) ?></td></tr>
                <tr><td>Closing Balance:</td><td class="fw-bold"><?= number_format($session['closing_balance'], 2) ?></td></tr>
            </table>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="summary-card">
            <h5><i class="fas fa-chart-pie"></i> Sales Summary</h5>
            <table class="table table-sm">
                <tr><td width="40%">Cash Sales:</td><td><?= number_format($session['cash_sales'], 2) ?></td></tr>
                <tr><td>Card Sales:</td><td><?= number_format($session['card_sales'], 2) ?></td></tr>
                <tr><td>bKash Sales:</td><td><?= number_format($session['bkash_sales'], 2) ?></td></tr>
                <tr><td>Nagad Sales:</td><td><?= number_format($session['nagad_sales'], 2) ?></td></tr>
                <tr><td><strong>Total Sales:</strong></td><td><strong><?= number_format($session['total_sales'], 2) ?></strong></td></tr>
                <tr><td>Total Expenses:</td><td><?= number_format($session['total_expenses'], 2) ?></td></tr>
            </table>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="summary-card">
            <h5><i class="fas fa-calculator"></i> Cash Reconciliation</h5>
            <table class="table">
                <tr>
                    <td width="30%">Opening Cash Balance:</td>
                    <td><?= number_format($session['opening_balance'], 2) ?></td>
                </tr>
                <tr>
                    <td>Add: Cash Sales:</td>
                    <td>+ <?= number_format($session['cash_sales'], 2) ?></td>
                </tr>
                <tr>
                    <td>Less: Cash Expenses:</td>
                    <td>- <?= number_format($session['total_expenses'], 2) ?></td>
                </tr>
                <tr class="border-top">
                    <td><strong>Expected Cash Balance:</strong></td>
                    <td><strong><?= number_format($session['expected_cash'], 2) ?></strong></td>
                </tr>
                <tr>
                    <td>Actual Cash Counted:</td>
                    <td><?= number_format($session['actual_cash'], 2) ?></td>
                </tr>
                <tr class="border-top">
                    <td><strong>Cash Difference:</strong></td>
                    <td class="<?= $session['cash_difference'] >= 0 ? 'amount-positive' : 'amount-negative' ?> fw-bold">
                        <?= number_format($session['cash_difference'], 2) ?>
                        <?= $session['cash_difference'] >= 0 ? '(Surplus)' : '(Shortage)' ?>
                    </td>
                </tr>
            </table>
            
            <?php if ($session['notes']): ?>
            <div class="alert alert-info">
                <strong>Notes:</strong> <?= nl2br(htmlspecialchars($session['notes'])) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="summary-card">
            <h5><i class="fas fa-list"></i> Transaction Details</h5>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr><th>Invoice #</th><th>Time</th><th>Customer</th><th>Payment</th><th>Total</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sales_data as $sale): ?>
                        <tr>
                            <td><a href="invoice.php?id=<?= $sale['id'] ?>">#<?= $sale['id'] ?></a></td>
                            <td><?= date('H:i:s', strtotime($sale['created_at'])) ?></td>
                            <td><?= htmlspecialchars($sale['customer_name'] ?? 'Walk-in') ?></td>
                            <td><?= ucfirst($sale['payment_method'] ?? 'cash') ?></td>
                            <td class="text-end"><?= number_format($sale['total'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12 text-center no-print">
        <button class="btn btn-primary" onclick="window.print()"><i class="fas fa-print"></i> Print Report</button>
        <a href="session_manager.php" class="btn btn-secondary">Back to Sessions</a>
        <a href="pos.php" class="btn btn-success">New Sale</a>
    </div>
</div>

<?php require_once 'footer.php'; ?>