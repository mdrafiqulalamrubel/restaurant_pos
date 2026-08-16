<?php
$page_title = 'Returns Report';
$page_icon = 'chart-bar';
require_once 'config.php';
require_once 'header.php';

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Get Sales Returns
$stmt = $pdo->prepare("
    SELECT sr.return_date as date, 'Sales Return' as type, CONCAT('Sale #', sr.sale_id) as reference, sr.total_refunded as amount 
    FROM sales_returns sr 
    WHERE DATE(sr.return_date) BETWEEN ? AND ?
");
$stmt->execute([$start_date, $end_date]);
$sales_returns = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get Purchase Returns
$stmt = $pdo->prepare("
    SELECT pr.return_date as date, 'Purchase Return' as type, CONCAT('Purchase #', pr.purchase_id) as reference, pr.total_refunded as amount 
    FROM purchase_returns pr 
    WHERE DATE(pr.return_date) BETWEEN ? AND ?
");
$stmt->execute([$start_date, $end_date]);
$purchase_returns = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Combine and sort
$all_returns = array_merge($sales_returns, $purchase_returns);
usort($all_returns, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']); // Descending
});

$total_sales_refunds = array_sum(array_column($sales_returns, 'amount'));
$total_purchase_refunds = array_sum(array_column($purchase_returns, 'amount'));
?>

<div class="card mb-4 no-print">
    <div class="card-body">
        <form class="row align-items-center" method="GET">
            <div class="col-auto">
                <label>Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>">
            </div>
            <div class="col-auto">
                <label>End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>">
            </div>
            <div class="col-auto mt-4">
                <button type="submit" class="btn btn-primary">Filter</button>
                <button type="button" class="btn btn-secondary" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
            </div>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card text-white bg-danger mb-3">
            <div class="card-body">
                <h5 class="card-title">Total Sales Refunds (Money Out)</h5>
                <h3 class="card-text"><?= money($total_sales_refunds) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card text-white bg-success mb-3">
            <div class="card-body">
                <h5 class="card-title">Total Purchase Refunds (Money In)</h5>
                <h3 class="card-text"><?= money($total_purchase_refunds) ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0">Combined Returns Register</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Type</th>
                        <th>Reference</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_returns as $r): ?>
                    <tr>
                        <td><?= date('M d, Y h:i A', strtotime($r['date'])) ?></td>
                        <td>
                            <?php if ($r['type'] == 'Sales Return'): ?>
                                <span class="badge bg-danger">Sales Return</span>
                            <?php else: ?>
                                <span class="badge bg-success">Purchase Return</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $r['reference'] ?></td>
                        <td class="text-end fw-bold <?= $r['type'] == 'Sales Return' ? 'text-danger' : 'text-success' ?>">
                            <?= $r['type'] == 'Sales Return' ? '-' : '+' ?><?= money($r['amount']) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($all_returns)): ?>
                    <tr><td colspan="4" class="text-center">No returns found in this date range.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
@media print {
    .no-print { display: none !important; }
    .card { border: none !important; box-shadow: none !important; }
    .bg-danger, .bg-success, .bg-dark { background: transparent !important; color: #000 !important; }
    .text-white { color: #000 !important; }
}
</style>

<?php require_once 'footer.php'; ?>
