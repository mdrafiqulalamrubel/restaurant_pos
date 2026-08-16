<?php
require 'config.php';
$stmt = $pdo->prepare("UPDATE items SET image = 'uploads/items/default.jpg' WHERE image IS NULL OR image = ''");
$stmt->execute();
echo "Updated " . $stmt->rowCount() . " items to use default image.";
