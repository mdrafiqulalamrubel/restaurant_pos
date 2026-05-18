<?php
$page_title = 'Recipe Management';
$page_icon = 'receipt';
require_once 'config.php';
require_once 'header.php';

$item_id = $_GET['item_id'] ?? 0;

// Get item details - ONLY from existing items table
$stmt = $pdo->prepare("SELECT * FROM items WHERE id = ? AND active = 1");
$stmt->execute([$item_id]);
$item = $stmt->fetch();

if (!$item) {
    // If no item selected, show item selector
    ?>
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-search"></i> Select Item to Create/Edit Recipe</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                Please select an existing menu item to create or edit its recipe.
                If the item doesn't exist, <a href="items.php">add it first</a>.
            </div>
            
            <form method="get" class="row g-3">
                <div class="col-md-8">
                    <label>Search or Select Item</label>
                    <select name="item_id" class="form-control" required>
                        <option value="">-- Select Menu Item --</option>
                        <?php
                        $items = $pdo->query("SELECT id, name, category, unit_price FROM items WHERE active = 1 ORDER BY name")->fetchAll();
                        foreach ($items as $i):
                        ?>
                            <option value="<?= $i['id'] ?>">
                                <?= htmlspecialchars($i['name']) ?> (<?= $i['category'] ?? 'Uncategorized' ?>) - <?= number_format($i['unit_price'], 2) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Select Item</button>
                </div>
            </form>
            
            <div class="mt-4 text-center">
                <a href="items.php" class="btn btn-success">
                    <i class="fas fa-plus"></i> Add New Item First
                </a>
            </div>
        </div>
    </div>
    <?php
    require_once 'footer.php';
    exit;
}

// Check if item is producible
if (!$item['is_producible']) {
    ?>
    <div class="card">
        <div class="card-header bg-warning">
            <h5><i class="fas fa-exclamation-triangle"></i> Item Not Producible</h5>
        </div>
        <div class="card-body">
            <p>This item is marked as not producible. To enable production:</p>
            <ol>
                <li>Go to <a href="items.php">Items Management</a></li>
                <li>Edit this item</li>
                <li>Check "Can be Produced" option</li>
            </ol>
            <a href="items.php?edit=<?= $item_id ?>" class="btn btn-warning">Edit Item</a>
            <a href="items.php" class="btn btn-secondary">Back to Items</a>
        </div>
    </div>
    <?php
    require_once 'footer.php';
    exit;
}

// Get existing recipe for this item
$stmt = $pdo->prepare("SELECT * FROM recipes WHERE item_id = ?");
$stmt->execute([$item_id]);
$recipe = $stmt->fetch();

// Handle recipe save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ingredients = json_decode($_POST['ingredients'], true);
    $total_cost = 0;
    
    // Calculate total cost
    foreach ($ingredients as $ing) {
        $total_cost += $ing['cost'] * $ing['quantity'];
    }
    
    if ($recipe) {
        // Update existing recipe
        $stmt = $pdo->prepare("UPDATE recipes SET total_cost = ?, profit_margin = ? WHERE item_id = ?");
        $profit_margin = (($item['unit_price'] - $total_cost) / $item['unit_price']) * 100;
        $stmt->execute([$total_cost, $profit_margin, $item_id]);
        
        // Delete old ingredients
        $pdo->prepare("DELETE FROM recipe_ingredients WHERE recipe_id = ?")->execute([$recipe['id']]);
        $recipe_id = $recipe['id'];
    } else {
        // Create new recipe
        $stmt = $pdo->prepare("INSERT INTO recipes (name, description, selling_price, total_cost, item_id, category) VALUES (?, ?, ?, ?, ?, ?)");
        $profit_margin = (($item['unit_price'] - $total_cost) / $item['unit_price']) * 100;
        $stmt->execute([$item['name'], $item['description'], $item['unit_price'], $total_cost, $item_id, $item['category']]);
        $recipe_id = $pdo->lastInsertId();
    }
    
    // Save ingredients
    foreach ($ingredients as $ing) {
        $stmt = $pdo->prepare("INSERT INTO recipe_ingredients (recipe_id, raw_material_id, quantity, unit, cost_per_unit, total_cost) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$recipe_id, $ing['material_id'], $ing['quantity'], $ing['unit'], $ing['cost'], $ing['cost'] * $ing['quantity']]);
    }
    
    $success = "Recipe saved successfully!";
    
    // Refresh recipe data
    $stmt = $pdo->prepare("SELECT * FROM recipes WHERE item_id = ?");
    $stmt->execute([$item_id]);
    $recipe = $stmt->fetch();
}

