<?php
require_once 'config.php';
header('Content-Type: application/json');

$stmt = $pdo->query("SELECT id, name, unit_price as price, category, image, description, booking_required, booking_type, current_stock, is_producible FROM items WHERE active = 1 ORDER BY category, name");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($products as &$p) {
    if ($p['image'] && file_exists($p['image'])) {
        $p['image'] = $p['image'];
    } else {
        $p['image'] = 'uploads/items/default.jpg';
    }
}

echo json_encode($products);
?>