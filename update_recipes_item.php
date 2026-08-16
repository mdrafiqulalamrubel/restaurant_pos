<?php
$c = file_get_contents('recipes.php');

// Fetch items
$c = str_replace(
    '$raw_materials = $pdo->query("SELECT id, name, unit, unit_price FROM raw_materials ORDER BY name")->fetchAll();',
    '$raw_materials = $pdo->query("SELECT id, name, unit, unit_price FROM raw_materials ORDER BY name")->fetchAll();
$items = $pdo->query("SELECT id, name FROM items WHERE is_producible = 1 ORDER BY name")->fetchAll();',
    $c
);

// Add item_id to Insert
$c = str_replace(
    '$stmt = $pdo->prepare("INSERT INTO recipes (name, description, selling_price, category) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST[\'name\'], $_POST[\'description\'], $_POST[\'selling_price\'], $_POST[\'category\']]);',
    '$stmt = $pdo->prepare("INSERT INTO recipes (name, description, selling_price, category, item_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_POST[\'name\'], $_POST[\'description\'], $_POST[\'selling_price\'], $_POST[\'category\'], $_POST[\'item_id\'] ?: null]);',
    $c
);

// Add item_id to Update
$c = str_replace(
    '$stmt = $pdo->prepare("UPDATE recipes SET name=?, description=?, selling_price=?, category=? WHERE id=?");
        $stmt->execute([$_POST[\'name\'], $_POST[\'description\'], $_POST[\'selling_price\'], $_POST[\'category\'], $_POST[\'recipe_id\']]);',
    '$stmt = $pdo->prepare("UPDATE recipes SET name=?, description=?, selling_price=?, category=?, item_id=? WHERE id=?");
        $stmt->execute([$_POST[\'name\'], $_POST[\'description\'], $_POST[\'selling_price\'], $_POST[\'category\'], $_POST[\'item_id\'] ?: null, $_POST[\'recipe_id\']]);',
    $c
);

// Add item_id UI
$ui = '                    <div class="mb-2">
                        <label>Category</label>
                        <input type="text" name="category" class="form-control" placeholder="Main Course, Appetizer, etc.">
                    </div>
                    <div class="mb-2">
                        <label>Link to Menu Item (Optional)</label>
                        <select name="item_id" class="form-select">
                            <option value="">-- No Link --</option>
                            <?php foreach ($items as $i): ?>
                                <option value="<?= $i[\'id\'] ?>"><?= htmlspecialchars($i[\'name\']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Links this recipe to a producible menu item so manufacturing it increases stock.</small>
                    </div>';

$c = str_replace(
    '                    <div class="mb-2">
                        <label>Category</label>
                        <input type="text" name="category" class="form-control" placeholder="Main Course, Appetizer, etc.">
                    </div>',
    $ui,
    $c
);

file_put_contents('recipes.php', $c);
echo 'done';
