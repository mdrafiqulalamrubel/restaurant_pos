<?php
require 'config.php';

$recipes = $pdo->query("SELECT * FROM recipes WHERE item_id IS NULL")->fetchAll();

foreach ($recipes as $r) {
    // check if item with this name already exists
    $stmt = $pdo->prepare("SELECT id FROM items WHERE name = ?");
    $stmt->execute([$r['name']]);
    $item_id = $stmt->fetchColumn();
    
    if (!$item_id) {
        $stmt = $pdo->prepare("INSERT INTO items (name, description, unit_price, cost_price, category, is_producible, current_stock) VALUES (?, ?, ?, ?, ?, 1, 0)");
        $stmt->execute([$r['name'], $r['description'], $r['selling_price'], $r['total_cost'] ?: 0, $r['category']]);
        $item_id = $pdo->lastInsertId();
    }
    
    $pdo->prepare("UPDATE recipes SET item_id = ? WHERE id = ?")->execute([$item_id, $r['id']]);
    
    // also update item cost_price if recipe total_cost is available
    if ($r['total_cost'] > 0) {
        $pdo->prepare("UPDATE items SET cost_price = ? WHERE id = ?")->execute([$r['total_cost'], $item_id]);
    }
}
echo "Linked existing recipes to items.";
