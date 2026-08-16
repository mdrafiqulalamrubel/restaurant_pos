<?php
$c = file_get_contents('recipes.php');

$target = '$stmt = $pdo->prepare("INSERT INTO recipes (name, description, selling_price, category, item_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_POST[\'name\'], $_POST[\'description\'], $_POST[\'selling_price\'], $_POST[\'category\'], $_POST[\'item_id\'] ?: null]);
        $recipe_id = $pdo->lastInsertId();';

$rep = '$item_id = $_POST[\'item_id\'] ?: null;
        if (!$item_id) {
            $istmt = $pdo->prepare("INSERT INTO items (name, description, unit_price, cost_price, category, is_producible, current_stock) VALUES (?, ?, ?, ?, ?, 1, 0)");
            $istmt->execute([$_POST[\'name\'], $_POST[\'description\'], $_POST[\'selling_price\'], 0, $_POST[\'category\']]);
            $item_id = $pdo->lastInsertId();
        }
        
        $stmt = $pdo->prepare("INSERT INTO recipes (name, description, selling_price, category, item_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_POST[\'name\'], $_POST[\'description\'], $_POST[\'selling_price\'], $_POST[\'category\'], $item_id]);
        $recipe_id = $pdo->lastInsertId();';

$c = str_replace($target, $rep, $c);
file_put_contents('recipes.php', $c);
echo 'done';
