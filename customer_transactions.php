<?php
$page_title = 'Customer Transaction History';
$page_icon = 'history';
require_once 'config.php';
require_once 'header.php';

$customer_id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$customer) die('Customer not found');

$transactions = $pdo->prepare("
    SELECT s.*, COUNT(si.id) as item_count 
    FROM sales s 
    LEFT JOIN sale_items si ON s.id = si.sale_id 
    WHERE s.customer_id = ? 
    GROUP BY s.id 
    ORDER BY s.id DESC
");
$transactions->execute([$customer_id]);
$sales = $transactions->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-user"></i> Transactions for: <?= htmlspecialchars($customer['name']) ?></h5>
        <p class="text-muted">Phone: <?= $customer['phone'] ?> | Email: <?= $customer['email'] ?></p>
        <p>Due Amount: <strong class="text-danger">€<?= number_format($customer['due_amount'] ?? 0, 2) ?></strong></p>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr><th>Invoice #</th><th>Date</th><th>Items</th><th>Total</th><th>Payment</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($sales as $sale): ?>
                    <tr>
                        <td>#<?= $sale['id'] ?></td>
                        <td><?= date('Y-m-d H:i', strtotime($sale['created_at'])) ?></td>
                        <td><?= $sale['item_count'] ?></td>
                        <td>€<?= number_format($sale['total'], 2) ?></td>
                        <td><?= ucfirst($sale['payment_method'] ?? 'cash') ?></td>
                        <td><a href="invoice.php?id=<?= $sale['id'] ?>" class="btn btn-sm btn-info">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <a href="customers.php" class="btn btn-secondary">Back to Customers</a>
    </div>
</div>

<?php require_once 'footer.php'; ?>