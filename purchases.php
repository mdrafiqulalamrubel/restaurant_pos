<?php
$page_title = 'Purchases';
$page_icon = 'shopping-cart';
require_once 'config.php';
require_once 'header.php';
require_once 'acc_core.php';

$purchases = $pdo->query("
    SELECT p.*, m.name as supplier_name 
    FROM purchases p 
    LEFT JOIN manufacturers m ON p.supplier_id = m.id 
    ORDER BY p.purchase_date DESC, p.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-shopping-cart"></i> Purchases</h2>
    <a href="purchase_add.php" class="btn btn-primary"><i class="fas fa-plus"></i> New Purchase</a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Reference No</th>
                        <th>Supplier</th>
                        <th>Total Amount</th>
                        <th>Paid Amount</th>
                        <th>Payment Method</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($purchases)): ?>
                        <tr><td colspan="9" class="text-center">No purchases found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($purchases as $p): ?>
                        <tr>
                            <td>PUR-<?= str_pad($p['id'], 5, '0', STR_PAD_LEFT) ?></td>
                            <td><?= date('M d, Y', strtotime($p['purchase_date'])) ?></td>
                            <td><?= htmlspecialchars($p['reference_no'] ?? '') ?></td>
                            <td><?= htmlspecialchars($p['supplier_name'] ?? 'N/A') ?></td>
                            <td class="fw-bold"><?= money($p['total_amount']) ?></td>
                            <td><?= money($p['paid_amount']) ?></td>
                            <td><span class="badge bg-secondary"><?= ucfirst($p['payment_method']) ?></span></td>
                            <td>
                                <?php if ($p['status'] == 'completed'): ?>
                                    <span class="badge bg-success">Completed</span>
                                <?php else: ?>
                                    <span class="badge bg-warning"><?= ucfirst($p['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <!-- View button -->
                                <a href="purchase_invoice.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary" title="View Invoice"><i class="fas fa-eye"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
<?php if (isset($_GET['print_id'])): ?>
<script>window.open('purchase_invoice.php?id=<?= (int)$_GET['print_id'] ?>&print=1', '_blank', 'width=1000,height=800');</script>
<?php endif; ?>
