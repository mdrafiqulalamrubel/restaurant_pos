<?php
$c = file_get_contents('items.php');

$target = '    if ($_POST[\'action\'] === \'add\') {
        $stmt = $pdo->prepare("INSERT INTO items (name, unit_price, cost_price, category, description, image, booking_required, booking_type, manufacturer_id, current_stock, min_stock_level, is_producible) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$name, $unit_price, $cost_price, $category, $description, $image_path, $booking_required, $booking_type, $manufacturer_id, $current_stock, $min_stock_level, $is_producible]);
        $success = "Item added successfully!";
    } elseif ($_POST[\'action\'] === \'edit\') {
        $id = $_POST[\'item_id\'];
        if ($image_path) {
            $stmt = $pdo->prepare("UPDATE items SET name=?, unit_price=?, cost_price=?, category=?, description=?, image=?, booking_required=?, booking_type=?, manufacturer_id=?, current_stock=?, min_stock_level=?, is_producible=? WHERE id=?");
            $stmt->execute([$name, $unit_price, $cost_price, $category, $description, $image_path, $booking_required, $booking_type, $manufacturer_id, $current_stock, $min_stock_level, $is_producible, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE items SET name=?, unit_price=?, cost_price=?, category=?, description=?, booking_required=?, booking_type=?, manufacturer_id=?, current_stock=?, min_stock_level=?, is_producible=? WHERE id=?");
            $stmt->execute([$name, $unit_price, $cost_price, $category, $description, $booking_required, $booking_type, $manufacturer_id, $current_stock, $min_stock_level, $is_producible, $id]);
        }
        $success = "Item updated successfully!";
    }';

$rep = '    if ($_POST[\'action\'] === \'add\') {
        $stmt = $pdo->prepare("INSERT INTO items (name, unit_price, cost_price, category, description, image, booking_required, booking_type, manufacturer_id, current_stock, min_stock_level, is_producible) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$name, $unit_price, $cost_price, $category, $description, $image_path, $booking_required, $booking_type, $manufacturer_id, $current_stock, $min_stock_level, $is_producible]);
        $item_id = $pdo->lastInsertId();
        
        if ($current_stock > 0 && !$is_producible) {
            require_once \'acc_core.php\';
            require_once \'accounting.php\';
            $inv_acc = acc_get_account_by_code(\'1300\');
            $ap_acc = acc_get_account_by_code(\'2000\');
            $exp_acc = acc_get_account_by_code(\'5400\');
            $credit_acc = $manufacturer_id ? $ap_acc : $exp_acc;
            
            if ($inv_acc && $credit_acc) {
                $val = $current_stock * $cost_price;
                $entries = [
                    [\'account_id\' => $inv_acc[\'id\'], \'debit\' => $val, \'credit\' => 0],
                    [\'account_id\' => $credit_acc[\'id\'], \'debit\' => 0, \'credit\' => $val]
                ];
                acc_create_journal(date(\'Y-m-d\'), \'ITEM-ADD-\'.$item_id, \'Initial Stock for Item: \' . $name, $entries, $manufacturer_id ? \'supplier\' : null, $manufacturer_id);
            }
        }
        $success = "Item added successfully!";
    } elseif ($_POST[\'action\'] === \'edit\') {
        $id = $_POST[\'item_id\'];
        
        $old_stmt = $pdo->prepare("SELECT current_stock FROM items WHERE id = ?");
        $old_stmt->execute([$id]);
        $old_stock = (float)$old_stmt->fetchColumn() ?: 0;
        
        if ($image_path) {
            $stmt = $pdo->prepare("UPDATE items SET name=?, unit_price=?, cost_price=?, category=?, description=?, image=?, booking_required=?, booking_type=?, manufacturer_id=?, current_stock=?, min_stock_level=?, is_producible=? WHERE id=?");
            $stmt->execute([$name, $unit_price, $cost_price, $category, $description, $image_path, $booking_required, $booking_type, $manufacturer_id, $current_stock, $min_stock_level, $is_producible, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE items SET name=?, unit_price=?, cost_price=?, category=?, description=?, booking_required=?, booking_type=?, manufacturer_id=?, current_stock=?, min_stock_level=?, is_producible=? WHERE id=?");
            $stmt->execute([$name, $unit_price, $cost_price, $category, $description, $booking_required, $booking_type, $manufacturer_id, $current_stock, $min_stock_level, $is_producible, $id]);
        }
        
        $diff = $current_stock - $old_stock;
        if (abs($diff) > 0.01 && !$is_producible) {
            require_once \'acc_core.php\';
            require_once \'accounting.php\';
            $inv_acc = acc_get_account_by_code(\'1300\');
            $ap_acc = acc_get_account_by_code(\'2000\'); 
            $exp_acc = acc_get_account_by_code(\'5400\'); 
            
            $val = abs($diff) * $cost_price;
            
            if ($inv_acc) {
                $entries = [];
                if ($diff > 0 && ($manufacturer_id ? $ap_acc : $exp_acc)) {
                    $credit_acc = $manufacturer_id ? $ap_acc : $exp_acc;
                    $entries[] = [\'account_id\' => $inv_acc[\'id\'], \'debit\' => $val, \'credit\' => 0];
                    $entries[] = [\'account_id\' => $credit_acc[\'id\'], \'debit\' => 0, \'credit\' => $val];
                    $desc = \'Stock Increase for Item: \' . $name;
                } elseif ($diff < 0 && $exp_acc) {
                    $entries[] = [\'account_id\' => $exp_acc[\'id\'], \'debit\' => $val, \'credit\' => 0];
                    $entries[] = [\'account_id\' => $inv_acc[\'id\'], \'debit\' => 0, \'credit\' => $val];
                    $desc = \'Stock Decrease/Adjustment for Item: \' . $name;
                }
                
                if (!empty($entries)) {
                    acc_create_journal(date(\'Y-m-d\'), \'ITEM-ADJ-\'.$id, $desc, $entries, $manufacturer_id ? \'supplier\' : null, $manufacturer_id);
                }
            }
        }
        
        $success = "Item updated successfully!";
    }';

$c = str_replace($target, $rep, $c);
file_put_contents('items.php', $c);
echo 'Done';
