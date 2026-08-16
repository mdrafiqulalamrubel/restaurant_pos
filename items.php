<?php
$page_title = 'Item Management';
$page_icon = 'utensils';
require_once 'config.php';
require_once 'header.php';

// Check admin access for edit/add
$is_admin = ($_SESSION['role'] === 'admin');

// Handle item addition/editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $name = $_POST['name'];
    $unit_price = $_POST['unit_price'];
    $cost_price = $_POST['cost_price'] ?? 0;
    $category = $_POST['category'];
    $description = $_POST['description'] ?? '';
    $booking_required = isset($_POST['booking_required']) ? 1 : 0;
    $booking_type = $_POST['booking_type'] ?? null;
    $manufacturer_id = $_POST['manufacturer_id'] ?: null;
    $current_stock = $_POST['current_stock'] ?? 0;
    $min_stock_level = $_POST['min_stock_level'] ?? 0;
    $is_producible = isset($_POST['is_producible']) ? 1 : 0;
    
    // Handle image upload
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/items/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = time() . '_' . uniqid() . '.' . $ext;
        $destination = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
            $image_path = $destination;
        }
    }
    
    if ($_POST['action'] === 'add') {
        $stmt = $pdo->prepare("INSERT INTO items (name, unit_price, cost_price, category, description, image, booking_required, booking_type, manufacturer_id, current_stock, min_stock_level, is_producible) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$name, $unit_price, $cost_price, $category, $description, $image_path, $booking_required, $booking_type, $manufacturer_id, $current_stock, $min_stock_level, $is_producible]);
        $success = "Item added successfully!";
    } elseif ($_POST['action'] === 'edit') {
        $id = $_POST['item_id'];
        if ($image_path) {
            $stmt = $pdo->prepare("UPDATE items SET name=?, unit_price=?, cost_price=?, category=?, description=?, image=?, booking_required=?, booking_type=?, manufacturer_id=?, current_stock=?, min_stock_level=?, is_producible=? WHERE id=?");
            $stmt->execute([$name, $unit_price, $cost_price, $category, $description, $image_path, $booking_required, $booking_type, $manufacturer_id, $current_stock, $min_stock_level, $is_producible, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE items SET name=?, unit_price=?, cost_price=?, category=?, description=?, booking_required=?, booking_type=?, manufacturer_id=?, current_stock=?, min_stock_level=?, is_producible=? WHERE id=?");
            $stmt->execute([$name, $unit_price, $cost_price, $category, $description, $booking_required, $booking_type, $manufacturer_id, $current_stock, $min_stock_level, $is_producible, $id]);
        }
        $success = "Item updated successfully!";
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $pdo->prepare("UPDATE items SET active=0 WHERE id=?")->execute([$id]);
    header('Location: items.php');
    exit;
}

// Get all items
$items = $pdo->query("SELECT * FROM items WHERE active=1 ORDER BY category, name")->fetchAll(PDO::FETCH_ASSOC);
$categories = $pdo->query("SELECT DISTINCT category FROM items WHERE category IS NOT NULL AND category != ''")->fetchAll(PDO::FETCH_ASSOC);
$manufacturers = $pdo->query("SELECT * FROM manufacturers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Get item for editing
$editItem = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM items WHERE id=?");
    $stmt->execute([$_GET['edit']]);
    $editItem = $stmt->fetch(PDO::FETCH_ASSOC);
}

$low_stock_items = array_filter($items, function($item) {
    return $item['current_stock'] <= $item['min_stock_level'] && $item['min_stock_level'] > 0;
});
?>

<style>
    .item-card {
        transition: transform 0.3s;
        margin-bottom: 20px;
    }
    .item-card:hover { transform: translateY(-5px); }
    .item-image { height: 150px; object-fit: cover; }
    .form-card { background: white; border-radius: 15px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    .success-msg { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
    .stock-low { background: #dc3545; color: white; padding: 2px 8px; border-radius: 20px; font-size: 0.7rem; }
    .stock-ok { background: #28a745; color: white; padding: 2px 8px; border-radius: 20px; font-size: 0.7rem; }
</style>

<?php if (isset($success)): ?>
    <div class="success-msg">✅ <?= $success ?></div>
<?php endif; ?>

<div class="row">
    <!-- Form Section -->
    <div class="col-md-4">
        <div class="form-card">
            <h5><i class="fas fa-<?= $editItem ? 'edit' : 'plus' ?>"></i> <?= $editItem ? 'Edit Item' : 'Add New Item' ?></h5>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?= $editItem ? 'edit' : 'add' ?>">
                <?php if ($editItem): ?>
                    <input type="hidden" name="item_id" value="<?= $editItem['id'] ?>">
                <?php endif; ?>
                
                <div class="mb-2">
                    <label>Item Name *</label>
                    <input type="text" name="name" class="form-control" value="<?= $editItem['name'] ?? '' ?>" required>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label>Selling Price *</label>
                        <input type="number" step="0.01" name="unit_price" class="form-control" value="<?= $editItem['unit_price'] ?? '' ?>" required>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label>Cost Price</label>
                        <input type="number" step="0.01" name="cost_price" class="form-control" value="<?= $editItem['cost_price'] ?? 0 ?>">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label>Current Stock</label>
                        <input type="number" step="0.01" name="current_stock" class="form-control" value="<?= $editItem['current_stock'] ?? 0 ?>">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label>Min Stock Level</label>
                        <input type="number" step="0.01" name="min_stock_level" class="form-control" value="<?= $editItem['min_stock_level'] ?? 0 ?>">
                    </div>
                </div>
                
                <div class="mb-2">
                    <label>Category</label>
                    <select name="category" class="form-control">
                        <option value="">Select Category</option>
                        <option value="Food" <?= ($editItem['category'] ?? '') == 'Food' ? 'selected' : '' ?>>🍔 Food</option>
                        <option value="Beverages" <?= ($editItem['category'] ?? '') == 'Beverages' ? 'selected' : '' ?>>🥤 Beverages</option>
                        <option value="Desserts" <?= ($editItem['category'] ?? '') == 'Desserts' ? 'selected' : '' ?>>🍰 Desserts</option>
                        <option value="Appetizers" <?= ($editItem['category'] ?? '') == 'Appetizers' ? 'selected' : '' ?>>🍤 Appetizers</option>
                        <option value="Main Course" <?= ($editItem['category'] ?? '') == 'Main Course' ? 'selected' : '' ?>>🍛 Main Course</option>
                        <option value="Booking" <?= ($editItem['category'] ?? '') == 'Booking' ? 'selected' : '' ?>>📅 Booking</option>
                    </select>
                </div>
                
                <div class="mb-2">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="2"><?= $editItem['description'] ?? '' ?></textarea>
                </div>
                
                <div class="mb-2">
                    <label>Item Image</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                    <?php if ($editItem && $editItem['image'] && file_exists($editItem['image'])): ?>
                        <small class="text-muted">Current: <a href="<?= $editItem['image'] ?>" target="_blank">View Image</a></small>
                    <?php endif; ?>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label>Supplier</label>
                        <select name="manufacturer_id" class="form-control">
                            <option value="">None</option>
                            <?php foreach ($manufacturers as $man): ?>
                                <option value="<?= $man['id'] ?>" <?= ($editItem['manufacturer_id'] ?? '') == $man['id'] ? 'selected' : '' ?>><?= $man['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-2">
                        <div class="form-check mt-4">
                            <input type="checkbox" name="is_producible" class="form-check-input" value="1" <?= ($editItem['is_producible'] ?? 1) ? 'checked' : '' ?>>
                            <label class="form-check-label">Can be Produced</label>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <div class="form-check">
                            <input type="checkbox" name="booking_required" class="form-check-input" value="1" <?= ($editItem['booking_required'] ?? 0) ? 'checked' : '' ?>>
                            <label class="form-check-label">Booking Required</label>
                        </div>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label>Booking Type</label>
                        <select name="booking_type" class="form-control">
                            <option value="">None</option>
                            <option value="table" <?= ($editItem['booking_type'] ?? '') == 'table' ? 'selected' : '' ?>>Table</option>
                            <option value="hall" <?= ($editItem['booking_type'] ?? '') == 'hall' ? 'selected' : '' ?>>Hall</option>
                            <option value="catering" <?= ($editItem['booking_type'] ?? '') == 'catering' ? 'selected' : '' ?>>Catering</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-save"></i> <?= $editItem ? 'Update Item' : 'Add Item' ?>
                </button>
                
                <?php if ($editItem): ?>
                    <a href="items.php" class="btn btn-secondary w-100 mt-2">Cancel Edit</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <!-- Items List Section -->
    <div class="col-md-8">
        <div class="form-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5><i class="fas fa-list"></i> Menu Items (<span id="itemCount"><?= count($items) ?></span> items)</h5>
                <div style="width: 250px;">
                    <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="🔍 Search items..." onkeyup="searchItems()">
                </div>
            </div>
            <?php if (!empty($low_stock_items)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <strong>Low Stock Alert:</strong> <?= count($low_stock_items) ?> item(s) are below minimum stock level.
                </div>
            <?php endif; ?>
            <div class="row">
                <?php foreach ($items as $item): ?>
                    <div class="col-md-6 col-lg-4 item-column">
                        <div class="card item-card">
                            <?php if ($item['image'] && file_exists($item['image'])): ?>
                                <img src="<?= $item['image'] ?>" class="card-img-top item-image" alt="<?= htmlspecialchars($item['name']) ?>">
                            <?php else: ?>
                                <div class="card-img-top item-image bg-secondary d-flex align-items-center justify-content-center">
                                    <i class="fas fa-utensils fa-3x text-white"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <h6 class="card-title item-name-text"><?= htmlspecialchars($item['name']) ?></h6>
                                <p class="card-text small text-muted"><?= htmlspecialchars($item['description'] ?? 'No description') ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-primary fw-bold"><?= number_format($item['unit_price'], 2) ?></span>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($item['category'] ?? 'Uncategorized') ?></span>
                                </div>
                                <div class="mt-2">
                                    <small>Stock: <?= number_format($item['current_stock'], 2) ?></small>
                                    <?php if ($item['current_stock'] <= $item['min_stock_level'] && $item['min_stock_level'] > 0): ?>
                                        <span class="stock-low ms-2">Low Stock</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($item['booking_required']): ?>
                                    <span class="badge bg-warning mt-2"><i class="fas fa-calendar"></i> Booking Item</span>
                                <?php endif; ?>
                                <?php if ($item['is_producible']): ?>
                                    <span class="badge bg-info mt-2"><i class="fas fa-industry"></i> Producible</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer bg-transparent">
                                <a href="?edit=<?= $item['id'] ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i> Edit</a>
                                <a href="recipe_for_item.php?item_id=<?= $item['id'] ?>" class="btn btn-sm btn-success"><i class="fas fa-receipt"></i> Recipe</a>
                                <a href="?delete=<?= $item['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this item?')"><i class="fas fa-trash"></i> Delete</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (empty($items)): ?>
                <p class="text-center text-muted">No items found. Add your first menu item!</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function searchItems() {
    const input = document.getElementById('searchInput').value.toLowerCase();
    const columns = document.querySelectorAll('.item-column');
    let count = 0;
    
    columns.forEach(col => {
        const name = col.querySelector('.item-name-text').textContent.toLowerCase();
        if (name.includes(input)) {
            col.style.display = 'block';
            count++;
        } else {
            col.style.display = 'none';
        }
    });
    
    document.getElementById('itemCount').textContent = count;
}
</script>

<?php require_once 'footer.php'; ?>