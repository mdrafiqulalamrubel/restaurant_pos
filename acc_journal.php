<?php
require_once 'acc_core.php';
require_once 'header.php';
display_flash();
if (($_SESSION['role'] ?? '') !== 'admin') { die('Access denied'); }
$page_title = 'Journal Entries';

$db = db();
$tid = 1;

// Filter
$search = trim($_GET['q'] ?? '');
$where = "WHERE 1=1 " . acc_branch_sql('');
$params = [];

if ($search) {
    $where .= " AND (reference LIKE :q OR description LIKE :q2)";
    $params[':q'] = "%$search%";
    $params[':q2'] = "%$search%";
}

// Pagination
$totalStmt = $db->prepare("SELECT COUNT(*) FROM acc_journals $where");
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();
$pg = paginate($total, 20, (int)($_GET['page'] ?? 1));

// Fetch journals
$stmt = $db->prepare("SELECT * FROM acc_journals $where ORDER BY date DESC, id DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}");
$stmt->execute($params);
$journals = $stmt->fetchAll();


?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
    <form method="get" style="display:flex;gap:10px;">
        <input type="text" name="q" value="<?= h($search) ?>" placeholder="Search reference or desc..." style="width:250px;padding:8px;border:1px solid #ccc;border-radius:4px;">
        <button type="submit" class="btn btn-outline">Search</button>
        <?php if($search): ?><a href="acc_journal.php" class="btn btn-outline">Clear</a><?php endif; ?>
    </form>
    <a href="acc_journal_add.php" class="btn btn-primary">+ New Journal Entry</a>
</div>

<div class="card form-card" style="padding:20px">
  <div class="table-responsive">
    <table class="table table-hover table-striped">
      <thead>
        <tr>
          <th>Date</th>
          <th>Reference</th>
          <th>Description</th>
          <th>Amount</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($journals as $j): 
          // get total amount for this journal (sum of debits)
          $amtStmt = $db->prepare("SELECT SUM(debit) FROM acc_journal_items WHERE journal_id = ?");
          $amtStmt->execute([$j['id']]);
          $amt = (float)$amtStmt->fetchColumn();
      ?>
        <tr>
          <td><?= fmt_date($j['date']) ?></td>
          <td><strong><?= h($j['reference']) ?></strong></td>
          <td><?= h($j['description']) ?></td>
          <td><?= money($amt) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if(empty($journals)): ?>
        <tr><td colspan="4" style="text-align:center;padding:20px">No journal entries found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="mt-4">
    <?= $pg['html'] ?>
</div>
<?php
require_once 'footer.php';
