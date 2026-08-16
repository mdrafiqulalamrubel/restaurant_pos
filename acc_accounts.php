<?php
require_once 'acc_core.php';
require_once 'header.php';
display_flash();
if (($_SESSION['role'] ?? '') !== 'admin') { die('Access denied'); } // Only admin should modify accounts ideally
$page_title = 'Chart of Accounts';

$db = db();
$tid = 1;

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $code = trim($_POST['code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $opening_balance = (float)($_POST['opening_balance'] ?? 0);
    
    if ($_POST['action'] === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        if ($code && $name && $type) {
            if ($id > 0) {
                // Update
                $stmt = $db->prepare("UPDATE acc_accounts SET code=?, name=?, type=? WHERE id=?");
                $stmt->execute([$code, $name, $type, $id]);
                flash('success', 'Account updated successfully.');
                
                // Update opening balance via adjusting journal if it changed
                $oldObStmt = $db->prepare("
                    SELECT SUM(i.debit) as d, SUM(i.credit) as c
                    FROM acc_journal_items i 
                    JOIN acc_journals j ON i.journal_id = j.id 
                    WHERE i.account_id=? AND (j.reference LIKE 'OB-%' OR j.reference='OPENING-BAL')
                ");
                $oldObStmt->execute([$id]);
                $old_ob = $oldObStmt->fetch();
                $old_ob_val = 0;
                if (in_array($type, ['Asset', 'Expense'])) {
                    $old_ob_val = ($old_ob['d'] ?? 0) - ($old_ob['c'] ?? 0);
                } else {
                    $old_ob_val = ($old_ob['c'] ?? 0) - ($old_ob['d'] ?? 0);
                }
                
                $difference = $opening_balance - $old_ob_val;
                
                if (abs($difference) > 0.01) {
                    $eqStmt = $db->prepare("SELECT id FROM acc_accounts WHERE code='3000'");
                    $eqStmt->execute([]);
                    $eq_id = $eqStmt->fetchColumn();
                    if ($eq_id) {
                        $dr = 0; $cr = 0;
                        if (in_array($type, ['Asset', 'Expense'])) {
                            $dr = $difference > 0 ? $difference : 0;
                            $cr = $difference < 0 ? abs($difference) : 0;
                        } else {
                            $cr = $difference > 0 ? $difference : 0;
                            $dr = $difference < 0 ? abs($difference) : 0;
                        }
                        
                        $entries = [
                            ['account_id' => $id, 'debit' => $dr, 'credit' => $cr],
                            ['account_id' => $eq_id, 'debit' => $cr, 'credit' => $dr]
                        ];
                        if (function_exists('acc_create_journal')) {
                            acc_create_journal(date('Y-m-d'), 'OB-ADJ-'.$code, 'Opening Balance Adj: ' . $name, $entries);
                        }
                    }
                }
            } else {
                // Insert
                $stmt = $db->prepare("INSERT INTO acc_accounts (code, name, type) VALUES (?, ?, ?)");
                $stmt->execute([$code, $name, $type]);
                $new_id = $db->lastInsertId();
                flash('success', 'Account created successfully.');

                if ($opening_balance > 0) {
                    $eqStmt = $db->prepare("SELECT id FROM acc_accounts WHERE code='3000'");
                    $eqStmt->execute([]);
                    $eq_id = $eqStmt->fetchColumn();
                    if ($eq_id) {
                        $dr = 0; $cr = 0;
                        if (in_array($type, ['Asset', 'Expense'])) {
                            $dr = $opening_balance;
                        } else {
                            $cr = $opening_balance;
                        }
                        
                        $entries = [
                            ['account_id' => $new_id, 'debit' => $dr, 'credit' => $cr],
                            ['account_id' => $eq_id, 'debit' => $cr, 'credit' => $dr]
                        ];
                        if (function_exists('acc_create_journal')) {
                            acc_create_journal(date('Y-m-d'), 'OB-'.$code, 'Opening Balance for ' . $name, $entries);
                        }
                    }
                }
            }
        } else {
            flash('error', 'All fields are required.');
        }
    } elseif ($_POST['action'] === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            // Check if there are journal entries
            $check = $db->prepare("SELECT COUNT(*) FROM acc_journal_items WHERE account_id=?");
            $check->execute([$id]);
            if ($check->fetchColumn() > 0) {
                flash('error', 'Cannot delete account with existing journal entries.');
            } else {
                $stmt = $db->prepare("DELETE FROM acc_accounts WHERE id=? AND is_default=0");
                $stmt->execute([$id]);
                if ($stmt->rowCount() > 0) {
                    flash('success', 'Account deleted.');
                } else {
                    flash('error', 'Cannot delete default accounts.');
                }
            }
        }
    }
    
    redirect('acc_accounts.php');
    exit;
}

// Fetch all accounts
$stmt = $db->prepare("SELECT * FROM acc_accounts ORDER BY type, code ASC");
$stmt->execute([]);
$accounts = $stmt->fetchAll();

// Fetch opening balances for all accounts
$obStmt = $db->prepare("
    SELECT i.account_id, SUM(i.debit) as tot_dr, SUM(i.credit) as tot_cr 
    FROM acc_journal_items i 
    JOIN acc_journals j ON i.journal_id = j.id 
    WHERE (j.reference LIKE 'OB-%' OR j.reference = 'OPENING-BAL') " . acc_branch_sql('j') . "
    GROUP BY i.account_id
");
$obStmt->execute([]);
$obs = [];
foreach ($obStmt->fetchAll() as $row) {
    $obs[$row['account_id']] = ['dr' => $row['tot_dr'], 'cr' => $row['tot_cr']];
}


?>
<div style="display:flex;justify-content:flex-end;margin-bottom:20px">
    <button onclick="openAccModal()" class="btn btn-primary">+ Add Account</button>
</div>

<div class="card form-card" style="padding:20px">
  <div class="table-responsive">
    <table class="table table-hover table-striped">
      <thead>
        <tr>
          <th>Code</th>
          <th>Account Name</th>
          <th>Type</th>
          <th>System Default</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($accounts as $acc): 
        $ob_dr = $obs[$acc['id']]['dr'] ?? 0;
        $ob_cr = $obs[$acc['id']]['cr'] ?? 0;
        $ob_val = 0;
        if (in_array($acc['type'], ['Asset', 'Expense'])) {
            $ob_val = $ob_dr - $ob_cr;
        } else {
            $ob_val = $ob_cr - $ob_dr;
        }
      ?>
        <tr>
          <td><strong><?= h($acc['code']) ?></strong></td>
          <td><?= h($acc['name']) ?></td>
          <td>
            <?php 
                $badge = 'bg-secondary';
                if ($acc['type'] == 'Asset') $badge = 'bg-success';
                elseif ($acc['type'] == 'Liability') $badge = 'bg-danger';
                elseif ($acc['type'] == 'Equity') $badge = 'bg-info';
                elseif ($acc['type'] == 'Revenue') $badge = 'bg-primary';
                elseif ($acc['type'] == 'Expense') $badge = 'bg-warning text-dark';
            ?>
            <span class="badge <?= $badge ?> rounded-pill"><?= h($acc['type']) ?></span>
          </td>
          <td>
            <?php if($acc['is_default']): ?>
                <span class="badge bg-success"><i class="fas fa-check"></i> Yes</span>
            <?php else: ?>
                <span class="badge bg-secondary"><i class="fas fa-times"></i> No</span>
            <?php endif; ?>
          </td>
          <td>
            <button onclick="editAcc(<?= $acc['id'] ?>, '<?= h($acc['code']) ?>', '<?= h($acc['name']) ?>', '<?= h($acc['type']) ?>', <?= $ob_val ?>)" class="btn btn-outline btn-sm">Edit</button>
            <?php if (!$acc['is_default']): ?>
            <form method="post" style="display:inline" onsubmit="return confirm('Delete this account?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $acc['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">Del</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if(empty($accounts)): ?>
        <tr><td colspan="5" style="text-align:center;padding:20px">No accounts found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal -->
<style>
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center}
.modal-overlay.open{display:flex}
.modal-box{background:#fff;border-radius:10px;padding:25px;width:400px;max-width:90vw;box-shadow:0 10px 30px rgba(0,0,0,.2)}
</style>
<div class="modal-overlay" id="accModal" onclick="if(event.target===this)closeAccModal()">
    <div class="modal-box">
        <h3 id="modalTitle" style="margin-top:0">Add Account</h3>
        <form method="post">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="accId" value="0">
            <div class="form-group">
                <label>Account Code</label>
                <input type="text" name="code" id="accCode" required placeholder="e.g. 1500">
            </div>
            <div class="form-group">
                <label>Account Name</label>
                <input type="text" name="name" id="accName" required placeholder="e.g. Petty Cash">
            </div>
            <div class="form-group">
                <label>Type</label>
                <select name="type" id="accType" required>
                    <option value="Asset">Asset</option>
                    <option value="Liability">Liability</option>
                    <option value="Equity">Equity</option>
                    <option value="Revenue">Revenue</option>
                    <option value="Expense">Expense</option>
                </select>
            </div>
            <div class="form-group" id="obGroup">
                <label>Opening Balance</label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" step="0.01" class="form-control" name="opening_balance" id="accOB" value="0" placeholder="0.00">
                </div>
                <small class="text-muted mt-1 d-block">Applies to this account against Opening Balance Equity.</small>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:25px">
                <button type="button" class="btn btn-secondary" onclick="closeAccModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Account</button>
            </div>
        </form>
    </div>
</div>
<script>
function openAccModal() {
    document.getElementById('modalTitle').textContent = 'Add Account';
    document.getElementById('accId').value = '0';
    document.getElementById('accCode').value = '';
    document.getElementById('accName').value = '';
    document.getElementById('accType').value = 'Asset';
    document.getElementById('accOB').value = '0';
    document.getElementById('obGroup').style.display = 'block';
    document.getElementById('accModal').classList.add('open');
}
function editAcc(id, code, name, type, ob_val) {
    document.getElementById('modalTitle').textContent = 'Edit Account';
    document.getElementById('accId').value = id;
    document.getElementById('accCode').value = code;
    document.getElementById('accName').value = name;
    document.getElementById('accType').value = type;
    document.getElementById('accOB').value = ob_val;
    document.getElementById('obGroup').style.display = 'block';
    document.getElementById('accModal').classList.add('open');
}
function closeAccModal() {
    document.getElementById('accModal').classList.remove('open');
}
</script>
<?php
require_once 'footer.php';
