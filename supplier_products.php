<?php
$page_title = 'Supplier Products';
$page_icon = 'box';
require_once 'config.php';
require_once 'header.php';

$supplier_id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM manufacturers WHERE id = ?");
$stmt->execute([$supplier_id]);
$supplier = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$supplier) die('Supplier not found');

$products = $pdo->prepare("SELECT * FROM items WHERE manufacturer_id = ? AND active = 1");
$products->execute([$supplier_id]);
$items = $products->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-box"></i> Products from: <?= htmlspecialchars($supplier['name']) ?></h5>
        <p><?= $supplier['phone'] ?> | <?= $supplier['email'] ?></p>
    </div>
    <div class="card-body">
        <div class="row">
            <?php foreach ($items as $item): ?>
            <div class="col-md-3 mb-3">
                <div class="card">
                    <?php if ($item['image'] && file_exists($item['image'])): ?>
                        <img src="<?= $item['image'] ?>" class="card-img-top" style="height: 120px; object-fit: cover;">
                    <?php else: ?>
                        <div class="card-img-top bg-secondary text-white d-flex align-items-center justify-content-center" style="height: 120px;">
                            <i class="fas fa-utensils fa-3x"></i>
                        </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h6><?= htmlspecialchars($item['name']) ?></h6>
                        <p class="text-primary">â‚<?= money($item['unit_price']) ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <a href="suppliers.php" class="btn btn-secondary">Back to Suppliers</a>
    </div>
</div>

<?php require_once 'footer.php'; ?>