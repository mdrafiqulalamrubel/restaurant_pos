<?php
require_once 'config.php';
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$manufacturers = $pdo->query("SELECT * FROM manufacturers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head><title>Manufacturers</title></head>
<body>
<h1>Food Manufacturers / Suppliers</h1>
<a href="manufacturer_add.php">Add Manufacturer</a>
<table border="1" cellpadding="5">
<tr><th>ID</th><th>Name</th><th>Contact</th><th>Phone</th><th>Actions</th></tr>
<?php foreach ($manufacturers as $m): ?>
<tr>
  <td><?= $m['id'] ?></td>
  <td><?= htmlspecialchars($m['name']) ?></td>
  <td><?= htmlspecialchars($m['contact_person'] ?? '') ?></td>
  <td><?= htmlspecialchars($m['phone'] ?? '') ?></td>
  <td><a href="manufacturer_edit.php?id=<?= $m['id'] ?>">Edit</a> | <a href="manufacturer_delete.php?id=<?= $m['id'] ?>" onclick="return confirm('Delete?')">Delete</a></td>
</tr>
<?php endforeach; ?>
</table>
<a href="pos.php">Back to POS</a>
</body>
</html>