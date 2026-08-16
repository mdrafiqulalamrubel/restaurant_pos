<?php
require 'config.php';

$sql = "
ALTER TABLE purchase_return_items ADD COLUMN item_type VARCHAR(50) NOT NULL DEFAULT 'item' AFTER return_id;
";

try {
    $pdo->exec($sql);
    echo "Added item_type successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
