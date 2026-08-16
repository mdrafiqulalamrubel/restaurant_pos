<?php
require_once 'acc_core.php';
require_once 'header.php';
display_flash();
if (($_SESSION['role'] ?? '') !== 'admin') { die('Access denied'); }
$page_title = 'Trial Balance';

$db = db();
$tid = 1;

$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Fetch all accounts and sum their balances up to end_date
$stmt = $db->prepare("
    SELECT a.id, a.code, a.name, a.type, 
           SUM(i.debit) as tot_debit, 
           SUM(i.credit) as tot_credit
    FROM acc_accounts a
    LEFT JOIN acc_journal_items i ON a.id = i.account_id
    LEFT JOIN acc_journals j ON i.journal_id = j.id AND j.date <= ? " . acc_branch_sql('j') . "
    
    GROUP BY a.id, a.code, a.name, a.type
    ORDER BY a.type, a.code
");
$stmt->execute([$end_date]);
$accounts = $stmt->fetchAll();

$total_debit = 0;
$total_credit = 0;


?>
<div class="card no-print" style="margin-bottom:20px;">
    <form method="get" style="display:flex;gap:15px;align-items:flex-end;">
        <div class="form-group" style="margin:0;">
            <label>As Of Date</label>
            <input type="date" name="end_date" value="<?= h($end_date) ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Generate</button>
    </form>
</div>

<div class="card" style="padding:0">
    <div style="padding:20px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
        <h3 style="margin:0;">Trial Balance as of <?= h($end_date) ?></h3>
        <button onclick="window.print()" class="btn btn-outline no-print">🖨️ Print Report</button>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Account Code</th>
                    <th>Account Name</th>
                    <th>Type</th>
                    <th style="text-align:right">Debit</th>
                    <th style="text-align:right">Credit</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($accounts as $a): 
                    $dr = (float)$a['tot_debit'];
                    $cr = (float)$a['tot_credit'];
                    
                    if ($dr == 0 && $cr == 0) continue; // Skip zero balance
                    
                    $balance = 0;
                    $is_dr = false;
                    
                    if (in_array($a['type'], ['Asset', 'Expense'])) {
                        $balance = $dr - $cr;
                        if ($balance >= 0) $is_dr = true;
                        else { $is_dr = false; $balance = abs($balance); }
                    } else {
                        $balance = $cr - $dr;
                        if ($balance >= 0) $is_dr = false;
                        else { $is_dr = true; $balance = abs($balance); }
                    }
                    
                    if ($balance == 0) continue;
                    
                    if ($is_dr) {
                        $total_debit += $balance;
                    } else {
                        $total_credit += $balance;
                    }
                ?>
                <tr>
                    <td><?= h($a['code']) ?></td>
                    <td><?= h($a['name']) ?></td>
                    <td><?= h($a['type']) ?></td>
                    <td style="text-align:right"><?= $is_dr ? money($balance) : '-' ?></td>
                    <td style="text-align:right"><?= !$is_dr ? money($balance) : '-' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f3f4f6; font-weight:bold;">
                    <td colspan="3" style="text-align:right">Totals:</td>
                    <td style="text-align:right; <?= abs($total_debit - $total_credit) > 0.01 ? 'color:red;' : 'color:green;' ?>"><?= money($total_debit) ?></td>
                    <td style="text-align:right; <?= abs($total_debit - $total_credit) > 0.01 ? 'color:red;' : 'color:green;' ?>"><?= money($total_credit) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php
require_once 'footer.php';
