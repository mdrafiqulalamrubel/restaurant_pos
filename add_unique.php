<?php
require 'config.php';
$pdo->exec('ALTER TABLE dining_tables ADD UNIQUE (table_number)');
echo 'done';
