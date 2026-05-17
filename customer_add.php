<?php
$page_title = 'Add/Edit Customer';
$page_icon = 'user-plus';
require_once 'config.php';
// Remove the header include for redirects - we'll handle it differently
// require_once 'header.php'; - DON'T include header before redirect

$id = $_GET['id'] ?? 0;
$is_edit = $id > 0;
$customer = ['name' => '', 'phone' => '', 'email' => '', 'address' => '', 'due_amount' => 0];

if ($is_edit) {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
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

// Only include header after processing POST (no redirect)
require_once 'header.php';
?>

<style>
    .customer-form {
        max-width: 600px;
        margin: 0 auto;
    }
    .form-card {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
</style>

<div class="customer-form">
    <div class="form-card">
        <h4 class="mb-4">
            <i class="fas fa-<?= $is_edit ? 'edit' : 'user-plus' ?>"></i> 
            <?= $is_edit ? 'Edit Customer' : 'Add New Customer' ?>
        </h4>
        
        <form method="post">
            <div class="mb-3">
                <label>Full Name *</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($customer['name']) ?>" required>
            </div>
            
            <div class="mb-3">
                <label>Phone Number</label>
                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($customer['phone'] ?? '') ?>">
            </div>
            
            <div class="mb-3">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($customer['email'] ?? '') ?>">
            </div>
            
            <div class="mb-3">
                <label>Address</label>
                <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($customer['address'] ?? '') ?></textarea>
            </div>
            
            <?php if ($is_edit): ?>
            <div class="mb-3">
                <label>Due Amount (€)</label>
                <input type="number" name="due_amount" class="form-control" step="0.01" value="<?= $customer['due_amount'] ?? 0 ?>">
                <small class="text-muted">Current outstanding balance</small>
            </div>
            <?php endif; ?>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?= $is_edit ? 'Update' : 'Save' ?> Customer
                </button>
                <a href="customers.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'footer.php'; ?>