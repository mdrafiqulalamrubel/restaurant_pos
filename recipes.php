<?php
$page_title = 'Recipe Management';
$page_icon = 'utensils';
require_once 'config.php';
require_once 'header.php';

if ($_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Handle recipe operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_recipe') {
        $stmt = $pdo->prepare("INSERT INTO recipes (name, description, selling_price, category) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['name'], $_POST['description'], $_POST['selling_price'], $_POST['category']]);
        $recipe_id = $pdo->lastInsertId();
        
        // Add ingredients
        $ingredients = json_decode($_POST['ingredients'], true);
        $total_cost = 0;
        foreach ($ingredients as $ing) {
            $stmt = $pdo->prepare("INSERT INTO recipe_ingredients (recipe_id, raw_material_id, quantity, unit, cost_per_unit, total_cost) VALUES (?, ?, ?, ?, ?, ?)");
            $cost_per_unit = $ing['cost'];
            $total_ing_cost = $cost_per_unit * $ing['quantity'];
            $stmt->execute([$recipe_id, $ing['material_id'], $ing['quantity'], $ing['unit'], $cost_per_unit, $total_ing_cost]);
            $total_cost += $total_ing_cost;
        }
        
        $profit_margin = (($_POST['selling_price'] - $total_cost) / $_POST['selling_price']) * 100;
        $stmt = $pdo->prepare("UPDATE recipes SET total_cost = ?, profit_margin = ? WHERE id = ?");
        $stmt->execute([$total_cost, $profit_margin, $recipe_id]);
        
        $success = "Recipe created successfully!";
        header('Location: recipes.php');
        exit;
        
    } elseif ($action === 'edit_recipe') {
        $stmt = $pdo->prepare("UPDATE recipes SET name=?, description=?, selling_price=?, category=? WHERE id=?");
        $stmt->execute([$_POST['name'], $_POST['description'], $_POST['selling_price'], $_POST['category'], $_POST['recipe_id']]);
        
        // Delete old ingredients and add new ones
        $pdo->prepare("DELETE FROM recipe_ingredients WHERE recipe_id = ?")->execute([$_POST['recipe_id']]);
        
        $ingredients = json_decode($_POST['ingredients'], true);
        $total_cost = 0;
        foreach ($ingredients as $ing) {
            $stmt = $pdo->prepare("INSERT INTO recipe_ingredients (recipe_id, raw_material_id, quantity, unit, cost_per_unit, total_cost) VALUES (?, ?, ?, ?, ?, ?)");
            $cost_per_unit = $ing['cost'];
            $total_ing_cost = $cost_per_unit * $ing['quantity'];
            $stmt->execute([$_POST['recipe_id'], $ing['material_id'], $ing['quantity'], $ing['unit'], $cost_per_unit, $total_ing_cost]);
            $total_cost += $total_ing_cost;
        }
        
        $profit_margin = (($_POST['selling_price'] - $total_cost) / $_POST['selling_price']) * 100;
        $stmt = $pdo->prepare("UPDATE recipes SET total_cost = ?, profit_margin = ? WHERE id = ?");
        $stmt->execute([$total_cost, $profit_margin, $_POST['recipe_id']]);
        
        $success = "Recipe updated successfully!";
        header('Location: recipes.php');
        exit;
    }
}

// Then include header
require_once 'header.php';

$recipes = $pdo->query("SELECT r.*, COUNT(ri.id) as ingredient_count FROM recipes r LEFT JOIN recipe_ingredients ri ON r.id = ri.recipe_id GROUP BY r.id ORDER BY r.name")->fetchAll();
$raw_materials = $pdo->query("SELECT id, name, unit, unit_price FROM raw_materials ORDER BY name")->fetchAll();
$items = $pdo->query("SELECT id, name FROM items WHERE is_producible = 1 ORDER BY name")->fetchAll();
?>

