<?php
// pos.php - NO SPACES OR CHARACTERS BEFORE THIS LINE
$page_title = 'Point of Sale';
$page_icon = 'cash-register';
require_once 'config.php';
require_once 'functions.php';

// Process POST checkout - MUST be before ANY output including header.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $items = json_decode($_POST['cart_data'], true);
    $customer_id = $_POST['customer_id'] ?? 0;
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $discount = $_POST['discount'] ?? 0;
    $table_id = $_POST['table_id'] ?? null;
    
    if (empty($items)) {
        $error = "Cart is empty";
    } else {
        try {
            $saleId = saveSale($items, $customer_id, $payment_method, $discount);
            
            if ($table_id) {
                $stmt = $pdo->prepare("INSERT INTO table_orders (table_id, sale_id, status) VALUES (?, ?, 'active')");
                $stmt->execute([$table_id, $saleId]);
                $stmt = $pdo->prepare("UPDATE dining_tables SET status = 'occupied', current_order_id = ? WHERE id = ?");
                $stmt->execute([$saleId, $table_id]);
            }
            
            $today = date('Y-m-d');
            $stmt = $pdo->prepare("SELECT COALESCE(MAX(token_number), 0) + 1 FROM order_tokens WHERE token_date = ?");
            $stmt->execute([$today]);
            $token_num = $stmt->fetchColumn();
            $stmt = $pdo->prepare("INSERT INTO order_tokens (sale_id, token_number, token_date) VALUES (?, ?, ?)");
            $stmt->execute([$saleId, $token_num, $today]);
            
            header("Location: print_bill.php?id=$saleId&type=thermal");
            exit;
        } catch (Exception $e) {
            $error = "Checkout failed: " . $e->getMessage();
        }
    }
}

// Now include header AFTER processing POST
require_once 'header.php';

