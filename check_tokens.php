<?php
require 'config.php';
$stmt = $pdo->query("DESCRIBE order_tokens");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
