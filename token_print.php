<?php
$page_title = 'Print Token';
$page_icon = 'print';
require_once 'config.php';

$sale_id = $_GET['sale_id'] ?? 0;
if (!$sale_id) die('Invalid sale ID');

// Get sale info
$stmt = $pdo->prepare("
    SELECT s.*, c.name as customer_name 
    FROM sales s 
    LEFT JOIN customers c ON s.customer_id = c.id 
    WHERE s.id = ?
");
$stmt->execute([$sale_id]);
$sale = $stmt->fetch(PDO::FETCH_ASSOC);

// Get or create token
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT * FROM order_tokens WHERE sale_id = ? AND token_date = ?");
$stmt->execute([$sale_id, $today]);
$token = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$token) {
    // Get next token number
    $stmt = $pdo->prepare("SELECT COALESCE(MAX(token_number), 0) + 1 FROM order_tokens WHERE token_date = ?");
    $stmt->execute([$today]);
    $token_num = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("INSERT INTO order_tokens (sale_id, token_number, token_date) VALUES (?, ?, ?)");
    $stmt->execute([$sale_id, $token_num, $today]);
    $token = ['token_number' => $token_num];
}

// Get order items
$items = $pdo->prepare("
    SELECT si.*, i.name 
    FROM sale_items si 
    JOIN items i ON si.item_id = i.id 
    WHERE si.sale_id = ?
");
$items->execute([$sale_id]);
$order_items = $items->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Order Token #<?= $token['token_number'] ?></title>
    <style>
        @media print {
            body { margin: 0; padding: 0; }
            .no-print { display: none; }
            .token-card { box-shadow: none; border: 1px solid #ddd; }
        }
        body {
            font-family: 'Courier New', monospace;
            background: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .token-card {
            width: 350px;
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            text-align: center;
        }
        .restaurant-name {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .token-number {
            font-size: 3rem;
            font-weight: bold;
            color: #667eea;
            margin: 15px 0;
            letter-spacing: 5px;
        }
        .token-label {
            font-size: 0.8rem;
            color: #666;
            text-transform: uppercase;
        }
        .order-details {
            text-align: left;
            margin: 15px 0;
            padding: 10px;
            border-top: 1px dashed #ddd;
            border-bottom: 1px dashed #ddd;
        }
        .order-item {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }
        .barcode {
            margin: 15px 0;
            padding: 10px;
            background: #f8f9fa;
        }
        .time-info {
            font-size: 0.8rem;
            color: #666;
            margin-top: 10px;
        }
        .print-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 15px;
            width: 100%;
        }
    </style>
</head>
<body>
<div class="token-card">
    <div class="restaurant-name">
        <i class="fas fa-utensils"></i> RESTAURANT POS
    </div>
    <div class="token-label">ORDER TOKEN</div>
    <div class="token-number">#<?= str_pad($token['token_number'], 3, '0', STR_PAD_LEFT) ?></div>
    
    <div class="order-details">
        <?php foreach ($order_items as $item): ?>
        <div class="order-item">
            <span><?= $item['qty'] ?>x <?= htmlspecialchars($item['name']) ?></span>
            <span>€<?= number_format($item['qty'] * $item['unit_price'], 2) ?></span>
        </div>
        <?php endforeach; ?>
        <div class="order-item mt-2" style="font-weight: bold; border-top: 1px solid #ddd; padding-top: 5px;">
            <span>TOTAL</span>
            <span>€<?= number_format($sale['total'], 2) ?></span>
        </div>
    </div>
    
    <div class="barcode">
        <svg id="barcode"></svg>
        <div><?= str_pad($token['token_number'], 8, '0', STR_PAD_LEFT) ?></div>
    </div>
    
    <div class="time-info">
        <?= date('d/m/Y H:i:s') ?>
    </div>
    
    <button class="print-btn no-print" onclick="window.print()">
        <i class="fas fa-print"></i> Print Token
    </button>
</div>

<script>
window.onload = function() {
    // Auto print
    setTimeout(() => {
        window.print();
    }, 500);
}
</script>
</body>
</html>