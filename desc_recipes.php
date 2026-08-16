<?php
require 'config.php';
print_r($pdo->query("DESCRIBE recipes")->fetchAll(PDO::FETCH_ASSOC));
