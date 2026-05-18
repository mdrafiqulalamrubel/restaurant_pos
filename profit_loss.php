<?php
$page_title = 'Profit & Loss Report';
$page_icon = 'chart-line';
require_once 'config.php';
require_once 'header.php';

$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-d');

// Calculate Income (Sales)
$sales = $pdo->prepare("SELECT COALESCE(SUM(total), 0) as total_sales, COUNT(*) as sale_count FROM sales WHERE DATE(created_at) BETWEEN ? AND ?");
$sales->execute([$from_date, $to_date]);
$sales_data = $sales->fetch();

// Calculate Cost of Goods Sold (Production costs)
$production_cost = $pdo->prepare("SELECT COALESCE(SUM(total_cost), 0) as total_production_cost FROM productions WHERE DATE(production_date) BETWEEN ? AND ?");
$production_cost->execute([$from_date, $to_date]);
$production_data = $production_cost->fetch();

// Calculate Other Expenses
$other_expenses = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total_expenses FROM expenses WHERE DATE(expense_date) BETWEEN ? AND ? AND is_production_cost = 0");
$other_expenses->execute([$from_date, $to_date]);
$expense_data = $other_expenses->fetch();

// Calculate Cash Transactions
$cash_in = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE transaction_type IN ('income', 'deposit') AND DATE(transaction_date) BETWEEN ? AND ?");
$cash_in->execute([$from_date, $to_date]);
$cash_income = $cash_in->fetchColumn();

$cash_out = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE transaction_type IN ('expense', 'withdraw') AND DATE(transaction_date) BETWEEN ? AND ?");
$cash_out->execute([$from_date, $to_date]);
$cash_expense = $cash_out->fetchColumn();

$gross_profit = $sales_data['total_sales'] - $production_data['total_production_cost'];
$net_profit = $gross_profit - $expense_data['total_expenses'];
$profit_margin = $sales_data['total_sales'] > 0 ? ($net_profit / $sales_data['total_sales']) * 100 : 0;
?>

<style>
    .report-card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    .profit-positive { color: #28a745; font-weight: bold; }
    .profit-negative { color: #dc3545; font-weight: bold; }
    .summary-number { font-size: 1.8rem; font-weight: bold; }
</style>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-4">
                <label>From Date</label>
                <input type="date" name="from_date" class="form-control" value="<?= $from_date ?>">
            </div>
            <div class="col-md-4">
                <label>To Date</label>
                <input type="date" name="to_date" class="form-control" value="<?= $to_date ?>">
            </div>
            <div class="col-md-4">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">Generate Report</button>
            </div>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="report-card">
            <h4><i class="fas fa-chart-line"></i> Income Statement</h4>
            <hr>
            <div class="d-flex justify-content-between mb-2">
                <span>Total Sales (Income):</span>
                <strong><?= number_format($sales_data['total_sales'], 2) ?></strong>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span>Number of Sales:</span>
                <span><?= $sales_data['sale_count'] ?> transactions</span>
            </div>
            <div class="d-flex justify-content-between mb-2 text-muted">
                <span>Less: Cost of Goods Sold (Production):</span>
                <span class="expense-text">-<?= number_format($production_data['total_production_cost'], 2) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2" style="border-top: 1px solid #ddd; padding-top: 10px;">
                <span><strong>Gross Profit:</strong></span>
                <strong class="<?= $gross_profit >= 0 ? 'profit-positive' : 'profit-negative' ?>">
                    <?= number_format($gross_profit, 2) ?>
                </strong>
            </div>
            <div class="d-flex justify-content-between mb-2 text-muted">
                <span>Less: Other Expenses:</span>
                <span class="expense-text">-<?= number_format($expense_data['total_expenses'], 2) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2" style="border-top: 2px solid #667eea; padding-top: 15px; margin-top: 10px;">
                <span><strong>Net Profit / Loss:</strong></span>
                <strong class="<?= $net_profit >= 0 ? 'profit-positive' : 'profit-negative' ?> summary-number">
                    <?= number_format($net_profit, 2) ?>
                </strong>
            </div>
            <div class="d-flex justify-content-between">
                <span>Profit Margin:</span>
                <span class="<?= $profit_margin >= 0 ? 'profit-positive' : 'profit-negative' ?>">
                    <?= number_format($profit_margin, 2) ?>%
                </span>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="report-card">
            <h4><i class="fas fa-calculator"></i> Cash Flow Summary</h4>
            <hr>
            <div class="d-flex justify-content-between mb-2">
                <span>Cash Inflow:</span>
                <strong class="income-text">+<?= number_format($cash_income, 2) ?></strong>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span>Cash Outflow:</span>
                <strong class="expense-text">-<?= number_format($cash_expense, 2) ?></strong>
            </div>
            <div class="d-flex justify-content-between" style="border-top: 1px solid #ddd; padding-top: 10px;">
                <span><strong>Net Cash Flow:</strong></span>
                <strong class="<?= ($cash_income - $cash_expense) >= 0 ? 'profit-positive' : 'profit-negative' ?>">
                    <?= number_format($cash_income - $cash_expense, 2) ?>
                </strong>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="report-card">
            <h5><i class="fas fa-chart-pie"></i> Expense Breakdown</h5>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr><th>Expense Category</th><th class="text-end">Amount</th><th>% of Total</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        $expense_cats = $pdo->prepare("SELECT category, SUM(amount) as total FROM expenses WHERE DATE(expense_date) BETWEEN ? AND ? AND is_production_cost = 0 GROUP BY category ORDER BY total DESC");
                        $expense_cats->execute([$from_date, $to_date]);
                        $total_exp = $expense_data['total_expenses'];
                        foreach ($expense_cats->fetchAll() as $cat):
                            $percent = $total_exp > 0 ? ($cat['total'] / $total_exp) * 100 : 0;
                        ?>
                        <tr>
                            <td><?= $cat['category'] ?></td>
                            <td class="text-end expense-text">-<?= number_format($cat['total'], 2) ?></td>
                            <td><?= number_format($percent, 1) ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>