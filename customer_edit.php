<?php
$page_title = 'Edit Customer';
$page_icon = 'user-edit';
require_once 'config.php';
require_once 'header.php';

$id = $_GET['id'] ?? 0;
$is_edit = $id > 0;
$customer = ['name' => '', 'phone' => '', 'email' => '', 'address' => '', 'due_amount' => 0];

if ($is_edit) {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$customer) die('Customer not found');
}

// Get customer purchase history
$purchase_history = [];
if ($is_edit) {
    $stmt = $pdo->prepare("
        SELECT s.*, COUNT(si.id) as item_count 
        FROM sales s 
        LEFT JOIN sale_items si ON s.id = si.sale_id 
        WHERE s.customer_id = ? 
        GROUP BY s.id 
        ORDER BY s.id DESC 
        LIMIT 10
    ");
    $stmt->execute([$id]);
    $purchase_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total spent
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total), 0) as total_spent FROM sales WHERE customer_id = ?");
    $stmt->execute([$id]);
    $total_spent = $stmt->fetchColumn();
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
        $success = "Customer updated successfully!";
        // Refresh customer data
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$id]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->prepare("INSERT INTO customers (name, phone, email, address, due_amount) VALUES (?,?,?,?,?)");
        $stmt->execute([$name, $phone, $email, $address, $due_amount]);
        header('Location: customers.php');
        exit;
    }
}

// Get company currency
$company = $pdo->query("SELECT * FROM company_settings WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
$currency = $company['currency'] ?? '€';
?>

<style>
    .profile-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 15px;
        padding: 30px;
        color: white;
        margin-bottom: 30px;
    }
    .profile-avatar {
        width: 80px;
        height: 80px;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        margin-bottom: 15px;
    }
    .info-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .info-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        color: #999;
        letter-spacing: 1px;
    }
    .info-value {
        font-size: 1.1rem;
        font-weight: 600;
        color: #333;
        margin-top: 5px;
    }
    .stat-box {
        text-align: center;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 10px;
        margin-bottom: 15px;
    }
    .stat-number {
        font-size: 1.8rem;
        font-weight: bold;
        color: #667eea;
    }
    .form-control, .form-select {
        border-radius: 8px;
        border: 1px solid #e0e0e0;
        padding: 10px 15px;
    }
    .form-control:focus, .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102,126,234,0.25);
    }
    .btn-save {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        padding: 12px;
        font-weight: 600;
    }
    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102,126,234,0.4);
    }
    .due-badge {
        background: #dc3545;
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
    }
    .paid-badge {
        background: #28a745;
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
    }
</style>

<?php if (isset($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> <?= $success ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Left Column - Customer Profile -->
    <div class="col-md-4">
        <div class="profile-header text-center">
            <div class="profile-avatar mx-auto">
                <i class="fas fa-user-circle"></i>
            </div>
            <h4><?= htmlspecialchars($customer['name']) ?></h4>
            <p class="mb-0">Customer ID: #<?= str_pad($id, 6, '0', STR_PAD_LEFT) ?></p>
            <p class="mb-0">Since: <?= date('M d, Y', strtotime($customer['created_at'] ?? 'now')) ?></p>
        </div>
        
        <div class="info-card">
            <h6 class="mb-3"><i class="fas fa-chart-line"></i> Customer Statistics</h6>
            <div class="stat-box">
                <div class="stat-number"><?= money($total_spent ?? 0) ?></div>
                <div class="text-muted">Total Spent</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?= count($purchase_history) ?></div>
                <div class="text-muted">Total Orders</div>
            </div>
            <div class="stat-box">
                <div class="stat-number">
                    <?php if (($customer['due_amount'] ?? 0) > 0): ?>
                        <span class="due-badge"><?= money($customer['due_amount']) ?> Due</span>
                    <?php else: ?>
                        <span class="paid-badge">Fully Paid</span>
                    <?php endif; ?>
                </div>
                <div class="text-muted">Current Balance</div>
            </div>
        </div>
    </div>
    
    <!-- Right Column - Edit Form -->
    <div class="col-md-8">
        <div class="info-card">
            <h5 class="mb-4"><i class="fas fa-edit"></i> Edit Customer Information</h5>
            
            <form method="post">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($customer['name']) ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($customer['phone'] ?? '') ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($customer['email'] ?? '') ?>">
                    </div>
                    
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($customer['address'] ?? '') ?></textarea>
                    </div>
                    
                    <?php if ($is_edit): ?>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Due Amount (<?= $currency ?>)</label>
                        <input type="number" name="due_amount" class="form-control" step="0.01" value="<?= $customer['due_amount'] ?? 0 ?>">
                        <small class="text-muted">Outstanding balance for this customer</small>
                    </div>
                    <?php endif; ?>
                    
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-save w-100">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a href="customers.php" class="btn btn-secondary w-100 mt-2">
                            <i class="fas fa-arrow-left"></i> Back to Customers
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Recent Purchase History -->
        <?php if ($is_edit && !empty($purchase_history)): ?>
        <div class="info-card">
            <h5 class="mb-3"><i class="fas fa-history"></i> Recent Purchase History</h5>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($purchase_history as $sale): ?>
                        <tr>
                            <td><a href="invoice.php?id=<?= $sale['id'] ?>">#<?= $sale['id'] ?></a></td>
                            <td><?= date('d M Y', strtotime($sale['created_at'])) ?></td>
                            <td><?= $sale['item_count'] ?> items</td>
                            <td><?= money($sale['total']) ?></td>
                            <td><span class="badge bg-success">Completed</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <a href="transactions.php?customer_id=<?= $id ?>" class="btn btn-sm btn-outline-primary">View All Orders →</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>