<style>
    .recipe-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: transform 0.3s;
    }
    .recipe-card:hover { transform: translateY(-3px); }
    .profit-positive { color: #28a745; }
    .profit-negative { color: #dc3545; }
</style>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-plus"></i> Create New Recipe</h5>
            </div>
            <div class="card-body">
                <form method="post" id="recipeForm">
                    <input type="hidden" name="action" value="add_recipe">
                    <input type="hidden" name="ingredients" id="ingredientsData">
                    
                    <div class="mb-2">
                        <label>Recipe Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Category</label>
                        <input type="text" name="category" class="form-control" placeholder="Main Course, Appetizer, etc.">
                    </div>
                    <div class="mb-2">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-2">
                        <label>Selling Price</label>
                        <input type="number" name="selling_price" class="form-control" step="0.01" required>
                    </div>
                    
                    <h6 class="mt-3">Ingredients</h6>
                    <div id="ingredientsList">
                        <div class="row mb-2 ingredient-row">
                            <div class="col-md-5"><select class="form-control ingredient-select" required><option value="">Select Material</option><?php foreach ($raw_materials as $rm): ?><option value="<?= $rm['id'] ?>" data-unit="<?= $rm['unit'] ?>" data-price="<?= $rm['unit_price'] ?>"><?= htmlspecialchars($rm['name']) ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-3"><input type="number" class="form-control ingredient-qty" placeholder="Quantity" step="0.001" required></div>
                            <div class="col-md-3"><input type="text" class="form-control ingredient-unit" readonly placeholder="Unit"></div>
                            <div class="col-md-1"><button type="button" class="btn btn-danger btn-sm" onclick="removeIngredient(this)">✕</button></div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-secondary mb-3" onclick="addIngredient()">+ Add Ingredient</button>
                    
                    <button type="submit" class="btn btn-primary w-100" onclick="prepareSubmit()">Create Recipe</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list"></i> Recipe List</h5>
            </div>
            <div class="card-body">
                <?php foreach ($recipes as $recipe): ?>
                <div class="recipe-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5><?= htmlspecialchars($recipe['name']) ?></h5>
                            <small class="text-muted"><?= $recipe['category'] ?? 'Uncategorized' ?></small>
                            <p class="mt-2 small"><?= htmlspecialchars($recipe['description'] ?? '') ?></p>
                        </div>
                        <div class="text-end">
                            <div class="mb-1"><strong>Selling:</strong> <?= number_format($recipe['selling_price'], 2) ?></div>
                            <div><strong>Cost:</strong> <?= number_format($recipe['total_cost'] ?? 0, 2) ?></div>
                            <div class="<?= ($recipe['profit_margin'] ?? 0) >= 0 ? 'profit-positive' : 'profit-negative' ?>">
                                <strong>Margin:</strong> <?= number_format($recipe['profit_margin'] ?? 0, 1) ?>%
                            </div>
                            <div class="mt-2">
                                <button class="btn btn-sm btn-warning" onclick="editRecipe(<?= htmlspecialchars(json_encode($recipe)) ?>)">Edit</button>
                                <a href="production.php?recipe_id=<?= $recipe['id'] ?>" class="btn btn-sm btn-success">Produce</a>
                            </div>
                        </div>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted"><?= $recipe['ingredient_count'] ?? 0 ?> ingredients</small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
function addIngredient() {
    const container = document.getElementById('ingredientsList');
    const newRow = document.createElement('div');
    newRow.className = 'row mb-2 ingredient-row';
    newRow.innerHTML = `
        <div class="col-md-5"><select class="form-control ingredient-select" required><option value="">Select Material</option><?php foreach ($raw_materials as $rm): ?><option value="<?= $rm['id'] ?>" data-unit="<?= $rm['unit'] ?>" data-price="<?= $rm['unit_price'] ?>"><?= htmlspecialchars($rm['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-3"><input type="number" class="form-control ingredient-qty" placeholder="Quantity" step="0.001" required></div>
        <div class="col-md-3"><input type="text" class="form-control ingredient-unit" readonly placeholder="Unit"></div>
        <div class="col-md-1"><button type="button" class="btn btn-danger btn-sm" onclick="removeIngredient(this)">✕</button></div>
    `;
    container.appendChild(newRow);
    
    // Add event listeners
    const select = newRow.querySelector('.ingredient-select');
    const qtyInput = newRow.querySelector('.ingredient-qty');
    const unitInput = newRow.querySelector('.ingredient-unit');
    
    select.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        unitInput.value = selected.dataset.unit || '';
    });
}

function removeIngredient(btn) {
    btn.closest('.ingredient-row').remove();
}

function prepareSubmit() {
    const ingredients = [];
    document.querySelectorAll('.ingredient-row').forEach(row => {
        const select = row.querySelector('.ingredient-select');
        const qty = row.querySelector('.ingredient-qty');
        if (select.value && qty.value) {
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
}
</script>

<?php require_once 'footer.php'; ?>