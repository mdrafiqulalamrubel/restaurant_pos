<?php
// print_bill.php - Pure print page with branch name
require_once 'config.php';

// DO NOT include header.php - this is a standalone print page

$sale_id = $_GET['id'] ?? 0;
$print_type = $_GET['type'] ?? 'thermal';

if (!$sale_id) die('Invalid sale ID');

// Get sale info with branch
$stmt = $pdo->prepare("
    SELECT s.*, c.name as customer_name, c.phone as customer_phone, b.name as branch_name, b.address as branch_address, b.phone as branch_phone
    FROM sales s 
    LEFT JOIN customers c ON s.customer_id = c.id 
    LEFT JOIN branches b ON s.branch_id = b.id
    WHERE s.id = ?
");
$stmt->execute([$sale_id]);
$sale = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sale) die('Sale not found');

// Get sale items
$items = $pdo->prepare("
    SELECT si.*, i.name 
    FROM sale_items si 
    JOIN items i ON si.item_id = i.id 
    WHERE si.sale_id = ?
");
$items->execute([$sale_id]);
$order_items = $items->fetchAll(PDO::FETCH_ASSOC);

// Get token
$stmt = $pdo->prepare("SELECT * FROM order_tokens WHERE sale_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$sale_id]);
$token = $stmt->fetch(PDO::FETCH_ASSOC);

// Get company settings
$company = $pdo->query("SELECT * FROM company_settings WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
if (!$company) {
    $company = [
        'name' => 'Restaurant POS', 
        'currency' => '', 
        'currency_code' => '', 
        'logo' => '', 
        'address' => '', 
        'phone' => '', 
        'tax_rate' => 10,
        'receipt_footer' => 'Thank you!'
    ];
}

$tax_rate = $company['tax_rate'] ?? 10;
$subtotal = array_sum(array_map(function($item) { return $item['qty'] * $item['unit_price']; }, $order_items));
$discount = $sale['discount'] ?? 0;
$tax = ($subtotal - $discount) * ($tax_rate / 100);
$total = $subtotal - $discount + $tax;

// Branch info
$branch_name = $sale['branch_name'] ?? 'Main Branch';
$branch_address = $sale['branch_address'] ?? '';
$branch_phone = $sale['branch_phone'] ?? '';
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Print Receipt - <?= str_pad($sale_id, 6, '0', STR_PAD_LEFT) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        @media print {
            body { margin: 0; padding: 0; }
            .no-print { display: none; }
            <?php if ($print_type === 'a4'): ?>
            @page { size: A4; margin: 15mm; }
            <?php else: ?>
            @page { size: 80mm auto; margin: 0mm; }
            <?php endif; ?>
        }
        
        body {
            font-family: 'Courier New', 'Lucida Console', monospace;
            background: #e0e0e0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .receipt-container {
            background: white;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            border-radius: 5px;
        }
        
        .receipt {
            <?php if ($print_type === 'a4'): ?>
            width: 100%;
            max-width: 210mm;
            padding: 20px;
            font-family: 'Segoe UI', Arial, sans-serif;
            <?php else: ?>
            width: 80mm;
            max-width: 80mm;
            padding: 10px;
            <?php endif; ?>
            margin: 0 auto;
            padding: 8px;
            background: white;
        }
        
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 5px 0; }
        .double-divider { border-top: 2px solid #000; margin: 5px 0; }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 5px 0;
        }
        .items-table td { padding: 2px 0; }
        .item-name { text-align: left; width: 45%; }
        .item-qty { text-align: center; width: 15%; }
        .item-price { text-align: right; width: 20%; }
        .item-total { text-align: right; width: 20%; }
        
        .total-row { width: 100%; margin: 3px 0; display: flex; justify-content: space-between; }
        .footer { margin-top: 10px; text-align: center; }
        
        .print-actions {
            position: fixed;
            bottom: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
            z-index: 1000;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
            font-family: Arial, sans-serif;
        }
        .btn-print { background: #667eea; color: white; }
        .btn-close { background: #dc3545; color: white; }
        .btn-back { background: #28a745; color: white; }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt">
            <!-- Header with Branch Name -->
            <div class="text-center">
                <div class="bold" style="font-size: 14pt;"><?= htmlspecialchars($company['name']) ?></div>
                <div class="bold" style="font-size: 10pt; margin-top: 3px;"><?= htmlspecialchars($branch_name) ?></div>
                <?php if ($branch_address): ?>
                <div style="font-size: 8pt;"><?= nl2br(htmlspecialchars($branch_address)) ?></div>
                <?php endif; ?>
                <?php if ($branch_phone): ?>
                <div style="font-size: 8pt;">Tel: <?= htmlspecialchars($branch_phone) ?></div>
                <?php endif; ?>
                <div class="divider"></div>
                <div>Receipt: <?= str_pad($sale_id, 6, '0', STR_PAD_LEFT) ?></div>
                <div><?= date('d/m/Y H:i:s', strtotime($sale['created_at'])) ?></div>
                <?php if ($token): ?>
                <div class="bold" style="font-size: 14pt; margin: 5px 0;">TOKEN: <?= str_pad($token['token_number'], 3, '0', STR_PAD_LEFT) ?></div>
                <?php endif; ?>
                <div>Cust: <?= htmlspecialchars($sale['customer_name'] ?? 'Walk-in') ?></div>
                <div class="divider"></div>
            </div>
            
            <!-- Items Table -->
            <table class="items-table">
                <tr style="border-bottom: 1px dotted #000;">
                    <td class="item-name bold">Item</td>
                    <td class="item-qty bold">Qty</td>
                    <td class="item-price bold">Price</td>
                    <td class="item-total bold">Amount</td>
                </tr>
                <?php foreach ($order_items as $item): ?>
                <tr>
                    <td class="item-name"><?= htmlspecialchars($item['name']) ?></td>
                    <td class="item-qty">x<?= number_format($item['qty'], 2) ?></td>
                    <td class="item-price"><?= number_format($item['unit_price'], 2) ?></td>
                    <td class="item-total"><?= number_format($item['qty'] * $item['unit_price'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            
            <div class="divider"></div>
            
            <!-- Totals -->
            <div class="total-row">
                <span>Subtotal:</span>
                <span><?= number_format($subtotal, 2) ?></span>
            </div>
            <?php if ($discount > 0): ?>
            <div class="total-row">
                <span>Discount:</span>
                <span>-<?= number_format($discount, 2) ?></span>
            </div>
            <?php endif; ?>
            <div class="total-row">
                <span>Tax (<?= $tax_rate ?>%):</span>
                <span><?= number_format($tax, 2) ?></span>
            </div>
            <div class="double-divider"></div>
            <div class="total-row bold">
                <span>TOTAL:</span>
                <span><?= number_format($total, 2) ?></span>
            </div>
            
            <div class="divider"></div>
            
            <!-- Payment -->
            <div class="total-row">
                <span>Payment:</span>
                <span><?= ucfirst($sale['payment_method'] ?? 'cash') ?></span>
            </div>
            <div class="total-row">
                <span>Amount:</span>
                <span><?= number_format($total, 2) ?></span>
            </div>
            
            <div class="divider"></div>
            
            <!-- Footer -->
            <div class="footer">
                <div class="bold">Thank you!</div>
                <div><?= nl2br(htmlspecialchars($company['receipt_footer'] ?? 'Please visit again')) ?></div>
                <div class="divider"></div>
                <div style="font-size: 8pt;">*** Computer generated receipt ***</div>
            </div>
        </div>
    </div>
    
    <!-- Print Actions -->
    <div class="print-actions no-print">
        <button class="btn btn-print" onclick="printReceipt()">🖨️ Print</button>
        <button class="btn btn-back" onclick="goBack()">🏠 Back to POS</button>
        <button class="btn btn-close" onclick="closeWindow()">✖ Close</button>
    </div>
    
    <script>
        function printReceipt() { window.print(); }
        function closeWindow() { window.close(); }
        function goBack() { window.location.href = 'pos.php'; }
        
        setTimeout(function() { window.print(); }, 500);
    </script>
</body>
</html>