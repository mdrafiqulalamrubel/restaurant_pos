<?php
$page_title = 'Branch Report';
$page_icon = 'chart-line';
require_once 'config.php';
require_once 'header.php';

$branch_id = $_GET['id'] ?? 0;
$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-d');

// Get branch details
$stmt = $pdo->prepare("SELECT * FROM branches WHERE id = ?");
$stmt->execute([$branch_id]);
$branch = $stmt->fetch();

if (!$branch) {
    die("Branch not found");
}

// Get branch sales
$sales = $pdo->prepare("
    SELECT COALESCE(SUM(total), 0) as total_sales, COUNT(*) as transaction_count 
    FROM sales WHERE branch_id = ? AND DATE(created_at) BETWEEN ? AND ?
");
$sales->execute([$branch_id, $from_date, $to_date]);
$sales_data = $sales->fetch();

// Get branch expenses
$expenses = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0) as total_expenses 
    FROM expenses WHERE branch_id = ? AND DATE(expense_date) BETWEEN ? AND ?
");
$expenses->execute([$branch_id, $from_date, $to_date]);
$expense_data = $expenses->fetch();

$profit = $sales_data['total_sales'] - $expense_data['total_expenses'];
$profit_margin = $sales_data['total_sales'] > 0 ? ($profit / $sales_data['total_sales']) * 100 : 0;

// Get daily breakdown
$daily = $pdo->prepare("
    SELECT DATE(created_at) as date, COUNT(*) as count, SUM(total) as total 
    FROM sales 
    WHERE branch_id = ? AND DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY date DESC
");
$daily->execute([$branch_id, $from_date, $to_date]);
$daily_data = $daily->fetchAll();
?>

<style>
    .report-card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    .stat-number { font-size: 1.8rem; font-weight: bold; }
    .profit-positive { color: #28a745; }
    .profit-negative { color: #dc3545; }
</style>

<div class="row">
    <div class="col-md-12">
        <div class="report-card">
            <h4><i class="fas fa-store"></i> Branch Report: <?= htmlspecialchars($branch['name']) ?></h4>
            <p class="text-muted">Code: <?= $branch['code'] ?> | Manager: <?= $branch['manager_name'] ?? 'Not assigned' ?></p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="report-card text-center">
            <i class="fas fa-chart-line fa-2x text-primary"></i>
            <div class="stat-number"><?= number_format($sales_data['total_sales'], 2) ?></div>
            <div>Total Sales</div>
            <small><?= $sales_data['transaction_count'] ?> transactions</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="report-card text-center">
            <i class="fas fa-money-bill-wave fa-2x text-danger"></i>
            <div class="stat-number"><?= number_format($expense_data['total_expenses'], 2) ?></div>
            <div>Total Expenses</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="report-card text-center">
            <i class="fas fa-chart-line fa-2x text-success"></i>
            <div class="stat-number <?= $profit >= 0 ? 'profit-positive' : 'profit-negative' ?>">
                <?= number_format($profit, 2) ?>
            </div>
            <div>Net Profit/Loss</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="report-card text-center">
            <i class="fas fa-percent fa-2x text-info"></i>
            <div class="stat-number <?= $profit_margin >= 0 ? 'profit-positive' : 'profit-negative' ?>">
                <?= number_format($profit_margin, 1) ?>%
            </div>
            <div>Profit Margin</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-calendar"></i> Daily Sales Breakdown</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr><th>Date</th><th>Transactions</th><th>Sales Amount</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($daily_data as $day): ?>
                    <tr>
                        <td><?= date('d-m-Y', strtotime($day['date'])) ?></td>
                        <td><?= $day['count'] ?></td>
                        <td class="fw-bold"><?= number_format($day['total'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    <a href="branches.php" class="btn btn-secondary">← Back to Branches</a>
</div>

<?php require_once 'footer.php'; ?>