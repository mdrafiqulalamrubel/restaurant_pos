<?php
$c = file_get_contents('suppliers.php');
$target = '        if (isset($_POST[\'supplier_id\']) && $_POST[\'supplier_id\'] > 0) {
            $stmt = $pdo->prepare("UPDATE manufacturers SET name=?, contact_person=?, phone=?, email=?, address=?, opening_balance=? WHERE id=?");
            $stmt->execute([$name, $contact_person, $phone, $email, $address, $opening_balance, $_POST[\'supplier_id\']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO manufacturers (name, contact_person, phone, email, address, opening_balance) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$name, $contact_person, $phone, $email, $address, $opening_balance]);
        }';
$rep = '        if (isset($_POST[\'supplier_id\']) && $_POST[\'supplier_id\'] > 0) {
            $supplier_id = $_POST[\'supplier_id\'];
            $old_stmt = $pdo->prepare("SELECT opening_balance FROM manufacturers WHERE id = ?");
            $old_stmt->execute([$supplier_id]);
            $old_bal = $old_stmt->fetchColumn() ?: 0;
            
            $stmt = $pdo->prepare("UPDATE manufacturers SET name=?, contact_person=?, phone=?, email=?, address=?, opening_balance=? WHERE id=?");
            $stmt->execute([$name, $contact_person, $phone, $email, $address, $opening_balance, $supplier_id]);
            
            $diff = $opening_balance - $old_bal;
            if ($diff != 0) {
                require_once \'acc_core.php\';
                require_once \'accounting.php\';
                $ap_acc = acc_get_account_by_code(\'2000\');
                $eq_acc = acc_get_account_by_code(\'3900\');
                if ($ap_acc && $eq_acc) {
                    $entries = [];
                    if ($diff > 0) {
                        $entries[] = [\'account_id\' => $eq_acc[\'id\'], \'debit\' => $diff, \'credit\' => 0];
                        $entries[] = [\'account_id\' => $ap_acc[\'id\'], \'debit\' => 0, \'credit\' => $diff];
                    } else {
                        $entries[] = [\'account_id\' => $ap_acc[\'id\'], \'debit\' => abs($diff), \'credit\' => 0];
                        $entries[] = [\'account_id\' => $eq_acc[\'id\'], \'debit\' => 0, \'credit\' => abs($diff)];
                    }
                    acc_create_journal(date(\'Y-m-d\'), \'OB-SUP-\'.$supplier_id, \'Opening Balance Adjustment\', $entries, \'supplier\', $supplier_id);
                }
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO manufacturers (name, contact_person, phone, email, address, opening_balance) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$name, $contact_person, $phone, $email, $address, $opening_balance]);
            $supplier_id = $pdo->lastInsertId();
            
            if ($opening_balance > 0) {
                require_once \'acc_core.php\';
                require_once \'accounting.php\';
                $ap_acc = acc_get_account_by_code(\'2000\');
                $eq_acc = acc_get_account_by_code(\'3900\');
                if ($ap_acc && $eq_acc) {
                    $entries = [
                        [\'account_id\' => $eq_acc[\'id\'], \'debit\' => $opening_balance, \'credit\' => 0],
                        [\'account_id\' => $ap_acc[\'id\'], \'debit\' => 0, \'credit\' => $opening_balance]
                    ];
                    acc_create_journal(date(\'Y-m-d\'), \'OB-SUP-\'.$supplier_id, \'Opening Balance\', $entries, \'supplier\', $supplier_id);
                }
            }
        }';
$c = str_replace($target, $rep, $c);
file_put_contents('suppliers.php', $c);
echo 'Done';
