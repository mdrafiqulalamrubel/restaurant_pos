<?php
$page_title = 'Cash Balance Report';
$page_icon = 'chart-line';
require_once 'config.php';
require_once 'header.php';

$report_date = $_GET['date'] ?? date('Y-m-d');

// Get cash sales for the day
$cash_sales = $pdo->prepare("
    SELECT SUM(total) as total, COUNT(*) as count 
    FROM sales 
    WHERE DATE(created_at) = ? AND payment_method = 'cash'
");
$cash_sales->execute([$report_date]);
$sales_data = $cash_sales->fetch(PDO::FETCH_ASSOC);

// Get cash expenses for the day
$cash_expenses = $pdo->prepare("
    SELECT SUM(amount) as total, COUNT(*) as count 
    FROM expenses 
    WHERE expense_date = ? AND payment_method = 'cash'
");
$cash_expenses->execute([$report_date]);
$expense_data = $cash_expenses->fetch(PDO::FETCH_ASSOC);

// Get other payment methods
$card_sales = $pdo->prepare("SELECT SUM(total) FROM sales WHERE DATE(created_at) = ? AND payment_method = 'card'");
$card_sales->execute([$report_date]);
$card_total = $card_sales->fetchColumn();

$bkash_sales = $pdo->prepare("SELECT SUM(total) FROM sales WHERE DATE(created_at) = ? AND payment_method = 'bkash'");
$bkash_sales->execute([$report_date]);
$bkash_total = $bkash_sales->fetchColumn();

$nagad_sales = $pdo->prepare("SELECT SUM(total) FROM sales WHERE DATE(created_at) = ? AND payment_method = 'nagad'");
$nagad_sales->execute([$report_date]);
$nagad_total = $nagad_sales->fetchColumn();

$opening_balance = $_GET['opening'] ?? 0;
$closing_balance = $opening_balance + ($sales_data['total'] ?? 0) - ($expense_data['total'] ?? 0);
?>

<style>
    .balance-card {
        background: white;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .cash-box {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 15px;
        margin: 10px 0;
    }
    .total-box {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
    }
</style>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="balance-card">
            <h4><i class="fas fa-calculator"></i> Daily Cash Balance Report</h4>
            <hr>
            
            <form method="get" class="row g-2 mb-4">
                <div class="col-md-4">
                    <label>Report Date</label>
                    <input type="date" name="date" class="form-control" value="<?= $report_date ?>">
                </div>
                <div class="col-md-4">
                    <label>Opening Balance</label>
                    <input type="number" name="opening" class="form-control" step="0.01" value="<?= $opening_balance ?>">
                </div>
                <div class="col-md-4">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Generate Report</button>
                </div>
            </form>
            
            <div class="cash-box">
                <h6>Cash Income</h6>
                <div class="d-flex justify-content-between">
                    <span>Cash Sales:</span>
                    <strong><?= money($sales_data['total'] ?? 0) ?></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Number of Cash Transactions:</span>
                    <strong><?= $sales_data['count'] ?? 0 ?></strong>
                </div>
            </div>
            
            <div class="cash-box">
                <h6>Cash Expenses</h6>
                <div class="d-flex justify-content-between">
                    <span>Total Expenses (Cash):</span>
                    <strong class="text-danger"><?= money($expense_data['total'] ?? 0) ?></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Number of Expense Transactions:</span>
                    <strong><?= $expense_data['count'] ?? 0 ?></strong>
                </div>
            </div>
            
            <div class="cash-box">
                <h6>Other Payments Received</h6>
                <div class="d-flex justify-content-between">
                    <span>Card Payments:</span>
                    <strong><?= money($card_total ?? 0) ?></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>bKash Payments:</span>
                    <strong><?= money($bkash_total ?? 0) ?></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Nagad Payments:</span>
                    <strong><?= money($nagad_total ?? 0) ?></strong>
                </div>
            </div>
            
            <div class="total-box mt-3">
                <h5>Cash Balance Summary</h5>
                <div class="d-flex justify-content-between">
                    <span>Opening Balance:</span>
                    <strong><?= money($opening_balance) ?></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Cash Received:</span>
                    <strong>+ <?= money($sales_data['total'] ?? 0) ?></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Cash Paid Out:</span>
                    <strong>- <?= money($expense_data['total'] ?? 0) ?></strong>
                </div>
                <hr style="background:white">
                <div class="d-flex justify-content-between" style="font-size: 1.3rem;">
                    <span><strong>Closing Balance:</strong></span>
                    <span><strong><?= money($closing_balance) ?></strong></span>
                </div>
            </div>
            
            <div class="text-center mt-3">
                <button class="btn btn-secondary" onclick="window.print()">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>