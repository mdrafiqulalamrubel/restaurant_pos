<?php
$page_title = 'Supplier Management';
$page_icon = 'building';
require_once 'config.php';
require_once 'header.php';

// Handle supplier addition/editing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM manufacturers WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        header('Location: suppliers.php');
        exit;
    } else {
        $name = $_POST['name'];
        $contact_person = $_POST['contact_person'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $address = $_POST['address'];
        $opening_balance = $_POST['opening_balance'] ?? 0;
        
        if (isset($_POST['supplier_id']) && $_POST['supplier_id'] > 0) {
            $stmt = $pdo->prepare("UPDATE manufacturers SET name=?, contact_person=?, phone=?, email=?, address=?, opening_balance=? WHERE id=?");
            $stmt->execute([$name, $contact_person, $phone, $email, $address, $opening_balance, $_POST['supplier_id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO manufacturers (name, contact_person, phone, email, address, opening_balance) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$name, $contact_person, $phone, $email, $address, $opening_balance]);
        }
        header('Location: suppliers.php');
        exit;
    }
}

$suppliers = $pdo->query("SELECT m.*, COUNT(i.id) as item_count FROM manufacturers m LEFT JOIN items i ON m.id = i.manufacturer_id GROUP BY m.id ORDER BY m.name")->fetchAll(PDO::FETCH_ASSOC);
$edit_supplier = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM manufacturers WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_supplier = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<style>
    .supplier-card {
        transition: transform 0.3s;
        margin-bottom: 20px;
    }
    .supplier-card:hover {
        transform: translateY(-3px);
    }
    .balance-positive { color: #dc3545; }
    .balance-negative { color: #28a745; }
</style>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Add/Edit Supplier</div>
            <div class="card-body">
                <form method="post">
                    <?php if ($edit_supplier): ?>
                        <input type="hidden" name="supplier_id" value="<?= $edit_supplier['id'] ?>">
                    <?php endif; ?>
                    <div class="mb-2">
                        <label>Company Name *</label>
                        <input type="text" name="name" class="form-control" value="<?= $edit_supplier['name'] ?? '' ?>" required>
                    </div>
                    <div class="mb-2">
                        <label>Contact Person</label>
                        <input type="text" name="contact_person" class="form-control" value="<?= $edit_supplier['contact_person'] ?? '' ?>">
                    </div>
                    <div class="mb-2">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?= $edit_supplier['phone'] ?? '' ?>">
                    </div>
                    <div class="mb-2">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?= $edit_supplier['email'] ?? '' ?>">
                    </div>
                    <div class="mb-2">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="2"><?= $edit_supplier['address'] ?? '' ?></textarea>
                    </div>
                    <div class="mb-2">
                        <label>Opening Balance</label>
                        <input type="number" name="opening_balance" class="form-control" step="0.01" value="<?= $edit_supplier['opening_balance'] ?? 0 ?>">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save"></i> <?= $edit_supplier ? 'Update' : 'Save' ?> Supplier
                    </button>
                    <?php if ($edit_supplier): ?>
                        <a href="suppliers.php" class="btn btn-secondary w-100 mt-2">Cancel Edit</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-truck"></i> Supplier List</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Company</th>
                                <th>Contact Person</th>
                                <th>Phone</th>
                                <th>Products</th>
                                <th>Balance</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($suppliers as $s): ?>
                            <tr>
                                <td><?= $s['id'] ?></td>
                                <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                                <td><?= htmlspecialchars($s['contact_person'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($s['phone'] ?? '-') ?></td>
                                <td><?= $s['item_count'] ?></td>
                                <td class="<?= ($s['opening_balance'] ?? 0) > 0 ? 'balance-positive' : 'balance-negative' ?>">
                                    €<?= number_format($s['opening_balance'] ?? 0, 2) ?>
                                </td>
                                <td>
                                    <a href="?edit=<?= $s['id'] ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn btn-sm btn-danger" onclick="deleteSupplier(<?= $s['id'] ?>, '<?= htmlspecialchars($s['name']) ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <a href="supplier_products.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-box"></i>
                                    </a>
                                 </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<form id="deleteForm" method="post" style="display:none">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="delete_id">
</form>

<script>
function deleteSupplier(id, name) {
    if (confirm(`Delete supplier "${name}"? This will remove supplier from all products.`)) {
        document.getElementById('delete_id').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php require_once 'footer.php'; ?>