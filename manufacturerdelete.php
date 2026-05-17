<?php
require_once 'config.php';
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$id = $_GET['id'] ?? 0;
// Optionally check if items use this manufacturer, then either block or set null
$pdo->prepare("UPDATE items SET manufacturer_id=NULL WHERE manufacturer_id=?")->execute([$id]);
$stmt = $pdo->prepare("DELETE FROM manufacturers WHERE id=?")->execute([$id]);
header('Location: manufacturers.php');
exit;