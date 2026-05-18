<?php
$page_title = 'Raw Materials Management';
$page_icon = 'boxes';
require_once 'config.php';
require_once 'header.php';

// Check admin access
if ($_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Handle add/edit/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO raw_materials (name, unit, unit_price, current_stock, min_stock_level, category, supplier_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['name'], $_POST['unit'], $_POST['unit_price'], $_POST['current_stock'] ?? 0, $_POST['min_stock_level'] ?? 0, $_POST['category'], $_POST['supplier_id'] ?: null]);
        $success = "Raw material added successfully!";
    } elseif ($action === 'edit') {
        $stmt = $pdo->prepare("UPDATE raw_materials SET name=?, unit=?, unit_price=?, current_stock=?, min_stock_level=?, category=?, supplier_id=? WHERE id=?");
        $stmt->execute([$_POST['name'], $_POST['unit'], $_POST['unit_price'], $_POST['current_stock'], $_POST['min_stock_level'], $_POST['category'], $_POST['supplier_id'] ?: null, $_POST['id']]);
        $success = "Raw material updated successfully!";
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM raw_materials WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        $success = "Raw material deleted successfully!";
    }
    header('Location: raw_materials.php');
    exit;
}

$raw_materials = $pdo->query("SELECT r.*, m.name as supplier_name FROM raw_materials r LEFT JOIN manufacturers m ON r.supplier_id = m.id ORDER BY r.name")->fetchAll();
$suppliers = $pdo->query("SELECT id, name FROM manufacturers ORDER BY name")->fetchAll();

$low_stock = array_filter($raw_materials, function($item) {
    return $item['current_stock'] <= $item['min_stock_level'];
});
?>

<style>
    .stock-badge { padding: 3px 8px; border-radius: 20px; font-size: 0.7rem; }
    .stock-low { background: #dc3545; color: white; }
    .stock-ok { background: #28a745; color: white; }
    .stats-card { background: white; border-radius: 12px; padding: 20px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
</style>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card">
            <i class="fas fa-boxes fa-2x text-primary"></i>
            <h3><?= count($raw_materials) ?></h3>
            <div>Total Materials</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
            <h3><?= count($low_stock) ?></h3>
            <div>Low Stock Items</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <i class="fas fa-truck fa-2x text-info"></i>
            <h3><?= count($suppliers) ?></h3>
            <div>Suppliers</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <i class="fas fa-chart-line fa-2x text-success"></i>
            <h3><?= number_format(array_sum(array_column($raw_materials, 'unit_price')), 2) ?></h3>
            <div>Inventory Value</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5><i class="fas fa-list"></i> Raw Materials Inventory</h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#materialModal" onclick="resetForm()">
            <i class="fas fa-plus"></i> Add Material
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th><th>Name</th><th>Category</th><th>Unit</th><th>Unit Price</th><th>Stock</th><th>Min Stock</th><th>Supplier</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($raw_materials as $m): ?>
                    <tr>
                        <td><?= $m['id'] ?></td>
                        <td><strong><?= htmlspecialchars($m['name']) ?></strong></td>
                        <td><?= $m['category'] ?? '-' ?></td>
                        <td><?= $m['unit'] ?></td>
                        <td><?= number_format($m['unit_price'], 2) ?></td>
                        <td><?= number_format($m['current_stock'], 2) ?> <?= $m['unit'] ?></td>
                        <td><?= number_format($m['min_stock_level'], 2) ?> <?= $m['unit'] ?></td>
                        <td><?= htmlspecialchars($m['supplier_name'] ?? '-') ?></td>
                        <td>
                            <?php if ($m['current_stock'] <= $m['min_stock_level']): ?>
                                <span class="stock-badge stock-low">Low Stock</span>
                            <?php else: ?>
                                <span class="stock-badge stock-ok">In Stock</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-warning" onclick="editMaterial(<?= htmlspecialchars(json_encode($m)) ?>)"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-danger" onclick="deleteMaterial(<?= $m['id'] ?>)"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Material Modal -->
<div class="modal fade" id="materialModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Raw Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="materialId">
                    <div class="mb-2">
                        <label>Material Name</label>
                        <input type="text" name="name" id="materialName" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label>Category</label>
                            <input type="text" name="category" id="materialCategory" class="form-control" placeholder="e.g., Dry Goods">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Unit</label>
                            <select name="unit" id="materialUnit" class="form-control">
                                <option value="kg">kg (Kilogram)</option>
                                <option value="g">g (Gram)</option>
                                <option value="liter">liter</option>
                                <option value="ml">ml (Milliliter)</option>
                                <option value="piece">piece</option>
                                <option value="box">box</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label>Unit Price</label>
                            <input type="number" name="unit_price" id="materialPrice" class="form-control" step="0.01" required>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Current Stock</label>
                            <input type="number" name="current_stock" id="materialStock" class="form-control" step="0.01">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label>Min Stock Level</label>
                            <input type="number" name="min_stock_level" id="materialMinStock" class="form-control" step="0.01">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Supplier</label>
                            <select name="supplier_id" id="materialSupplier" class="form-control">
                                <option value="">Select Supplier</option>
                                <?php foreach ($suppliers as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Material</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetForm() {
    document.getElementById('formAction').value = 'add';
    document.getElementById('modalTitle').innerHTML = 'Add Raw Material';
    document.getElementById('materialId').value = '';
    document.getElementById('materialName').value = '';
    document.getElementById('materialCategory').value = '';
    document.getElementById('materialUnit').value = 'kg';
    document.getElementById('materialPrice').value = '';
    document.getElementById('materialStock').value = '';
    document.getElementById('materialMinStock').value = '';
    document.getElementById('materialSupplier').value = '';
}

function editMaterial(material) {
    document.getElementById('formAction').value = 'edit';
    document.getElementById('modalTitle').innerHTML = 'Edit Raw Material';
    document.getElementById('materialId').value = material.id;
    document.getElementById('materialName').value = material.name;
    document.getElementById('materialCategory').value = material.category || '';
    document.getElementById('materialUnit').value = material.unit;
    document.getElementById('materialPrice').value = material.unit_price;
    document.getElementById('materialStock').value = material.current_stock;
    document.getElementById('materialMinStock').value = material.min_stock_level;
    document.getElementById('materialSupplier').value = material.supplier_id || '';
    
    new bootstrap.Modal(document.getElementById('materialModal')).show();
}

function deleteMaterial(id) {
    if (confirm('Are you sure you want to delete this raw material?')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' + id + '">';
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once 'footer.php'; ?>