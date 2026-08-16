<?php
$page_title = 'New Purchase';
$page_icon = 'shopping-cart';
require_once 'config.php';
require_once 'acc_core.php';
require_once 'accounting.php';

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_id = $_POST['supplier_id'] ?? null;
    $purchase_date = $_POST['purchase_date'] ?? date('Y-m-d');
    $reference_no = $_POST['reference_no'] ?? '';
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $paid_amount = (float)($_POST['paid_amount'] ?? 0);
    
    $item_types = $_POST['item_type'] ?? [];
    $item_ids = $_POST['item_id'] ?? [];
    $qtys = $_POST['qty'] ?? [];
    $unit_prices = $_POST['unit_price'] ?? [];
    
    if (empty($item_ids)) {
        $error = "Please add at least one item to purchase.";
    } else {
        try {
            $pdo->beginTransaction();
            
            $total_amount = 0;
            
            $stmt = $pdo->prepare("INSERT INTO purchases (supplier_id, purchase_date, paid_amount, payment_method, reference_no, status) VALUES (?, ?, ?, ?, ?, 'completed')");
            $stmt->execute([$supplier_id, $purchase_date, $paid_amount, $payment_method, $reference_no]);
            $purchase_id = $pdo->lastInsertId();
            
            for ($i = 0; $i < count($item_ids); $i++) {
                $type = $item_types[$i];
                $id = $item_ids[$i];
                $qty = (float)$qtys[$i];
                $price = (float)$unit_prices[$i];
                $subtotal = $qty * $price;
                $total_amount += $subtotal;
                
                // Insert into purchase_items
                $pi_stmt = $pdo->prepare("INSERT INTO purchase_items (purchase_id, item_type, item_id, qty, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
                $pi_stmt->execute([$purchase_id, $type, $id, $qty, $price, $subtotal]);
                
                // Update Inventory
                if ($type === 'raw_material') {
                    $pdo->prepare("UPDATE raw_materials SET current_stock = current_stock + ? WHERE id = ?")->execute([$qty, $id]);
                } else {
                    $pdo->prepare("UPDATE items SET current_stock = current_stock + ? WHERE id = ?")->execute([$qty, $id]);
                }
            }
            
            // Update purchase total
            $pdo->prepare("UPDATE purchases SET total_amount = ? WHERE id = ?")->execute([$total_amount, $purchase_id]);
            
            // Record in accounting
            $supplier_name = '';
            if ($supplier_id) {
                $s_stmt = $pdo->prepare("SELECT name FROM manufacturers WHERE id = ?");
                $s_stmt->execute([$supplier_id]);
                $supplier_name = $s_stmt->fetchColumn() ?: '';
            }
            
            acc_record_purchase($purchase_id, $total_amount, $paid_amount, $payment_method, $supplier_name, $supplier_id);
            
            $pdo->commit();
            header('Location: purchases.php?print_id=' . $purchase_id);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error saving purchase: " . $e->getMessage();
        }
    }
}

require_once 'header.php';

$suppliers = $pdo->query("SELECT id, name FROM manufacturers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$raw_materials = $pdo->query("SELECT id, name, unit_price FROM raw_materials ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$items = $pdo->query("SELECT id, name, cost_price as unit_price FROM items WHERE is_producible = 0 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="card-header">
        <h5>Create New Purchase</h5>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        <form method="post" id="purchaseForm">
            <div class="row mb-3">
                <div class="col-md-3">
                    <label>Supplier</label>
                    <select name="supplier_id" class="form-select">
                        <option value="">-- No Supplier --</option>
                        <?php foreach ($suppliers as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Purchase Date</label>
                    <input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-3">
                    <label>Reference No</label>
                    <input type="text" name="reference_no" class="form-control" placeholder="Inv# or PO#">
                </div>
            </div>

            <hr>
            <h6>Items</h6>
            <div class="table-responsive">
                <table class="table table-bordered" id="itemsTable">
                    <thead class="table-light">
                        <tr>
                            <th>Type</th>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Subtotal</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        <!-- Rows added by JS -->
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-end fw-bold">Grand Total</td>
                            <td class="fw-bold"><span id="grandTotal">0.00</span></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <button type="button" class="btn btn-outline-primary mb-3" onclick="addRow()"><i class="fas fa-plus"></i> Add Item Row</button>

            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6>Payment Details</h6>
                            <div class="mb-2">
                                <label>Amount Paid</label>
                                <input type="number" name="paid_amount" id="paidAmount" class="form-control" step="0.01" value="0">
                            </div>
                            <div class="mb-2">
                                <label>Payment Method</label>
                                <select name="payment_method" class="form-select">
                                    <option value="cash">Cash</option>
                                    <option value="bank">Bank/Card</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Purchase</button>
            <a href="purchases.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<script>
const rawMaterials = <?= json_encode($raw_materials) ?>;
const itemsList = <?= json_encode($items) ?>;

function addRow() {
    const tbody = document.getElementById('itemsBody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <select name="item_type[]" class="form-select item-type" onchange="typeChanged(this)" required>
                <option value="raw_material">Raw Material</option>
                <option value="item">Menu Item</option>
            </select>
        </td>
        <td>
            <select name="item_id[]" class="form-select item-select" onchange="itemChanged(this)" required>
                <option value="">Select...</option>
                ${rawMaterials.map(rm => `<option value="${rm.id}" data-price="${rm.unit_price}">${rm.name}</option>`).join('')}
            </select>
        </td>
        <td><input type="number" name="qty[]" class="form-control item-qty" step="0.01" min="0.01" value="1" onchange="calcRow(this)" onkeyup="calcRow(this)" required></td>
        <td><input type="number" name="unit_price[]" class="form-control item-price" step="0.01" min="0" onchange="calcRow(this)" onkeyup="calcRow(this)" required></td>
        <td class="row-subtotal fw-bold">0.00</td>
        <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove(); calcTotal()"><i class="fas fa-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
}

function typeChanged(select) {
    const tr = select.closest('tr');
    const itemSelect = tr.querySelector('.item-select');
    itemSelect.innerHTML = '<option value="">Select...</option>';
    
    const list = select.value === 'raw_material' ? rawMaterials : itemsList;
    list.forEach(i => {
        itemSelect.innerHTML += `<option value="${i.id}" data-price="${i.unit_price}">${i.name}</option>`;
    });
    
    tr.querySelector('.item-price').value = '';
    calcRow(itemSelect);
}

function itemChanged(select) {
    const tr = select.closest('tr');
    const opt = select.options[select.selectedIndex];
    if(opt && opt.dataset.price) {
        tr.querySelector('.item-price').value = opt.dataset.price;
    }
    calcRow(select);
}

function calcRow(el) {
    const tr = el.closest('tr');
    const qty = parseFloat(tr.querySelector('.item-qty').value) || 0;
    const price = parseFloat(tr.querySelector('.item-price').value) || 0;
    const sub = qty * price;
    tr.querySelector('.row-subtotal').textContent = sub.toFixed(2);
    calcTotal();
}

function calcTotal() {
    let total = 0;
    document.querySelectorAll('.row-subtotal').forEach(el => {
        total += parseFloat(el.textContent) || 0;
    });
    document.getElementById('grandTotal').textContent = total.toFixed(2);
}

// Add first row by default
window.onload = function() { addRow(); };
</script>

<?php require_once 'footer.php'; ?>
