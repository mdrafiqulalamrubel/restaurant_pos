<?php
// ============================================================
//  src/accounting.php — Accounting Logic (Journals, Ledger)
// ============================================================

/**
 * Get account by code for the current tenant.
 */
function acc_get_account_by_code(string $code): ?array {
    $stmt = db()->prepare("SELECT * FROM acc_accounts WHERE code = ? LIMIT 1");
    $stmt->execute([$code, 1]);
    $res = $stmt->fetch();
    return $res ?: null;
}

/**
 * Record a double-entry journal.
 * $entries = [ ['account_id' => X, 'debit' => Y, 'credit' => Z], ... ]
 */
function acc_create_journal(string $date, string $reference, string $description, array $entries, ?string $contact_type = null, ?int $contact_id = null): bool {
    $tenant_id = 1;
    $branch_id = ($_SESSION['branch_id'] ?? 1);
    $user_id = $_SESSION['user_id'] ?? 0;
    
    // Validate debit == credit
    $total_debit = 0;
    $total_credit = 0;
    foreach ($entries as $e) {
        $total_debit += (float)($e['debit'] ?? 0);
        $total_credit += (float)($e['credit'] ?? 0);
    }
    
    if (abs($total_debit - $total_credit) > 0.01) {
        return false; // Unbalanced journal
    }
    
    $db = db();
    
    try {
        if (!$db->inTransaction()) {
            $db->beginTransaction();
            $commit = true;
        } else {
            $commit = false;
        }
        
        $stmt = $db->prepare("INSERT INTO acc_journals (branch_id, date, reference, description, created_by, contact_type, contact_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$branch_id, $date, $reference, $description, $user_id, $contact_type, $contact_id]);
        $journal_id = $db->lastInsertId();
        
        $entryStmt = $db->prepare("INSERT INTO acc_journal_items (journal_id, account_id, debit, credit) VALUES (?, ?, ?, ?)");
        
        foreach ($entries as $e) {
            $entryStmt->execute([
                $journal_id,
                $e['account_id'],
                $e['debit'] ?? 0,
                $e['credit'] ?? 0
            ]);
        }
        
        if ($commit) {
            $db->commit();
        }
        return true;
    } catch (Exception $ex) {
        if (isset($commit) && $commit) {
            $db->rollBack();
        }
        return false;
    }
}

/**
 * Trigger: Sale
 * Debit Cash/Bank/AR (1000/1100/1200), Credit Sales Revenue (4000)
 * Debit COGS (5000), Credit Inventory (1300)
 */
function acc_record_sale($sale_id, $total, $paid, $payment_method = 'cash', $customer_name = '', $total_cogs = 0, $customer_id = null) {
    $date = date('Y-m-d');
    $revenue_acc = acc_get_account_by_code('4000');
    $cash_acc = acc_get_account_by_code($payment_method == 'bank' ? '1100' : '1000');
    $ar_acc = acc_get_account_by_code('1200'); // Accounts Receivable
    
    $cogs_acc = acc_get_account_by_code('5000');
    $inv_acc = acc_get_account_by_code('1300');
    
    if ($revenue_acc) {
        $entries = [];
        
        // Revenue credit
        $entries[] = ['account_id' => $revenue_acc['id'], 'debit' => 0, 'credit' => $total];
        
        // Cash / AR debits
        $due = $total - $paid;
        if ($paid > 0 && $cash_acc) {
            $entries[] = ['account_id' => $cash_acc['id'], 'debit' => $paid, 'credit' => 0];
        }
        if ($due > 0 && $ar_acc) {
            $entries[] = ['account_id' => $ar_acc['id'], 'debit' => $due, 'credit' => 0];
        }
        
        // COGS and Inventory
        if ($total_cogs > 0 && $cogs_acc && $inv_acc) {
            $entries[] = ['account_id' => $cogs_acc['id'], 'debit' => $total_cogs, 'credit' => 0];
            $entries[] = ['account_id' => $inv_acc['id'], 'debit' => 0, 'credit' => $total_cogs];
        }
        
        acc_create_journal($date, 'INV-' . $sale_id, 'Sale Invoice ' . $sale_id . ($customer_name ? ' to ' . $customer_name : ''), $entries, 'customer', $customer_id);
    }
}

/**
 * Trigger: Purchase
 * Debit Inventory (1300), Credit Cash/Bank/AP (1000/1100/2000)
 */
function acc_record_purchase($purchase_id, $total, $paid, $payment_method = 'cash', $supplier_name = '', $supplier_id = null) {
    $date = date('Y-m-d');
    $inv_acc = acc_get_account_by_code('1300');
    $cash_acc = acc_get_account_by_code($payment_method == 'bank' ? '1100' : '1000');
    $ap_acc = acc_get_account_by_code('2000'); // Accounts Payable
    
    if ($inv_acc) {
        $entries = [];
        
        // Inventory debit
        $entries[] = ['account_id' => $inv_acc['id'], 'debit' => $total, 'credit' => 0];
        
        // Cash / AP credits
        $due = $total - $paid;
        if ($paid > 0 && $cash_acc) {
            $entries[] = ['account_id' => $cash_acc['id'], 'debit' => 0, 'credit' => $paid];
        }
        if ($due > 0 && $ap_acc) {
            $entries[] = ['account_id' => $ap_acc['id'], 'debit' => 0, 'credit' => $due];
        }
        
        acc_create_journal($date, 'PUR-' . $purchase_id, 'Purchase ' . $purchase_id . ($supplier_name ? ' from ' . $supplier_name : ''), $entries, 'supplier', $supplier_id);
    }
}

/**
 * Trigger: Expense
 * Debit General Expense (5400) or specific, Credit Cash/Bank (1000/1100)
 */
function acc_record_expense($expense_id, $amount, $category_name, $payment_method = 'cash') {
    $date = date('Y-m-d');
    $expense_acc = acc_get_account_by_code('5400'); // Default general expense
    // Map category name to specific expense if needed
    if (stripos($category_name, 'salary') !== false) {
        $acc = acc_get_account_by_code('5100');
        if ($acc) $expense_acc = $acc;
    } elseif (stripos($category_name, 'rent') !== false) {
        $acc = acc_get_account_by_code('5200');
        if ($acc) $expense_acc = $acc;
    }
    
    $cash_acc = acc_get_account_by_code($payment_method == 'bank' ? '1100' : '1000');
    
    if ($expense_acc && $cash_acc) {
        acc_create_journal($date, 'EXP-' . $expense_id, 'Expense: ' . $category_name, [
            ['account_id' => $expense_acc['id'], 'debit' => $amount, 'credit' => 0],
            ['account_id' => $cash_acc['id'], 'debit' => 0, 'credit' => $amount]
        ]);
    }
}
