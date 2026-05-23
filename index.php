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

// Base query with branch filter - Use table alias to avoid ambiguity
$branch_condition = "";
if (!$is_admin || !isset($_GET['show_all'])) {
    $branch_condition = "AND s.branch_id = " . intval($current_branch_id);
}

// Get dashboard stats for current branch
$total_sales_today = $pdo->query("SELECT COALESCE(SUM(s.total),0) FROM sales s WHERE DATE(s.created_at) = CURDATE() $branch_condition")->fetchColumn();
$total_transactions_today = $pdo->query("SELECT COUNT(*) FROM sales s WHERE DATE(s.created_at) = CURDATE() $branch_condition")->fetchColumn();
$total_items = $pdo->query("SELECT COUNT(*) FROM items WHERE active=1")->fetchColumn();
$total_customers = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$total_bookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE DATE(booking_date) >= CURDATE()")->fetchColumn();

// Get monthly sales for current branch
$monthly_sales = $pdo->prepare("
    SELECT DATE_FORMAT(s.created_at, '%Y-%m') as month, 
           SUM(s.total) as total, 
           COUNT(*) as count 
    FROM sales s
    WHERE s.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) $branch_condition
    GROUP BY DATE_FORMAT(s.created_at, '%Y-%m')
    ORDER BY month DESC
");
$monthly_sales->execute();
$monthly_data = $monthly_sales->fetchAll();

// Get recent sales for current branch
$recent_sales = $pdo->prepare("
    SELECT s.*, c.name as customer_name 
    FROM sales s 
    LEFT JOIN customers c ON s.customer_id = c.id 
    WHERE 1=1 $branch_condition
    ORDER BY s.id DESC LIMIT 5
");
$recent_sales->execute();
$recent_sales_data = $recent_sales->fetchAll();

// Get top items for current branch
$top_items = $pdo->prepare("
    SELECT i.name, SUM(si.qty) as total_qty, SUM(si.qty * si.unit_price) as revenue
    FROM sale_items si
    JOIN items i ON si.item_id = i.id
    JOIN sales s ON si.sale_id = s.id
    WHERE MONTH(s.created_at) = MONTH(CURDATE()) AND YEAR(s.created_at) = YEAR(CURDATE()) $branch_condition
    GROUP BY si.item_id
    ORDER BY revenue DESC
    LIMIT 5
");
$top_items->execute();
$top_items_data = $top_items->fetchAll();
?>

<style>
    .stats-card {
        background: white;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        transition: transform 0.3s;
        position: relative;
        overflow: hidden;
    }
    .stats-card:hover { transform: translateY(-5px); }
    .stats-icon {
        font-size: 2.5rem;
        float: right;
        opacity: 0.3;
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
    }
    .stats-number { font-size: 2rem; font-weight: bold; color: #667eea; }
    .stats-label { color: #666; margin-top: 10px; font-size: 0.9rem; }
    .dashboard-card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    .branch-badge { background: #667eea; color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.8rem; display: inline-block; margin-left: 10px; }
</style>

<div class="row">
    <div class="col-md-12">
        <div class="dashboard-card">
            <h5>
                <i class="fas fa-store"></i> Branch: <?= htmlspecialchars($branch_name) ?>
                <?php if ($is_admin): ?>
                <a href="?show_all=1" class="btn btn-sm btn-outline-primary ms-2">Show All Branches</a>
                <a href="?" class="btn btn-sm btn-outline-secondary ms-1">Show Current Branch</a>
                <?php endif; ?>
            </h5>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="stats-card">
            <i class="fas fa-chart-line stats-icon"></i>
            <div class="stats-number"><?= number_format($total_sales_today, 2) ?></div>
            <div class="stats-label">Today's Sales</div>
            <small><?= $total_transactions_today ?> transactions</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <i class="fas fa-utensils stats-icon"></i>
            <div class="stats-number"><?= $total_items ?></div>
            <div class="stats-label">Menu Items</div>
            <small>Active products</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <i class="fas fa-users stats-icon"></i>
            <div class="stats-number"><?= $total_customers ?></div>
            <div class="stats-label">Customers</div>
            <small>Registered</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <i class="fas fa-calendar stats-icon"></i>
            <div class="stats-number"><?= $total_bookings ?></div>
            <div class="stats-label">Upcoming Bookings</div>
            <small>Pending reservations</small>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="dashboard-card">
            <h5><i class="fas fa-chart-line"></i> Monthly Sales Overview (<?= htmlspecialchars($branch_name) ?>)</h5>
            <canvas id="salesChart" height="200"></canvas>
        </div>
        
        <div class="dashboard-card">
            <h5><i class="fas fa-clock"></i> Recent Transactions</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_sales_data as $sale): ?>
                        <tr>
                            <td><a href="invoice.php?id=<?= $sale['id'] ?>">#<?= $sale['id'] ?></a></td>
                            <td><?= htmlspecialchars($sale['customer_name'] ?? 'Walk-in') ?></td>
                            <td class="fw-bold"><?= number_format($sale['total'], 2) ?></td>
                            <td><?= ucfirst($sale['payment_method'] ?? 'cash') ?></td>
                            <td><?= date('H:i', strtotime($sale['created_at'])) ?></td>
                            <td><a href="invoice.php?id=<?= $sale['id'] ?>" class="btn btn-sm btn-info">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="dashboard-card">
            <h5><i class="fas fa-trophy"></i> Top Selling Items (This Month)</h5>
            <?php if (empty($top_items_data)): ?>
                <p class="text-muted text-center">No sales data</p>
            <?php else: ?>
                <?php foreach ($top_items_data as $index => $item): ?>
                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                    <div>
                        <span class="badge bg-<?= $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : 'info') ?> me-2">#<?= $index + 1 ?></span>
                        <strong><?= htmlspecialchars($item['name']) ?></strong>
                    </div>
                    <div class="text-end">
                        <small><?= $item['total_qty'] ?> sold</small><br>
                        <small class="text-primary"><?= number_format($item['revenue'], 2) ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('salesChart').getContext('2d');
const monthlyData = <?= json_encode(array_reverse($monthly_data)) ?>;

new Chart(ctx, {
    type: 'line',
    data: {
        labels: monthlyData.map(item => item.month),
        datasets: [{
            label: 'Sales',
            data: monthlyData.map(item => parseFloat(item.total)),
            borderColor: '#667eea',
            backgroundColor: 'rgba(102,126,234,0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true
    }
});
</script>

<?php require_once 'footer.php'; ?>