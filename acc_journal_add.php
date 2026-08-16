<?php
require_once 'acc_core.php';
require_once 'header.php';
display_flash();
if (($_SESSION['role'] ?? '') !== 'admin') { die('Access denied'); }
$page_title = 'New Journal Entry';
$db = db();
$tid = 1;

// Fetch accounts for dropdown
$accStmt = $db->prepare("SELECT id, code, name, type FROM acc_accounts ORDER BY type, code");
$accStmt->execute([]);
$accounts = $accStmt->fetchAll();

$custStmt = $db->prepare("SELECT id, name FROM customers ORDER BY name");
$custStmt->execute([]);
$customers = $custStmt->fetchAll();

$suppStmt = $db->prepare("SELECT id, name FROM suppliers ORDER BY name");
$suppStmt->execute([]);
$suppliers = $suppStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'] ?? date('Y-m-d');
    $reference = trim($_POST['reference'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    $acc_ids = $_POST['account_id'] ?? [];
    $debits = $_POST['debit'] ?? [];
    $credits = $_POST['credit'] ?? [];
    
    $entries = [];
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
        }
    }
    
    $contact_type = $_POST['contact_type'] ?? '';
    $contact_id = (int)($_POST['contact_id'] ?? 0);
    if (!in_array($contact_type, ['customer', 'supplier'])) {
        $contact_type = null;
        $contact_id = null;
    }

    if (count($entries) < 2) {
        flash('error', 'A journal entry must have at least two line items.');
    } else {
        if (acc_create_journal($date, $reference, $description, $entries, $contact_type, $contact_id)) {
            flash('success', 'Journal entry saved successfully.');
            redirect('acc_journal.php');
            exit;
        } else {
            flash('error', 'Failed to save journal. Ensure total debits equal total credits.');
        }
    }
}


