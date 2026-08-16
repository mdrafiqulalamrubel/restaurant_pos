<?php
// checkout.php - Process Checkout with session support
require_once 'config.php';

require_once 'acc_core.php';
require_once 'accounting.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: pos.php');
    exit;
}

$cart_data = json_decode($_POST['cart_data'], true);
$payment_method = $_POST['payment_method'] ?? 'cash';
$customer_id = $_POST['customer_id'] ?? 0;
$discount = $_POST['discount'] ?? 0;
$table_id = $_POST['table_id'] ?? null;
$session_id = $_POST['session_id'] ?? null;

if (empty($cart_data)) {
    header('Location: pos.php');
    exit;
}

try {
    $pdo->beginTransaction();
    
    $subtotal = 0;
    foreach ($cart_data as $item) {
        $subtotal += $item['price'] * $item['qty'];
    }
    $company = $pdo->query("SELECT * FROM company_settings WHERE id = 1")->fetch();
    $tax_rate = $company['tax_rate'] ?? 10;
    $tax = ($subtotal - $discount) * ($tax_rate / 100);
    $total = $subtotal - $discount + $tax;
    
    $stmt = $pdo->prepare("INSERT INTO sales (total, customer_id, payment_method, discount, tax, session_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$total, $customer_id ?: null, $payment_method, $discount, $tax, $session_id]);
    $sale_id = $pdo->lastInsertId();
    
    $total_cogs = 0;
    foreach ($cart_data as $item) {
        $stmt = $pdo->prepare("INSERT INTO sale_items (sale_id, item_id, qty, unit_price) VALUES (?, ?, ?, ?)");
        $stmt->execute([$sale_id, $item['id'], $item['qty'], $item['price']]);
        
        // Calculate COGS
        $item_stmt = $pdo->prepare("SELECT cost_price FROM items WHERE id = ?");
        $item_stmt->execute([$item['id']]);
        $cost_price = $item_stmt->fetchColumn() ?: 0;
        $total_cogs += $cost_price * $item['qty'];
        
        if ($item['booking_required'] && !empty($item['bk_date'])) {
            $sale_item_id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("INSERT INTO bookings (sale_item_id, booking_date, booking_time, duration) VALUES (?, ?, ?, ?)");
            $stmt->execute([$sale_item_id, $item['bk_date'], $item['bk_time'], 1]);
        }
    }
    
    if ($table_id) {
        $stmt = $pdo->prepare("INSERT INTO table_orders (table_id, sale_id, status) VALUES (?, ?, 'active')");
        $stmt->execute([$table_id, $sale_id]);
        $stmt = $pdo->prepare("UPDATE dining_tables SET status = 'occupied', current_order_id = ? WHERE id = ?");
        $stmt->execute([$sale_id, $table_id]);
    }
    
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COALESCE(MAX(token_number), 0) + 1 FROM order_tokens WHERE token_date = ?");
    $stmt->execute([$today]);
    $token_num = $stmt->fetchColumn();
    $stmt = $pdo->prepare("INSERT INTO order_tokens (sale_id, token_number, token_date, status) VALUES (?, ?, ?, 'pending')");
    $stmt->execute([$sale_id, $token_num, $today]);
    
    // Record in accounting system
    $customer_name = '';
    if ($customer_id) {
        $c_stmt = $pdo->prepare("SELECT name FROM customers WHERE id = ?");
        $c_stmt->execute([$customer_id]);
        $customer_name = $c_stmt->fetchColumn() ?: '';
    }
    // Assume full payment at POS checkout
    acc_record_sale($sale_id, $total, $total, $payment_method, $customer_name, $total_cogs, $customer_id ?: null);
    
    $pdo->commit();
    
    // Redirect to POS with success
    header("Location: pos.php?success=1&sale_id=$sale_id&token=$token_num&total=$total&payment=$payment_method");
    exit;
    
} catch (Exception $e) {
    $pdo->rollBack();
    die("Error: " . $e->getMessage());
}
?>