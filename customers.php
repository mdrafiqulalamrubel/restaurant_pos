<?php
$page_title = 'Customer Management';
$page_icon = 'users';
require_once 'config.php';
require_once 'header.php';

// Handle due payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pay_due') {
    $customer_id = $_POST['customer_id'];
    $amount = $_POST['amount'];
    $stmt = $pdo->prepare("UPDATE customers SET due_amount = due_amount - ? WHERE id = ?");
    $stmt->execute([$amount, $customer_id]);
    header('Location: customers.php');
    exit;
}

$search = $_GET['search'] ?? '';
if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE name LIKE ? OR phone LIKE ? OR email LIKE ? ORDER BY name");
    $stmt->execute(["%$search%", "%$search%", "%$search%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM customers ORDER BY name");
}
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_due = array_sum(array_column($customers, 'due_amount'));
$total_customers = count($customers);
?>

<style>
    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .due-badge {
        background: #dc3545;
        color: white;
        padding: 2px 8px;
        border-radius: 20px;
        font-size: 0.8rem;
    }
    .customer-card {
        transition: transform 0.3s;
        margin-bottom: 20px;
    }
    .customer-card:hover {
        transform: translateY(-3px);
    }
</style>

<div class="row">
    <div class="col-md-4">
        <div class="stats-card">
            <h6>Total Customers</h6>
            <h2><?= $total_customers ?></h2>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stats-card">
            <h6>Total Due Amount</h6>
            <h2>€<?= number_format($total_due, 2) ?></h2>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stats-card">
            <h6>Average Due</h6>
            <h2>€<?= number_format($total_customers > 0 ? $total_due / $total_customers : 0, 2) ?></h2>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5><i class="fas fa-list"></i> Customer List</h5>
        <a href="customer_add.php" class="btn btn-primary btn-sm">
            <i class="fas fa-user-plus"></i> Add Customer
        </a>
    </div>
    <div class="card-body">
        <form method="get" class="mb-3">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Search by name, phone or email..." value="<?= htmlspecialchars($search) ?>">
                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Search</button>
                <?php if ($search): ?>
                    <a href="customers.php" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </div>
        </form>
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Total Purchases</th>
                        <th>Due Amount</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $c): 
                        // Get total purchases
                        $stmt = $pdo->prepare("SELECT COALESCE(SUM(total),0) as total_spent FROM sales WHERE customer_id = ?");
                        $stmt->execute([$c['id']]);
                        $total_spent = $stmt->fetchColumn();
                    ?>
                    <tr>
                        <td><?= $c['id'] ?></td>
                        <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
                        <td><?= htmlspecialchars($c['phone'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($c['email'] ?? '-') ?></td>
                        <td>€<?= number_format($total_spent, 2) ?></td>
                        <td>
                            <?php if (($c['due_amount'] ?? 0) > 0): ?>
                                <span class="due-badge">Due: €<?= number_format($c['due_amount'], 2) ?></span>
                            <?php else: ?>
                                <span class="text-success">€0.00</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="customer_edit.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#dueModal" onclick="setCustomer(<?= $c['id'] ?>, '<?= htmlspecialchars($c['name']) ?>', <?= $c['due_amount'] ?? 0 ?>)">
                                <i class="fas fa-money-bill"></i> Pay Due
                            </button>
                            <a href="customer_transactions.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-secondary">
                                <i class="fas fa-history"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Due Payment Modal -->
<div class="modal fade" id="dueModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Pay Due Amount</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="pay_due">
                    <input type="hidden" name="customer_id" id="due_customer_id">
                    <div class="mb-3">
                        <label>Customer</label>
                        <input type="text" id="due_customer_name" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label>Current Due</label>
                        <input type="text" id="due_current_amount" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label>Payment Amount</label>
                        <input type="number" name="amount" id="due_amount" class="form-control" step="0.01" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Process Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function setCustomer(id, name, due) {
    document.getElementById('due_customer_id').value = id;
    document.getElementById('due_customer_name').value = name;
    document.getElementById('due_current_amount').value = '€' + due.toFixed(2);
    document.getElementById('due_amount').max = due;
    document.getElementById('due_amount').value = due;
}
</script>

<?php require_once 'footer.php'; ?>