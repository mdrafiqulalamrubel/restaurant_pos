<?php
require_once 'acc_core.php';
require_once 'header.php';
display_flash();
if (($_SESSION['role'] ?? '') !== 'admin') { die('Access denied'); }
$page_title = 'Opening Balances';
$db = db();
$tid = 1;

// Fetch company settings
$stmt = $db->prepare('SELECT fiscal_year_start FROM company_settings ');
$stmt->execute([]);
$cs = $stmt->fetch() ?: [];
$fiscal_year_start = !empty($cs['fiscal_year_start']) ? $cs['fiscal_year_start'] : date('Y-m-01');

// Fetch all accounts
$accStmt = $db->prepare("SELECT id, code, name, type FROM acc_accounts ORDER BY type, code");
$accStmt->execute([]);
$accounts = $accStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'] ?? $fiscal_year_start;
    $reference = 'OPENING-BAL';
    $description = 'Opening Balance Entry';
    
    $acc_ids = $_POST['account_id'] ?? [];
    $debits = $_POST['debit'] ?? [];
    $credits = $_POST['credit'] ?? [];
    
    $entries = [];
    $total_dr = 0;
    $total_cr = 0;
    
    for ($i = 0; $i < count($acc_ids); $i++) {
        $aid = (int)$acc_ids[$i];
        $d = (float)($debits[$i] ?? 0);
        $c = (float)($credits[$i] ?? 0);
        
        if ($aid > 0 && ($d > 0 || $c > 0)) {
            $entries[] = [
                'account_id' => $aid,
                'debit' => $d,
                'credit' => $c
            ];
            $total_dr += $d;
            $total_cr += $c;
        }
    }
    
    $diff = abs($total_dr - $total_cr);
    if (count($entries) < 1) {
        flash('error', 'Please enter at least one balance.');
    } elseif ($diff > 0.01) {
        flash('error', 'Total Debits and Credits must be equal. Difference is ' . number_format($diff, 2));
    } else {
        if (acc_create_journal($date, $reference, $description, $entries)) {
            flash('success', 'Opening balances saved successfully.');
            redirect('acc_journal.php');
            exit;
        } else {
            flash('error', 'Failed to save opening balances.');
        }
    }
}


?>
<div class="card">
    <div class="card-title">⚖️ Opening Balances Entry</div>
    <p style="color:var(--c-muted); margin-bottom: 20px;">
        Enter the initial balances for your accounts. Make sure your total Debits equal your total Credits.
        Differences are typically posted to <strong>Retained Earnings</strong> or <strong>Opening Balance Equity</strong>.
    </p>

    <form method="post" id="obForm">
        <div style="display:flex;gap:15px;margin-bottom:20px; align-items: center;">
            <div class="form-group" style="flex:1;">
                <label>Opening Balance Date</label>
                <input type="date" name="date" value="<?= h($fiscal_year_start) ?>" required>
            </div>
            <div style="flex:3;"></div>
        </div>

        <table style="width:100%; border-collapse: collapse; margin-bottom: 20px;">
            <thead>
                <tr style="background:#f3f4f6; text-align:left;">
                    <th style="padding:10px;">Account Code & Name</th>
                    <th style="padding:10px; width:150px;">Debit</th>
                    <th style="padding:10px; width:150px;">Credit</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $last_type = '';
                foreach($accounts as $a): 
                    if ($last_type !== $a['type']):
                        $last_type = $a['type'];
                ?>
                    <tr>
                        <td colspan="3" style="padding:8px 10px; background:#e5e7eb; font-weight:bold; text-transform:uppercase; font-size:12px;">
                            <?= h($last_type) ?>
                        </td>
                    </tr>
                <?php endif; ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding:5px 10px;">
                        <input type="hidden" name="account_id[]" value="<?= $a['id'] ?>">
                        <strong><?= h($a['code']) ?></strong> - <?= h($a['name']) ?>
                    </td>
                    <td style="padding:5px;"><input type="number" step="0.01" name="debit[]" class="dr-input" oninput="calcTotals()" placeholder="0.00"></td>
                    <td style="padding:5px;"><input type="number" step="0.01" name="credit[]" class="cr-input" oninput="calcTotals()" placeholder="0.00"></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f9fafb;">
                    <td style="padding:10px; text-align:right; font-weight:bold;">TOTALS:</td>
                    <td style="padding:10px; font-weight:bold; font-size:16px;" id="totDr">0.00</td>
                    <td style="padding:10px; font-weight:bold; font-size:16px;" id="totCr">0.00</td>
                </tr>
                <tr id="diffRow" style="display:none;">
                    <td style="padding:10px; text-align:right; font-weight:bold; color:red;">DIFFERENCE:</td>
                    <td colspan="2" style="padding:10px; font-weight:bold; font-size:16px; color:red;" id="totDiff">0.00</td>
                </tr>
            </tfoot>
        </table>

        <div style="display:flex;justify-content:flex-end;gap:10px;">
            <button type="submit" class="btn btn-primary" id="saveBtn">Save Opening Balances</button>
        </div>
    </form>
</div>

<script>
function calcTotals() {
    let dr = 0; let cr = 0;
    document.querySelectorAll('.dr-input').forEach(inp => dr += parseFloat(inp.value || 0));
    document.querySelectorAll('.cr-input').forEach(inp => cr += parseFloat(inp.value || 0));
    
    document.getElementById('totDr').textContent = dr.toFixed(2);
    document.getElementById('totCr').textContent = cr.toFixed(2);
    
    const diff = Math.abs(dr - cr);
    const saveBtn = document.getElementById('saveBtn');
    const diffRow = document.getElementById('diffRow');
    const diffVal = document.getElementById('totDiff');
    
    if (diff > 0.01 || (dr === 0 && cr === 0)) {
        document.getElementById('totDr').style.color = 'red';
        document.getElementById('totCr').style.color = 'red';
        if (diff > 0.01) {
            diffRow.style.display = 'table-row';
            diffVal.textContent = diff.toFixed(2);
        }
    } else {
        document.getElementById('totDr').style.color = 'green';
        document.getElementById('totCr').style.color = 'green';
        diffRow.style.display = 'none';
    }
}
</script>
<?php
require_once 'footer.php';
