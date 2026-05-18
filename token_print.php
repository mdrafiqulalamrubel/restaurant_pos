<?php
// token_print.php - Updated to handle both parameter names
$page_title = 'Print Token';
$page_icon = 'print';
require_once 'config.php';

// Get sale_id from various possible parameter names
$sale_id = $_GET['sale_id'] ?? $_GET['id'] ?? $_GET['invoice_id'] ?? 0;
$type = $_GET['type'] ?? 'both';
 // kitchen, customer, both

if (!$sale_id) {
    // If no sale_id, show error with back button
      header('Location: select_sale_for_token.php');
    exit;
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Error - No Sale ID</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { background: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; }
            .error-box { background: white; border-radius: 15px; padding: 40px; text-align: center; max-width: 500px; }
        </style>
    </head>
    <body>
        <div class="error-box">
            <i class="fas fa-exclamation-triangle fa-4x text-warning mb-3"></i>
            <h3>No Sale ID Found</h3>
            <p>Unable to print token because no sale was selected.</p>
            <a href="pos.php" class="btn btn-primary mt-3">Go to POS</a>
            <a href="transactions.php" class="btn btn-secondary mt-3">View Transactions</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Get company settings
$company = $pdo->query("SELECT * FROM company_settings WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
if (!$company) {
    $company = ['name' => 'Restaurant POS', 'currency' => '€', 'currency_code' => 'EUR', 'logo' => '', 'address' => '', 'phone' => ''];
}
$currency = $company['currency'];

// Get sale info
$stmt = $pdo->prepare("
    SELECT s.*, c.name as customer_name, c.phone as customer_phone 
    FROM sales s 
    LEFT JOIN customers c ON s.customer_id = c.id 
    WHERE s.id = ?
");
$stmt->execute([$sale_id]);
$sale = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sale) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Error - Sale Not Found</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { background: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; }
            .error-box { background: white; border-radius: 15px; padding: 40px; text-align: center; max-width: 500px; }
        </style>
    </head>
    <body>
        <div class="error-box">
            <i class="fas fa-search fa-4x text-danger mb-3"></i>
            <h3>Sale Not Found</h3>
            <p>Sale ID #<?= $sale_id ?> does not exist.</p>
            <a href="pos.php" class="btn btn-primary mt-3">Go to POS</a>
            <a href="transactions.php" class="btn btn-secondary mt-3">View Transactions</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Get or create token
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT * FROM order_tokens WHERE sale_id = ? AND token_date = ?");
$stmt->execute([$sale_id, $today]);
$token = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$token) {
    $stmt = $pdo->prepare("SELECT COALESCE(MAX(token_number), 0) + 1 FROM order_tokens WHERE token_date = ?");
    $stmt->execute([$today]);
    $token_num = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("INSERT INTO order_tokens (sale_id, token_number, token_date, status) VALUES (?, ?, ?, 'pending')");
    $stmt->execute([$sale_id, $token_num, $today]);
    $token = ['token_number' => $token_num, 'status' => 'pending'];
}

// Get order items
$items = $pdo->prepare("
    SELECT si.*, i.name, i.booking_required 
    FROM sale_items si 
    JOIN items i ON si.item_id = i.id 
    WHERE si.sale_id = ?
");
$items->execute([$sale_id]);
$order_items = $items->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$subtotal = array_sum(array_map(function($item) {
    return $item['qty'] * $item['unit_price'];
}, $order_items));
$tax = $sale['tax'] ?? ($subtotal * 0.10);
$total = $sale['total'];

// Update token status to printed
$stmt = $pdo->prepare("UPDATE order_tokens SET status = 'printed', printed_at = NOW() WHERE sale_id = ? AND token_date = ?");
$stmt->execute([$sale_id, $today]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Token #<?= str_pad($token['token_number'], 3, '0', STR_PAD_LEFT) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        @media print {
            body { margin: 0; padding: 0; background: white; }
            .no-print { display: none !important; }
            .token-card { box-shadow: none; border: 1px solid #ddd; page-break-after: avoid; }
            .kitchen-token, .customer-token { page-break-after: avoid; }
        }
        
        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        
        .token-container {
            max-width: 500px;
            margin: 0 auto;
        }
        
        .kitchen-token {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 5px solid #ff9800;
        }
        
        .customer-token {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 5px solid #4caf50;
        }
        
        .token-header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px dashed #ddd;
        }
        
        .restaurant-name {
            font-size: 1.3rem;
            font-weight: bold;
            color: #667eea;
        }
        
        .token-type {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 5px;
        }
        
        .kitchen-token .token-type { color: #ff9800; }
        .customer-token .token-type { color: #4caf50; }
        
        .token-number {
            font-size: 3rem;
            font-weight: bold;
            text-align: center;
            margin: 15px 0;
            letter-spacing: 5px;
        }
        
        .kitchen-token .token-number { color: #ff9800; }
        .customer-token .token-number { color: #4caf50; }
        
        .order-table {
            width: 100%;
            margin: 10px 0;
            border-collapse: collapse;
        }
        
        .order-table th, .order-table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .order-table th {
            font-weight: bold;
            border-bottom: 1px solid #ddd;
        }
        
        .order-table .text-right { text-align: right; }
        .order-table .text-center { text-align: center; }
        
        .total-row {
            font-weight: bold;
            border-top: 1px solid #ddd;
            margin-top: 10px;
            padding-top: 10px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
            font-size: 0.85rem;
        }
        
        .barcode {
            text-align: center;
            margin: 15px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .barcode-number {
            font-family: 'Courier New', monospace;
            letter-spacing: 2px;
            font-size: 1rem;
        }
        
        .footer-note {
            text-align: center;
            font-size: 0.7rem;
            color: #666;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px dashed #ddd;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary { background: #667eea; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ff9800; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        
        .btn:hover { opacity: 0.9; transform: translateY(-1px); }
        
        @media (max-width: 600px) {
            body { padding: 10px; }
            .token-number { font-size: 2rem; }
        }
    </style>
</head>
<body>
    <div class="token-container">
        <?php if ($type == 'kitchen' || $type == 'both'): ?>
        <!-- KITCHEN TOKEN -->
        <div class="kitchen-token" id="kitchenToken">
            <div class="token-header">
                <div class="restaurant-name">🍽️ <?= htmlspecialchars($company['name']) ?></div>
                <div class="token-type">🍳 KITCHEN ORDER TOKEN</div>
            </div>
            
            <div class="token-number">
                #<?= str_pad($token['token_number'], 3, '0', STR_PAD_LEFT) ?>
            </div>
            
            <div class="info-row">
                <span>📅 Date: <?= date('d/m/Y H:i') ?></span>
                <span>👨‍🍳 Order: #<?= str_pad($sale_id, 6, '0', STR_PAD_LEFT) ?></span>
            </div>
            
            <table class="order-table">
                <thead>
                    <tr style="background: #fff3e0;">
                        <th>Item</th>
                        <th class="text-center">Qty</th>
                        <th class="text-right">Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['name']) ?>
                            <?php if ($item['booking_required']): ?>
                                <span style="background:#ff9800; color:white; font-size:10px; padding:2px 5px; border-radius:3px; margin-left:5px;">Booking</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><?= $item['qty'] ?></td>
                        <td class="text-right">-</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="footer-note">
                <p>🔥 Please prepare as soon as possible</p>
                <p>Order Time: <?= date('h:i A') ?></p>
            </div>
            
            <div class="barcode">
                <div class="barcode-number"><?= str_pad($token['token_number'], 8, '0', STR_PAD_LEFT) ?></div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($type == 'customer' || $type == 'both'): ?>
        <!-- CUSTOMER TOKEN -->
        <div class="customer-token" id="customerToken">
            <div class="token-header">
                <?php if ($company['logo'] && file_exists($company['logo'])): ?>
                    <img src="<?= $company['logo'] ?>" style="max-width: 100px; max-height: 40px; margin-bottom: 5px;">
                <?php endif; ?>
                <div class="restaurant-name">🍽️ <?= htmlspecialchars($company['name']) ?></div>
                <div class="token-type">🎫 CUSTOMER ORDER TOKEN</div>
            </div>
            
            <div class="token-number">
                #<?= str_pad($token['token_number'], 3, '0', STR_PAD_LEFT) ?>
            </div>
            
            <div class="info-row">
                <span>📅 Date: <?= date('d/m/Y') ?></span>
                <span>⏰ Time: <?= date('h:i A') ?></span>
            </div>
            
            <div class="info-row">
                <span>👤 Customer: <?= htmlspecialchars($sale['customer_name'] ?? 'Walk-in Customer') ?></span>
                <?php if ($sale['customer_phone']): ?>
                <span>📞 <?= $sale['customer_phone'] ?></span>
                <?php endif; ?>
            </div>
            
            <table class="order-table">
                <thead>
                    <tr style="background: #e8f5e9;">
                        <th>Item</th>
                        <th class="text-center">Qty</th>
                        <th class="text-right">Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td class="text-center"><?= $item['qty'] ?></td>
                        <td class="text-right"><?= $currency ?><?= number_format($item['unit_price'] * $item['qty'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="text-right"><strong>Subtotal:</strong></td>
                        <td class="text-right"><?= $currency ?><?= number_format($subtotal, 2) ?></td>
                    </tr>
                    <?php if ($sale['discount'] > 0): ?>
                    <tr>
                        <td colspan="2" class="text-right"><strong>Discount:</strong></td>
                        <td class="text-right">-<?= $currency ?><?= number_format($sale['discount'], 2) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td colspan="2" class="text-right"><strong>Tax:</strong></td>
                        <td class="text-right"><?= $currency ?><?= number_format($tax, 2) ?></td>
                    </tr>
                    <tr style="border-top: 2px solid #ddd;">
                        <td colspan="2" class="text-right"><strong>TOTAL:</strong></td>
                        <td class="text-right"><strong><?= $currency ?><?= number_format($total, 2) ?></strong></td>
                    </tr>
                </tfoot>
            </table>
            
            <div class="info-row">
                <span>💳 Payment: <?= ucfirst($sale['payment_method'] ?? 'Cash') ?></span>
                <span>✅ Status: Paid</span>
            </div>
            
            <div class="footer-note">
                <p><?= nl2br(htmlspecialchars($company['receipt_footer'] ?? 'Thank you for dining with us!')) ?></p>
                <p>Please show this token to collect your order</p>
            </div>
            
            <div class="barcode">
                <div class="barcode-number"><?= str_pad($token['token_number'], 8, '0', STR_PAD_LEFT) ?></div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Action Buttons -->
        <div class="action-buttons no-print">
            <?php if ($type == 'both'): ?>
                <button class="btn btn-primary" onclick="printKitchen()">🍳 Print Kitchen Token</button>
                <button class="btn btn-success" onclick="printCustomer()">🎫 Print Customer Token</button>
                <button class="btn btn-warning" onclick="printBoth()">🖨️ Print Both</button>
            <?php elseif ($type == 'kitchen'): ?>
                <button class="btn btn-primary" onclick="window.print()">🍳 Print Kitchen Token</button>
                <a href="token_print.php?sale_id=<?= $sale_id ?>&type=customer" class="btn btn-success">🎫 Customer Token</a>
                <a href="token_print.php?sale_id=<?= $sale_id ?>&type=both" class="btn btn-warning">📋 Both Tokens</a>
            <?php elseif ($type == 'customer'): ?>
                <button class="btn btn-success" onclick="window.print()">🎫 Print Customer Token</button>
                <a href="token_print.php?sale_id=<?= $sale_id ?>&type=kitchen" class="btn btn-primary">🍳 Kitchen Token</a>
                <a href="token_print.php?sale_id=<?= $sale_id ?>&type=both" class="btn btn-warning">📋 Both Tokens</a>
            <?php endif; ?>
            
            <a href="invoice.php?id=<?= $sale_id ?>" class="btn btn-secondary">📄 View Invoice</a>
            <a href="pos.php" class="btn btn-success">💰 New Sale</a>
        </div>
    </div>
    
    <script>
        function printKitchen() {
            var printContent = document.getElementById('kitchenToken').innerHTML;
            var originalContent = document.body.innerHTML;
            document.body.innerHTML = '<div style="padding:20px;">' + printContent + '</div>';
            window.print();
            document.body.innerHTML = originalContent;
            setTimeout(function() { location.reload(); }, 500);
        }
        
        function printCustomer() {
            var printContent = document.getElementById('customerToken').innerHTML;
            var originalContent = document.body.innerHTML;
            document.body.innerHTML = '<div style="padding:20px;">' + printContent + '</div>';
            window.print();
            document.body.innerHTML = originalContent;
            setTimeout(function() { location.reload(); }, 500);
        }
        
        function printBoth() {
            var kitchenContent = document.getElementById('kitchenToken').innerHTML;
            var customerContent = document.getElementById('customerToken').innerHTML;
            var originalContent = document.body.innerHTML;
            document.body.innerHTML = '<div style="padding:20px;">' + kitchenContent + '<div style="page-break-after: always; margin-bottom: 20px;"></div>' + customerContent + '</div>';
            window.print();
            document.body.innerHTML = originalContent;
            setTimeout(function() { location.reload(); }, 500);
        }
        
        <?php if ($type == 'kitchen' || $type == 'customer'): ?>
        setTimeout(function() { window.print(); }, 500);
        <?php endif; ?>
    </script>
</body>
</html>