$items = getItems();
$categories = $pdo->query("SELECT DISTINCT category FROM items WHERE active=1 AND category IS NOT NULL AND category != ''")->fetchAll(PDO::FETCH_ASSOC);
// Get company settings
$company = $pdo->query("SELECT * FROM company_settings WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
if (!$company) {
    $company = ['name' => 'Restaurant POS', 'currency' => '€', 'currency_code' => 'EUR', 'logo' => ''];
}
?>

<style>
    .pos-layout {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }
    .products-section {
        flex: 2;
        min-width: 300px;
    }
    .cart-section {
        flex: 1;
        min-width: 380px;
        background: white;
        border-radius: 15px;
        padding: 20px;
        position: sticky;
        top: 20px;
        height: fit-content;
        max-height: 85vh;
        overflow-y: auto;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .category-filter {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 20px;
        background: white;
        padding: 15px;
        border-radius: 10px;
    }
    .category-btn {
        padding: 8px 20px;
        border: none;
        background: #e9ecef;
        border-radius: 25px;
        cursor: pointer;
        transition: all 0.3s;
    }
    .category-btn.active, .category-btn:hover {
        background: #667eea;
        color: white;
    }
    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 15px;
        max-height: 65vh;
        overflow-y: auto;
        padding: 5px;
    }
    .product-item {
        background: white;
        border-radius: 12px;
        padding: 12px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .product-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    }
    .product-image {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 10px;
        margin-bottom: 10px;
    }
    .product-name {
        font-weight: 600;
        margin: 8px 0;
        font-size: 0.9rem;
    }
    .product-price {
        color: #667eea;
        font-weight: bold;
        font-size: 1.1rem;
    }
    .booking-badge {
        background: #ff9800;
        color: white;
        padding: 2px 8px;
        border-radius: 20px;
        font-size: 0.7rem;
        display: inline-block;
        margin-bottom: 5px;
    }
    .cart-item {
        border-bottom: 1px solid #eee;
        padding: 12px 0;
    }
    .cart-item-image {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 5px;
    }
    .cart-total {
        border-top: 2px solid #667eea;
        padding-top: 15px;
        margin-top: 15px;
        font-size: 1.2rem;
        font-weight: bold;
    }
    .payment-methods {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        margin: 15px 0;
    }
    .payment-btn {
        padding: 10px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
        transition: all 0.3s;
    }
    .payment-btn.cash { background: #27ae60; color: white; }
    .payment-btn.card { background: #3498db; color: white; }
    .payment-btn.bkash { background: #e67e22; color: white; }
    .payment-btn.nagad { background: #e74c3c; color: white; }
    .payment-btn.selected { opacity: 1; box-shadow: 0 0 0 2px #fff, 0 0 0 4px #333; }
    .qty-control {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .qty-btn {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        border: none;
        background: #667eea;
        color: white;
        cursor: pointer;
    }
    .cart-scroll {
        max-height: 350px;
        overflow-y: auto;
    }
    .error-msg {
        background: #f8d7da;
        color: #721c24;
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 15px;
    }
</style>

<?php if (isset($error)): ?>
    <div class="error-msg"><?= $error ?></div>
<?php endif; ?>

<form method="post" id="posForm" style="margin:0; padding:0;">
<input type="hidden" name="checkout" value="1">
<input type="hidden" name="cart_data" id="cartData">
<input type="hidden" name="payment_method" id="paymentMethod" value="cash">

<div class="pos-layout">
    <!-- Products Section -->
    <div class="products-section">
        <div class="category-filter">
            <button type="button" class="category-btn active" data-category="all">🍽️ All Items</button>
            <?php foreach ($categories as $cat): ?>
                <button type="button" class="category-btn" data-category="<?= $cat['category'] ?>">
                    <?= $cat['category'] ?>
                </button>
            <?php endforeach; ?>
        </div>
        <div class="product-grid" id="productGrid">
            <div class="text-center">Loading products...</div>
        </div>
    </div>
    
    <!-- Cart Section -->
    <div class="cart-section">
        <h5><i class="fas fa-shopping-cart"></i> Current Order</h5>
        <hr>
        
        <div class="mb-3">
            <label><i class="fas fa-user"></i> Customer:</label>
            <select name="customer_id" class="form-select mt-1">
                <option value="">👤 Walk-in Customer</option>
                <?php
                $customers = $pdo->query("SELECT id, name, phone FROM customers ORDER BY name")->fetchAll();
                foreach ($customers as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= $c['phone'] ?>)</option>
                <?php endforeach; ?>
            </select>
            <a href="customer_add.php" class="btn btn-sm btn-link" target="_blank">+ Add New Customer</a>
        </div>
        
        <div class="mb-3">
            <label><i class="fas fa-chair"></i> Table Number:</label>
            <select name="table_id" class="form-select mt-1">
                <option value="">No Table (Takeaway)</option>
                <?php
                $tables = $pdo->query("SELECT id, table_number, capacity FROM dining_tables WHERE status = 'available' ORDER BY table_number")->fetchAll();
                foreach ($tables as $t): ?>
                    <option value="<?= $t['id'] ?>">Table <?= $t['table_number'] ?> (<?= $t['capacity'] ?> seats)</option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="cart-scroll" id="cartItems">
            <p class="text-muted text-center">🛒 Cart is empty</p>
        </div>
        
        <div class="cart-total">
            <div class="d-flex justify-content-between">
                <span>Subtotal:</span>
                <span id="subtotal"><?= $company['currency'] ?>0.00</span>
            </div>
            <div class="d-flex justify-content-between">
                <span>Discount:</span>
                <input type="number" name="discount" id="discount" value="0" step="0.01" style="width: 100px;" class="form-control form-control-sm">
            </div>
            <div class="d-flex justify-content-between">
                <span>Tax (<?= $company['tax_rate'] ?? 10 ?>%):</span>
                <span id="tax"><?= $company['currency'] ?>0.00</span>
            </div>
            <div class="d-flex justify-content-between mt-2" style="font-size: 1.3rem;">
                <span><strong>Total:</strong></span>
                <span><strong id="total"><?= $company['currency'] ?>0.00</strong></span>
            </div>
        </div>
        
        <div class="payment-methods">
            <button type="button" class="payment-btn cash selected" onclick="setPayment('cash', this)"><i class="fas fa-money-bill"></i> Cash</button>
            <button type="button" class="payment-btn card" onclick="setPayment('card', this)"><i class="fas fa-credit-card"></i> Card</button>
            <button type="button" class="payment-btn bkash" onclick="setPayment('bkash', this)"><i class="fas fa-mobile-alt"></i> bKash</button>
            <button type="button" class="payment-btn nagad" onclick="setPayment('nagad', this)"><i class="fas fa-mobile-alt"></i> Nagad</button>
        </div>
        
        <button type="submit" class="btn btn-primary w-100 mt-3" onclick="prepareSubmit()">
            <i class="fas fa-check-circle"></i> Complete Sale (F12)
        </button>
    </div>
</div>
</form>

<script>
let cart = [];
let products = [];

// Load products
fetch('get_products.php')
    .then(res => res.json())
    .then(data => {
        products = data;
        displayProducts(products);
    });

function displayProducts(productsToShow) {
    const grid = document.getElementById('productGrid');
    if (!productsToShow.length) {
        grid.innerHTML = '<p class="text-muted text-center">No products found. <a href="items.php">Add items</a></p>';
        return;
    }
    
    grid.innerHTML = productsToShow.map(p => `
        <div class="product-item" onclick="addToCart(${p.id})">
            ${p.booking_required ? '<div class="booking-badge">📅 Booking</div>' : ''}
            <img src="${p.image || 'uploads/items/default.jpg'}" class="product-image" onerror="this.src='uploads/items/default.jpg'">
            <div class="product-name"><strong>${escapeHtml(p.name)}</strong></div>
            <div class="product-price"><?= $company['currency'] ?>${parseFloat(p.price).toFixed(2)}</div>
            <small class="text-muted">${p.category || ''}</small>
        </div>
    `).join('');
}

document.querySelectorAll('.category-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const category = this.dataset.category;
        const filtered = category === 'all' ? products : products.filter(p => p.category === category);
        displayProducts(filtered);
    });
});

function addToCart(productId) {
    const product = products.find(p => p.id == productId);
    if (!product) return;
    
    const existing = cart.find(i => i.id === productId);
    if (existing) {
        existing.qty++;
    } else {
        cart.push({
            id: product.id,
            name: product.name,
            price: parseFloat(product.price),
            qty: 1,
            image: product.image,
            booking_required: product.booking_required,
            bk_date: '',
            bk_time: '',
            bk_duration: 1
        });
    }
    renderCart();
}

function renderCart() {
    const container = document.getElementById('cartItems');
    if (cart.length === 0) {
        container.innerHTML = '<p class="text-muted text-center">🛒 Cart is empty</p>';
        updateTotals();
        return;
    }
    
    container.innerHTML = cart.map((item, idx) => `
        <div class="cart-item">
            <div class="d-flex gap-2">
                <img src="${item.image || 'uploads/items/default.jpg'}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 5px;">
                <div style="flex:1">
                    <div class="d-flex justify-content-between">
                        <strong>${escapeHtml(item.name)}</strong>
                        <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(${idx})">✕</button>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <div class="qty-control">
                            <button type="button" class="qty-btn" onclick="changeQty(${idx}, -0.5)">-</button>
                            <input type="number" value="${item.qty}" min="0.5" step="0.5" style="width: 60px; text-align:center" onchange="setQty(${idx}, this.value)">
                            <button type="button" class="qty-btn" onclick="changeQty(${idx}, 0.5)">+</button>
                        </div>
                        <span class="fw-bold"><?= $company['currency'] ?>${(item.price * item.qty).toFixed(2)}</span>
                    </div>
                    ${item.booking_required ? `
                        <div class="mt-2 small">
                            <input type="date" placeholder="Date" class="form-control form-control-sm mb-1" onchange="setBooking(${idx}, 'date', this.value)" value="${item.bk_date}">
                            <input type="time" placeholder="Time" class="form-control form-control-sm" onchange="setBooking(${idx}, 'time', this.value)" value="${item.bk_time}">
                        </div>
                    ` : ''}
                </div>
            </div>
        </div>
    `).join('');
    updateTotals();
}

function changeQty(idx, delta) {
    let newQty = cart[idx].qty + delta;
    if (newQty >= 0.5) {
        cart[idx].qty = newQty;
        renderCart();
    } else if (newQty < 0.5 && delta < 0) {
        removeItem(idx);
    }
}

function setQty(idx, val) {
    cart[idx].qty = parseFloat(val) || 0;
    if (cart[idx].qty <= 0) removeItem(idx);
    else renderCart();
}

function setBooking(idx, field, value) {
    if (field === 'date') cart[idx].bk_date = value;
    if (field === 'time') cart[idx].bk_time = value;
}

function removeItem(idx) {
    cart.splice(idx, 1);
    renderCart();
}

function updateTotals() {
    let subtotal = cart.reduce((sum, i) => sum + (i.price * i.qty), 0);
    let discount = parseFloat(document.getElementById('discount').value) || 0;
    let taxRate = <?= $company['tax_rate'] ?? 10 ?>;
    let tax = (subtotal - discount) * (taxRate / 100);
    let total = subtotal - discount + tax;
    
    document.getElementById('subtotal').innerHTML = '<?= $company['currency'] ?>' + subtotal.toFixed(2);
    document.getElementById('tax').innerHTML = '<?= $company['currency'] ?>' + tax.toFixed(2);
    document.getElementById('total').innerHTML = '<?= $company['currency'] ?>' + total.toFixed(2);
}

document.getElementById('discount').addEventListener('input', () => updateTotals());

function setPayment(method, btn) {
    document.getElementById('paymentMethod').value = method;
    document.querySelectorAll('.payment-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
}

function prepareSubmit() {
    if (cart.length === 0) {
        alert('Cart is empty!');
        return false;
    }
    
    for (let item of cart) {
        if (item.booking_required && (!item.bk_date || !item.bk_time)) {
            alert(`Please set booking date and time for ${item.name}`);
            return false;
        }
    }
    
    document.getElementById('cartData').value = JSON.stringify(cart);
    return true;
}

document.getElementById('posForm').addEventListener('submit', function(e) {
    if (!prepareSubmit()) {
        e.preventDefault();
    }
});

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'F12') {
        e.preventDefault();
        if (cart.length > 0 && prepareSubmit()) {
            document.getElementById('posForm').submit();
        } else if (cart.length === 0) {
            alert('Cart is empty!');
        }
    }
});
</script>

<?php require_once 'footer.php'; ?>