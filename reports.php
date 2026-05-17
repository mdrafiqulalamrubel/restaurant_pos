<?php
$page_title = 'Dashboard';
$page_icon = 'home';
require_once 'config.php';
require_once 'header.php';

// Get company settings for currency
$company = $pdo->query("SELECT * FROM company_settings WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
if (!$company) {
    $company = ['currency' => '€', 'currency_code' => 'EUR', 'tax_rate' => 10];
}
$currency = $company['currency'];

// Get dashboard stats
$total_sales_today = $pdo->query("SELECT COALESCE(SUM(total),0) FROM sales WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$total_transactions_today = $pdo->query("SELECT COUNT(*) FROM sales WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$total_items = $pdo->query("SELECT COUNT(*) FROM items WHERE active=1")->fetchColumn();
$total_customers = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$total_bookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE DATE(booking_date) >= CURDATE()")->fetchColumn();

// Get monthly sales
$monthly_sales = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, 
           SUM(total) as total, 
           COUNT(*) as count 
    FROM sales 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get recent sales
$recent_sales = $pdo->query("
    SELECT s.*, c.name as customer_name 
    FROM sales s 
    LEFT JOIN customers c ON s.customer_id = c.id 
    ORDER BY s.id DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Get upcoming bookings
$upcoming_bookings = $pdo->query("
    SELECT b.*, i.name as item_name 
    FROM bookings b 
    JOIN sale_items si ON b.sale_item_id = si.id 
    JOIN items i ON si.item_id = i.id 
    WHERE DATE(b.booking_date) >= CURDATE() 
    ORDER BY b.booking_date ASC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Get top selling items this month
$top_items = $pdo->query("
    SELECT i.name, SUM(si.qty) as total_qty, SUM(si.qty * si.unit_price) as revenue
    FROM sale_items si
    JOIN items i ON si.item_id = i.id
    JOIN sales s ON si.sale_id = s.id
    WHERE MONTH(s.created_at) = MONTH(CURDATE()) AND YEAR(s.created_at) = YEAR(CURDATE())
    GROUP BY si.item_id
    ORDER BY revenue DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
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
    .stats-card:hover {
        transform: translateY(-5px);
    }
    .stats-icon {
        font-size: 2.5rem;
        float: right;
        opacity: 0.3;
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
    }
    .stats-number {
        font-size: 2rem;
        font-weight: bold;
        color: #667eea;
    }
    .stats-label {
        color: #666;
        margin-top: 10px;
        font-size: 0.9rem;
    }
    .dashboard-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .chart-container {
        max-height: 300px;
        margin: 20px 0;
    }
</style>

<div class="row">
    <div class="col-md-3">
        <div class="stats-card">
            <i class="fas fa-chart-line stats-icon"></i>
            <div class="stats-number"><?= $currency ?><?= number_format($total_sales_today, 2) ?></div>
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
            <h5><i class="fas fa-chart-line"></i> Monthly Sales Overview</h5>
            <div class="chart-container">
                <canvas id="salesChart"></canvas>
            </div>
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
                        <?php foreach ($recent_sales as $sale): ?>
                        <tr>
                            <td><a href="invoice.php?id=<?= $sale['id'] ?>">#<?= $sale['id'] ?></a></td>
                            <td><?= htmlspecialchars($sale['customer_name'] ?? 'Walk-in') ?></td>
                            <td class="fw-bold"><?= $currency ?><?= number_format($sale['total'], 2) ?></td>
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
            <h5><i class="fas fa-calendar"></i> Upcoming Bookings</h5>
            <?php if (empty($upcoming_bookings)): ?>
                <p class="text-muted text-center">No upcoming bookings</p>
            <?php else: ?>
                <?php foreach ($upcoming_bookings as $booking): ?>
                <div class="alert alert-info mb-2">
                    <strong><?= htmlspecialchars($booking['item_name']) ?></strong><br>
                    <small><i class="fas fa-calendar"></i> <?= $booking['booking_date'] ?> at <?= $booking['booking_time'] ?></small><br>
                    <small><?= $booking['duration'] ?> hour(s)</small>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="dashboard-card">
            <h5><i class="fas fa-trophy"></i> Top Selling Items (This Month)</h5>
            <?php if (empty($top_items)): ?>
                <p class="text-muted text-center">No sales data</p>
            <?php else: ?>
                <?php foreach ($top_items as $index => $item): ?>
                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                    <div>
                        <span class="badge bg-<?= $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : 'info') ?> me-2">#<?= $index + 1 ?></span>
                        <strong><?= htmlspecialchars($item['name']) ?></strong>
                    </div>
                    <div class="text-end">
                        <small><?= $item['total_qty'] ?> sold</small><br>
                        <small class="text-primary"><?= $currency ?><?= number_format($item['revenue'], 2) ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Monthly Sales Chart
const ctx = document.getElementById('salesChart').getContext('2d');
const monthlyData = <?= json_encode(array_reverse($monthly_sales)) ?>;

new Chart(ctx, {
    type: 'line',
    data: {
        labels: monthlyData.map(item => item.month),
        datasets: [{
            label: 'Sales (<?= $currency ?>)',
            data: monthlyData.map(item => parseFloat(item.total)),
            borderColor: '#667eea',
            backgroundColor: 'rgba(102,126,234,0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'top',
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return '<?= $currency ?>' + context.raw.toFixed(2);
                    }
                }
            }
        }
    }
});
</script>

<?php require_once 'footer.php'; ?>