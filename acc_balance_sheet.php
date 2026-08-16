<?php
require_once 'acc_core.php';
require_once 'header.php';
display_flash();
if (($_SESSION['role'] ?? '') !== 'admin') { die('Access denied'); }
$page_title = 'Balance Sheet';

$db = db();
$tid = 1;

$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Function to get balance of account type up to a date
function get_bs_balances($type, $end) {
    global $db, $tid;
    $stmt = $db->prepare("
        SELECT a.code, a.name, SUM(i.debit) as tot_dr, SUM(i.credit) as tot_cr
        FROM acc_accounts a
        LEFT JOIN acc_journal_items i ON a.id = i.account_id
        LEFT JOIN acc_journals j ON i.journal_id = j.id AND j.date <= ?
        WHERE a.type = ? " . acc_branch_sql('j') . "
        GROUP BY a.id, a.code, a.name
    ");
    $stmt->execute([$end, $type]);
    $results = [];
    foreach($stmt->fetchAll() as $row) {
        $bal = 0;
        if ($type == 'Asset') {
            $bal = (float)$row['tot_dr'] - (float)$row['tot_cr'];
        } else {
            $bal = (float)$row['tot_cr'] - (float)$row['tot_dr'];
        }
        if ($bal != 0) {
            $results[] = ['name' => $row['name'], 'bal' => $bal];
        }
    }
    return $results;
}

// Calculate Net Income (Revenue - Expense) up to end_date to add to Retained Earnings
$stmt = $db->prepare("
    SELECT 
        SUM(CASE WHEN a.type='Revenue' THEN i.credit - i.debit ELSE 0 END) as tot_rev,
        SUM(CASE WHEN a.type='Expense' THEN i.debit - i.credit ELSE 0 END) as tot_exp
    FROM acc_journal_items i
    JOIN acc_journals j ON i.journal_id = j.id
    JOIN acc_accounts a ON i.account_id = a.id
    WHERE j.date <= ? " . acc_branch_sql('j') . "
");
$stmt->execute([$end_date]);
$ni = $stmt->fetch();
$net_income = (float)$ni['tot_rev'] - (float)$ni['tot_exp'];


$assets = get_bs_balances('Asset', $end_date);
$liabilities = get_bs_balances('Liability', $end_date);
$equity = get_bs_balances('Equity', $end_date);

// Add Net Income to Equity
if ($net_income != 0) {
    $equity[] = ['name' => 'Current Year Net Income', 'bal' => $net_income];
}

$tot_assets = 0;
foreach($assets as $a) $tot_assets += $a['bal'];

$tot_liabilities = 0;
foreach($liabilities as $l) $tot_liabilities += $l['bal'];

$tot_equity = 0;
foreach($equity as $e) $tot_equity += $e['bal'];


?>
<div class="card no-print" style="margin-bottom:20px;">
    <form method="get" style="display:flex;gap:15px;align-items:flex-end;">
        <div class="form-group" style="margin:0;">
            <label>As Of Date</label>
            <input type="date" name="end_date" value="<?= h($end_date) ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Generate Report</button>
    </form>
</div>

<div class="card form-card" style="padding:30px; max-width:800px; margin:0 auto; position:relative;">
    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm no-print" style="position:absolute; top:20px; right:20px;">🖨️ Print</button>
    <h2 style="text-align:center; margin-top:0;">Balance Sheet</h2>
    <p style="text-align:center; color:#666; margin-bottom:30px;">As of: <?= fmt_date($end_date) ?></p>

    <div class="row">
        <!-- Left Side: Assets -->
        <div class="col-md-6 mb-4">
            <h3 style="border-bottom:2px solid #ddd; padding-bottom:10px; color:#0284c7;">Assets</h3>
            <div class="table-responsive">
                <table class="table table-hover">
                    <?php foreach($assets as $a): ?>
                    <tr>
                        <td style="padding:8px 0;"><?= h($a['name']) ?></td>
                        <td style="text-align:right; padding:8px 0;"><?= money($a['bal']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td style="padding:15px 0; font-weight:bold; font-size:16px;">Total Assets</td>
                        <td style="text-align:right; font-weight:bold; font-size:16px; border-top:2px solid #ddd; border-bottom:4px double #000;"><?= money($tot_assets) ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Right Side: Liabilities & Equity -->
        <div class="col-md-6 mb-4">
            <h3 style="border-bottom:2px solid #ddd; padding-bottom:10px; color:#dc2626;">Liabilities</h3>
            <div class="table-responsive">
                <table class="table table-hover mb-4">
                    <?php foreach($liabilities as $l): ?>
                    <tr>
                        <td style="padding:8px 0;"><?= h($l['name']) ?></td>
                        <td style="text-align:right; padding:8px 0;"><?= money($l['bal']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td style="padding:10px 0; font-weight:bold;">Total Liabilities</td>
                        <td style="text-align:right; font-weight:bold; border-top:1px solid #ddd;"><?= money($tot_liabilities) ?></td>
                    </tr>
                </table>
            </div>

            <h3 style="border-bottom:2px solid #ddd; padding-bottom:10px; color:#059669;">Equity</h3>
            <div class="table-responsive">
                <table class="table table-hover">
                    <?php foreach($equity as $e): ?>
                    <tr>
                        <td style="padding:8px 0;"><?= h($e['name']) ?></td>
                        <td style="text-align:right; padding:8px 0;"><?= money($e['bal']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td style="padding:10px 0; font-weight:bold;">Total Equity</td>
                        <td style="text-align:right; font-weight:bold; border-top:1px solid #ddd;"><?= money($tot_equity) ?></td>
                    </tr>
                    <tr>
                        <td style="padding:15px 0; font-weight:bold; font-size:16px;">Total Liabilities & Equity</td>
                        <td style="text-align:right; font-weight:bold; font-size:16px; border-top:2px solid #ddd; border-bottom:4px double #000;"><?= money($tot_liabilities + $tot_equity) ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
<?php
require_once 'footer.php';
