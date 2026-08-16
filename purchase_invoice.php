<?php
$page_title = 'Purchase Invoice';
$page_icon = 'file-invoice';
require_once 'config.php';
require_once 'header.php';

$id = $_GET['id'] ?? 0;

// Get purchase info
$stmt = $pdo->prepare("
    SELECT p.*, m.name as supplier_name, m.phone as supplier_phone, m.address as supplier_address
    FROM purchases p 
    LEFT JOIN manufacturers m ON p.supplier_id = m.id 
    WHERE p.id = ?
");
$stmt->execute([$id]);
$purchase = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$purchase) die('Purchase not found');

// Get purchase items
$stmt = $pdo->prepare("
    SELECT pi.*, 
        CASE 
            WHEN pi.item_type = 'raw_material' THEN rm.name 
            ELSE i.name 
        END as name
    FROM purchase_items pi
    LEFT JOIN raw_materials rm ON pi.item_id = rm.id AND pi.item_type = 'raw_material'
    LEFT JOIN items i ON pi.item_id = i.id AND pi.item_type = 'item'
    WHERE pi.purchase_id = ?
");
$stmt->execute([$id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$company = $pdo->query("SELECT * FROM company_settings WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
if (!$company) {
    $company = ['name' => 'Restaurant POS', 'address' => '', 'phone' => '', 'email' => '', 'logo' => ''];
}
?>

<style>
    @media print {
        body * { visibility: hidden; }
        #print-area, #print-area * { visibility: visible; }
        #print-area { position: absolute; left: 0; top: 0; width: 100%; }
        .no-print { display: none !important; }
        .card { border: none !important; box-shadow: none !important; }
    }
    .invoice-header { border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 20px; }
    .invoice-title { font-size: 2rem; font-weight: bold; color: #333; }
    .bill-to { background: #f8f9fa; padding: 15px; border-radius: 8px; }
</style>

<div class="card" id="print-area">
    <div class="card-body p-5">
        <div class="row invoice-header">
            <div class="col-sm-6">
                <?php if (!empty($company['logo']) && file_exists('uploads/' . $company['logo'])): ?>
                    <img src="uploads/<?= $company['logo'] ?>" alt="Logo" style="max-height: 80px;">
                <?php else: ?>
                    <h2><?= htmlspecialchars($company['name']) ?></h2>
                <?php endif; ?>
                <p class="text-muted mt-2 mb-0">
                    <?= nl2br(htmlspecialchars($company['address'])) ?><br>
                    <?= htmlspecialchars($company['phone']) ?><br>
                    <?= htmlspecialchars($company['email']) ?>
                </p>
            </div>
            <div class="col-sm-6 text-end">
                <h1 class="invoice-title">PURCHASE INVOICE</h1>
                <p class="mb-0"><strong>Invoice No:</strong> PUR-<?= str_pad($purchase['id'], 5, '0', STR_PAD_LEFT) ?></p>
                <p class="mb-0"><strong>Date:</strong> <?= date('d M Y', strtotime($purchase['purchase_date'])) ?></p>
                <p class="mb-0"><strong>Ref No:</strong> <?= htmlspecialchars($purchase['reference_no']) ?></p>
                <p class="mb-0"><strong>Status:</strong> <span class="badge bg-<?= $purchase['status'] == 'completed' ? 'success' : 'warning' ?>"><?= ucfirst($purchase['status']) ?></span></p>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-sm-6">
                <div class="bill-to">
                    <h6 class="text-uppercase text-muted mb-2">Supplier Details:</h6>
                    <h5 class="mb-1"><?= htmlspecialchars($purchase['supplier_name'] ?? 'Walk-in Supplier') ?></h5>
                    <?php if (!empty($purchase['supplier_phone'])): ?>
                        <p class="mb-0"><i class="fas fa-phone fa-fw text-muted"></i> <?= htmlspecialchars($purchase['supplier_phone']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($purchase['supplier_address'])): ?>
                        <p class="mb-0"><i class="fas fa-map-marker-alt fa-fw text-muted"></i> <?= htmlspecialchars($purchase['supplier_address']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>Item Type</th>
                    <th>Item Name</th>
                    <th class="text-center">Qty</th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= $item['item_type'] == 'raw_material' ? 'Raw Material' : 'Finished Item' ?></td>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td class="text-center"><?= number_format($item['qty'], 2) ?></td>
                    <td class="text-end"><?= money($item['unit_price']) ?></td>
                    <td class="text-end"><?= money($item['qty'] * $item['unit_price']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4" class="text-end">Total Amount:</th>
                    <th class="text-end"><?= money($purchase['total_amount']) ?></th>
                </tr>
                <tr>
                    <th colspan="4" class="text-end">Paid Amount:</th>
                    <th class="text-end"><?= money($purchase['paid_amount']) ?></th>
                </tr>
                <tr>
                    <th colspan="4" class="text-end">Due Amount:</th>
                    <th class="text-end"><?= money($purchase['total_amount'] - $purchase['paid_amount']) ?></th>
                </tr>
            </tfoot>
        </table>

        <?php if (!empty($purchase['notes'])): ?>
            <div class="mt-4">
                <h6>Notes:</h6>
                <p class="text-muted"><?= nl2br(htmlspecialchars($purchase['notes'])) ?></p>
            </div>
        <?php endif; ?>

        <div class="text-center mt-5 no-print">
            <button onclick="window.print()" class="btn btn-primary btn-lg px-5">
                <i class="fas fa-print"></i> Print Invoice
            </button>
            <a href="purchases.php" class="btn btn-secondary btn-lg px-5">
                <i class="fas fa-arrow-left"></i> Back to Purchases
            </a>
        </div>
    </div>
</div>

<?php 
if (isset($_GET['print']) && $_GET['print'] == 1) {
    echo "<script>window.onload = function() { window.print(); }</script>";
}
require_once 'footer.php'; 
?>
