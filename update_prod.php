<?php
$c = file_get_contents('production.php');
$target = '                // Record production
                $stmt = $pdo->prepare("INSERT INTO productions (recipe_id, quantity_produced, total_cost, production_date, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$recipe_id, $quantity, $total_cost, date(\'Y-m-d\'), $_POST[\'notes\'] ?? \'\', $_SESSION[\'user_id\']]);
                $production_id = $pdo->lastInsertId();
                
                // Add to expenses as production cost
                $stmt = $pdo->prepare("INSERT INTO expenses (expense_date, category, description, amount, payment_method, is_production_cost, production_id) VALUES (?, ?, ?, ?, ?, 1, ?)");
                $stmt->execute([date(\'Y-m-d\'), \'Production Cost\', "Production of {$recipe[\'name\']} x{$quantity}", $total_cost, \'cash\', $production_id]);
                
                // Add cash transaction
                $stmt = $pdo->prepare("INSERT INTO cash_transactions (transaction_type, amount, category, description, reference_id, reference_type, transaction_date, created_by) VALUES (\'expense\', ?, \'Production\', ?, ?, \'production\', ?, ?)");
                $stmt->execute([$total_cost, "Production cost for {$recipe[\'name\']} x{$quantity}", $production_id, date(\'Y-m-d\'), $_SESSION[\'user_id\']]);
                
                $pdo->commit();';

$rep = '                // Record production
                $stmt = $pdo->prepare("INSERT INTO productions (recipe_id, quantity_produced, total_cost, production_date, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$recipe_id, $quantity, $total_cost, date(\'Y-m-d\'), $_POST[\'notes\'] ?? \'\', $_SESSION[\'user_id\']]);
                $production_id = $pdo->lastInsertId();
                
                // Increase finished goods stock if associated with an item
                if (!empty($recipe[\'item_id\'])) {
                    $stmt = $pdo->prepare("UPDATE items SET current_stock = current_stock + ? WHERE id = ?");
                    $stmt->execute([$quantity, $recipe[\'item_id\']]);
                }
                
                // Note: No accounting journal is generated because this is simply a transfer of value from Raw Materials to Finished Goods within the same Inventory Asset account.
                
                $pdo->commit();';

$c = str_replace($target, $rep, $c);
file_put_contents('production.php', $c);
echo 'done';
