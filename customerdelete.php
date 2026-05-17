<?php
require_once 'config.php';
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("DELETE FROM customers WHERE id=?");
$stmt->execute([$id]);
header('Location: customers.php');
exit;