<?php
$page_title = 'Cash Management';
$page_icon = 'money-bill-wave';
require_once 'config.php';
require_once 'header.php';

require_once 'acc_core.php';
require_once 'accounting.php';

// Handle cash transactions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['transaction_type'];
    $amount = $_POST['amount'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    
    $stmt = $pdo->prepare("INSERT INTO cash_transactions (transaction_type, amount, category, description, transaction_date, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$type, $amount, $category, $description, date('Y-m-d'), $_SESSION['user_id']]);
    $tx_id = $pdo->lastInsertId();
    
    // Record in accounting system
    acc_record_cash_transaction($tx_id, $type, $amount, $description);
    
    $success = "Transaction recorded successfully!";
}

// Then include header
require_once 'header.php';

// Get true cash balance from accounting system
$cash_account = acc_get_account_by_code('1000');
$cash_balance = $cash_account ? acc_get_account_balance($cash_account['id']) : 0;

$income = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE transaction_type IN ('income', 'deposit')")->fetchColumn();
$expenses = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE transaction_type IN ('expense', 'withdraw')")->fetchColumn();

// Get recent transactions
$transactions = $pdo->query("SELECT * FROM cash_transactions ORDER BY created_at DESC LIMIT 50")->fetchAll();

// Get daily summary
$daily_summary = $pdo->query("
    SELECT DATE(transaction_date) as date, 
           SUM(CASE WHEN transaction_type IN ('income', 'deposit') THEN amount ELSE 0 END) as total_in,
           SUM(CASE WHEN transaction_type IN ('expense', 'withdraw') THEN amount ELSE 0 END) as total_out
    FROM cash_transactions 
    WHERE transaction_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(transaction_date)
    ORDER BY date DESC
")->fetchAll();
?>

<style>
    .balance-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
        padding: 25px;
        text-align: center;
        margin-bottom: 20px;
    }
    .balance-number { font-size: 2.5rem; font-weight: bold; }
    .stats-box { background: white; border-radius: 10px; padding: 15px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .income-text { color: #28a745; }
    .expense-text { color: #dc3545; }
</style>

<div class="row">
    <div class="col-md-4">
        <div class="balance-card">
            <h6>Cash in Hand</h6>
            <div class="balance-number"><?= number_format($cash_balance, 2) ?></div>
            <small>As of <?= date('d-m-Y H:i') ?></small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stats-box">
            <i class="fas fa-arrow-down fa-2x income-text"></i>
            <h3 class="income-text">+<?= number_format($income, 2) ?></h3>
            <div>Total Income</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stats-box">
            <i class="fas fa-arrow-up fa-2x expense-text"></i>
            <h3 class="expense-text">-<?= number_format($expenses, 2) ?></h3>
            <div>Total Expenses</div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-plus-circle"></i> Add Transaction</h5>
            </div>
            <div class="card-body">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>
                
                <form method="post">
                    <div class="mb-2">
                        <label>Transaction Type</label>
                        <select name="transaction_type" class="form-control" required>
                            <option value="income">Income (Sales/Cash In)</option>
                            <option value="expense">Expense (Cash Out)</option>
                            <option value="deposit">Deposit to Bank</option>
                            <option value="withdraw">Withdraw from Bank</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label>Amount</label>
                        <input type="number" name="amount" class="form-control" step="0.01" required>
                    </div>
                    <div class="mb-2">
                        <label>Category</label>
                        <select name="category" class="form-control">
                            <option value="Sales">Sales Income</option>
                            <option value="Production">Production Cost</option>
                            <option value="Salary">Salary</option>
                            <option value="Rent">Rent</option>
                            <option value="Utilities">Utilities</option>
                            <option value="Supplier">Supplier Payment</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Transaction details..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Record Transaction</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list"></i> Recent Transactions</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr><th>Date</th><th>Type</th><th>Category</th><th>Description</th><th class="text-end">Amount</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td><?= date('d-m-Y H:i', strtotime($t['created_at'])) ?></td>
                                <td>
                                    <?php
                                    $icons = ['income' => '↓', 'expense' => '↑', 'deposit' => '🏦', 'withdraw' => '💰'];
                                    $colors = ['income' => 'green', 'expense' => 'red', 'deposit' => 'blue', 'withdraw' => 'orange'];
                                    ?>
                                    <span style="color: <?= $colors[$t['transaction_type']] ?>"><?= $icons[$t['transaction_type']] ?> <?= ucfirst($t['transaction_type']) ?></span>
                                </td>
                                <td><?= $t['category'] ?></td>
                                <td><?= htmlspecialchars($t['description'] ?? '-') ?></td>
                                <td class="text-end <?= in_array($t['transaction_type'], ['expense', 'withdraw']) ? 'expense-text' : 'income-text' ?>">
                                    <?= in_array($t['transaction_type'], ['expense', 'withdraw']) ? '-' : '+' ?><?= number_format($t['amount'], 2) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>