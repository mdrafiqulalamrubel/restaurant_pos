<?php
$page_title = 'Select Sale for Token';
$page_icon = 'ticket-alt';
require_once 'config.php';
require_once 'header.php';

// Get search parameters
$search = $_GET['search'] ?? '';
$from_date = $_GET['from_date'] ?? date('Y-m-d');
$to_date = $_GET['to_date'] ?? date('Y-m-d');

// Build query
$query = "
    SELECT s.id, s.total, s.payment_method, s.created_at, 
           c.name as customer_name,
           COUNT(si.id) as item_count,
           t.token_number,
           t.status as token_status
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    LEFT JOIN sale_items si ON s.id = si.sale_id
    LEFT JOIN order_tokens t ON s.id = t.sale_id AND t.token_date = CURDATE()
    WHERE DATE(s.created_at) BETWEEN ? AND ?
";

$params = [$from_date, $to_date];

if ($search) {
    $query .= " AND (s.id LIKE ? OR c.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " GROUP BY s.id ORDER BY s.id DESC LIMIT 50";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .sale-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 15px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: all 0.3s;
        cursor: pointer;
    }
    .sale-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.15);
    }
    .sale-card.selected {
        border: 2px solid #667eea;
        background: #f8f9ff;
    }
    .token-status {
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: bold;
    }
    .token-printed { background: #28a745; color: white; }
    .token-pending { background: #ff9800; color: white; }
    .token-none { background: #6c757d; color: white; }
    .filter-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
</style>

<div class="filter-card">
    <h5><i class="fas fa-filter"></i> Filter Sales</h5>
    <form method="get" class="row g-3">
        <div class="col-md-3">
            <label>From Date</label>
            <input type="date" name="from_date" class="form-control" value="<?= $from_date ?>">
        </div>
        <div class="col-md-3">
            <label>To Date</label>
            <input type="date" name="to_date" class="form-control" value="<?= $to_date ?>">
        </div>
        <div class="col-md-4">
            <label>Search (Invoice # or Customer)</label>
            <input type="text" name="search" class="form-control" placeholder="Invoice ID or Customer name..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-2">
            <label>&nbsp;</label>
            <button type="submit" class="btn btn-primary w-100">Search</button>
        </div>
    </form>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5><i class="fas fa-list"></i> Select Sale to Print Token</h5>
            <div>
                <button class="btn btn-success" onclick="printSelectedToken()" id="printBtn" disabled>
                    <i class="fas fa-print"></i> Print Token
                </button>
                <button class="btn btn-warning" onclick="printBothTokens()" id="printBothBtn" disabled>
                    <i class="fas fa-layer-group"></i> Print Both Tokens
                </button>
            </div>
        </div>
        
        <div id="salesList">
            <?php if (empty($sales)): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle"></i> No sales found for the selected period.
                </div>
            <?php else: ?>
                <?php foreach ($sales as $sale): ?>
                <div class="sale-card" onclick="selectSale(<?= $sale['id'] ?>, this)" data-sale-id="<?= $sale['id'] ?>">
                    <div class="row align-items-center">
                        <div class="col-md-2">
                            <h5 class="mb-0">#<?= str_pad($sale['id'], 6, '0', STR_PAD_LEFT) ?></h5>
                            <small class="text-muted"><?= date('d/m/Y H:i', strtotime($sale['created_at'])) ?></small>
                        </div>
                        <div class="col-md-3">
                            <strong><?= htmlspecialchars($sale['customer_name'] ?? 'Walk-in Customer') ?></strong>
                        </div>
                        <div class="col-md-2">
                            <span class="badge bg-primary"><?= $sale['item_count'] ?> items</span>
                        </div>
                        <div class="col-md-2">
                            <strong>$<?= number_format($sale['total'], 2) ?></strong>
                            <br><small><?= ucfirst($sale['payment_method']) ?></small>
                        </div>
                        <div class="col-md-3">
                            <?php if ($sale['token_number']): ?>
                                <span class="token-status token-printed">
                                    <i class="fas fa-check-circle"></i> Token #<?= str_pad($sale['token_number'], 3, '0', STR_PAD_LEFT) ?>
                                </span>
                                <br><small>Printed: <?= date('H:i', strtotime($sale['printed_at'] ?? $sale['created_at'])) ?></small>
                            <?php else: ?>
                                <span class="token-status token-none">
                                    <i class="fas fa-clock"></i> No Token Yet
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<form id="tokenForm" method="get" action="token_print.php" target="_blank">
    <input type="hidden" name="sale_id" id="selectedSaleId">
    <input type="hidden" name="type" id="tokenType" value="both">
</form>

<script>
let selectedSaleId = null;
let selectedCard = null;

function selectSale(saleId, cardElement) {
    // Remove previous selection
    if (selectedCard) {
        selectedCard.classList.remove('selected');
    }
    
    // Add new selection
    selectedSaleId = saleId;
    selectedCard = cardElement;
    selectedCard.classList.add('selected');
    
    // Enable buttons
    document.getElementById('printBtn').disabled = false;
    document.getElementById('printBothBtn').disabled = false;
}

function printSelectedToken() {
    if (!selectedSaleId) {
        alert('Please select a sale first');
        return;
    }
    
    // Ask which token to print
    const tokenType = prompt('Select token type:\n1 - Kitchen Token\n2 - Customer Token\n3 - Both Tokens', '3');
    
    if (tokenType === '1') {
        document.getElementById('tokenType').value = 'kitchen';
    } else if (tokenType === '2') {
        document.getElementById('tokenType').value = 'customer';
    } else if (tokenType === '3') {
        document.getElementById('tokenType').value = 'both';
    } else {
        return;
    }
    
    document.getElementById('selectedSaleId').value = selectedSaleId;
    document.getElementById('tokenForm').submit();
    
    // Reset selection after printing
    setTimeout(() => {
        if (selectedCard) {
            selectedCard.classList.remove('selected');
        }
        selectedSaleId = null;
        selectedCard = null;
        document.getElementById('printBtn').disabled = true;
        document.getElementById('printBothBtn').disabled = true;
    }, 1000);
}

function printBothTokens() {
    if (!selectedSaleId) {
        alert('Please select a sale first');
        return;
    }
    
    document.getElementById('tokenType').value = 'both';
    document.getElementById('selectedSaleId').value = selectedSaleId;
    document.getElementById('tokenForm').submit();
    
    // Reset selection
    setTimeout(() => {
        if (selectedCard) {
            selectedCard.classList.remove('selected');
        }
        selectedSaleId = null;
        selectedCard = null;
        document.getElementById('printBtn').disabled = true;
        document.getElementById('printBothBtn').disabled = true;
    }, 1000);
}
</script>

<?php require_once 'footer.php'; ?>