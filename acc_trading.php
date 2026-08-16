<?php
require_once 'acc_core.php';
require_once 'header.php';
display_flash();
if (($_SESSION['role'] ?? '') !== 'admin') { die('Access denied'); }
$page_title = 'Trading Account';

$db = db();
$tid = 1;

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

function get_acct_bal($code, $start, $end) {
    global $db, $tid;
    $stmt = $db->prepare("
        SELECT SUM(i.credit) - SUM(i.debit) as bal
        FROM acc_journal_items i
        JOIN acc_journals j ON i.journal_id = j.id
        JOIN acc_accounts a ON i.account_id = a.id
        WHERE a.code = ? AND j.date >= ? AND j.date <= ? " . acc_branch_sql('j') . "
    ");
    $stmt->execute([$code, $start, $end]);
    return (float)$stmt->fetchColumn();
}

// Sales Revenue (4000)
$sales = get_acct_bal('4000', $start_date, $end_date);

// COGS (5000) - Expense, so debit - credit
$stmt = $db->prepare("
    SELECT SUM(i.debit) - SUM(i.credit) as bal
    FROM acc_journal_items i
    JOIN acc_journals j ON i.journal_id = j.id
    JOIN acc_accounts a ON i.account_id = a.id
    WHERE a.code = '5000' AND j.date >= ? AND j.date <= ? " . acc_branch_sql('j') . "
");
$stmt->execute([$start_date, $end_date]);
$cogs = (float)$stmt->fetchColumn();

$gross_profit = $sales - $cogs;


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
        <button type="submit" class="btn btn-primary">Generate</button>
    </form>
</div>

<div class="card" style="padding:30px; max-width:600px; margin:0 auto; position:relative;">
    <button onclick="window.print()" class="btn btn-outline btn-sm no-print" style="position:absolute; top:20px; right:20px;">🖨️ Print</button>
    <h2 style="text-align:center; margin-top:0;">Trading Account</h2>
    <p style="text-align:center; color:#666; margin-bottom:30px;">For the period: <?= fmt_date($start_date) ?> to <?= fmt_date($end_date) ?></p>

    <table style="width:100%; border-collapse:collapse; font-size:16px;">
        <tr>
            <td style="padding:10px 0;">Sales Revenue</td>
            <td style="text-align:right; padding:10px 0;"><?= money($sales) ?></td>
        </tr>
        <tr>
            <td style="padding:10px 0; border-bottom:1px solid #ddd;">Less: Cost of Goods Sold</td>
            <td style="text-align:right; padding:10px 0; border-bottom:1px solid #ddd;"><?= money($cogs) ?></td>
        </tr>
        <tr style="background:#f0f9ff;">
            <td style="padding:20px 10px; font-weight:bold; font-size:18px;">Gross Profit (Transferred to P&L)</td>
            <td style="text-align:right; padding:20px 10px; font-weight:bold; font-size:18px; color:<?= $gross_profit >= 0 ? '#059669' : '#dc2626' ?>; border-bottom:4px double <?= $gross_profit >= 0 ? '#059669' : '#dc2626' ?>;"><?= money($gross_profit) ?></td>
        </tr>
    </table>
</div>

<?php
require_once 'footer.php';
