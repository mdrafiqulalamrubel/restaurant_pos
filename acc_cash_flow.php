<?php
require_once 'acc_core.php';
require_once 'header.php';
display_flash();
if (($_SESSION['role'] ?? '') !== 'admin') { die('Access denied'); }
$page_title = 'Cash Flow Statement';

$db = db();
$tid = 1;

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Get all Cash/Bank accounts
$cashSt = $db->prepare("SELECT id FROM acc_accounts WHERE code IN ('1000', '1100')");
$cashSt->execute([]);
$cash_acc_ids = $cashSt->fetchAll(PDO::FETCH_COLUMN);

if (empty($cash_acc_ids)) $cash_acc_ids = [0];
$in_ids = implode(',', $cash_acc_ids);

// Cash In (Debits to cash accounts)
$stmtIn = $db->prepare("
    SELECT j.date, j.reference, j.description, i.debit as amount
    FROM acc_journal_items i
    JOIN acc_journals j ON i.journal_id = j.id
    WHERE i.account_id IN ($in_ids) AND i.debit > 0 AND j.date >= ? AND j.date <= ? " . acc_branch_sql('j') . "
    ORDER BY j.date ASC
");
$stmtIn->execute([$start_date, $end_date]);
$cash_in = $stmtIn->fetchAll();

// Cash Out (Credits to cash accounts)
$stmtOut = $db->prepare("
    SELECT j.date, j.reference, j.description, i.credit as amount
    FROM acc_journal_items i
    JOIN acc_journals j ON i.journal_id = j.id
    WHERE i.account_id IN ($in_ids) AND i.credit > 0 AND j.date >= ? AND j.date <= ? " . acc_branch_sql('j') . "
    ORDER BY j.date ASC
");
$stmtOut->execute([$start_date, $end_date]);
$cash_out = $stmtOut->fetchAll();

$tot_in = 0;
foreach($cash_in as $c) $tot_in += $c['amount'];

$tot_out = 0;
foreach($cash_out as $c) $tot_out += $c['amount'];

$net_cash = $tot_in - $tot_out;


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
<div class="card form-card" style="padding:30px; max-width:800px; margin:0 auto; position:relative;">
    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm no-print" style="position:absolute; top:20px; right:20px;">🖨️ Print</button>
    <h2 style="text-align:center; margin-top:0;">Cash Flow Statement</h2>
    <p style="text-align:center; color:#666; margin-bottom:30px;">For the period: <?= fmt_date($start_date) ?> to <?= fmt_date($end_date) ?></p>

    <h3 style="color:#059669; border-bottom:2px solid #ddd; padding-bottom:5px;">Cash Inflows (Receipts)</h3>
    <div class="table-responsive">
        <table class="table table-hover mb-4">
            <?php foreach($cash_in as $c): ?>
            <tr>
                <td style="padding:8px 0;"><?= fmt_date($c['date']) ?> - <?= h($c['description']) ?> (<?= h($c['reference']) ?>)</td>
                <td style="text-align:right; padding:8px 0;"><?= money($c['amount']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($cash_in)): ?><tr><td colspan="2" style="padding:8px 0; color:#999;">No cash inflows.</td></tr><?php endif; ?>
            <tr>
                <td style="padding:10px 0; font-weight:bold;">Total Cash Inflow</td>
                <td style="text-align:right; font-weight:bold; border-top:1px solid #ddd;"><?= money($tot_in) ?></td>
            </tr>
        </table>
    </div>

    <h3 style="color:#dc2626; border-bottom:2px solid #ddd; padding-bottom:5px;">Cash Outflows (Payments)</h3>
    <div class="table-responsive">
        <table class="table table-hover mb-4">
            <?php foreach($cash_out as $c): ?>
            <tr>
                <td style="padding:8px 0;"><?= fmt_date($c['date']) ?> - <?= h($c['description']) ?> (<?= h($c['reference']) ?>)</td>
                <td style="text-align:right; padding:8px 0;"><?= money($c['amount']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($cash_out)): ?><tr><td colspan="2" style="padding:8px 0; color:#999;">No cash outflows.</td></tr><?php endif; ?>
            <tr>
                <td style="padding:10px 0; font-weight:bold;">Total Cash Outflow</td>
                <td style="text-align:right; font-weight:bold; border-top:1px solid #ddd;"><?= money($tot_out) ?></td>
            </tr>
        </table>
    </div>

    <div class="table-responsive mt-4">
        <table class="table">
            <tr style="background:<?= $net_cash >= 0 ? '#ecfdf5' : '#fef2f2' ?>;">
                <td style="padding:20px 10px; font-weight:bold; font-size:18px;">Net Cash Flow</td>
                <td style="text-align:right; padding:20px 10px; font-weight:bold; font-size:18px; color:<?= $net_cash >= 0 ? '#059669' : '#dc2626' ?>; border-bottom:4px double <?= $net_cash >= 0 ? '#059669' : '#dc2626' ?>;"><?= money($net_cash) ?></td>
            </tr>
        </table>
    </div>
</div>

<?php
require_once 'footer.php';