// Get existing ingredients if recipe exists
$existing_ingredients = [];
if ($recipe) {
    $stmt = $pdo->prepare("
        SELECT ri.*, rm.name as material_name, rm.unit 
        FROM recipe_ingredients ri 
        JOIN raw_materials rm ON ri.raw_material_id = rm.id 
        WHERE ri.recipe_id = ?
    ");
    $stmt->execute([$recipe['id']]);
    $existing_ingredients = $stmt->fetchAll();
}

$raw_materials = $pdo->query("SELECT id, name, unit, unit_price FROM raw_materials ORDER BY name")->fetchAll();
?>

<style>
    .ingredient-row { margin-bottom: 10px; }
    .cost-summary { background: #f8f9fa; padding: 15px; border-radius: 10px; margin-top: 20px; }
    .profit-positive { color: #28a745; }
    .profit-negative { color: #dc3545; }
    .item-info { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px; border-radius: 10px; margin-bottom: 20px; }
</style>

<div class="row">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-receipt"></i> Recipe for: <?= htmlspecialchars($item['name']) ?></h5>
            </div>
            <div class="card-body">
                <div class="item-info">
                    <div class="row">
                        <div class="col-md-6">
                            <small>Selling Price</small>
                            <h4><?= number_format($item['unit_price'], 2) ?></h4>
                        </div>
                        <div class="col-md-6">
                            <small>Current Stock</small>
                            <h4><?= number_format($item['current_stock'], 2) ?></h4>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-12">
                            <small>Category</small>
                            <div><?= $item['category'] ?? 'Uncategorized' ?></div>
                        </div>
                    </div>
                </div>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>
                
                <form method="post" id="recipeForm">
                    <input type="hidden" name="ingredients" id="ingredientsData">
                    
                    <h6>Ingredients (Raw Materials)</h6>
                    <div id="ingredientsList">
                        <?php if (!empty($existing_ingredients)): ?>
                            <?php foreach ($existing_ingredients as $ing): ?>
                            <div class="row ingredient-row">
                                <div class="col-md-5">
                                    <select class="form-control ingredient-select" required>
                                        <option value="">Select Material</option>
                                        <?php foreach ($raw_materials as $rm): ?>
                                            <option value="<?= $rm['id'] ?>" data-unit="<?= $rm['unit'] ?>" data-price="<?= $rm['unit_price'] ?>" <?= $ing['raw_material_id'] == $rm['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($rm['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <input type="number" class="form-control ingredient-qty" placeholder="Quantity" step="0.001" value="<?= $ing['quantity'] ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <input type="text" class="form-control ingredient-unit" readonly placeholder="Unit" value="<?= $ing['unit'] ?>">
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeIngredient(this)">✕</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> No ingredients added yet. Add raw materials to create recipe.
                            </div>
                            <div class="row ingredient-row">
                                <div class="col-md-5">
                                    <select class="form-control ingredient-select" required>
                                        <option value="">Select Material</option>
                                        <?php foreach ($raw_materials as $rm): ?>
                                            <option value="<?= $rm['id'] ?>" data-unit="<?= $rm['unit'] ?>" data-price="<?= $rm['unit_price'] ?>"><?= htmlspecialchars($rm['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <input type="number" class="form-control ingredient-qty" placeholder="Quantity" step="0.001" required>
                                </div>
                                <div class="col-md-3">
                                    <input type="text" class="form-control ingredient-unit" readonly placeholder="Unit">
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeIngredient(this)">✕</button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn btn-sm btn-secondary mb-3" onclick="addIngredient()">+ Add Ingredient</button>
                    
                    <div class="cost-summary" id="costSummary">
                        <h6>Cost Summary</h6>
                        <div class="d-flex justify-content-between">
                            <span>Total Raw Material Cost:</span>
                            <strong id="totalCost">0.00</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Selling Price:</span>
                            <strong><?= number_format($item['unit_price'], 2) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Profit per Unit:</span>
                            <strong id="profit">0.00</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Profit Margin:</span>
                            <strong id="margin">0%</strong>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 mt-3" onclick="prepareSubmit()">Save Recipe</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-industry"></i> Production</h5>
            </div>
            <div class="card-body">
                <?php if ($recipe): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> Recipe is ready! You can now produce this item.
                    </div>
                    
                    <form method="post" action="produce_item.php">
                        <input type="hidden" name="item_id" value="<?= $item_id ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <label>Quantity to Produce</label>
                                <input type="number" name="quantity" class="form-control" min="1" value="1" required>
                            </div>
                            <div class="col-md-6">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fas fa-play"></i> Produce Item
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <div class="mt-3">
                        <h6>Recipe Details:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr style="background: #f8f9fa;">
                                        <th>Raw Material</th>
                                        <th class="text-center">Quantity</th>
                                        <th class="text-end">Cost</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($existing_ingredients as $ing): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($ing['material_name']) ?></td>
                                        <td class="text-center"><?= $ing['quantity'] ?> <?= $ing['unit'] ?></td>
                                        <td class="text-end"><?= number_format($ing['cost_per_unit'] * $ing['quantity'], 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr style="border-top: 2px solid #ddd;">
                                        <th colspan="2">Total Cost per Unit:</th>
                                        <th class="text-end"><?= number_format($recipe['total_cost'] ?? 0, 2) ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        <strong>No recipe defined for this item.</strong><br>
                        Please add ingredients above to create a recipe first.
                    </div>
                <?php endif; ?>
                
                <hr>
                <div class="d-flex justify-content-between">
                    <a href="items.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Items
                    </a>
                    <a href="raw_materials.php" class="btn btn-info">
                        <i class="fas fa-boxes"></i> Manage Raw Materials
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function addIngredient() {
    const container = document.getElementById('ingredientsList');
    const newRow = document.createElement('div');
    newRow.className = 'row ingredient-row';
    newRow.innerHTML = `
        <div class="col-md-5">
            <select class="form-control ingredient-select" required>
                <option value="">Select Material</option>
                <?php foreach ($raw_materials as $rm): ?>
                    <option value="<?= $rm['id'] ?>" data-unit="<?= $rm['unit'] ?>" data-price="<?= $rm['unit_price'] ?>"><?= htmlspecialchars($rm['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <input type="number" class="form-control ingredient-qty" placeholder="Quantity" step="0.001" required>
        </div>
        <div class="col-md-3">
            <input type="text" class="form-control ingredient-unit" readonly placeholder="Unit">
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-danger btn-sm" onclick="removeIngredient(this)">✕</button>
        </div>
    `;
    container.appendChild(newRow);
    
    const select = newRow.querySelector('.ingredient-select');
    const unitInput = newRow.querySelector('.ingredient-unit');
    
    select.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        unitInput.value = selected.dataset.unit || '';
        updateCost();
    });
    
    const qtyInput = newRow.querySelector('.ingredient-qty');
    qtyInput.addEventListener('input', function() { updateCost(); });
    
    // Remove the "no ingredients" alert if present
    const alertDiv = container.querySelector('.alert');
    if (alertDiv && container.children.length > 1) {
        alertDiv.remove();
    }
    
    updateCost();
}

function removeIngredient(btn) {
    btn.closest('.ingredient-row').remove();
    const container = document.getElementById('ingredientsList');
    if (container.children.length === 0) {
        container.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle"></i> No ingredients added yet. Add raw materials to create recipe.</div>';
    }
    updateCost();
}

function updateCost() {
    let totalCost = 0;
    document.querySelectorAll('.ingredient-row').forEach(row => {
        const select = row.querySelector('.ingredient-select');
        const qty = row.querySelector('.ingredient-qty');
        if (select && select.value && qty && qty.value) {
            const selected = select.options[select.selectedIndex];
            const price = parseFloat(selected.dataset.price) || 0;
            const quantity = parseFloat(qty.value) || 0;
            totalCost += price * quantity;
        }
    });
    
    const sellingPrice = <?= $item['unit_price'] ?>;
    const profit = sellingPrice - totalCost;
    const margin = sellingPrice > 0 ? (profit / sellingPrice) * 100 : 0;
    
    document.getElementById('totalCost').innerText = totalCost.toFixed(2);
    document.getElementById('profit').innerHTML = profit.toFixed(2);
    document.getElementById('margin').innerHTML = margin.toFixed(1) + '%';
    
    const profitElem = document.getElementById('profit');
    const marginElem = document.getElementById('margin');
    if (profit >= 0) {
        profitElem.className = 'profit-positive';
        marginElem.className = 'profit-positive';
    } else {
        profitElem.className = 'profit-negative';
        marginElem.className = 'profit-negative';
    }
}

function prepareSubmit() {
    const ingredients = [];
    document.querySelectorAll('.ingredient-row').forEach(row => {
        const select = row.querySelector('.ingredient-select');
        const qty = row.querySelector('.ingredient-qty');
        if (select && select.value && qty && qty.value) {
            const selected = select.options[select.selectedIndex];
            ingredients.push({
                material_id: select.value,
                quantity: parseFloat(qty.value),
                unit: selected.dataset.unit,
                cost: parseFloat(selected.dataset.price)
            });
        }
    });
    document.getElementById('ingredientsData').value = JSON.stringify(ingredients);
    updateCost();
}

// Initialize cost calculation on page load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.ingredient-select').forEach(select => {
        if (select) {
            select.addEventListener('change', function() {
                const row = this.closest('.ingredient-row');
                const unitInput = row.querySelector('.ingredient-unit');
                const selected = this.options[this.selectedIndex];
                if (unitInput) unitInput.value = selected.dataset.unit || '';
                updateCost();
            });
        }
    });
    
    document.querySelectorAll('.ingredient-qty').forEach(qty => {
        if (qty) {
            qty.addEventListener('input', function() { updateCost(); });
        }
    });
    
    updateCost();
});
</script>

<?php require_once 'footer.php'; ?>