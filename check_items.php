<?php
require 'config.php';
echo "--- RECIPES ---\n";
print_r($pdo->query('SELECT id, name, item_id FROM recipes')->fetchAll(PDO::FETCH_ASSOC));

echo "--- ITEMS ---\n";
print_r($pdo->query('SELECT id, name, is_producible, current_stock, active FROM items')->fetchAll(PDO::FETCH_ASSOC));
