<?php
require_once 'acc_core.php';
require_once 'header.php';
display_flash();
if (($_SESSION['role'] ?? '') !== 'admin') { die('Access denied'); }
$page_title = 'Profit & Loss Statement';

$db = db();
$tid = 1;

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Function to get balance for an account or group of accounts in a date range
function get_balance_sum($type, $start, $end) {
    global $db, $tid;
    $stmt = $db->prepare("
        SELECT SUM(i.credit) - SUM(i.debit) as bal
        FROM acc_journal_items i
        JOIN acc_journals j ON i.journal_id = j.id
        JOIN acc_accounts a ON i.account_id = a.id
        WHERE a.type = ? AND j.date >= ? AND j.date <= ? " . acc_branch_sql('j') . "
    ");
    $stmt->execute([$type, $start, $end]);
    return (float)$stmt->fetchColumn();
}

// For expenses, we calculate Debit - Credit
function get_expense_sum($start, $end) {
    global $db, $tid;
    $stmt = $db->prepare("
        SELECT a.code, a.name, SUM(i.debit) - SUM(i.credit) as bal
        FROM acc_journal_items i
        JOIN acc_journals j ON i.journal_id = j.id
        JOIN acc_accounts a ON i.account_id = a.id
        WHERE a.type = 'Expense' AND j.date >= ? AND j.date <= ? " . acc_branch_sql('j') . "
        GROUP BY a.id, a.code, a.name
        HAVING bal != 0
        ORDER BY a.code
    ");
    $stmt->execute([$start, $end]);
    return $stmt->fetchAll();
}

function get_revenue_sum($start, $end) {
    global $db, $tid;
    $stmt = $db->prepare("
        SELECT a.code, a.name, SUM(i.credit) - SUM(i.debit) as bal
        FROM acc_journal_items i
        JOIN acc_journals j ON i.journal_id = j.id
        JOIN acc_accounts a ON i.account_id = a.id
        WHERE a.type = 'Revenue' AND j.date >= ? AND j.date <= ? " . acc_branch_sql('j') . "
        GROUP BY a.id, a.code, a.name
        HAVING bal != 0
        ORDER BY a.code
    ");
    $stmt->execute([$start, $end]);
    return $stmt->fetchAll();
}

$revenues = get_revenue_sum($start_date, $end_date);
$expenses = get_expense_sum($start_date, $end_date);

$tot_rev = 0;
foreach($revenues as $r) $tot_rev += $r['bal'];

$tot_cogs = 0;
$tot_exp = 0;
$cogs_list = [];
$exp_list = [];

foreach($expenses as $e) {
    // Treat COGS (5000) separately for Gross Profit
    if ($e['code'] == '5000') {
        $tot_cogs += $e['bal'];
        $cogs_list[] = $e;
    } else {
        $tot_exp += $e['bal'];
        $exp_list[] = $e;
    }
}

$gross_profit = $tot_rev - $tot_cogs;
$net_profit = $gross_profit - $tot_exp;


?>
<div class="card no-print" style="margin-bottom:20px;">
    <form method="get" style="display:flex;gap:15px;align-items:flex-end;">
        <div class="form-group" style="margin:0;">
            <label>Start Date</label>
            <input type="date" name="start_date" value="<?= h($start_date) ?>" required>
        </div>
        <div class="form-group" style="margin:0;">
            <label>End Date</label>
            <input type="date" name="end_date" value="<?= h($end_date) ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Generate Report</button>
    </form>
</div>

<div class="card" style="padding:30px; max-width:800px; margin:0 auto; position:relative;">
    <button onclick="window.print()" class="btn btn-outline btn-sm no-print" style="position:absolute; top:20px; right:20px;">🖨️ Print</button>
    <h2 style="text-align:center; margin-top:0;">Profit & Loss Statement</h2>
    <p style="text-align:center; color:#666; margin-bottom:30px;">For the period: <?= fmt_date($start_date) ?> to <?= fmt_date($end_date) ?></p>

    <table style="width:100%; border-collapse:collapse; font-size:15px;">
        <!-- REVENUE -->
        <tr>
            <td colspan="2" style="font-weight:bold; font-size:16px; padding:10px 0; border-bottom:2px solid #ddd; color:#0284c7;">Revenues</td>
        </tr>
        <?php foreach($revenues as $r): ?>
        <tr>
            <td style="padding:8px 0; padding-left:20px;"><?= h($r['name']) ?></td>
            <td style="text-align:right; padding:8px 0;"><?= money($r['bal']) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr>
            <td style="padding:10px 0; font-weight:bold;">Total Revenue</td>
            <td style="text-align:right; font-weight:bold; border-top:1px solid #ddd;"><?= money($tot_rev) ?></td>
        </tr>

        <!-- COGS -->
        <tr>
            <td colspan="2" style="font-weight:bold; font-size:16px; padding:10px 0; border-bottom:2px solid #ddd; color:#ea580c; margin-top:20px;">Cost of Goods Sold (COGS)</td>
        </tr>
        <?php foreach($cogs_list as $e): ?>
        <tr>
            <td style="padding:8px 0; padding-left:20px;"><?= h($e['name']) ?></td>
            <td style="text-align:right; padding:8px 0;"><?= money($e['bal']) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr>
            <td style="padding:10px 0; font-weight:bold;">Total COGS</td>
            <td style="text-align:right; font-weight:bold; border-top:1px solid #ddd;"><?= money($tot_cogs) ?></td>
        </tr>

        <!-- GROSS PROFIT -->
        <tr style="background:#f0f9ff;">
            <td style="padding:15px 10px; font-weight:bold; font-size:16px;">Gross Profit</td>
            <td style="text-align:right; padding:15px 10px; font-weight:bold; font-size:16px; color:<?= $gross_profit >= 0 ? 'green' : 'red' ?>"><?= money($gross_profit) ?></td>
        </tr>

        <!-- OPERATING EXPENSES -->
        <tr>
            <td colspan="2" style="font-weight:bold; font-size:16px; padding:10px 0; border-bottom:2px solid #ddd; color:#dc2626; margin-top:20px;">Operating Expenses</td>
        </tr>
        <?php foreach($exp_list as $e): ?>
        <tr>
            <td style="padding:8px 0; padding-left:20px;"><?= h($e['name']) ?></td>
            <td style="text-align:right; padding:8px 0;"><?= money($e['bal']) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr>
            <td style="padding:10px 0; font-weight:bold;">Total Operating Expenses</td>
            <td style="text-align:right; font-weight:bold; border-top:1px solid #ddd;"><?= money($tot_exp) ?></td>
        </tr>

        <!-- NET PROFIT -->
        <tr style="background:<?= $net_profit >= 0 ? '#ecfdf5' : '#fef2f2' ?>;">
            <td style="padding:20px 10px; font-weight:bold; font-size:18px;">Net Profit (Loss)</td>
            <td style="text-align:right; padding:20px 10px; font-weight:bold; font-size:18px; color:<?= $net_profit >= 0 ? '#059669' : '#dc2626' ?>; border-bottom:4px double <?= $net_profit >= 0 ? '#059669' : '#dc2626' ?>;"><?= money($net_profit) ?></td>
        </tr>
    </table>
</div>

<?php
require_once 'footer.php';
