<?php
$page_title = 'Dashboard';
$page_icon = 'home';
require_once 'config.php';
require_once 'header.php';

$is_admin = ($_SESSION['role'] === 'admin');
$current_branch_id = $_SESSION['branch_id'];

// Get branch name
$stmt = $pdo->prepare("SELECT name FROM branches WHERE id = ?");
$stmt->execute([$current_branch_id]);
$branch_name = $stmt->fetchColumn() ?: 'All Branches';

// Base query with branch filter
$branch_condition = "";
if (!$is_admin || !isset($_GET['show_all'])) {
    $branch_condition = "AND s.branch_id = " . intval($current_branch_id);
}

// Stats
$total_sales_today        = $pdo->query("SELECT COALESCE(SUM(s.total),0) FROM sales s WHERE DATE(s.created_at) = CURDATE() $branch_condition")->fetchColumn();
$total_transactions_today = $pdo->query("SELECT COUNT(*) FROM sales s WHERE DATE(s.created_at) = CURDATE() $branch_condition")->fetchColumn();
$total_purchases_today    = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM purchases WHERE DATE(purchase_date) = CURDATE()")->fetchColumn();
$total_customers          = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$total_bookings           = $pdo->query("SELECT COUNT(*) FROM bookings WHERE DATE(booking_date) >= CURDATE()")->fetchColumn();

// Monthly sales (last 6 months)
$ms = $pdo->prepare("
    SELECT DATE_FORMAT(s.created_at, '%Y-%m') as month, SUM(s.total) as total, COUNT(*) as count
    FROM sales s
    WHERE s.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) $branch_condition
    GROUP BY DATE_FORMAT(s.created_at, '%Y-%m')
    ORDER BY month DESC
");
$ms->execute();
$monthly_data = $ms->fetchAll();

