<?php
$page_title = 'Resources & Halls';
$page_icon = 'building';
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $stmt = $pdo->prepare("INSERT INTO resources (resource_type_id, name, description, capacity, price_per_hour, price_per_day, features) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['resource_type_id'], $_POST['name'], $_POST['description'], $_POST['capacity'], $_POST['price_per_hour'], $_POST['price_per_day'], $_POST['features']]);
        header('Location: resources.php');
        exit;
    } elseif ($_POST['action'] === 'edit') {
        $stmt = $pdo->prepare("UPDATE resources SET resource_type_id=?, name=?, description=?, capacity=?, price_per_hour=?, price_per_day=?, features=? WHERE id=?");
        $stmt->execute([$_POST['resource_type_id'], $_POST['name'], $_POST['description'], $_POST['capacity'], $_POST['price_per_hour'], $_POST['price_per_day'], $_POST['features'], $_POST['id']]);
        header('Location: resources.php');
        exit;
    } elseif ($_POST['action'] === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM resources WHERE id=?");
        $stmt->execute([$_POST['id']]);
        header('Location: resources.php');
        exit;
    }
}

require_once 'header.php';

$resources = $pdo->query("SELECT r.*, rt.name as type_name FROM resources r LEFT JOIN resource_types rt ON r.resource_type_id = rt.id ORDER BY rt.name, r.name")->fetchAll(PDO::FETCH_ASSOC);
$types = $pdo->query("SELECT * FROM resource_types ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-building"></i> Resources & Halls</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Add Resource</button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Name</th>
                        <th>Capacity</th>
                        <th>Price/Hour</th>
                        <th>Price/Day</th>
                        <th>Features</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resources as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['type_name']) ?></td>
                        <td><?= htmlspecialchars($r['name']) ?></td>
                        <td><?= $r['capacity'] ?></td>
                        <td><?= money($r['price_per_hour']) ?></td>
                        <td><?= money($r['price_per_day']) ?></td>
                        <td><?= htmlspecialchars($r['features']) ?></td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick='editResource(<?= json_encode($r) ?>)'><i class="fas fa-edit"></i></button>
                            <form method="post" class="d-inline" onsubmit="return confirm('Delete this resource?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title">Add Resource</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Type</label>
                            <select name="resource_type_id" class="form-select" required>
                                <?php foreach ($types as $t): ?>
                                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Capacity</label>
                            <input type="number" name="capacity" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Price / Hour</label>
                            <input type="number" name="price_per_hour" step="0.01" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Price / Day</label>
                            <input type="number" name="price_per_day" step="0.01" class="form-control">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label>Features</label>
                            <input type="text" name="features" class="form-control" placeholder="e.g. WiFi, Projector">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Resource</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Name</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Type</label>
                            <select name="resource_type_id" id="edit_type" class="form-select" required>
                                <?php foreach ($types as $t): ?>
                                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Capacity</label>
                            <input type="number" name="capacity" id="edit_capacity" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Price / Hour</label>
                            <input type="number" name="price_per_hour" id="edit_price_hour" step="0.01" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Price / Day</label>
                            <input type="number" name="price_per_day" id="edit_price_day" step="0.01" class="form-control">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label>Features</label>
                            <input type="text" name="features" id="edit_features" class="form-control">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label>Description</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editResource(r) {
    document.getElementById('edit_id').value = r.id;
    document.getElementById('edit_name').value = r.name;
    document.getElementById('edit_type').value = r.resource_type_id;
    document.getElementById('edit_capacity').value = r.capacity;
    document.getElementById('edit_price_hour').value = r.price_per_hour;
    document.getElementById('edit_price_day').value = r.price_per_day;
    document.getElementById('edit_features').value = r.features;
    document.getElementById('edit_description').value = r.description;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

<?php require_once 'footer.php'; ?>
