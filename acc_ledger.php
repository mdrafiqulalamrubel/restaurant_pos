<?php
require_once 'acc_core.php';
require_once 'header.php';
display_flash();
if (($_SESSION['role'] ?? '') !== 'admin') { die('Access denied'); }
$page_title = 'General Ledger';

$db = db();
$tid = 1;

// Fetch accounts for dropdown
$accStmt = $db->prepare("SELECT id, code, name, type FROM acc_accounts ORDER BY type, code");
$accStmt->execute([]);
$accounts = $accStmt->fetchAll();

$account_id = (int)($_GET['account_id'] ?? 0);
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

$transactions = [];
$opening_balance = 0;
$account = null;

if ($account_id > 0) {
    // get account details
    $ac = $db->prepare("SELECT * FROM acc_accounts WHERE id=?");
    $ac->execute([$account_id]);
    $account = $ac->fetch();
    
    if ($account) {
        // Calculate opening balance before start_date
        $obStmt = $db->prepare("
            SELECT SUM(i.debit) as tot_dr, SUM(i.credit) as tot_cr 
            FROM acc_journal_items i
            JOIN acc_journals j ON i.journal_id = j.id
            WHERE i.account_id = ? AND j.date < ? " . acc_branch_sql('j') . "
        ");
        $obStmt->execute([$account_id, $start_date]);
        $ob = $obStmt->fetch();
        
        $dr = (float)$ob['tot_dr'];
        $cr = (float)$ob['tot_cr'];
        
        if (in_array($account['type'], ['Asset', 'Expense'])) {
            $opening_balance = $dr - $cr;
        } else {
            $opening_balance = $cr - $dr;
        }
        
        // Fetch transactions in date range
        $txStmt = $db->prepare("
            SELECT j.date, j.reference, j.description, i.debit, i.credit
            FROM acc_journal_items i
            JOIN acc_journals j ON i.journal_id = j.id
            WHERE i.account_id = ? AND j.date >= ? AND j.date <= ? " . acc_branch_sql('j') . "
            ORDER BY j.date ASC, j.id ASC
        ");
        $txStmt->execute([$account_id, $start_date, $end_date]);
        $transactions = $txStmt->fetchAll();
    }
}


?>
<div class="card no-print" style="margin-bottom:20px;">
    <form method="get" style="display:flex;gap:15px;align-items:flex-end;flex-wrap:wrap;">
        <div class="form-group" style="margin:0; min-width:250px;">
            <label>Select Account</label>
            <select name="account_id" required>
                <option value="">-- Choose Account --</option>
                <?php foreach($accounts as $a): ?>
                    <option value="<?= $a['id'] ?>" <?= $a['id'] == $account_id ? 'selected' : '' ?>>
                        <?= h($a['code']) ?> - <?= h($a['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin:0;">
            <label>Start Date</label>
            <input type="date" name="start_date" value="<?= h($start_date) ?>" required>
        </div>
        <div class="form-group" style="margin:0;">
            <label>End Date</label>
            <input type="date" name="end_date" value="<?= h($end_date) ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Generate Ledger</button>
    </form>
</div>

<?php if ($account): ?>
<div class="card form-card" style="padding:20px;">
    <div style="border-bottom:1px solid #eee; padding-bottom:15px; margin-bottom:15px; display:flex; justify-content:space-between; align-items:flex-start;">
        <div>
            <h3 style="margin:0;"><?= h($account['name']) ?> (<?= h($account['code']) ?>)</h3>
            <p style="margin:5px 0 0; color:#666;">Type: <?= h($account['type']) ?></p>
        </div>
        <button onclick="window.print()" class="btn btn-outline no-print">🖨️ Print Ledger</button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Reference</th>
                    <th>Description</th>
                    <th style="text-align:right">Debit</th>
                    <th style="text-align:right">Credit</th>
                    <th style="text-align:right">Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr style="background:#f9fafb;">
                    <td colspan="3"><strong>Opening Balance</strong></td>
                    <td colspan="2"></td>
                    <td style="text-align:right"><strong><?= money($opening_balance) ?></strong></td>
                </tr>
                <?php 
                $running_balance = $opening_balance;
                $tot_dr = 0; $tot_cr = 0;
                foreach($transactions as $t): 
                    $d = (float)$t['debit'];
                    $c = (float)$t['credit'];
                    $tot_dr += $d;
                    $tot_cr += $c;
                    
                    if (in_array($account['type'], ['Asset', 'Expense'])) {
                        $running_balance += ($d - $c);
                    } else {
                        $running_balance += ($c - $d);
                    }
                ?>
                <tr>
                    <td><?= fmt_date($t['date']) ?></td>
                    <td><?= h($t['reference']) ?></td>
                    <td><?= h($t['description']) ?></td>
                    <td style="text-align:right"><?= $d > 0 ? money($d) : '-' ?></td>
                    <td style="text-align:right"><?= $c > 0 ? money($c) : '-' ?></td>
                    <td style="text-align:right"><?= money($running_balance) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f3f4f6;">
                    <td colspan="3" style="text-align:right;font-weight:bold;">Totals:</td>
                    <td style="text-align:right;font-weight:bold;"><?= money($tot_dr) ?></td>
                    <td style="text-align:right;font-weight:bold;"><?= money($tot_cr) ?></td>
                    <td style="text-align:right;font-weight:bold;"><?= money($running_balance) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php endif; ?>

<?php
require_once 'footer.php';