// Monthly purchases (last 6 months)
$monthly_purchases_data = $pdo->query("
    SELECT DATE_FORMAT(purchase_date, '%Y-%m') as month, SUM(total_amount) as total
    FROM purchases
    WHERE purchase_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(purchase_date, '%Y-%m')
    ORDER BY month DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Recent sales
$rs = $pdo->prepare("
    SELECT s.*, c.name as customer_name
    FROM sales s LEFT JOIN customers c ON s.customer_id = c.id
    WHERE 1=1 $branch_condition
    ORDER BY s.id DESC LIMIT 5
");
$rs->execute();
$recent_sales_data = $rs->fetchAll();

// Recent purchases
$recent_purchases_data = $pdo->query("
    SELECT p.*, m.name as supplier_name
    FROM purchases p LEFT JOIN manufacturers m ON p.supplier_id = m.id
    ORDER BY p.id DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Top items
$ti = $pdo->prepare("
    SELECT i.name, SUM(si.qty) as total_qty, SUM(si.qty * si.unit_price) as revenue
    FROM sale_items si
    JOIN items i ON si.item_id = i.id
    JOIN sales s ON si.sale_id = s.id
    WHERE MONTH(s.created_at) = MONTH(CURDATE()) AND YEAR(s.created_at) = YEAR(CURDATE()) $branch_condition
    GROUP BY si.item_id
    ORDER BY revenue DESC
    LIMIT 6
");
$ti->execute();
$top_items_data = $ti->fetchAll();
?>

<style>
    .stats-card {
        border-radius: 16px;
        padding: 22px 20px;
        margin-bottom: 20px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.12);
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        position: relative;
        overflow: hidden;
        color: white;
    }
    .stats-card:hover { transform: translateY(-4px); box-shadow: 0 8px 25px rgba(0,0,0,0.18); }
    .stats-icon { font-size: 3rem; opacity: 0.18; position: absolute; right: 18px; top: 50%; transform: translateY(-50%); }
    .stats-number { font-size: 1.9rem; font-weight: 800; line-height: 1.1; }
    .stats-label { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; opacity: 0.85; margin-top: 4px; }
    .stats-sub { font-size: 0.8rem; opacity: 0.75; margin-top: 2px; }

    .dash-card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.07); }
    .dash-card h5 { font-size: 0.9rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; color: #555; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #f3f3f3; }
    .dash-card h5 i { margin-right: 8px; }

    .table th { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.5px; color: #999; font-weight: 700; border-top: none; padding: 8px 10px; }
    .table td { font-size: 0.875rem; padding: 8px 10px; vertical-align: middle; }
    .table-hover tbody tr:hover { background-color: #f9f9ff; }

    .top-item-card { background: #f8f9fa; border-radius: 10px; padding: 12px; text-align: center; height: 100%; transition: background 0.2s; }
    .top-item-card:hover { background: #eef0ff; }
    .top-item-card .item-name { font-size: 0.8rem; font-weight: 600; margin-top: 5px; }
    .top-item-card .item-revenue { font-size: 0.85rem; font-weight: 700; color: #667eea; }
    .top-item-card .item-qty { font-size: 0.72rem; color: #999; }
</style>

<!-- Branch Bar -->
<div class="row">
    <div class="col-12">
        <div class="dash-card py-2 px-3 d-flex align-items-center justify-content-between mb-3">
            <div><i class="fas fa-store text-primary me-2"></i><strong>Branch:</strong> <?= htmlspecialchars($branch_name) ?></div>
            <?php if ($is_admin): ?>
            <div>
                <a href="?show_all=1" class="btn btn-sm btn-outline-primary">All Branches</a>
                <a href="?" class="btn btn-sm btn-outline-secondary ms-1">Current</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Stat Cards -->
<div class="row">
    <div class="col-6 col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #667eea, #764ba2);">
            <i class="fas fa-chart-line stats-icon"></i>
            <div class="stats-number"><?= money($total_sales_today) ?></div>
            <div class="stats-label">Today's Sales</div>
            <div class="stats-sub"><?= $total_transactions_today ?> transactions</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #f5576c, #f093fb);">
            <i class="fas fa-shopping-cart stats-icon"></i>
            <div class="stats-number"><?= money($total_purchases_today) ?></div>
            <div class="stats-label">Today's Purchases</div>
            <div class="stats-sub">Materials &amp; stock</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #4facfe, #00f2fe);">
            <i class="fas fa-users stats-icon"></i>
            <div class="stats-number"><?= $total_customers ?></div>
            <div class="stats-label">Customers</div>
            <div class="stats-sub">Registered</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #43e97b, #38f9d7);">
            <i class="fas fa-calendar stats-icon"></i>
            <div class="stats-number"><?= $total_bookings ?></div>
            <div class="stats-label">Upcoming Bookings</div>
            <div class="stats-sub">Reservations</div>
        </div>
    </div>
</div>

<!-- Chart -->
<div class="row">
    <div class="col-12">
        <div class="dash-card">
            <h5><i class="fas fa-chart-bar text-primary"></i> Monthly Sales &amp; Purchases — <?= htmlspecialchars($branch_name) ?></h5>
            <canvas id="salesChart" height="80"></canvas>
        </div>
    </div>
</div>

<!-- Recent Tables: side by side -->
<div class="row">
    <div class="col-lg-6">
        <div class="dash-card">
            <h5><i class="fas fa-receipt text-success"></i> Recent Sales</h5>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Invoice</th><th>Customer</th><th>Total</th><th>Time</th><th></th></tr></thead>
                    <tbody>
                        <?php if (empty($recent_sales_data)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x d-block mb-2 opacity-25"></i>No recent sales</td></tr>
                        <?php else: ?>
                            <?php foreach ($recent_sales_data as $sale): ?>
                            <tr>
                                <td><a href="invoice.php?id=<?= $sale['id'] ?>" class="fw-bold text-decoration-none">#<?= $sale['id'] ?></a></td>
                                <td><?= htmlspecialchars($sale['customer_name'] ?? 'Walk-in') ?></td>
                                <td class="fw-bold text-success"><?= money($sale['total']) ?></td>
                                <td class="text-muted"><?= date('H:i', strtotime($sale['created_at'])) ?></td>
                                <td><a href="invoice.php?id=<?= $sale['id'] ?>" class="btn btn-sm btn-outline-primary py-0">View</a></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="dash-card">
            <h5><i class="fas fa-shopping-cart text-danger"></i> Recent Purchases</h5>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Invoice</th><th>Supplier</th><th>Total</th><th>Date</th><th></th></tr></thead>
                    <tbody>
                        <?php if (empty($recent_purchases_data)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x d-block mb-2 opacity-25"></i>No recent purchases</td></tr>
                        <?php else: ?>
                            <?php foreach ($recent_purchases_data as $p): ?>
                            <tr>
                                <td><a href="purchase_invoice.php?id=<?= $p['id'] ?>" class="fw-bold text-decoration-none">PUR-<?= str_pad($p['id'], 4, '0', STR_PAD_LEFT) ?></a></td>
                                <td><?= htmlspecialchars($p['supplier_name'] ?? 'N/A') ?></td>
                                <td class="fw-bold text-danger"><?= money($p['total_amount']) ?></td>
                                <td class="text-muted"><?= date('M d', strtotime($p['purchase_date'])) ?></td>
                                <td><a href="purchase_invoice.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary py-0">View</a></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Top Items -->
<div class="row">
    <div class="col-12">
        <div class="dash-card">
            <h5><i class="fas fa-trophy text-warning"></i> Top Selling Items — This Month</h5>
            <?php if (empty($top_items_data)): ?>
                <p class="text-muted text-center py-3">No sales data for this month</p>
            <?php else: ?>
            <div class="row g-3">
                <?php foreach ($top_items_data as $idx => $item): ?>
                <div class="col-6 col-sm-4 col-md-2">
                    <div class="top-item-card">
                        <span class="badge bg-<?= $idx == 0 ? 'warning' : ($idx == 1 ? 'secondary' : ($idx == 2 ? 'info' : 'light text-dark')) ?>">#<?= $idx + 1 ?></span>
                        <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                        <div class="item-qty"><?= $item['total_qty'] ?> sold</div>
                        <div class="item-revenue"><?= money($item['revenue']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('salesChart').getContext('2d');
const salesData   = <?= json_encode(array_reverse($monthly_data)) ?>;
const purchData   = <?= json_encode(array_reverse($monthly_purchases_data)) ?>;
const allMonths   = [...new Set([...salesData.map(i => i.month), ...purchData.map(i => i.month)])].sort();

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: allMonths,
        datasets: [
            {
                label: 'Sales (<?= CURRENCY_SYMBOL ?>)',
                data: allMonths.map(m => { let r = salesData.find(x => x.month === m); return r ? parseFloat(r.total) : 0; }),
                backgroundColor: 'rgba(102,126,234,0.75)',
                borderColor: '#667eea',
                borderWidth: 1,
                borderRadius: 5,
                order: 2
            },
            {
                label: 'Purchases (<?= CURRENCY_SYMBOL ?>)',
                data: allMonths.map(m => { let r = purchData.find(x => x.month === m); return r ? parseFloat(r.total) : 0; }),
                backgroundColor: 'rgba(245,87,108,0.65)',
                borderColor: '#f5576c',
                borderWidth: 1,
                borderRadius: 5,
                order: 1
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            tooltip: { callbacks: { label: c => '<?= CURRENCY_SYMBOL ?>' + c.raw.toFixed(2) } }
        },
        scales: {
            y: { beginAtZero: true, ticks: { callback: v => '<?= CURRENCY_SYMBOL ?>' + v.toLocaleString() } }
        }
    }
});
</script>

<?php require_once 'footer.php'; ?>