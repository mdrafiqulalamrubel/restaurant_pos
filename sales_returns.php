<?php
$page_title = 'Sales Returns';
$page_icon = 'undo';
require_once 'config.php';
require_once 'header.php';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Search
$search = $_GET['search'] ?? '';
$where = "1=1";
$params = [];

if ($search) {
    $where .= " AND (sr.id LIKE ? OR s.id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Count total
$stmt = $pdo->prepare("SELECT COUNT(*) FROM sales_returns sr JOIN sales s ON sr.sale_id = s.id WHERE $where");
$stmt->execute($params);
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Get records
$sql = "
    SELECT sr.*, s.total as sale_total, c.name as customer_name
    FROM sales_returns sr
    JOIN sales s ON sr.sale_id = s.id
    LEFT JOIN customers c ON s.customer_id = c.id
    WHERE $where
    ORDER BY sr.return_date DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$returns = $stmt->fetchAll();
?>

<div class="row mb-4">
    <div class="col-md-6">
        <form class="d-flex" method="GET">
            <input type="text" name="search" class="form-control me-2" placeholder="Search Return ID or Sale ID..." value="<?= htmlspecialchars($search) ?>">
            <button class="btn btn-outline-primary" type="submit">Search</button>
        </form>
    </div>
    <div class="col-md-6 text-end">
        <a href="sales_return_add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Process New Return</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Return ID</th>
                        <th>Sale ID</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Amount Refunded</th>
                        <th>Reason</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($returns as $r): ?>
                    <tr>
                        <td>RET-<?= str_pad($r['id'], 6, '0', STR_PAD_LEFT) ?></td>
                        <td><a href="invoice.php?id=<?= $r['sale_id'] ?>">#<?= str_pad($r['sale_id'], 6, '0', STR_PAD_LEFT) ?></a></td>
                        <td><?= date('M d, Y h:i A', strtotime($r['return_date'])) ?></td>
                        <td><?= htmlspecialchars($r['customer_name'] ?? 'Walk-in') ?></td>
                        <td class="text-danger fw-bold">-<?= money($r['total_refunded']) ?></td>
                        <td><?= htmlspecialchars($r['reason']) ?></td>
                        <td>
                            <!-- <a href="sales_return_view.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i> View</a> -->
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($returns)): ?>
                    <tr><td colspan="7" class="text-center">No sales returns found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>
