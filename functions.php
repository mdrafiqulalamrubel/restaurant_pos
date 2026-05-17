<?php
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

function saveSale($items, $customer_id = 0, $payment_method = 'cash', $discount = 0) {
    global $pdo;
    $pdo->beginTransaction();
    try {
        $subtotal = 0;
        foreach ($items as $item) $subtotal += $item['price'] * $item['qty'];
        $tax = ($subtotal - $discount) * 0.10;
        $total = $subtotal - $discount + $tax;
        
        // For now, assume full payment (no due)
        $paid_amount = $total;
        $due_amount = 0;
        
        $stmt = $pdo->prepare("INSERT INTO sales (total, customer_id, payment_method, discount, tax, paid_amount, due_amount) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$total, $customer_id ?: null, $payment_method, $discount, $tax, $paid_amount, $due_amount]);
        $saleId = $pdo->lastInsertId();

        foreach ($items as $item) {
            $stmt = $pdo->prepare("INSERT INTO sale_items (sale_id, item_id, qty, unit_price) VALUES (?,?,?,?)");
            $stmt->execute([$saleId, $item['id'], $item['qty'], $item['price']]);
            $saleItemId = $pdo->lastInsertId();

            if (!empty($item['booking_required'])) {
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

        $pdo->commit();
        return $saleId;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
?>