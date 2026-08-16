<?php
// ============================================================
//  src/accounting.php — Accounting Logic (Journals, Ledger)
// ============================================================
require_once 'acc_core.php';

/**
 * Get account by code for the current tenant.
 */
function acc_get_account_by_code(string $code): ?array {
    $stmt = db()->prepare("SELECT * FROM acc_accounts WHERE code = ? LIMIT 1");
    $stmt->execute([$code]);
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
 * Trigger: Sales Return
 * Debit Sales Revenue (4000), Credit Cash/AR (1000/1200)
 * Debit Inventory (1300), Credit COGS (5000)
 */
function acc_record_sales_return($return_id, $sale_id, $refund_amount, $payment_method = 'cash', $customer_name = '', $total_cogs_returned = 0, $customer_id = null) {
    $date = date('Y-m-d');
    $sales_acc = acc_get_account_by_code('4000'); // Sales Revenue
    $cash_acc = acc_get_account_by_code($payment_method == 'bank' ? '1100' : '1000');
    $cogs_acc = acc_get_account_by_code('5000');
    $inv_acc = acc_get_account_by_code('1300');
    
    $entries = [];
    
    // Reverse Income
    if ($sales_acc && $cash_acc) {
        $entries[] = ['account_id' => $sales_acc['id'], 'debit' => $refund_amount, 'credit' => 0]; // Debit Sales
        $entries[] = ['account_id' => $cash_acc['id'], 'debit' => 0, 'credit' => $refund_amount]; // Credit Cash
    }
    
    // Reverse COGS
    if ($total_cogs_returned > 0 && $cogs_acc && $inv_acc) {
        $entries[] = ['account_id' => $inv_acc['id'], 'debit' => $total_cogs_returned, 'credit' => 0]; // Debit Inventory
        $entries[] = ['account_id' => $cogs_acc['id'], 'debit' => 0, 'credit' => $total_cogs_returned]; // Credit COGS
    }
    
    if (!empty($entries)) {
        acc_create_journal($date, 'SRET-' . $return_id, 'Sales Return #' . $return_id . ' for Sale ' . $sale_id, $entries, 'customer', $customer_id);
    }
}

/**
 * Trigger: Purchase Return
 * Debit Cash/AP (1000/2000), Credit Inventory (1300)
 */
function acc_record_purchase_return($return_id, $purchase_id, $refund_amount, $payment_method = 'cash', $supplier_name = '', $supplier_id = null) {
    $date = date('Y-m-d');
    $inv_acc = acc_get_account_by_code('1300');
    $cash_acc = acc_get_account_by_code($payment_method == 'bank' ? '1100' : '1000');
    
    if ($inv_acc && $cash_acc) {
        $entries = [];
        $entries[] = ['account_id' => $cash_acc['id'], 'debit' => $refund_amount, 'credit' => 0]; // Debit Cash
        $entries[] = ['account_id' => $inv_acc['id'], 'debit' => 0, 'credit' => $refund_amount]; // Credit Inventory
        
        acc_create_journal($date, 'PRET-' . $return_id, 'Purchase Return #' . $return_id . ' for Purchase ' . $purchase_id, $entries, 'supplier', $supplier_id);
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

/**
 * Trigger: Cash Transaction
 */
function acc_record_cash_transaction($tx_id, $type, $amount, $description) {
    $date = date('Y-m-d');
    $cash = acc_get_account_by_code('1000');
    $bank = acc_get_account_by_code('1100');
    $other_income = acc_get_account_by_code('4200');
    $general_exp = acc_get_account_by_code('5400');
    
    if (!$cash || !$bank || !$other_income || !$general_exp) return;

    $entries = [];
    if ($type === 'income') {
        $entries[] = ['account_id' => $cash['id'], 'debit' => $amount, 'credit' => 0];
        $entries[] = ['account_id' => $other_income['id'], 'debit' => 0, 'credit' => $amount];
    } elseif ($type === 'expense') {
        $entries[] = ['account_id' => $general_exp['id'], 'debit' => $amount, 'credit' => 0];
        $entries[] = ['account_id' => $cash['id'], 'debit' => 0, 'credit' => $amount];
    } elseif ($type === 'deposit') {
        $entries[] = ['account_id' => $bank['id'], 'debit' => $amount, 'credit' => 0];
        $entries[] = ['account_id' => $cash['id'], 'debit' => 0, 'credit' => $amount];
    } elseif ($type === 'withdraw') {
        $entries[] = ['account_id' => $cash['id'], 'debit' => $amount, 'credit' => 0];
        $entries[] = ['account_id' => $bank['id'], 'debit' => 0, 'credit' => $amount];
    }
    
    if (!empty($entries)) {
        acc_create_journal($date, 'CASH-' . $tx_id, $description, $entries);
    }
}

/**
 * Get the true balance of an account
 */
function acc_get_account_balance($account_id) {
    $stmt = db()->prepare("
        SELECT COALESCE(SUM(debit) - SUM(credit), 0) as bal 
        FROM acc_journal_items ji 
        JOIN acc_journals j ON ji.journal_id = j.id
        WHERE ji.account_id = ? " . acc_branch_sql('j'));
    $stmt->execute([$account_id]);
    $bal = $stmt->fetchColumn();
    
    // For liability, equity, revenue, normal balance is credit
    $acc = db()->prepare("SELECT type FROM acc_accounts WHERE id = ?");
    $acc->execute([$account_id]);
    $type = $acc->fetchColumn();
    
    if (in_array($type, ['Liability', 'Equity', 'Revenue'])) {
        $bal = -$bal;
    }
    
    return $bal;
}
