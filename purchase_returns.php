<?php
$page_title = 'Purchase Returns';
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
    $where .= " AND (pr.id LIKE ? OR p.id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Count total
$stmt = $pdo->prepare("SELECT COUNT(*) FROM purchase_returns pr JOIN purchases p ON pr.purchase_id = p.id WHERE $where");
$stmt->execute($params);
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Get records
$sql = "
    SELECT pr.*, p.total_amount as purchase_total, s.name as supplier_name
    FROM purchase_returns pr
    JOIN purchases p ON pr.purchase_id = p.id
    LEFT JOIN manufacturers s ON p.supplier_id = s.id
    WHERE $where
    ORDER BY pr.return_date DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$returns = $stmt->fetchAll();
?>

<div class="row mb-4">
    <div class="col-md-6">
        <form class="d-flex" method="GET">
            <input type="text" name="search" class="form-control me-2" placeholder="Search Return ID or Purchase ID..." value="<?= htmlspecialchars($search) ?>">
            <button class="btn btn-outline-primary" type="submit">Search</button>
        </form>
    </div>
    <div class="col-md-6 text-end">
        <a href="purchase_return_add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Process Purchase Return</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Return ID</th>
                        <th>Purchase ID</th>
                        <th>Date</th>
                        <th>Supplier</th>
                        <th>Amount Refunded</th>
                        <th>Reason</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($returns as $r): ?>
                    <tr>
                        <td>PRET-<?= str_pad($r['id'], 6, '0', STR_PAD_LEFT) ?></td>
                        <td><a href="purchases.php">#<?= str_pad($r['purchase_id'], 6, '0', STR_PAD_LEFT) ?></a></td>
                        <td><?= date('M d, Y h:i A', strtotime($r['return_date'])) ?></td>
                        <td><?= htmlspecialchars($r['supplier_name'] ?? 'Unknown') ?></td>
                        <td class="text-success fw-bold">+<?= money($r['total_refunded']) ?></td>
                        <td><?= htmlspecialchars($r['reason']) ?></td>
                        <td>
                            <!-- View logic can go here -->
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($returns)): ?>
                    <tr><td colspan="7" class="text-center">No purchase returns found.</td></tr>
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
