<?php
$dir = 'f:/xampp82/htdocs/restaurant_pos/';

function replace_sql($file, $search, $replace) {
    global $dir;
    $path = $dir . $file;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        if (is_array($search)) {
            foreach ($search as $i => $s) {
                $content = str_replace($s, $replace[$i], $content);
            }
        } else {
            $content = str_replace($search, $replace, $content);
        }
        file_put_contents($path, $content);
        echo "Injected branch logic to $file\n";
    }
}

// acc_trial_balance.php
replace_sql('acc_trial_balance.php', 
    'AND j.date <= ?', 
    'AND j.date <= ? " . acc_branch_sql(\'j\') . "'
);

// acc_pl.php
replace_sql('acc_pl.php', [
    'WHERE a.type = ? AND j.date >= ? AND j.date <= ?',
    'WHERE a.type = \'Expense\' AND j.date >= ? AND j.date <= ?',
    'WHERE a.type = \'Revenue\' AND j.date >= ? AND j.date <= ?'
], [
    'WHERE a.type = ? AND j.date >= ? AND j.date <= ? " . acc_branch_sql(\'j\') . "',
    'WHERE a.type = \'Expense\' AND j.date >= ? AND j.date <= ? " . acc_branch_sql(\'j\') . "',
    'WHERE a.type = \'Revenue\' AND j.date >= ? AND j.date <= ? " . acc_branch_sql(\'j\') . "'
]);

// acc_trading.php
replace_sql('acc_trading.php', [
    'WHERE a.code = ? AND j.date >= ? AND j.date <= ?',
    'WHERE a.code = \'5000\' AND j.date >= ? AND j.date <= ?'
], [
    'WHERE a.code = ? AND j.date >= ? AND j.date <= ? " . acc_branch_sql(\'j\') . "',
    'WHERE a.code = \'5000\' AND j.date >= ? AND j.date <= ? " . acc_branch_sql(\'j\') . "'
]);

// acc_balance_sheet.php
replace_sql('acc_balance_sheet.php', [
    'WHERE a.type = ?',
    'WHERE j.date <= ?'
], [
    'WHERE a.type = ? " . acc_branch_sql(\'j\') . "',
    'WHERE j.date <= ? " . acc_branch_sql(\'j\') . "'
]);

// acc_cash_flow.php
replace_sql('acc_cash_flow.php', [
    'j.date >= ? AND j.date <= ?',
    'j.date < ?'
], [
    'j.date >= ? AND j.date <= ? " . acc_branch_sql(\'j\') . "',
    'j.date < ? " . acc_branch_sql(\'j\') . "'
]);

// acc_journal.php
replace_sql('acc_journal.php', 
    '$where = "WHERE 1=1";', 
    '$where = "WHERE 1=1 " . acc_branch_sql(\'acc_journals\');'
);
$journal_path = $dir . 'acc_journal.php';
$j_content = file_get_contents($journal_path);
$j_content = str_replace('acc_branch_sql(\'acc_journals\')', 'acc_branch_sql(\'\')', $j_content); // Since acc_journals is the FROM table and no alias is used initially, actually 'acc_journals' is fine if we use the table name. Or no alias.
// Wait, if no alias, we can just do str_replace("acc_branch_sql('')", "acc_branch_sql('acc_journals')", ... 
// Let's just fix it properly in the script.
file_put_contents($journal_path, $j_content);

// acc_ledger.php
replace_sql('acc_ledger.php', [
    'WHERE i.account_id = ? AND j.date < ?',
    'WHERE i.account_id = ? AND j.date >= ? AND j.date <= ?'
], [
    'WHERE i.account_id = ? AND j.date < ? " . acc_branch_sql(\'j\') . "',
    'WHERE i.account_id = ? AND j.date >= ? AND j.date <= ? " . acc_branch_sql(\'j\') . "'
]);

// acc_accounts.php
replace_sql('acc_accounts.php', 
    'WHERE (j.reference LIKE \'OB-%\' OR j.reference = \'OPENING-BAL\')',
    'WHERE (j.reference LIKE \'OB-%\' OR j.reference = \'OPENING-BAL\') " . acc_branch_sql(\'j\') . "'
);
