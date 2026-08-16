<?php
function patch_recent_purchases($file) {
    $content = file_get_contents($file);

    // 1. Add recent purchases query
    $search1 = '// Get top items';
    if (strpos($content, '$recent_purchases_data') === false) {
        $replace1 = '// Get recent purchases
$recent_purchases = $pdo->query("
    SELECT p.*, m.name as supplier_name 
    FROM purchases p 
    LEFT JOIN manufacturers m ON p.supplier_id = m.id 
    ORDER BY p.id DESC LIMIT 5
");
$recent_purchases_data = $recent_purchases->fetchAll(PDO::FETCH_ASSOC);

// Get top items';
        $content = str_replace($search1, $replace1, $content);
    }
    
    // 2. Add Recent Purchases table HTML
    $search2 = '<div class="col-md-4">';
    if (strpos($content, 'Recent Purchases') === false) {
        $replace2 = '<div class="dashboard-card mt-4">
            <h5><i class="fas fa-shopping-cart"></i> Recent Purchases</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Supplier</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_purchases_data)): ?>
                            <tr><td colspan="6" class="text-center">No recent purchases found</td></tr>
                        <?php else: ?>
                            <?php foreach ($recent_purchases_data as $purchase): ?>
                            <tr>
                                <td><a href="purchase_invoice.php?id=<?= $purchase[\'id\'] ?>">PUR-<?= str_pad($purchase[\'id\'], 5, \'0\', STR_PAD_LEFT) ?></a></td>
                                <td><?= htmlspecialchars($purchase[\'supplier_name\'] ?? \'Walk-in\') ?></td>
                                <td class="fw-bold"><?= money($purchase[\'total_amount\']) ?></td>
                                <td><span class="badge bg-secondary"><?= ucfirst($purchase[\'payment_method\'] ?? \'cash\') ?></span></td>
                                <td><?= date(\'M d, Y\', strtotime($purchase[\'purchase_date\'])) ?></td>
                                <td><a href="purchase_invoice.php?id=<?= $purchase[\'id\'] ?>" class="btn btn-sm btn-info">View</a></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
    
    <div class="col-md-4">';
        // The original string is `    <div class="col-md-4">`
        // We replace it so the new card goes at the bottom of col-md-8
        $content = str_replace('    <div class="col-md-4">', $replace2, $content);
    }
    
    file_put_contents($file, $content);
    echo "Patched $file\n";
}

patch_recent_purchases('F:\xampp82\htdocs\restaurant_pos\index.php');
patch_recent_purchases('F:\xampp82\htdocs\restaurant_pos\reports.php');
