<?php
$page_title = 'Process Purchase Return';
$page_icon = 'undo';
require_once 'config.php';
require_once 'header.php';
require_once 'accounting.php';

$purchase = null;
$items = [];
$error = '';
$success = '';

if (isset($_GET['purchase_id'])) {
    $purchase_id = (int)$_GET['purchase_id'];
    $stmt = $pdo->prepare("SELECT p.*, s.name as supplier_name FROM purchases p LEFT JOIN manufacturers s ON p.supplier_id = s.id WHERE p.id = ?");
    $stmt->execute([$purchase_id]);
    $purchase = $stmt->fetch();
    
    if ($purchase) {
        $stmt = $pdo->prepare("
            SELECT pi.*, 
            CASE 
                WHEN pi.item_type = 'raw_material' THEN rm.name 
                ELSE i.name 
            END as item_name
            FROM purchase_items pi 
            LEFT JOIN items i ON pi.item_id = i.id AND pi.item_type = 'item'
            LEFT JOIN raw_materials rm ON pi.item_id = rm.id AND pi.item_type = 'raw_material'
            WHERE pi.purchase_id = ?
        ");
        $stmt->execute([$purchase_id]);
        $items = $stmt->fetchAll();
        
        // Find already returned qtys
        $stmt = $pdo->prepare("SELECT CONCAT(item_type, '_', item_id) as type_id, SUM(qty_returned) as returned FROM purchase_return_items pri JOIN purchase_returns pr ON pri.return_id = pr.id WHERE pr.purchase_id = ? GROUP BY type_id");
        $stmt->execute([$purchase_id]);
        $returned = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        foreach ($items as &$item) {
            $key = $item['item_type'] . '_' . $item['item_id'];
            $item['already_returned'] = $returned[$key] ?? 0;
            $item['returnable_qty'] = $item['qty'] - $item['already_returned'];
        }
    } else {
        $error = "Purchase ID not found.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_return'])) {
    $purchase_id = (int)$_POST['purchase_id'];
    $reason = $_POST['reason'] ?? '';
    $return_qtys = $_POST['return_qty'] ?? []; // Format: array[item_type_item_id] = qty
    
    $total_refund = 0;
    $items_to_return = [];
    
    // Validate
    foreach ($return_qtys as $key => $qty) {
        if ($qty > 0) {
            list($type, $item_id) = explode('_', $key, 2);
            // Find item in purchase
            $stmt = $pdo->prepare("SELECT unit_price FROM purchase_items WHERE purchase_id = ? AND item_type = ? AND item_id = ?");
            $stmt->execute([$purchase_id, $type, $item_id]);
            $item = $stmt->fetch();
            if ($item) {
                $refund = $item['unit_price'] * $qty;
                $total_refund += $refund;
                $items_to_return[] = [
                    'item_type' => $type,
                    'item_id' => $item_id,
                    'qty' => $qty,
                    'refund' => $refund
                ];
            }
        }
    }
    
    if (empty($items_to_return)) {
        $error = "No items selected for return.";
    } else {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO purchase_returns (purchase_id, total_refunded, reason, processed_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$purchase_id, $total_refund, $reason, $_SESSION['user_id']]);
            $return_id = $pdo->lastInsertId();
            
            foreach ($items_to_return as $rtn) {
                $stmt = $pdo->prepare("INSERT INTO purchase_return_items (return_id, item_type, item_id, qty_returned, refund_amount) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$return_id, $rtn['item_type'], $rtn['item_id'], $rtn['qty'], $rtn['refund']]);
                
                // Deduct inventory
                if ($rtn['item_type'] == 'raw_material') {
                    $stmt = $pdo->prepare("UPDATE raw_materials SET current_stock = current_stock - ? WHERE id = ?");
                } else {
                    $stmt = $pdo->prepare("UPDATE items SET current_stock = current_stock - ? WHERE id = ?");
                }
                $stmt->execute([$rtn['qty'], $rtn['item_id']]);
            }
            
            // Accounting entry
            acc_record_purchase_return($return_id, $purchase_id, $total_refund, 'cash');
            
            $pdo->commit();
            $success = "Return processed successfully. Refund received: " . money($total_refund);
            $purchase = null; // Reset
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error processing return: " . $e->getMessage();
        }
    }
}

?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?> <a href="purchase_returns.php" class="alert-link">View all returns</a></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row align-items-center">
            <div class="col-auto">
                <label>Find Purchase ID:</label>
            </div>
            <div class="col-auto">
                <input type="number" name="purchase_id" class="form-control" value="<?= $_GET['purchase_id'] ?? '' ?>" required>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Lookup Purchase</button>
            </div>
        </form>
    </div>
</div>

<?php if ($purchase): ?>
<form method="POST">
    <input type="hidden" name="purchase_id" value="<?= $purchase['id'] ?>">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Return Items for Purchase #<?= str_pad($purchase['id'], 6, '0', STR_PAD_LEFT) ?> (Supplier: <?= htmlspecialchars($purchase['supplier_name'] ?? 'Unknown') ?>)</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Item</th>
                        <th>Price</th>
                        <th>Purchased Qty</th>
                        <th>Already Returned</th>
                        <th>Available to Return</th>
                        <th>Return Qty</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><span class="badge bg-secondary"><?= ucfirst(str_replace('_', ' ', $item['item_type'])) ?></span></td>
                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                        <td><?= money($item['unit_price']) ?></td>
                        <td><?= $item['qty'] ?></td>
                        <td><?= $item['already_returned'] ?></td>
                        <td><?= $item['returnable_qty'] ?></td>
                        <td>
                            <input type="number" name="return_qty[<?= $item['item_type'] ?>_<?= $item['item_id'] ?>]" class="form-control" 
                                   min="0" max="<?= $item['returnable_qty'] ?>" step="0.5" value="0" 
                                   <?= $item['returnable_qty'] <= 0 ? 'disabled' : '' ?>>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="mt-3">
                <label>Reason for Return:</label>
                <textarea name="reason" class="form-control" rows="2" placeholder="Optional..."></textarea>
            </div>
            
            <div class="mt-4 text-end">
                <button type="submit" name="process_return" class="btn btn-danger"><i class="fas fa-undo"></i> Process Purchase Return</button>
            </div>
        </div>
    </div>
</form>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
