<?php
require 'config.php';

$tables = $pdo->query("SELECT id, table_number FROM dining_tables ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

$kept = [];
$deleted_count = 0;
$updated_orders = 0;

foreach ($tables as $t) {
    $num = $t['table_number'];
    if (!isset($kept[$num])) {
        $kept[$num] = $t['id'];
    } else {
        $primary_id = $kept[$num];
        $dup_id = $t['id'];
        
        // Reassign table_orders
        $stmt = $pdo->prepare("UPDATE table_orders SET table_id = ? WHERE table_id = ?");
        $stmt->execute([$primary_id, $dup_id]);
        $updated_orders += $stmt->rowCount();
        
        // Delete duplicate
        $stmt = $pdo->prepare("DELETE FROM dining_tables WHERE id = ?");
        $stmt->execute([$dup_id]);
        $deleted_count++;
    }
}

echo "Deleted $deleted_count duplicate tables.\n";
echo "Reassigned $updated_orders table orders.\n";