?>
<div class="card">
    <form method="post" id="journalForm">
        <div style="display:flex;gap:15px;margin-bottom:20px;">
            <div class="form-group" style="flex:1;">
                <label>Date</label>
                <input type="date" name="date" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group" style="flex:1;">
                <label>Reference</label>
                <input type="text" name="reference" placeholder="e.g. ADJ-001">
            </div>
            <div class="form-group" style="flex:2;">
                <label>Description</label>
                <input type="text" name="description" placeholder="Brief explanation">
            </div>
        </div>
        
        <div style="display:flex;gap:15px;margin-bottom:20px;">
            <div class="form-group" style="flex:1;">
                <label>Contact Type (Optional)</label>
                <select name="contact_type" id="contact_type" onchange="toggleContactLists()">
                    <option value="">-- None --</option>
                    <option value="customer">Customer</option>
                    <option value="supplier">Supplier</option>
                </select>
            </div>
            <div class="form-group" style="flex:2;" id="contact_div" style="display:none;">
                <label>Contact</label>
                <select name="contact_id" id="contact_id">
                    <option value="">-- Select Contact --</option>
                </select>
            </div>
        </div>

        <table style="width:100%; border-collapse: collapse; margin-bottom: 20px;">
            <thead>
                <tr style="background:#f3f4f6; text-align:left;">
                    <th style="padding:10px;">Account</th>
                    <th style="padding:10px; width:150px;">Debit</th>
                    <th style="padding:10px; width:150px;">Credit</th>
                    <th style="padding:10px; width:50px;"></th>
                </tr>
            </thead>
            <tbody id="journalLines">
                <!-- Two initial rows -->
                <tr>
                    <td style="padding:5px;">
                        <select name="account_id[]" class="acc-select" required>
                            <option value="">-- Select Account --</option>
                            <?php foreach($accounts as $a): ?>
                                <option value="<?= $a['id'] ?>"><?= h($a['code']) ?> - <?= h($a['name']) ?> (<?= h($a['type']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td style="padding:5px;"><input type="number" step="0.01" name="debit[]" class="dr-input" oninput="calcTotals()"></td>
                    <td style="padding:5px;"><input type="number" step="0.01" name="credit[]" class="cr-input" oninput="calcTotals()"></td>
                    <td style="padding:5px;"></td>
                </tr>
                <tr>
                    <td style="padding:5px;">
                        <select name="account_id[]" class="acc-select" required>
                            <option value="">-- Select Account --</option>
                            <?php foreach($accounts as $a): ?>
                                <option value="<?= $a['id'] ?>"><?= h($a['code']) ?> - <?= h($a['name']) ?> (<?= h($a['type']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td style="padding:5px;"><input type="number" step="0.01" name="debit[]" class="dr-input" oninput="calcTotals()"></td>
                    <td style="padding:5px;"><input type="number" step="0.01" name="credit[]" class="cr-input" oninput="calcTotals()"></td>
                    <td style="padding:5px;"></td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td style="padding:10px;">
                        <button type="button" class="btn btn-outline btn-sm" onclick="addRow()">+ Add Line</button>
                    </td>
                    <td style="padding:10px; font-weight:bold; font-size:16px;" id="totDr">0.00</td>
                    <td style="padding:10px; font-weight:bold; font-size:16px;" id="totCr">0.00</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <div style="display:flex;justify-content:flex-end;gap:10px;">
            <a href="acc_journal.php" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary" id="saveBtn">Save Entry</button>
        </div>
    </form>
</div>

<script>
function addRow() {
    const tbody = document.getElementById('journalLines');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td style="padding:5px;">
            <select name="account_id[]" class="acc-select" required>
                <option value="">-- Select Account --</option>
                <?php foreach($accounts as $a): ?>
                    <option value="<?= $a['id'] ?>"><?= h($a['code']) ?> - <?= h($a['name']) ?> (<?= h($a['type']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </td>
        <td style="padding:5px;"><input type="number" step="0.01" name="debit[]" class="dr-input" oninput="calcTotals()"></td>
        <td style="padding:5px;"><input type="number" step="0.01" name="credit[]" class="cr-input" oninput="calcTotals()"></td>
        <td style="padding:5px;"><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove();calcTotals();">X</button></td>
    `;
    tbody.appendChild(tr);
}

function calcTotals() {
    let dr = 0; let cr = 0;
    document.querySelectorAll('.dr-input').forEach(inp => dr += parseFloat(inp.value || 0));
    document.querySelectorAll('.cr-input').forEach(inp => cr += parseFloat(inp.value || 0));
    
    document.getElementById('totDr').textContent = dr.toFixed(2);
    document.getElementById('totCr').textContent = cr.toFixed(2);
    
    const diff = Math.abs(dr - cr);
    const saveBtn = document.getElementById('saveBtn');
    
    if (diff > 0.01 || dr === 0) {
        document.getElementById('totDr').style.color = 'red';
        document.getElementById('totCr').style.color = 'red';
        saveBtn.disabled = true;
    } else {
        document.getElementById('totDr').style.color = 'green';
        document.getElementById('totCr').style.color = 'green';
        saveBtn.disabled = false;
    }
}

const customers = <?= json_encode($customers) ?>;
const suppliers = <?= json_encode($suppliers) ?>;

function toggleContactLists() {
    const type = document.getElementById('contact_type').value;
    const select = document.getElementById('contact_id');
    const div = document.getElementById('contact_div');
    
    select.innerHTML = '<option value="">-- Select Contact --</option>';
    
    if (type === 'customer') {
        customers.forEach(c => {
            select.innerHTML += `<option value="${c.id}">${c.name}</option>`;
        });
        div.style.display = 'block';
    } else if (type === 'supplier') {
        suppliers.forEach(s => {
            select.innerHTML += `<option value="${s.id}">${s.name}</option>`;
        });
        div.style.display = 'block';
    } else {
        div.style.display = 'none';
    }
}
</script>
<?php
require_once 'footer.php';
