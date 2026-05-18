<?php
$page_title = 'Production';
$page_icon = 'industry';
require_once 'config.php';
require_once 'header.php';

$recipe_id = $_GET['recipe_id'] ?? 0;
$recipe = null;

if ($recipe_id) {
    $stmt = $pdo->prepare("SELECT * FROM recipes WHERE id = ?");
    $stmt->execute([$recipe_id]);
    $recipe = $stmt->fetch();
}

// Handle production submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['produce'])) {
    $recipe_id = $_POST['recipe_id'];
    $quantity = $_POST['quantity'];
    
    // Get recipe details with ingredients
    $stmt = $pdo->prepare("
        SELECT r.*, ri.raw_material_id, ri.quantity as required_qty, ri.cost_per_unit,
               rm.name as material_name, rm.current_stock, rm.unit
        FROM recipes r
        JOIN recipe_ingredients ri ON r.id = ri.recipe_id
        JOIN raw_materials rm ON ri.raw_material_id = rm.id
        WHERE r.id = ?
    ");
    $stmt->execute([$recipe_id]);
    $ingredients = $stmt->fetchAll();
    
    if (empty($ingredients)) {
        $error = "Recipe not found or has no ingredients!";
    } else {
        // Check if enough stock available
        $insufficient = [];
        foreach ($ingredients as $ing) {
            $required = $ing['required_qty'] * $quantity;
            if ($required > $ing['current_stock']) {
                $insufficient[] = "{$ing['material_name']} (Need: $required, Available: {$ing['current_stock']} {$ing['unit']})";
            }
        }
        
        if (!empty($insufficient)) {
            $error = "Insufficient stock for: " . implode(", ", $insufficient);
        } else {
            try {
                $pdo->beginTransaction();
                
                // Deduct stock and calculate total cost
                $total_cost = 0;
                foreach ($ingredients as $ing) {
                    $required = $ing['required_qty'] * $quantity;
                    $cost = $ing['cost_per_unit'] * $required;
                    $total_cost += $cost;
                    
                    $stmt = $pdo->prepare("UPDATE raw_materials SET current_stock = current_stock - ? WHERE id = ?");
                    $stmt->execute([$required, $ing['raw_material_id']]);
                }
                
                // Record production
                $stmt = $pdo->prepare("INSERT INTO productions (recipe_id, quantity_produced, total_cost, production_date, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$recipe_id, $quantity, $total_cost, date('Y-m-d'), $_POST['notes'] ?? '', $_SESSION['user_id']]);
                $production_id = $pdo->lastInsertId();
                
                // Add to expenses as production cost
                $stmt = $pdo->prepare("INSERT INTO expenses (expense_date, category, description, amount, payment_method, is_production_cost, production_id) VALUES (?, ?, ?, ?, ?, 1, ?)");
                $stmt->execute([date('Y-m-d'), 'Production Cost', "Production of {$recipe['name']} x{$quantity}", $total_cost, 'cash', $production_id]);
                
                // Add cash transaction
                $stmt = $pdo->prepare("INSERT INTO cash_transactions (transaction_type, amount, category, description, reference_id, reference_type, transaction_date, created_by) VALUES ('expense', ?, 'Production', ?, ?, 'production', ?, ?)");
                $stmt->execute([$total_cost, "Production cost for {$recipe['name']} x{$quantity}", $production_id, date('Y-m-d'), $_SESSION['user_id']]);
                
                $pdo->commit();
                $success = "Production completed! Total cost: " . number_format($total_cost, 2);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Production failed: " . $e->getMessage();
            }
        }
    }
}

// Then include header
require_once 'header.php';

// Get recent productions
$productions = $pdo->query("
    SELECT p.*, r.name as recipe_name 
    FROM productions p 
    JOIN recipes r ON p.recipe_id = r.id 
    ORDER BY p.created_at DESC 
    LIMIT 20
")->fetchAll();
?>

<style>
    .production-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .cost-positive { color: #dc3545; }
</style>

<div class="row">
    <?php if ($recipe): ?>
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-industry"></i> Produce: <?= htmlspecialchars($recipe['name']) ?></h5>
            </div>
            <div class="card-body">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                
                <form method="post">
                    <input type="hidden" name="recipe_id" value="<?= $recipe['id'] ?>">
                    <div class="mb-3">
                        <label>Quantity to Produce</label>
                        <input type="number" name="quantity" class="form-control" min="1" value="1" required>
                    </div>
                    <div class="mb-3">
                        <label>Notes (Optional)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Batch number, special instructions..."></textarea>
                    </div>
                    <button type="submit" name="produce" class="btn btn-primary w-100">
                        <i class="fas fa-play"></i> Start Production
                    </button>
                </form>
                
                <div class="mt-3">
                    <h6>Required Ingredients (per unit):</h6>
                    <?php
                    $stmt = $pdo->prepare("
                        SELECT ri.*, rm.name, rm.unit 
                        FROM recipe_ingredients ri 
                        JOIN raw_materials rm ON ri.raw_material_id = rm.id 
                        WHERE ri.recipe_id = ?
                    ");
                    $stmt->execute([$recipe['id']]);
                    $ingredients = $stmt->fetchAll();
                    ?>
                    <table class="table table-sm">
                        <?php foreach ($ingredients as $ing): ?>
                        <tr>
                            <td><?= htmlspecialchars($ing['name']) ?></td>
                            <td><?= $ing['quantity'] ?> <?= $ing['unit'] ?></td>
                            <td class="text-end"><?= number_format($ing['cost_per_unit'] * $ing['quantity'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="<?= $recipe ? 'col-md-7' : 'col-md-12' ?>">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-history"></i> Recent Productions</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr><th>Date</th><th>Recipe</th><th>Quantity</th><th>Total Cost</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productions as $p): ?>
                            <tr>
                                <td><?= date('d-m-Y H:i', strtotime($p['created_at'])) ?></td>
                                <td><?= htmlspecialchars($p['recipe_name']) ?></td>
                                <td class="text-center">x<?= $p['quantity_produced'] ?></td>
                                <td class="cost-positive"><?= number_format($p['total_cost'], 2) ?></td>
                                <td><span class="badge bg-success">Completed</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <a href="recipes.php" class="btn btn-secondary mt-2">← Back to Recipes</a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>