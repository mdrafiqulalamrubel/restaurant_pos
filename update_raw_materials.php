<?php
$c = file_get_contents('raw_materials.php');

$target = '    if ($action === \'add\') {
        $stmt = $pdo->prepare("INSERT INTO raw_materials (name, unit, unit_price, current_stock, min_stock_level, category, supplier_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST[\'name\'], $_POST[\'unit\'], $_POST[\'unit_price\'], $_POST[\'current_stock\'] ?? 0, $_POST[\'min_stock_level\'] ?? 0, $_POST[\'category\'], $_POST[\'supplier_id\'] ?: null]);
        $success = "Raw material added successfully!";
    } elseif ($action === \'edit\') {
        $stmt = $pdo->prepare("UPDATE raw_materials SET name=?, unit=?, unit_price=?, current_stock=?, min_stock_level=?, category=?, supplier_id=? WHERE id=?");
        $stmt->execute([$_POST[\'name\'], $_POST[\'unit\'], $_POST[\'unit_price\'], $_POST[\'current_stock\'], $_POST[\'min_stock_level\'], $_POST[\'category\'], $_POST[\'supplier_id\'] ?: null, $_POST[\'id\']]);
        $success = "Raw material updated successfully!";
    }';

$rep = '    if ($action === \'add\') {
        $current_stock = $_POST[\'current_stock\'] ?? 0;
        $unit_price = $_POST[\'unit_price\'];
        $supplier_id = $_POST[\'supplier_id\'] ?: null;
        
        $stmt = $pdo->prepare("INSERT INTO raw_materials (name, unit, unit_price, current_stock, min_stock_level, category, supplier_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST[\'name\'], $_POST[\'unit\'], $unit_price, $current_stock, $_POST[\'min_stock_level\'] ?? 0, $_POST[\'category\'], $supplier_id]);
        $rm_id = $pdo->lastInsertId();
        
        if ($current_stock > 0) {
            require_once \'acc_core.php\';
            require_once \'accounting.php\';
            $inv_acc = acc_get_account_by_code(\'1300\');
            $ap_acc = acc_get_account_by_code(\'2000\'); // Assume AP for new stock if supplier exists
            $exp_acc = acc_get_account_by_code(\'5400\'); // Spoilage/Adjustment if no supplier
            
            $credit_acc = $supplier_id ? $ap_acc : $exp_acc;
            
            if ($inv_acc && $credit_acc) {
                $total_value = $current_stock * $unit_price;
                $entries = [
                    [\'account_id\' => $inv_acc[\'id\'], \'debit\' => $total_value, \'credit\' => 0],
                    [\'account_id\' => $credit_acc[\'id\'], \'debit\' => 0, \'credit\' => $total_value]
                ];
                acc_create_journal(date(\'Y-m-d\'), \'RM-ADD-\'.$rm_id, \'Initial Stock for Raw Material: \' . $_POST[\'name\'], $entries, $supplier_id ? \'supplier\' : null, $supplier_id);
            }
        }
        $success = "Raw material added successfully!";
    } elseif ($action === \'edit\') {
        $id = $_POST[\'id\'];
        $new_stock = (float)$_POST[\'current_stock\'];
        $unit_price = (float)$_POST[\'unit_price\'];
        $supplier_id = $_POST[\'supplier_id\'] ?: null;
        
        $old_stmt = $pdo->prepare("SELECT current_stock FROM raw_materials WHERE id = ?");
        $old_stmt->execute([$id]);
        $old_stock = (float)$old_stmt->fetchColumn() ?: 0;
        
        $stmt = $pdo->prepare("UPDATE raw_materials SET name=?, unit=?, unit_price=?, current_stock=?, min_stock_level=?, category=?, supplier_id=? WHERE id=?");
        $stmt->execute([$_POST[\'name\'], $_POST[\'unit\'], $unit_price, $new_stock, $_POST[\'min_stock_level\'], $_POST[\'category\'], $supplier_id, $id]);
        
        $diff = $new_stock - $old_stock;
        if (abs($diff) > 0.01) {
            require_once \'acc_core.php\';
            require_once \'accounting.php\';
            $inv_acc = acc_get_account_by_code(\'1300\');
            $ap_acc = acc_get_account_by_code(\'2000\'); 
            $exp_acc = acc_get_account_by_code(\'5400\'); 
            
            $val = abs($diff) * $unit_price;
            
            if ($inv_acc) {
                $entries = [];
                if ($diff > 0 && ($supplier_id ? $ap_acc : $exp_acc)) {
                    // Increase stock
                    $credit_acc = $supplier_id ? $ap_acc : $exp_acc;
                    $entries[] = [\'account_id\' => $inv_acc[\'id\'], \'debit\' => $val, \'credit\' => 0];
                    $entries[] = [\'account_id\' => $credit_acc[\'id\'], \'debit\' => 0, \'credit\' => $val];
                    $desc = \'Stock Increase for Raw Material: \' . $_POST[\'name\'];
                } elseif ($diff < 0 && $exp_acc) {
                    // Decrease stock (Adjustment / Spoilage)
                    $entries[] = [\'account_id\' => $exp_acc[\'id\'], \'debit\' => $val, \'credit\' => 0];
                    $entries[] = [\'account_id\' => $inv_acc[\'id\'], \'debit\' => 0, \'credit\' => $val];
                    $desc = \'Stock Decrease/Adjustment for Raw Material: \' . $_POST[\'name\'];
                }
                
                if (!empty($entries)) {
                    acc_create_journal(date(\'Y-m-d\'), \'RM-ADJ-\'.$id, $desc, $entries, $supplier_id ? \'supplier\' : null, $supplier_id);
                }
            }
        }
        $success = "Raw material updated successfully!";
    }';

$c = str_replace($target, $rep, $c);
file_put_contents('raw_materials.php', $c);
echo 'Done';
