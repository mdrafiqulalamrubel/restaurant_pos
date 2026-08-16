<?php
$file = 'F:\xampp82\htdocs\restaurant_pos\bookings.php';
$content = file_get_contents($file);

// Replace action block
$target = <<<EOT
        \$stmt->execute([\$resource_id, \$customer_id, \$booking_date, \$start_time, \$end_time, \$duration, \$event_type, \$guests, \$special_requests, \$total_amount]);
        
        header('Location: bookings.php?success=1');
        exit;
    }
}
EOT;

$replacement = <<<EOT
        \$stmt->execute([\$resource_id, \$customer_id, \$booking_date, \$start_time, \$end_time, \$duration, \$event_type, \$guests, \$special_requests, \$total_amount]);
        
        header('Location: bookings.php?success=1');
        exit;
    } elseif (\$_POST['action'] === 'cancel_booking') {
        \$booking_id = \$_POST['booking_id'];
        \$stmt = \$pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ?");
        \$stmt->execute([\$booking_id]);
        header('Location: bookings.php?success=1');
        exit;
    }
}
EOT;

$content = str_replace($target, $replacement, $content);

// Replace the table row for Today's bookings to add a Cancel button
$target2 = <<<EOT
                                <td><span class="badge bg-<?= \$b['status'] == 'confirmed' ? 'success' : (\$b['status'] == 'pending' ? 'warning' : 'danger') ?>"><?= \$b['status'] ?></span></td>
                            </tr>
EOT;

$replacement2 = <<<EOT
                                <td><span class="badge bg-<?= \$b['status'] == 'confirmed' ? 'success' : (\$b['status'] == 'pending' ? 'warning' : 'danger') ?>"><?= \$b['status'] ?></span></td>
                                <td>
                                    <?php if (\$b['status'] !== 'cancelled'): ?>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                        <input type="hidden" name="action" value="cancel_booking">
                                        <input type="hidden" name="booking_id" value="<?= \$b['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-times"></i> Cancel</button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
EOT;

$content = str_replace($target2, $replacement2, $content);

// Also need to add the <th>Action</th> to the table header
$target3 = <<<EOT
                            <tr><th>Time</th><th>Resource</th><th>Customer</th><th>Event</th><th>Guests</th><th>Status</th></tr>
EOT;

$replacement3 = <<<EOT
                            <tr><th>Time</th><th>Resource</th><th>Customer</th><th>Event</th><th>Guests</th><th>Status</th><th>Action</th></tr>
EOT;

$content = str_replace($target3, $replacement3, $content);

file_put_contents($file, $content);
echo "Updated bookings.php";
