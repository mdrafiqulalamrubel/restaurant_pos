<?php
require_once 'config.php';
$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM sales WHERE id=?");
$stmt->execute([$id]);
$sale = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$sale) die('Sale not found');

$stmt = $pdo->prepare("
    SELECT si.*, i.name, i.booking_required, b.booking_date, b.booking_time, b.duration, b.notes
    FROM sale_items si
    JOIN items i ON si.item_id = i.id
    LEFT JOIN bookings b ON b.sale_item_id = si.id
    WHERE si.sale_id = ?
");
$stmt->execute([$id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head><title>Invoice #<?= $id ?></title></head>
<body>
<h1>Invoice #<?= $id ?></h1>
<p>Date: <?= $sale['created_at'] ?></p>
<table border="1" cellpadding="5">
<tr><th>Item</th><th>Qty</th><th>Price</th><th>Subtotal</th><th>Booking Details</th></tr>
<?php foreach ($items as $i): ?>
<tr>
   <td><?= htmlspecialchars($i['name']) ?></td>
   <td><?= $i['qty'] ?></td>
   <td><?= $i['unit_price'] ?></td>
   <td><?= $i['qty'] * $i['unit_price'] ?></td>
   <td>
    <?php if ($i['booking_required']): ?>
      <?= $i['booking_date'] ?> <?= $i['booking_time'] ?> (<?= $i['duration'] ?> hrs)<br>
      <?= htmlspecialchars($i['notes'] ?? '') ?>
    <?php else: ?>
      –
    <?php endif; ?>
   </td>
</tr>
<?php endforeach; ?>
</table>
<p><strong>Total: €<?= $sale['total'] ?></strong></p>
<a href="reports.php">Back to Reports</a>
</body>
</html>