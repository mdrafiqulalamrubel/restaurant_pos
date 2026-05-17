<?php
// tables.php - Process POST BEFORE any output
$page_title = 'Dining Tables';
$page_icon = 'chair';
require_once 'config.php';
// Do NOT include header.php yet - process POST first

// Update table status - MUST be before ANY output including header.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $table_id = $_POST['table_id'];
        $status = $_POST['status'];
        $stmt = $pdo->prepare("UPDATE dining_tables SET status = ? WHERE id = ?");
        $stmt->execute([$status, $table_id]);
        header('Location: tables.php');
        exit;
    }
}

// Now include header AFTER processing POST
require_once 'header.php';

$tables = $pdo->query("SELECT * FROM dining_tables ORDER BY table_number")->fetchAll(PDO::FETCH_ASSOC);
$status_counts = $pdo->query("SELECT status, COUNT(*) as count FROM dining_tables GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .table-card {
        background: white;
        border-radius: 15px;
        padding: 20px;
        text-align: center;
        transition: all 0.3s;
        cursor: pointer;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .table-card:hover {
        transform: translateY(-5px);
    }
    .table-card.available { border-left: 5px solid #28a745; }
    .table-card.occupied { border-left: 5px solid #dc3545; }
    .table-card.reserved { border-left: 5px solid #ffc107; }
    .table-card.maintenance { border-left: 5px solid #6c757d; }
    
    .table-icon {
        font-size: 3rem;
        color: #667eea;
    }
    .table-number {
        font-size: 1.5rem;
        font-weight: bold;
        margin: 10px 0;
    }
    .status-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        display: inline-block;
    }
    .status-available { background: #d4edda; color: #155724; }
    .status-occupied { background: #f8d7da; color: #721c24; }
    .status-reserved { background: #fff3cd; color: #856404; }
    .status-maintenance { background: #e2e3e5; color: #383d41; }
    
    .stats-card {
        background: white;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 15px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .stats-card h3 {
        margin: 0;
        font-size: 1.8rem;
    }
</style>

<div class="row">
    <div class="col-md-3">
        <div class="stats-card">
            <h5>Table Summary</h5>
            <hr>
            <?php 
            $total_available = 0;
            $total_occupied = 0;
            $total_reserved = 0;
            $total_maintenance = 0;
            foreach ($status_counts as $sc) {
                if ($sc['status'] == 'available') $total_available = $sc['count'];
                if ($sc['status'] == 'occupied') $total_occupied = $sc['count'];
                if ($sc['status'] == 'reserved') $total_reserved = $sc['count'];
                if ($sc['status'] == 'maintenance') $total_maintenance = $sc['count'];
            }
            ?>
            <div class="d-flex justify-content-between mb-2">
                <span><i class="fas fa-check-circle text-success"></i> Available:</span>
                <strong><?= $total_available ?></strong>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span><i class="fas fa-chair text-danger"></i> Occupied:</span>
                <strong><?= $total_occupied ?></strong>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span><i class="fas fa-clock text-warning"></i> Reserved:</span>
                <strong><?= $total_reserved ?></strong>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span><i class="fas fa-tools text-secondary"></i> Maintenance:</span>
                <strong><?= $total_maintenance ?></strong>
            </div>
            <hr>
            <div class="d-flex justify-content-between">
                <span><strong>Total Tables:</strong></span>
                <strong><?= count($tables) ?></strong>
            </div>
        </div>
        
        <div class="stats-card">
            <h5>Quick Actions</h5>
            <hr>
            <a href="pos.php" class="btn btn-primary w-100 mb-2">
                <i class="fas fa-cash-register"></i> Go to POS
            </a>
            <button class="btn btn-success w-100" onclick="window.location.reload()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>
    
    <div class="col-md-9">
        <div class="row">
            <?php foreach ($tables as $table): ?>
            <div class="col-md-4 col-lg-3">
                <div class="table-card <?= $table['status'] ?>" data-bs-toggle="modal" data-bs-target="#tableModal" onclick="showTableModal(<?= htmlspecialchars(json_encode($table)) ?>)">
                    <div class="table-icon">
                        <i class="fas fa-chair"></i>
                    </div>
                    <div class="table-number">Table <?= $table['table_number'] ?></div>
                    <div class="mb-2">
                        <i class="fas fa-users"></i> <?= $table['capacity'] ?> seats
                    </div>
                    <div class="status-badge status-<?= $table['status'] ?>">
                        <?php 
                        $status_icons = [
                            'available' => '✅',
                            'occupied' => '🔴',
                            'reserved' => '🟡',
                            'maintenance' => '🔧'
                        ];
                        echo $status_icons[$table['status']] ?? '❓';
                        ?> <?= ucfirst($table['status']) ?>
                    </div>
                    <?php if ($table['location']): ?>
                        <small class="d-block mt-2 text-muted">📍 <?= $table['location'] ?></small>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($tables)): ?>
            <div class="alert alert-warning text-center">
                <i class="fas fa-exclamation-triangle"></i> No tables found. 
                <a href="setup.php">Run setup</a> to create tables.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Table Status Modal -->
<div class="modal fade" id="tableModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="update_status">
                <div class="modal-header">
                    <h5 class="modal-title">Update Table Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="table_id" id="modalTableId">
                    <div class="mb-3">
                        <label>Table Number</label>
                        <input type="text" id="modalTableNumber" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label>Table Status</label>
                        <select name="status" id="modalTableStatus" class="form-control">
                            <option value="available">✅ Available</option>
                            <option value="occupied">🔴 Occupied</option>
                            <option value="reserved">🟡 Reserved</option>
                            <option value="maintenance">🔧 Maintenance</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Capacity</label>
                        <input type="text" id="modalTableCapacity" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label>Location</label>
                        <input type="text" id="modalTableLocation" class="form-control" readonly>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showTableModal(table) {
    document.getElementById('modalTableId').value = table.id;
    document.getElementById('modalTableNumber').value = 'Table ' + table.table_number;
    document.getElementById('modalTableStatus').value = table.status;
    document.getElementById('modalTableCapacity').value = table.capacity + ' seats';
    document.getElementById('modalTableLocation').value = table.location || 'Not specified';
}
</script>

<?php require_once 'footer.php'; ?>