<?php
require_once 'config.php';
// No session_start() needed - config already handles it

$id = $_GET['id'] ?? 0;
$is_edit = $id > 0;
$customer = ['name'=>'','phone'=>'','email'=>'','address'=>'','due_amount'=>0];

if ($is_edit) {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id=?");
    $stmt->execute([$id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$customer) die('Customer not found');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $due_amount = $_POST['due_amount'] ?? 0;
    
    if ($is_edit) {
        $stmt = $pdo->prepare("UPDATE customers SET name=?, phone=?, email=?, address=?, due_amount=? WHERE id=?");
        $stmt->execute([$name, $phone, $email, $address, $due_amount, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO customers (name, phone, email, address, due_amount) VALUES (?,?,?,?,?)");
        $stmt->execute([$name, $phone, $email, $address, $due_amount]);
    }
    header('Location: customers.php');
    exit;
}

// Only include header after processing (no redirect)
require_once 'header.php';
?>
<!DOCTYPE html>
<html>
<head><title><?= $is_edit ? 'Edit' : 'Add' ?> Customer</title></head>
<body>
<h1><?= $is_edit ? 'Edit' : 'Add' ?> Customer</h1>
<form method="post">
  <input name="name" placeholder="Name" value="<?= htmlspecialchars($customer['name']) ?>" required><br>
  <input name="phone" placeholder="Phone" value="<?= htmlspecialchars($customer['phone'] ?? '') ?>"><br>
  <input name="email" placeholder="Email" value="<?= htmlspecialchars($customer['email'] ?? '') ?>"><br>
  <textarea name="address" placeholder="Address"><?= htmlspecialchars($customer['address'] ?? '') ?></textarea><br>
  <?php if ($is_edit): ?>
  <input name="due_amount" placeholder="Due Amount" value="<?= $customer['due_amount'] ?? 0 ?>"><br>
  <?php endif; ?>
  <button>Save</button>
  <a href="customers.php">Cancel</a>
</form>
</body>
</html>