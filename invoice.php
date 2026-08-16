<?php
$page_title = 'Invoice';
$page_icon = 'file-invoice';
require_once 'config.php';
require_once 'header.php';

$id = $_GET['id'] ?? 0;

// Get sale info
$stmt = $pdo->prepare("
    SELECT s.*, c.name as customer_name, c.phone as customer_phone, c.email as customer_email, c.address as customer_address
    FROM sales s 
    LEFT JOIN customers c ON s.customer_id = c.id 
    WHERE s.id = ?
");
$stmt->execute([$id]);
$sale = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$sale) die('Sale not found');

// Get sale items
$stmt = $pdo->prepare("
    SELECT si.*, i.name, i.booking_required, b.booking_date, b.booking_time, b.duration, b.notes
    FROM sale_items si
    JOIN items i ON si.item_id = i.id
    LEFT JOIN bookings b ON b.sale_item_id = si.id
    WHERE si.sale_id = ?
");
$stmt->execute([$id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get token
$stmt = $pdo->prepare("SELECT * FROM order_tokens WHERE sale_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$id]);
$token = $stmt->fetch(PDO::FETCH_ASSOC);

// Get company settings
$company = $pdo->query("SELECT * FROM company_settings WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
if (!$company) {
    $company = ['name' => 'Restaurant POS', 'currency' => '€', 'currency_code' => 'EUR', 'logo' => '', 'address' => '', 'phone' => '', 'email' => '', 'tax_rate' => 10];
}
$currency = $company['currency'];
$tax_rate = $company['tax_rate'] ?? 10;

$subtotal = array_sum(array_map(function($item) {
    return $item['qty'] * $item['unit_price'];
}, $items));
$discount = $sale['discount'] ?? 0;
$tax = $sale['tax'] ?? ($subtotal * ($tax_rate / 100));
$total = $sale['total'];
?>

<style>
    .invoice-wrapper {
        background: #f0f2f5;
        padding: 40px 0;
        min-height: 100vh;
    }
    .invoice-container {
        max-width: 1000px;
        margin: 0 auto;
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    .invoice-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 40px;
        color: white;
        position: relative;
    }
    .invoice-header .company-logo {
        max-width: 150px;
        max-height: 60px;
        margin-bottom: 15px;
    }
    .invoice-title {
        font-size: 2rem;
        font-weight: 600;
        margin: 0;
    }
    .invoice-token {
        position: absolute;
        top: 40px;
        right: 40px;
        background: rgba(255,255,255,0.2);
        padding: 8px 20px;
        border-radius: 30px;
        font-weight: bold;
    }
    .invoice-body {
        padding: 40px;
    }
    .bill-to {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 30px;
    }
    .bill-to h6 {
        color: #667eea;
        margin-bottom: 15px;
    }
    .info-row {
        display: flex;
        margin-bottom: 8px;
    }
    .info-label {
        width: 100px;
        font-weight: 600;
        color: #666;
    }
    .info-value {
        flex: 1;
        color: #333;
    }
    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 30px;
    }
    .items-table th {
        background: #f8f9fa;
        padding: 12px;
        text-align: left;
        font-weight: 600;
        color: #555;
        border-bottom: 2px solid #e0e0e0;
    }
    .items-table td {
        padding: 12px;
        border-bottom: 1px solid #eee;
    }
    .items-table .item-name {
        font-weight: 500;
    }
    .totals-section {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 30px;
    }
    .totals-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
    }
    .totals-row.grand-total {
        border-top: 2px solid #ddd;
        margin-top: 10px;
        padding-top: 15px;
        font-size: 1.2rem;
        font-weight: bold;
        color: #667eea;
    }
    .payment-info {
        background: #e8f5e9;
        padding: 15px;
        border-radius: 12px;
        margin-bottom: 20px;
    }
    .footer-note {
        text-align: center;
        padding-top: 30px;
        border-top: 1px solid #eee;
        color: #999;
        font-size: 12px;
    }
    .action-buttons {
        position: fixed;
        bottom: 30px;
        right: 30px;
        display: flex;
        gap: 10px;
        z-index: 1000;
    }
    .action-btn {
        padding: 12px 24px;
        border-radius: 30px;
        font-weight: 600;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .action-btn.print {
        background: #667eea;
        color: white;
        border: none;
    }
    .action-btn.print:hover {
        background: #5a67d8;
        transform: translateY(-2px);
    }
    .action-btn.back {
        background: #28a745;
        color: white;
    }
    .action-btn.back:hover {
        background: #218838;
        transform: translateY(-2px);
    }
    .booking-details {
        font-size: 0.8rem;
        color: #ff9800;
        margin-top: 5px;
    }
    @media print {
        .action-buttons, .sidebar, .top-bar, .no-print {
            display: none !important;
        }
        .invoice-wrapper {
            padding: 0;
            background: white;
            min-height: auto;
        }
        .invoice-container {
            box-shadow: none;
            max-width: 100%;
        }
        body {
            background: white;
            font-size: 10px !important;
        }
        * {
            font-size: 10px !important;
            line-height: 1.2 !important;
        }
        .invoice-header {
            padding: 10px 15px !important;
            background: #fff !important;
            color: #000 !important;
            border-bottom: 2px solid #000;
        }
        .invoice-title {
            font-size: 16px !important;
            color: #000 !important;
        }
        .invoice-token {
            position: static;
            background: none;
            padding: 0;
            color: #000;
        }
        .invoice-body {
            padding: 10px 15px !important;
        }
        .bill-to, .totals-section, .payment-info {
            background: #fff !important;
            padding: 5px !important;
            border: 1px solid #ddd;
            margin-bottom: 10px !important;
        }
        .bill-to h6 {
            color: #000 !important;
            margin-bottom: 5px !important;
            font-weight: bold;
        }
        .items-table th {
            background: #fff !important;
            color: #000 !important;
            padding: 4px !important;
            border-bottom: 1px solid #000 !important;
        }
        .items-table td {
            padding: 4px !important;
        }
        .info-row {
            margin-bottom: 2px !important;
        }
        h1, h2, h3, h4, h5, h6 { margin: 0; padding: 0; }
        .footer-note {
            padding-top: 10px;
        }
    }
</style>

<div class="invoice-wrapper">
    <div class="invoice-container">
        <!-- Invoice Header -->
        <div class="invoice-header">
            <?php if ($company['logo'] && file_exists($company['logo'])): ?>
                <img src="<?= $company['logo'] ?>" class="company-logo" alt="Logo">
            <?php endif; ?>
            <h1 class="invoice-title">TAX INVOICE</h1>
            <?php if ($token): ?>
                <div class="invoice-token">
                    <i class="fas fa-ticket-alt"></i> Token: <?= str_pad($token['token_number'], 3, '0', STR_PAD_LEFT) ?>
                </div>
            <?php endif; ?>
            <div class="row mt-4">
                <div class="col-md-6">
                    <h6><?= htmlspecialchars($company['name']) ?></h6>
                    <p class="mb-0 small"><?= nl2br(htmlspecialchars($company['address'] ?? '')) ?></p>
                    <p class="mb-0 small">Tel: <?= $company['phone'] ?? '' ?> | Email: <?= $company['email'] ?? '' ?></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0"><strong>Invoice #:</strong> <?= str_pad($id, 6, '0', STR_PAD_LEFT) ?></p>
                    <p class="mb-0"><strong>Date:</strong> <?= date('d/m/Y', strtotime($sale['created_at'])) ?></p>
                    <p class="mb-0"><strong>Time:</strong> <?= date('h:i A', strtotime($sale['created_at'])) ?></p>
                </div>
            </div>
        </div>
        
        <!-- Invoice Body -->
        <div class="invoice-body">
            <!-- Bill To Section -->
            <div class="bill-to">
                <h6><i class="fas fa-user"></i> Bill To:</h6>
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label">Customer Name:</div>
                            <div class="info-value"><strong><?= htmlspecialchars($sale['customer_name'] ?? 'Walk-in Customer') ?></strong></div>
                        </div>
                        <?php if ($sale['customer_phone']): ?>
                        <div class="info-row">
                            <div class="info-label">Phone:</div>
                            <div class="info-value"><?= htmlspecialchars($sale['customer_phone']) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <?php if ($sale['customer_email']): ?>
                        <div class="info-row">
                            <div class="info-label">Email:</div>
                            <div class="info-value"><?= htmlspecialchars($sale['customer_email']) ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if ($sale['customer_address']): ?>
                        <div class="info-row">
                            <div class="info-label">Address:</div>
                            <div class="info-value"><?= htmlspecialchars($sale['customer_address']) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Items Table -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Item Description</th>
                        <th class="text-center">Quantity</th>
                        <th class="text-end">Unit Price</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($items as $item): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td class="item-name">
                            <?= htmlspecialchars($item['name']) ?>
                            <?php if ($item['booking_required'] && $item['booking_date']): ?>
                                <div class="booking-details">
                                    <i class="fas fa-calendar"></i> Booking: <?= $item['booking_date'] ?> at <?= $item['booking_time'] ?>
                                    (<?= $item['duration'] ?> hrs)
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><?= $item['qty'] ?></td>
                        <td class="text-end"><?= money($item['unit_price']) ?></td>
                        <td class="text-end"><?= money($item['qty'] * $item['unit_price']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Totals Section -->
            <div class="totals-section">
                <div class="totals-row">
                    <span>Subtotal:</span>
                    <span><?= money($subtotal) ?></span>
                </div>
                <?php if ($discount > 0): ?>
                <div class="totals-row">
                    <span>Discount:</span>
                    <span>- <?= money($discount) ?></span>
                </div>
                <?php endif; ?>
                <div class="totals-row">
                    <span>Tax (<?= $tax_rate ?>%):</span>
                    <span><?= money($tax) ?></span>
                </div>
                <div class="totals-row grand-total">
                    <span>Grand Total:</span>
                    <span><?= money($total) ?></span>
                </div>
            </div>
            
            <!-- Payment Information -->
            <div class="payment-info">
                <div class="row">
                    <div class="col-md-6">
                        <strong><i class="fas fa-credit-card"></i> Payment Method:</strong>
                        <?= ucfirst($sale['payment_method'] ?? 'Cash') ?>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <strong><i class="fas fa-check-circle"></i> Payment Status:</strong>
                        <span class="badge bg-success">Paid</span>
                    </div>
                </div>
            </div>
            
            <!-- Footer Note -->
            <div class="footer-note">
                <p><?= nl2br(htmlspecialchars($company['receipt_footer'] ?? 'Thank you for your business!')) ?></p>
                <p class="mb-0">This is a computer generated invoice. No signature required.</p>
                <p class="mb-0">For support, please contact <?= $company['phone'] ?? 'our support team' ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Floating Action Buttons -->
<div class="action-buttons no-print">
    <button class="action-btn print" onclick="window.print()">
        <i class="fas fa-print"></i> A4 Print
    </button>
    <button class="action-btn print" style="background: #ff9800;" onclick="window.open('print_bill.php?id=<?= $id ?>&type=thermal', 'Print', 'width=450,height=600')">
        <i class="fas fa-receipt"></i> Thermal Print
    </button>
    <a href="pos.php" class="action-btn back">
        <i class="fas fa-cash-register"></i> New Sale
    </a>
    <a href="transactions.php" class="action-btn back" style="background: #6c757d;">
        <i class="fas fa-list"></i> All Sales
    </a>
    <a href="token_print.php?sale_id=<?= $id ?>&type=both" class="btn btn-warning" style="display:flex;align-items:center;border-radius:30px;padding:12px 24px;font-weight:600;color:#000;text-decoration:none;">
        <i class="fas fa-ticket-alt me-2"></i> Print Token
    </a>
</div>

<script>
    <?php if (isset($_GET['print']) && $_GET['print'] == 1): ?>
    setTimeout(function() { window.print(); }, 500);
    <?php endif; ?>
</script>

<?php require_once 'footer.php'; ?>