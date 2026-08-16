<?php
$page_title = 'Process Sales Return';
$page_icon = 'undo';
require_once 'config.php';
require_once 'header.php';
require_once 'accounting.php';

$sale = null;
$items = [];
$error = '';
$success = '';

if (isset($_GET['sale_id'])) {
    $sale_id = (int)$_GET['sale_id'];
    $stmt = $pdo->prepare("SELECT s.*, c.name as customer_name FROM sales s LEFT JOIN customers c ON s.customer_id = c.id WHERE s.id = ?");
    $stmt->execute([$sale_id]);
    $sale = $stmt->fetch();
    
    if ($sale) {
        $stmt = $pdo->prepare("
            SELECT si.*, i.name, i.current_stock, i.is_producible, i.cost_price 
            FROM sale_items si 
            JOIN items i ON si.item_id = i.id 
            WHERE si.sale_id = ?
        ");
        $stmt->execute([$sale_id]);
        $items = $stmt->fetchAll();
        
        // Find already returned qtys
        $stmt = $pdo->prepare("SELECT item_id, SUM(qty_returned) as returned FROM sales_return_items sri JOIN sales_returns sr ON sri.return_id = sr.id WHERE sr.sale_id = ? GROUP BY item_id");
        $stmt->execute([$sale_id]);
        $returned = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        foreach ($items as &$item) {
            $item['already_returned'] = $returned[$item['item_id']] ?? 0;
            $item['returnable_qty'] = $item['qty'] - $item['already_returned'];
        }
    } else {
        $error = "Sale ID not found.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_return'])) {
    $sale_id = (int)$_POST['sale_id'];
    $reason = $_POST['reason'] ?? '';
    $return_qtys = $_POST['return_qty'] ?? [];
    
    $total_refund = 0;
    $total_cogs_returned = 0;
    $items_to_return = [];
    
    // Validate
    foreach ($return_qtys as $item_id => $qty) {
        if ($qty > 0) {
            // Find item in sale
            $stmt = $pdo->prepare("SELECT si.unit_price, i.cost_price FROM sale_items si JOIN items i ON si.item_id = i.id WHERE si.sale_id = ? AND si.item_id = ?");
            $stmt->execute([$sale_id, $item_id]);
            $item = $stmt->fetch();
            if ($item) {
                $refund = $item['unit_price'] * $qty;
                $total_refund += $refund;
                $total_cogs_returned += ($item['cost_price'] * $qty);
                $items_to_return[] = [
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
            $stmt = $pdo->prepare("INSERT INTO sales_returns (sale_id, total_refunded, reason, processed_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$sale_id, $total_refund, $reason, $_SESSION['user_id']]);
            $return_id = $pdo->lastInsertId();
            
            foreach ($items_to_return as $rtn) {
                $stmt = $pdo->prepare("INSERT INTO sales_return_items (return_id, item_id, qty_returned, refund_amount) VALUES (?, ?, ?, ?)");
                $stmt->execute([$return_id, $rtn['item_id'], $rtn['qty'], $rtn['refund']]);
                
                // Restock inventory
                $stmt = $pdo->prepare("UPDATE items SET current_stock = current_stock + ? WHERE id = ?");
                $stmt->execute([$rtn['qty'], $rtn['item_id']]);
            }
            
            // Accounting entry
            acc_record_sales_return($return_id, $sale_id, $total_refund, 'cash', '', $total_cogs_returned);
            
            $pdo->commit();
            $success = "Return processed successfully. Refund amount: " . money($total_refund);
            $sale = null; // Reset
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
    <div class="alert alert-success"><?= $success ?> <a href="sales_returns.php" class="alert-link">View all returns</a></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row align-items-center">
            <div class="col-auto">
                <label>Find Sale ID:</label>
            </div>
            <div class="col-auto">
                <input type="number" name="sale_id" class="form-control" value="<?= $_GET['sale_id'] ?? '' ?>" required>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Lookup Sale</button>
            </div>
        </form>
    </div>
</div>

<?php if ($sale): ?>
<form method="POST">
    <input type="hidden" name="sale_id" value="<?= $sale['id'] ?>">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Return Items for Sale #<?= str_pad($sale['id'], 6, '0', STR_PAD_LEFT) ?> (Customer: <?= $sale['customer_name'] ?? 'Walk-in' ?>)</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
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
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td><?= money($item['unit_price']) ?></td>
                        <td><?= $item['qty'] ?></td>
                        <td><?= $item['already_returned'] ?></td>
                        <td><?= $item['returnable_qty'] ?></td>
                        <td>
                            <input type="number" name="return_qty[<?= $item['item_id'] ?>]" class="form-control" 
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
                <button type="submit" name="process_return" class="btn btn-danger"><i class="fas fa-undo"></i> Process Return</button>
            </div>
        </div>
    </div>
</form>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
