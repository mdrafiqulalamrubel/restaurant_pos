<?php
$page_title = 'Print Bill';
$page_icon = 'print';
require_once 'config.php';
require_once 'header.php';

$sale_id = $_GET['id'] ?? 0;
$print_type = $_GET['type'] ?? 'thermal'; // thermal or a4

if (!$sale_id) die('Invalid sale ID');

// Get sale info
$stmt = $pdo->prepare("
    SELECT s.*, c.name as customer_name, c.phone as customer_phone 
    FROM sales s 
    LEFT JOIN customers c ON s.customer_id = c.id 
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
        'currency' => '€', 
        'currency_code' => 'EUR', 
        'logo' => '', 
        'address' => '', 
        'phone' => '', 
        'tax_rate' => 10,
        'receipt_footer' => 'Thank you!'
    ];
}

$currency = $company['currency'];
$tax_rate = $company['tax_rate'] ?? 10;

$subtotal = array_sum(array_map(function($item) {
    return $item['qty'] * $item['unit_price'];
}, $order_items));
$discount = $sale['discount'] ?? 0;
$tax = ($subtotal - $discount) * ($tax_rate / 100);
$total = $subtotal - $discount + $tax;
?>

<style>
    .print-container {
        background: white;
        border-radius: 15px;
        padding: 30px;
        margin-top: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    @media print {
        .no-print {
            display: none !important;
        }
        .sidebar, .top-bar, .print-actions {
            display: none !important;
        }
        .content {
            padding: 0 !important;
            margin: 0 !important;
        }
        .print-container {
            box-shadow: none;
            padding: 0;
            margin: 0;
        }
    }
    
    /* Thermal receipt style */
    .thermal-receipt {
        max-width: 400px;
        margin: 0 auto;
        font-family: 'Courier New', monospace;
        font-size: 12px;
    }
    
    /* A4 invoice style */
    .a4-invoice {
        max-width: 800px;
        margin: 0 auto;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .receipt-header { text-align: center; margin-bottom: 15px; }
    .receipt-divider { border-top: 1px dashed #000; margin: 10px 0; }
    .receipt-item { display: flex; justify-content: space-between; margin: 5px 0; }
    .receipt-total { font-weight: bold; margin-top: 10px; padding-top: 10px; border-top: 1px solid #000; }
    .thankyou { text-align: center; margin: 15px 0; font-weight: bold; }
    
    .invoice-header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #667eea; padding-bottom: 20px; }
    .invoice-title { font-size: 24px; margin: 20px 0; }
    .logo-img { max-width: 120px; max-height: 60px; margin-bottom: 10px; }
    
    .print-actions {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 1000;
        display: flex;
        gap: 10px;
    }
    
    .btn-print {
        background: #667eea;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
        font-weight: bold;
    }
    
    .btn-back {
        background: #28a745;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
        font-weight: bold;
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-back:hover {
        background: #218838;
        color: white;
    }
</style>

<div class="print-container">
    <?php if ($print_type == 'thermal'): ?>
        <!-- Thermal Receipt -->
        <div class="thermal-receipt" id="receiptContent">
            <div class="receipt-header">
                <?php if ($company['logo'] && file_exists($company['logo'])): ?>
                    <img src="<?= $company['logo'] ?>" class="logo-img" alt="Logo">
                <?php endif; ?>
                <h3><?= htmlspecialchars($company['name']) ?></h3>
                <div style="font-size: 10px;"><?= nl2br(htmlspecialchars($company['address'] ?? '')) ?></div>
                <div style="font-size: 10px;">Tel: <?= $company['phone'] ?? '' ?></div>
                <div class="receipt-divider"></div>
                <div>Receipt #: <?= str_pad($sale_id, 6, '0', STR_PAD_LEFT) ?></div>
                <div>Date: <?= date('d/m/Y H:i:s', strtotime($sale['created_at'])) ?></div>
                <?php if ($token): ?>
                <div style="font-size: 18px; font-weight: bold; margin: 10px 0;">TOKEN: <?= str_pad($token['token_number'], 3, '0', STR_PAD_LEFT) ?></div>
                <?php endif; ?>
                <div>Customer: <?= htmlspecialchars($sale['customer_name'] ?? 'Walk-in') ?></div>
                <div class="receipt-divider"></div>
            </div>
            
            <div>
                <?php foreach ($order_items as $item): ?>
                <div class="receipt-item">
                    <span><?= $item['qty'] ?>x <?= htmlspecialchars($item['name']) ?></span>
                    <span><?= $currency ?><?= number_format($item['qty'] * $item['unit_price'], 2) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="receipt-divider"></div>
            
            <div class="receipt-item">
                <span>Subtotal:</span>
                <span><?= $currency ?><?= number_format($subtotal, 2) ?></span>
            </div>
            <?php if ($discount > 0): ?>
            <div class="receipt-item">
                <span>Discount:</span>
                <span>-<?= $currency ?><?= number_format($discount, 2) ?></span>
            </div>
            <?php endif; ?>
            <div class="receipt-item">
                <span>Tax (<?= $tax_rate ?>%):</span>
                <span><?= $currency ?><?= number_format($tax, 2) ?></span>
            </div>
            <div class="receipt-total">
                <div class="receipt-item">
                    <span><strong>TOTAL:</strong></span>
                    <span><strong><?= $currency ?><?= number_format($total, 2) ?></strong></span>
                </div>
            </div>
            
            <div class="receipt-item">
                <span>Payment:</span>
                <span><?= ucfirst($sale['payment_method'] ?? 'cash') ?></span>
            </div>
            
            <div class="receipt-divider"></div>
            
            <div class="thankyou">
                Thank you!<br>
                Please visit again
            </div>
            
            <div style="text-align: center; font-size: 10px; margin-top: 10px;">
                <?= nl2br(htmlspecialchars($company['receipt_footer'] ?? '*** Thank you ***')) ?>
            </div>
        </div>
        
    <?php else: ?>
        <!-- A4 Invoice -->
        <div class="a4-invoice" id="receiptContent">
            <div class="invoice-header">
                <?php if ($company['logo'] && file_exists($company['logo'])): ?>
                    <img src="<?= $company['logo'] ?>" class="logo-img" alt="Logo">
                <?php endif; ?>
                <h2><?= htmlspecialchars($company['name']) ?></h2>
                <div><?= nl2br(htmlspecialchars($company['address'] ?? '')) ?></div>
                <div>Phone: <?= $company['phone'] ?? '' ?> | Email: <?= $company['email'] ?? '' ?></div>
                <div class="invoice-title">TAX INVOICE</div>
                <?php if ($token): ?>
                <div class="badge" style="background: #667eea; color: white; padding: 5px 15px; border-radius: 20px;">Order Token: <?= str_pad($token['token_number'], 3, '0', STR_PAD_LEFT) ?></div>
                <?php endif; ?>
            </div>
            
            <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
                <div>
                    <strong>Bill To:</strong><br>
                    <strong><?= htmlspecialchars($sale['customer_name'] ?? 'Walk-in Customer') ?></strong><br>
                    <?php if ($sale['customer_phone']): ?>
                    Phone: <?= $sale['customer_phone'] ?><br>
                    <?php endif; ?>
                </div>
                <div style="text-align: right;">
                    <strong>Invoice Details:</strong><br>
                    Invoice #: <?= str_pad($sale_id, 6, '0', STR_PAD_LEFT) ?><br>
                    Date: <?= date('d/m/Y', strtotime($sale['created_at'])) ?><br>
                    Time: <?= date('h:i A', strtotime($sale['created_at'])) ?>
                </div>
            </div>
            
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                <thead>
                    <tr style="background: #f8f9fa; border-bottom: 2px solid #ddd;">
                        <th style="padding: 10px; text-align: left;">#</th>
                        <th style="padding: 10px; text-align: left;">Item Description</th>
                        <th style="padding: 10px; text-align: center;">Qty</th>
                        <th style="padding: 10px; text-align: right;">Unit Price</th>
                        <th style="padding: 10px; text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($order_items as $item): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 8px;"><?= $i++ ?></td>
                        <td style="padding: 8px;"><?= htmlspecialchars($item['name']) ?></td>
                        <td style="padding: 8px; text-align: center;"><?= $item['qty'] ?></td>
                        <td style="padding: 8px; text-align: right;"><?= $currency ?><?= number_format($item['unit_price'], 2) ?></td>
                        <td style="padding: 8px; text-align: right;"><?= $currency ?><?= number_format($item['qty'] * $item['unit_price'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="border-top: 2px solid #ddd;">
                        <td colspan="4" style="padding: 8px; text-align: right;"><strong>Subtotal:</strong></td>
                        <td style="padding: 8px; text-align: right;"><?= $currency ?><?= number_format($subtotal, 2) ?></td>
                    </tr>
                    <?php if ($discount > 0): ?>
                    <tr>
                        <td colspan="4" style="padding: 8px; text-align: right;"><strong>Discount:</strong></td>
                        <td style="padding: 8px; text-align: right;">-<?= $currency ?><?= number_format($discount, 2) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td colspan="4" style="padding: 8px; text-align: right;"><strong>Tax (<?= $tax_rate ?>%):</strong></td>
                        <td style="padding: 8px; text-align: right;"><?= $currency ?><?= number_format($tax, 2) ?></td>
                    </tr>
                    <tr style="background: #667eea; color: white;">
                        <td colspan="4" style="padding: 10px; text-align: right;"><strong>GRAND TOTAL:</strong></td>
                        <td style="padding: 10px; text-align: right;"><strong><?= $currency ?><?= number_format($total, 2) ?></strong></td>
                    </tr>
                </tfoot>
            </table>
            
            <div style="display: flex; justify-content: space-between; margin-top: 20px;">
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; flex: 1; margin-right: 10px;">
                    <strong>Payment Method:</strong> <?= ucfirst($sale['payment_method'] ?? 'Cash') ?><br>
                    <strong>Amount Paid:</strong> <?= $currency ?><?= number_format($total, 2) ?>
                </div>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; flex: 1; text-align: center;">
                    <strong>Authorized Signature</strong><br><br>
                    ___________________
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666;">
                <p><?= nl2br(htmlspecialchars($company['receipt_footer'] ?? 'Thank you for your business!')) ?></p>
                <p>*** GST Registered ***</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Print Actions - Fixed at bottom right -->
<div class="print-actions no-print">
    <button class="btn-print" onclick="printReceipt()">
        <i class="fas fa-print"></i> Print
    </button>
    
    <?php if ($print_type == 'thermal'): ?>
        <a href="print_bill.php?id=<?= $sale_id ?>&type=a4" class="btn-back">
            <i class="fas fa-file-alt"></i> A4 Bill
        </a>
    <?php else: ?>
        <a href="print_bill.php?id=<?= $sale_id ?>&type=thermal" class="btn-back">
            <i class="fas fa-receipt"></i> Thermal Receipt
        </a>
    <?php endif; ?>
    
    <a href="pos.php" class="btn-back">
        <i class="fas fa-cash-register"></i> New Sale
    </a>
    
    <a href="index.php" class="btn-back" style="background: #6c757d;">
        <i class="fas fa-home"></i> Dashboard
    </a>
</div>

<script>
function printReceipt() {
    // Store original title
    var originalTitle = document.title;
    document.title = 'Print Receipt';
    
    // Print
    window.print();
    
    // Restore title
    setTimeout(function() {
        document.title = originalTitle;
    }, 500);
}

// Auto print after page loads
setTimeout(function() {
    window.print();
}, 500);
</script>

<?php require_once 'footer.php'; ?>