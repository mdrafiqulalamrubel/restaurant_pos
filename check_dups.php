<?php
require 'config.php';
echo "Duplicate Dining Tables:\n";
print_r($pdo->query('SELECT COUNT(*), table_number FROM dining_tables GROUP BY table_number HAVING COUNT(*) > 1')->fetchAll(PDO::FETCH_ASSOC));

echo "Duplicate Resources:\n";
print_r($pdo->query('SELECT COUNT(*), name FROM resources GROUP BY name HAVING COUNT(*) > 1')->fetchAll(PDO::FETCH_ASSOC));
