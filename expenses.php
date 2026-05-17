<?php
$page_title = 'Expense Management';
$page_icon = 'money-bill-wave';
require_once 'config.php';
require_once 'header.php';

// Create expenses table
$pdo->exec("CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expense_date DATE NOT NULL,
    category VARCHAR(50) NOT NULL,
    description TEXT,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(20) DEFAULT 'cash',
    receipt VARCHAR(255),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Add expense
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $stmt = $pdo->prepare("INSERT INTO expenses (expense_date, category, description, amount, payment_method) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['expense_date'], $_POST['category'], $_POST['description'], $_POST['amount'], $_POST['payment_method']]);
        $success = "Expense added successfully!";
    } elseif ($_POST['action'] === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        header('Location: expenses.php');
        exit;
    }
}

// Get date filter
$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-d');

$expenses = $pdo->prepare("SELECT * FROM expenses WHERE expense_date BETWEEN ? AND ? ORDER BY expense_date DESC, id DESC");
$expenses->execute([$from_date, $to_date]);
$expense_list = $expenses->fetchAll(PDO::FETCH_ASSOC);

// Get totals
$total_expenses = array_sum(array_column($expense_list, 'amount'));

// Get sales total
$sales_total = $pdo->prepare("SELECT COALESCE(SUM(total), 0) FROM sales WHERE DATE(created_at) BETWEEN ? AND ?");
$sales_total->execute([$from_date, $to_date]);
$total_sales = $sales_total->fetchColumn();

$net_profit = $total_sales - $total_expenses;

// Get cash balance
$cash_sales = $pdo->prepare("SELECT COALESCE(SUM(total), 0) FROM sales WHERE DATE(created_at) BETWEEN ? AND ? AND payment_method = 'cash'");
$cash_sales->execute([$from_date, $to_date]);
$cash_income = $cash_sales->fetchColumn();

$cash_expenses = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE expense_date BETWEEN ? AND ? AND payment_method = 'cash'");
$cash_expenses->execute([$from_date, $to_date]);
$cash_out = $cash_expenses->fetchColumn();

$cash_balance = $cash_income - $cash_out;

$expense_categories = ['Rent', 'Utilities', 'Salary', 'Food Cost', 'Marketing', 'Maintenance', 'Equipment', 'Tax', 'Other'];
?>

<style>
    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        text-align: center;
    }
    .stats-card.income { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
    .stats-card.expense { background: linear-gradient(135deg, #ee5a24 0%, #ff6b6b 100%); }
    .stats-card.profit { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
    .stats-card.balance { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
    .stats-number { font-size: 2rem; font-weight: bold; }
</style>

<div class="row">
    <div class="col-md-3">
        <div class="stats-card income">
            <h6>Total Sales</h6>
            <div class="stats-number"><?= $settings['currency'] ?? '€' ?><?= number_format($total_sales, 2) ?></div>
            <small><?= date('d M Y', strtotime($from_date)) ?> - <?= date('d M Y', strtotime($to_date)) ?></small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card expense">
            <h6>Total Expenses</h6>
            <div class="stats-number"><?= $settings['currency'] ?? '€' ?><?= number_format($total_expenses, 2) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card profit">
            <h6>Net Profit</h6>
            <div class="stats-number"><?= $settings['currency'] ?? '€' ?><?= number_format($net_profit, 2) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card balance">
            <h6>Cash Balance</h6>
            <div class="stats-number"><?= $settings['currency'] ?? '€' ?><?= number_format($cash_balance, 2) ?></div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Add Expense Form -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-plus-circle"></i> Add Expense</h5>
            </div>
            <div class="card-body">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>
                
                <form method="post">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-2">
                        <label>Date</label>
                        <input type="date" name="expense_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    
                    <div class="mb-2">
                        <label>Category</label>
                        <select name="category" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php foreach ($expense_categories as $cat): ?>
                                <option value="<?= $cat ?>"><?= $cat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-2">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Expense details..."></textarea>
                    </div>
                    
                    <div class="mb-2">
                        <label>Amount</label>
                        <input type="number" name="amount" class="form-control" step="0.01" required>
                    </div>
                    
                    <div class="mb-2">
                        <label>Payment Method</label>
                        <select name="payment_method" class="form-control">
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="bank">Bank Transfer</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save"></i> Add Expense
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Expense List -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list"></i> Expense List</h5>
            </div>
            <div class="card-body">
                <form method="get" class="row g-2 mb-3">
                    <div class="col-md-5">
                        <label>From Date</label>
                        <input type="date" name="from_date" class="form-control" value="<?= $from_date ?>">
                    </div>
                    <div class="col-md-5">
                        <label>To Date</label>
                        <input type="date" name="to_date" class="form-control" value="<?= $to_date ?>">
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
                
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr><th>Date</th><th>Category</th><th>Description</th><th>Amount</th><th>Payment</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expense_list as $exp): ?>
                            <tr>
                                <td><?= date('d-m-Y', strtotime($exp['expense_date'])) ?></td>
                                <td><?= $exp['category'] ?></td>
                                <td><?= htmlspecialchars($exp['description'] ?? '-') ?></td>
                                <td class="text-danger fw-bold"><?= $settings['currency'] ?? '€' ?><?= number_format($exp['amount'], 2) ?></td>
                                <td><?= ucfirst($exp['payment_method']) ?></td>
                                <td>
                                    <form method="post" style="display:inline" onsubmit="return confirm('Delete this expense?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $exp['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                 </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($expense_list)): ?>
                                <tr><td colspan="6" class="text-center text-muted">No expenses found</td></tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-dark">
                                <th colspan="3">Total Expenses</th>
                                <th colspan="3"><?= $settings['currency'] ?? '€' ?><?= number_format($total_expenses, 2) ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>