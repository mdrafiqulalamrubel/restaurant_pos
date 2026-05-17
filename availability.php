<?php
// availability.php?item_id=1&date=2025-04-10&time=14:00&duration=2
require_once 'config.php';

$itemId = $_GET['item_id'] ?? 0;
$date = $_GET['date'] ?? '';
$time = $_GET['time'] ?? '';
$duration = $_GET['duration'] ?? 1;

if (!$itemId || !$date || !$time) {
    echo json_encode(['available' => false, 'error' => 'Missing parameters']);
    exit;
}

// Count overlapping bookings (simple check: start < end of new AND end > start of new)
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM bookings b
    JOIN sale_items si ON b.sale_item_id = si.id
    WHERE si.item_id = ? AND b.booking_date = ? AND b.status != 'cancelled'
    AND b.booking_time < ADDTIME(?, SEC_TO_TIME(?*3600))
    AND ADDTIME(b.booking_time, SEC_TO_TIME(b.duration*3600)) > ?
");
$stmt->execute([$itemId, $date, $time, $duration, $time]);
$count = $stmt->fetchColumn();

echo json_encode(['available' => $count == 0]);
?>