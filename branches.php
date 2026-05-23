<?php
$page_title = 'Branch Management';
$page_icon = 'store';
require_once 'config.php';
require_once 'header.php';

// Check admin access
if ($_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Handle branch operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO branches (name, code, address, phone, email, manager_name, opening_time, closing_time, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['name'], $_POST['code'], $_POST['address'], $_POST['phone'], 
            $_POST['email'], $_POST['manager_name'], $_POST['opening_time'], 
            $_POST['closing_time'], $_POST['status']
        ]);
        $success = "Branch added successfully!";
        header('Location: branches.php');
        exit;
        
    } elseif ($action === 'edit') {
        $stmt = $pdo->prepare("UPDATE branches SET name=?, code=?, address=?, phone=?, email=?, manager_name=?, opening_time=?, closing_time=?, status=? WHERE id=?");
        $stmt->execute([
            $_POST['name'], $_POST['code'], $_POST['address'], $_POST['phone'], 
            $_POST['email'], $_POST['manager_name'], $_POST['opening_time'], 
            $_POST['closing_time'], $_POST['status'], $_POST['branch_id']
        ]);
        $success = "Branch updated successfully!";
        header('Location: branches.php');
        exit;
        
    } elseif ($action === 'delete') {
        // Check if branch has sales
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE branch_id = ?");
        $stmt->execute([$_POST['branch_id']]);
        $sales_count = $stmt->fetchColumn();
        
        if ($sales_count > 0) {
            $error = "Cannot delete branch with existing sales records!";
        } else {
            $stmt = $pdo->prepare("DELETE FROM branches WHERE id = ?");
            $stmt->execute([$_POST['branch_id']]);
            $success = "Branch deleted successfully!";
        }
        header('Location: branches.php');
        exit;
    }
}

$branches = $pdo->query("SELECT b.*, 
    (SELECT COUNT(*) FROM users WHERE branch_id = b.id) as staff_count,
    (SELECT COUNT(*) FROM sales WHERE branch_id = b.id) as sales_count
    FROM branches b ORDER BY b.name")->fetchAll();
?>

<style>
    .branch-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: transform 0.3s;
    }
    .branch-card:hover { transform: translateY(-3px); }
    .branch-status { padding: 3px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: bold; }
    .status-active { background: #28a745; color: white; }
    .status-inactive { background: #dc3545; color: white; }
    .stats-number { font-size: 1.5rem; font-weight: bold; color: #667eea; }
</style>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-plus"></i> Add New Branch</h5>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                <form method="post">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-2">
                        <label>Branch Name *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Branch Code *</label>
                        <input type="text" name="code" class="form-control" placeholder="e.g., MB001" required>
                    </div>
                    <div class="mb-2">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label>Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label>Manager Name</label>
                        <input type="text" name="manager_name" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label>Opening Time</label>
                            <input type="time" name="opening_time" class="form-control" value="09:00">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Closing Time</label>
                            <input type="time" name="closing_time" class="form-control" value="22:00">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Create Branch</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-store"></i> All Branches</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($branches as $branch): ?>
                    <div class="col-md-6">
                        <div class="branch-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6><?= htmlspecialchars($branch['name']) ?></h6>
                                    <small class="text-muted">Code: <?= $branch['code'] ?></small>
                                </div>
                                <div>
                                    <span class="branch-status status-<?= $branch['status'] ?>">
                                        <?= ucfirst($branch['status']) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-6">
                                    <div class="stats-number"><?= $branch['staff_count'] ?></div>
                                    <small>Staff Members</small>
                                </div>
                                <div class="col-6">
                                    <div class="stats-number"><?= $branch['sales_count'] ?></div>
                                    <small>Total Sales</small>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small><i class="fas fa-phone"></i> <?= $branch['phone'] ?? 'N/A' ?></small><br>
                                <small><i class="fas fa-user"></i> Manager: <?= $branch['manager_name'] ?? 'Not assigned' ?></small>
                            </div>
                            <div class="mt-3">
                                <button class="btn btn-sm btn-warning" onclick="editBranch(<?= htmlspecialchars(json_encode($branch)) ?>)">Edit</button>
                                <button class="btn btn-sm btn-danger" onclick="deleteBranch(<?= $branch['id'] ?>)">Delete</button>
                                <a href="branch_reports.php?id=<?= $branch['id'] ?>" class="btn btn-sm btn-info">Reports</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="branch_id" id="edit_branch_id">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Branch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label>Branch Name</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Branch Code</label>
                        <input type="text" name="code" id="edit_code" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Address</label>
                        <textarea name="address" id="edit_address" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label>Phone</label>
                            <input type="text" name="phone" id="edit_phone" class="form-control">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Email</label>
                            <input type="email" name="email" id="edit_email" class="form-control">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label>Manager Name</label>
                        <input type="text" name="manager_name" id="edit_manager" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label>Opening Time</label>
                            <input type="time" name="opening_time" id="edit_opening" class="form-control">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Closing Time</label>
                            <input type="time" name="closing_time" id="edit_closing" class="form-control">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label>Status</label>
                        <select name="status" id="edit_status" class="form-control">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editBranch(branch) {
    document.getElementById('edit_branch_id').value = branch.id;
    document.getElementById('edit_name').value = branch.name;
    document.getElementById('edit_code').value = branch.code;
    document.getElementById('edit_address').value = branch.address || '';
    document.getElementById('edit_phone').value = branch.phone || '';
    document.getElementById('edit_email').value = branch.email || '';
    document.getElementById('edit_manager').value = branch.manager_name || '';
    document.getElementById('edit_opening').value = branch.opening_time || '09:00';
    document.getElementById('edit_closing').value = branch.closing_time || '22:00';
    document.getElementById('edit_status').value = branch.status;
    
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function deleteBranch(id) {
    if (confirm('Are you sure you want to delete this branch? This will remove branch association from all records.')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="branch_id" value="' + id + '">';
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once 'footer.php'; ?>