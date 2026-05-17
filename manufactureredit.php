<?php
require_once 'config.php';
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$id = $_GET['id'] ?? 0;
$is_edit = $id > 0;
$m = ['name'=>'','contact_person'=>'','phone'=>'','email'=>'','address'=>''];
if ($is_edit) {
    $stmt = $pdo->prepare("SELECT * FROM manufacturers WHERE id=?");
    $stmt->execute([$id]);
    $m = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$m) die('Not found');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $contact = $_POST['contact_person'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    if ($is_edit) {
        $stmt = $pdo->prepare("UPDATE manufacturers SET name=?, contact_person=?, phone=?, email=?, address=? WHERE id=?");
        $stmt->execute([$name, $contact, $phone, $email, $address, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO manufacturers (name, contact_person, phone, email, address) VALUES (?,?,?,?,?)");
        $stmt->execute([$name, $contact, $phone, $email, $address]);
    }
    header('Location: manufacturers.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head><title><?= $is_edit ? 'Edit' : 'Add' ?> Manufacturer</title></head>
<body>
<h1><?= $is_edit ? 'Edit' : 'Add' ?> Manufacturer</h1>
<form method="post">
  <input name="name" placeholder="Company Name" value="<?= htmlspecialchars($m['name']) ?>" required><br>
  <input name="contact_person" placeholder="Contact Person" value="<?= htmlspecialchars($m['contact_person'] ?? '') ?>"><br>
  <input name="phone" placeholder="Phone" value="<?= htmlspecialchars($m['phone'] ?? '') ?>"><br>
  <input name="email" placeholder="Email" value="<?= htmlspecialchars($m['email'] ?? '') ?>"><br>
  <textarea name="address" placeholder="Address"><?= htmlspecialchars($m['address'] ?? '') ?></textarea><br>
  <button>Save</button>
  <a href="manufacturers.php">Cancel</a>
</form>
</body>
</html>