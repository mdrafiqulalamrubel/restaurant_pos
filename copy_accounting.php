<?php
$source_dir = 'f:/xampp82/htdocs/pos/public/';
$dest_dir = 'f:/xampp82/htdocs/restaurant_pos/';

$files = [
    'acc_opening_balances.php',
    'acc_accounts.php',
    'acc_journal.php',
    'acc_journal_add.php',
    'acc_ledger.php',
    'acc_trial_balance.php',
    'acc_pl.php',
    'acc_balance_sheet.php',
    'acc_trading.php',
    'acc_cash_flow.php'
];

foreach ($files as $file) {
    if (file_exists($source_dir . $file)) {
        $content = file_get_contents($source_dir . $file);
        
        // Remove layout logic and replace with header/footer
        $content = preg_replace("/require_once __DIR__ \. '\/\.\.\/src\/core\.php';/", "require_once 'config.php';\nrequire_once 'header.php';", $content);
        
        $content = str_replace("\$user = require_auth('admin');", "if ((\$_SESSION['role'] ?? '') !== 'admin') { die('Access denied'); }", $content);
        $content = str_replace("\$user = require_auth('manager');", "if ((\$_SESSION['role'] ?? '') !== 'admin' && (\$_SESSION['role'] ?? '') !== 'manager') { die('Access denied'); }", $content);
        
        $content = str_replace("ob_start();", "", $content);
        $content = preg_replace("/\\\$content = ob_get_clean\(\);\s*include __DIR__ \. '\/\.\.\/templates\/layout\.php';/", "require_once 'footer.php';", $content);
        
        // Remove tid() and brid() usage
        $content = str_replace("\$tid = tid();", "\$tid = 1;", $content);
        $content = str_replace("tid()", "1", $content);
        $content = str_replace("brid()", "(\$_SESSION['branch_id'] ?? 1)", $content);
        
        file_put_contents($dest_dir . $file, $content);
        echo "Copied $file\n";
    }
}
// Also copy src/accounting.php
$acc_src = 'f:/xampp82/htdocs/pos/src/accounting.php';
if (file_exists($acc_src)) {
    $content = file_get_contents($acc_src);
    $content = str_replace("tid()", "1", $content);
    $content = str_replace("brid()", "(\$_SESSION['branch_id'] ?? 1)", $content);
    // Remove auth() call
    $content = str_replace("\$user_id = auth('id') ?: 0;", "\$user_id = \$_SESSION['user_id'] ?? 0;", $content);
    file_put_contents($dest_dir . 'accounting.php', $content);
    echo "Copied accounting.php\n";
}
