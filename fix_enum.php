<?php
require 'config.php';
$stmt = $pdo->query("ALTER TABLE order_tokens MODIFY COLUMN status ENUM('pending','printed','ready','served','completed') DEFAULT 'pending'");
echo "Updated enum";
