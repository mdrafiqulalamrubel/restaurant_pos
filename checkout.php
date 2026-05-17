<?php
require_once 'config.php';
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['items'])) {
    die('Invalid request');
}

$items = [];
$customer_id = $_POST['customer_id'] ?? 0;
$payment_method = $_POST['payment_method'] ?? 'cash';
$discount = $_POST['discount'] ?? 0;

foreach ($_POST['items'] as $i => $data) {
    $item = [
        'id' => $data['id'],
        'qty' => $data['qty'],
        'price' => $data['price'],
        'booking_required' => $data['booking_required'] ?? 0,
        'bk_date' => $data['bk_date'] ?? '',
        'bk_time' => $data['bk_time'] ?? '',
        'bk_duration' => $data['bk_duration'] ?? 1,
        'bk_notes' => $data['bk_notes'] ?? ''
    ];
    $items[] = $item;
}

try {
    $saleId = saveSale($items, $customer_id, $payment_method, $discount);
    header('Location: invoice.php?id=' . $saleId);
    exit;
} catch (Exception $e) {
    die('Checkout failed: ' . $e->getMessage());
}
?>