<?php
require 'config.php';
echo "=== bookings ===\n";
try { $r = $pdo->query('DESCRIBE bookings'); foreach($r as $row) echo $row['Field'] . ' | ' . $row['Type'] . "\n"; } catch(Exception $e){ echo "No bookings table\n"; }
echo "=== reservations ===\n";
try { $r = $pdo->query('DESCRIBE reservations'); foreach($r as $row) echo $row['Field'] . ' | ' . $row['Type'] . "\n"; } catch(Exception $e){ echo "No reservations table\n"; }
