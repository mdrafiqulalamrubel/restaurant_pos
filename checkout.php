<?php
// checkout.php - Fixed to redirect to token printing
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: pos.php');
    exit;
}

$cart_data = json_decode($_POST['cart_data'], true);
$payment_method = $_POST['payment_method'] ?? 'cash';

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
    $tax = $subtotal * ($tax_rate / 100);
    $total = $subtotal + $tax;
    
    $stmt = $pdo->prepare("INSERT INTO sales (total, payment_method, tax) VALUES (?, ?, ?)");
    $stmt->execute([$total, $payment_method, $tax]);
    $sale_id = $pdo->lastInsertId();
    
    foreach ($cart_data as $item) {
        $stmt = $pdo->prepare("INSERT INTO sale_items (sale_id, item_id, qty, unit_price) VALUES (?, ?, ?, ?)");
        $stmt->execute([$sale_id, $item['id'], $item['qty'], $item['price']]);
        
        if ($item['booking_required'] && !empty($item['bk_date'])) {
            $sale_item_id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("INSERT INTO bookings (sale_item_id, booking_date, booking_time, duration) VALUES (?, ?, ?, ?)");
            $stmt->execute([$sale_item_id, $item['bk_date'], $item['bk_time'], 1]);
        }
    }
    
    $pdo->commit();
    
    // After successful checkout, redirect to token selection page
    header("Location: select_sale_for_token.php");
    exit;
    
} catch (Exception $e) {
    $pdo->rollBack();
    die("Error: " . $e->getMessage());
}
?>