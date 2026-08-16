<?php
// functions.php - Complete with session support

function getItems() {
    global $pdo;
    return $pdo->query("SELECT * FROM items WHERE active=1 ORDER BY category, name")->fetchAll(PDO::FETCH_ASSOC);
}

function getItem($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM items WHERE id=?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function saveSale($items, $customer_id = 0, $payment_method = 'cash', $discount = 0, $session_id = null) {
    global $pdo;
    $pdo->beginTransaction();
    try {
        $subtotal = 0;
        foreach ($items as $item) $subtotal += $item['price'] * $item['qty'];
        $tax = ($subtotal - $discount) * 0.10;
        $total = $subtotal - $discount + $tax;
        
        $paid_amount = $total;
        $due_amount = 0;
        
        // Get current branch from session
        $branch_id = $_SESSION['branch_id'] ?? null;
        
        $stmt = $pdo->prepare("INSERT INTO sales (total, customer_id, payment_method, discount, tax, paid_amount, due_amount, branch_id, session_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$total, $customer_id ?: null, $payment_method, $discount, $tax, $paid_amount, $due_amount, $branch_id, $session_id]);
        $saleId = $pdo->lastInsertId();

        $total_cogs = 0;
        foreach ($items as $item) {
            $stmt = $pdo->prepare("INSERT INTO sale_items (sale_id, item_id, qty, unit_price) VALUES (?,?,?,?)");
            $stmt->execute([$saleId, $item['id'], $item['qty'], $item['price']]);
            $saleItemId = $pdo->lastInsertId();

            // Calculate COGS
            $item_stmt = $pdo->prepare("SELECT cost_price FROM items WHERE id = ?");
            $item_stmt->execute([$item['id']]);
            $cost_price = $item_stmt->fetchColumn() ?: 0;
            $total_cogs += $cost_price * $item['qty'];

            // Deduct stock if not a booking item
            if (empty($item['booking_required'])) {
                $stock_stmt = $pdo->prepare("SELECT current_stock, is_producible FROM items WHERE id = ? FOR UPDATE");
                $stock_stmt->execute([$item['id']]);
                $item_data = $stock_stmt->fetch(PDO::FETCH_ASSOC);
                $current_stock = $item_data['current_stock'] ?: 0;
                $is_producible = $item_data['is_producible'] ?? 0;
                
                // Block if insufficient stock AND not a producible item
                if ($current_stock < $item['qty'] && empty($is_producible)) {
                    throw new Exception("Insufficient stock for item ID: {$item['id']}");
                }
                
                $upd_stmt = $pdo->prepare("UPDATE items SET current_stock = current_stock - ? WHERE id = ?");
                $upd_stmt->execute([$item['qty'], $item['id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO bookings (sale_item_id, booking_date, booking_time, duration, notes) VALUES (?,?,?,?,?)");
                $stmt->execute([
                    $saleItemId,
                    $item['bk_date'],
                    $item['bk_time'],
                    $item['bk_duration'] ?? 1,
                    $item['bk_notes'] ?? ''
                ]);
            }
        }
        
        // Record in accounting system
        require_once 'acc_core.php';
        require_once 'accounting.php';
        $customer_name = '';
        if ($customer_id) {
            $c_stmt = $pdo->prepare("SELECT name FROM customers WHERE id = ?");
            $c_stmt->execute([$customer_id]);
            $customer_name = $c_stmt->fetchColumn() ?: '';
        }
        acc_record_sale($saleId, $total, $paid_amount, $payment_method, $customer_name, $total_cogs, $customer_id ?: null);

        $pdo->commit();
        return $saleId;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
?>