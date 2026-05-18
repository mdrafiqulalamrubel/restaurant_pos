<?php
// produce_item.php - Production script
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: items.php');
    exit;
}

$item_id = $_POST['item_id'] ?? 0;
$quantity = $_POST['quantity'] ?? 1;

if (!$item_id) {
    die("Invalid item");
}

// Get item details
$stmt = $pdo->prepare("SELECT * FROM items WHERE id = ? AND active = 1");
$stmt->execute([$item_id]);
$item = $stmt->fetch();

if (!$item) {
    die("Item not found. <a href='items.php'>Go back to items</a>");
}

// Check if item is producible
if (!$item['is_producible']) {
    die("This item is not marked as producible. Please edit the item and enable production.");
}

// Get recipe with ingredients
$stmt = $pdo->prepare("
    SELECT r.*, ri.raw_material_id, ri.quantity as required_qty, ri.cost_per_unit,
           rm.name as material_name, rm.current_stock, rm.unit
    FROM recipes r
    JOIN recipe_ingredients ri ON r.id = ri.recipe_id
    JOIN raw_materials rm ON ri.raw_material_id = rm.id
    WHERE r.item_id = ?
");
$stmt->execute([$item_id]);
$ingredients = $stmt->fetchAll();

if (empty($ingredients)) {
    die("No recipe found for this item. <a href='recipe_for_item.php?item_id=$item_id'>Create recipe first</a>");
}

// Check stock availability
$insufficient = [];
foreach ($ingredients as $ing) {
    $required = $ing['required_qty'] * $quantity;
    if ($required > $ing['current_stock']) {
        $insufficient[] = "{$ing['material_name']} (Need: $required, Available: {$ing['current_stock']} {$ing['unit']})";
    }
}

if (!empty($insufficient)) {
    die("Insufficient stock for: " . implode(", ", $insufficient) . "<br><a href='recipe_for_item.php?item_id=$item_id'>Go back</a>");
}

try {
    $pdo->beginTransaction();
    
    // Deduct raw materials
    $total_cost = 0;
    foreach ($ingredients as $ing) {
        $required = $ing['required_qty'] * $quantity;
        $cost = $ing['cost_per_unit'] * $required;
        $total_cost += $cost;
        
        $stmt = $pdo->prepare("UPDATE raw_materials SET current_stock = current_stock - ? WHERE id = ?");
        $stmt->execute([$required, $ing['raw_material_id']]);
    }
    
    // Increase item stock
    $stmt = $pdo->prepare("UPDATE items SET current_stock = current_stock + ? WHERE id = ?");
    $stmt->execute([$quantity, $item_id]);
    
    // Record production
    $stmt = $pdo->prepare("INSERT INTO productions (recipe_id, quantity_produced, total_cost, production_date, notes, created_by) 
                           SELECT id, ?, ?, NOW(), ?, ? FROM recipes WHERE item_id = ?");
    $stmt->execute([$quantity, $total_cost, "Production of {$item['name']} x{$quantity}", $_SESSION['user_id'], $item_id]);
    
    // Add to expenses
    $stmt = $pdo->prepare("INSERT INTO expenses (expense_date, category, description, amount, payment_method, is_production_cost) 
                           VALUES (NOW(), 'Production Cost', ?, ?, 'cash', 1)");
    $stmt->execute(["Production of {$item['name']} x{$quantity}", $total_cost]);
    
    // Add cash transaction
    $stmt = $pdo->prepare("INSERT INTO cash_transactions (transaction_type, amount, category, description, transaction_date, created_by) 
                           VALUES ('expense', ?, 'Production', ?, NOW(), ?)");
    $stmt->execute([$total_cost, "Production cost for {$item['name']} x{$quantity}", $_SESSION['user_id']]);
    
    $pdo->commit();
    
    // Redirect back to item recipe page with success
    header("Location: recipe_for_item.php?item_id=$item_id&success=1");
    exit;
    
} catch (Exception $e) {
    $pdo->rollBack();
    die("Production failed: " . $e->getMessage());
}
?>