<?php
$dir = 'f:/xampp82/htdocs/restaurant_pos/';

function replace_in_file($filename, $search, $replace) {
    global $dir;
    $path = $dir . $filename;
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
        echo "Fixed $filename\n";
    }
}

// acc_accounts.php
replace_in_file('acc_accounts.php', [
    'WHERE id=? AND tenant_id=?',
    'execute([$code, $name, $type, $id, $tid])',
    "WHERE tenant_id=? AND code='3000'",
    'execute([$tid])',
    'INSERT INTO acc_accounts (tenant_id, code, name, type) VALUES (?, ?, ?, ?)',
    'execute([$tid, $code, $name, $type])',
    'WHERE id=? AND tenant_id=? AND is_default=0',
    'execute([$id, $tid])',
    'WHERE tenant_id=? ORDER BY type, code ASC',
    'WHERE j.tenant_id=? AND (j.reference'
], [
    'WHERE id=?',
    'execute([$code, $name, $type, $id])',
    "WHERE code='3000'",
    'execute([])',
    'INSERT INTO acc_accounts (code, name, type) VALUES (?, ?, ?)',
    'execute([$code, $name, $type])',
    'WHERE id=? AND is_default=0',
    'execute([$id])',
    'ORDER BY type, code ASC',
    'WHERE (j.reference'
]);

// acc_balance_sheet.php
replace_in_file('acc_balance_sheet.php', [
    'WHERE a.tenant_id = ? AND a.type = ?',
    'execute([$tid, $type])',
    'WHERE a.tenant_id = ? AND j.date <= ?',
    'execute([$tid, $date])'
], [
    'WHERE a.type = ?',
    'execute([$type])',
    'WHERE j.date <= ?',
    'execute([$date])'
]);

// acc_cash_flow.php
replace_in_file('acc_cash_flow.php', [
    "WHERE tenant_id = ? AND code IN ('1000', '1100')",
    'execute([$tid])',
    'AND j.tenant_id = ?',
    '[$tid, $start_date, $end_date]',
    '[$tid, $start_date]'
], [
    "WHERE code IN ('1000', '1100')",
    'execute([])',
    '',
    '[$start_date, $end_date]',
    '[$start_date]'
]);

// acc_journal.php
replace_in_file('acc_journal.php', [
    '$where = "WHERE tenant_id = :tid";',
    '$params = [\':tid\' => $tid];'
], [
    '$where = "WHERE 1=1";',
    '$params = [];'
]);

// acc_journal_add.php
replace_in_file('acc_journal_add.php', [
    'WHERE tenant_id = ? ORDER BY type, code',
    'execute([$tid])',
    'WHERE tenant_id = ? ORDER BY name',
    'execute([$tid])'
], [
    'ORDER BY type, code',
    'execute([])',
    'ORDER BY name',
    'execute([])'
]);

// acc_ledger.php
replace_in_file('acc_ledger.php', [
    'WHERE tenant_id = ? ORDER BY type, code',
    'execute([$tid])',
    'WHERE id=? AND tenant_id=?',
    'execute([$account_id, $tid])',
    'AND a.tenant_id=?'
], [
    'ORDER BY type, code',
    'execute([])',
    'WHERE id=?',
    'execute([$account_id])',
    ''
]);

// acc_opening_balances.php
replace_in_file('acc_opening_balances.php', [
    'WHERE tenant_id=?',
    'execute([$tid])',
    'WHERE tenant_id = ? ORDER BY type, code'
], [
    '',
    'execute([])',
    'ORDER BY type, code'
]);

// acc_pl.php
replace_in_file('acc_pl.php', [
    'WHERE a.tenant_id = ? AND a.type = ? AND j.date >= ? AND j.date <= ?',
    'execute([$tid, \'Revenue\', $start_date, $end_date])',
    'WHERE a.tenant_id = ? AND a.type = \'Expense\' AND j.date >= ? AND j.date <= ?',
    'execute([$tid, $start_date, $end_date])',
    'WHERE a.tenant_id = ? AND a.type = \'Revenue\' AND j.date >= ? AND j.date <= ?'
], [
    'WHERE a.type = ? AND j.date >= ? AND j.date <= ?',
    'execute([\'Revenue\', $start_date, $end_date])',
    'WHERE a.type = \'Expense\' AND j.date >= ? AND j.date <= ?',
    'execute([$start_date, $end_date])',
    'WHERE a.type = \'Revenue\' AND j.date >= ? AND j.date <= ?'
]);

// acc_trading.php
replace_in_file('acc_trading.php', [
    'WHERE a.tenant_id = ? AND a.code = ? AND j.date >= ? AND j.date <= ?',
    'execute([$tid, \'4000\', $start_date, $end_date])',
    'WHERE a.tenant_id = ? AND a.code = \'5000\' AND j.date >= ? AND j.date <= ?',
    'execute([$tid, $start_date, $end_date])'
], [
    'WHERE a.code = ? AND j.date >= ? AND j.date <= ?',
    'execute([\'4000\', $start_date, $end_date])',
    'WHERE a.code = \'5000\' AND j.date >= ? AND j.date <= ?',
    'execute([$start_date, $end_date])'
]);

// acc_trial_balance.php
replace_in_file('acc_trial_balance.php', [
    'WHERE a.tenant_id = ?',
    'execute([$tid])'
], [
    '',
    'execute([])'
]);

// accounting.php
replace_in_file('accounting.php', [
    'WHERE code = ? AND tenant_id = ?',
    'execute([$code, $tenant_id])',
    'INSERT INTO acc_journals (tenant_id, branch_id, date, reference, description, created_by, contact_type, contact_id)',
    'VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
    'execute([$tenant_id, $branch_id, $date, $reference, $description, $user_id, $contact_type, $contact_id])'
], [
    'WHERE code = ?',
    'execute([$code])',
    'INSERT INTO acc_journals (branch_id, date, reference, description, created_by, contact_type, contact_id)',
    'VALUES (?, ?, ?, ?, ?, ?, ?)',
    'execute([$branch_id, $date, $reference, $description, $user_id, $contact_type, $contact_id])'
]);
