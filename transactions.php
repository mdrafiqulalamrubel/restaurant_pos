<?php
$page_title = 'Transactions';
$page_icon = 'receipt';
require_once 'config.php';
require_once 'header.php';

// Get company settings for currency
$company = $pdo->query("SELECT * FROM company_settings WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
if (!$company) {
    $company = ['currency' => '€', 'currency_code' => 'EUR', 'tax_rate' => 10];
}
$currency = $company['currency'];

// Get date filter
$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT s.*, c.name as customer_name, 
           COUNT(si.id) as item_count,
           (SELECT SUM(si2.qty * si2.unit_price) FROM sale_items si2 WHERE si2.sale_id = s.id) as subtotal
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    LEFT JOIN sale_items si ON s.id = si.sale_id
    WHERE DATE(s.created_at) BETWEEN ? AND ?
    GROUP BY s.id
    ORDER BY s.id DESC
");
$stmt->execute([$from_date, $to_date]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics - Calculate AFTER transactions are fetched
$total_sales = array_sum(array_column($transactions, 'total'));
$total_transactions = count($transactions);
$avg_sale = $total_transactions > 0 ? $total_sales / $total_transactions : 0;
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
    .stats-card h2 {
        margin: 10px 0;
        font-size: 2rem;
    }
    .filter-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .transaction-row {
        cursor: pointer;
        transition: background 0.2s;
    }
    .transaction-row:hover {
        background: #f8f9fa;
    }
    .badge-paid { background: #28a745; }
    .badge-pending { background: #ffc107; color: #333; }
    .badge-cancelled { background: #dc3545; }
</style>

<div class="row">
    <div class="col-md-4">
        <div class="stats-card">
            <i class="fas fa-chart-line fa-2x"></i>
            <h2><?= $currency ?><?= number_format($total_sales, 2) ?></h2>
            <div>Total Sales</div>
            <small><?= $from_date ?> to <?= $to_date ?></small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stats-card">
            <i class="fas fa-receipt fa-2x"></i>
            <h2><?= $total_transactions ?></h2>
            <div>Total Transactions</div>
            <small>Orders processed</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stats-card">
            <i class="fas fa-calculator fa-2x"></i>
            <h2><?= $currency ?><?= number_format($avg_sale, 2) ?></h2>
            <div>Average Sale Value</div>
            <small>Per transaction</small>
        </div>
    </div>
</div>

<!-- Filter Section -->
<div class="filter-card">
    <h5><i class="fas fa-filter"></i> Filter Transactions</h5>
    <form method="get" class="row g-3">
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
</div>

<!-- Transactions Table -->
<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-list"></i> Transaction History</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Date & Time</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Subtotal</th>
                        <th>Discount</th>
                        <th>Tax</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $t): ?>
                    <tr class="transaction-row" onclick="window.location='invoice.php?id=<?= $t['id'] ?>'">
                        <td><a href="invoice.php?id=<?= $t['id'] ?>" onclick="event.stopPropagation()">#<?= $t['id'] ?></a></td>
                        <td><?= date('Y-m-d H:i', strtotime($t['created_at'])) ?></td>
                        <td><?= htmlspecialchars($t['customer_name'] ?? 'Walk-in') ?></td>
                        <td><?= $t['item_count'] ?></td>
                        <td><?= $currency ?><?= number_format($t['subtotal'] ?? 0, 2) ?></td>
                        <td><?= $currency ?><?= number_format($t['discount'] ?? 0, 2) ?></td>
                        <td><?= $currency ?><?= number_format($t['tax'] ?? 0, 2) ?></td>
                        <td class="fw-bold"><?= $currency ?><?= number_format($t['total'], 2) ?></td>
                        <td>
                            <?php
                            $method = $t['payment_method'] ?? 'cash';
                            $icons = ['cash' => 'fa-money-bill', 'card' => 'fa-credit-card', 'bkash' => 'fa-mobile-alt', 'nagad' => 'fa-mobile-alt'];
                            $method_name = ucfirst($method);
                            ?>
                            <i class="fas <?= $icons[$method] ?? 'fa-money-bill' ?>"></i> <?= $method_name ?>
                        </td>
                        <td>
                            <a href="invoice.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-info" onclick="event.stopPropagation()">
                                <i class="fas fa-print"></i>
                            </a>
                            <a href="print_bill.php?id=<?= $t['id'] ?>&type=thermal" class="btn btn-sm btn-secondary" onclick="event.stopPropagation()">
                                <i class="fas fa-receipt"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                No transactions found for the selected period
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($transactions)): ?>
                <tfoot class="table-dark">
                    <tr>
                        <th colspan="7" class="text-end">Grand Total:</th>
                        <th><?= $currency ?><?= number_format($total_sales, 2) ?></th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<script>
// Make rows clickable - already handled by onclick on tr
// This prevents event bubbling on action buttons
document.querySelectorAll('.transaction-row a, .transaction-row button').forEach(el => {
    el.addEventListener('click', (e) => {
        e.stopPropagation();
    });
});
</script>

<?php require_once 'footer.php'; ?>