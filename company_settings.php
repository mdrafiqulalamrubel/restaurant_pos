<?php
$page_title = 'Company Settings';
$page_icon = 'building';
require_once 'config.php';
require_once 'header.php';

// Create settings table if not exists
$pdo->exec("CREATE TABLE IF NOT EXISTS company_settings (
    id INT PRIMARY KEY DEFAULT 1,
    name VARCHAR(200) DEFAULT 'Restaurant POS',
    address TEXT,
    phone VARCHAR(50),
    email VARCHAR(100),
    website VARCHAR(100),
    logo VARCHAR(255),
    currency VARCHAR(10) DEFAULT '€',
    currency_code VARCHAR(5) DEFAULT 'EUR',
    tax_rate DECIMAL(5,2) DEFAULT 10.00,
    receipt_footer TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $website = $_POST['website'];
    $currency = $_POST['currency'];
    $currency_code = $_POST['currency_code'];
    $tax_rate = $_POST['tax_rate'];
    $receipt_footer = $_POST['receipt_footer'];
    
    $logo_path = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/company/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $filename = 'logo.' . $ext;
        $destination = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $destination)) {
            $logo_path = $destination;
        }
    }
    
    if ($logo_path) {
        $stmt = $pdo->prepare("UPDATE company_settings SET name=?, address=?, phone=?, email=?, website=?, logo=?, currency=?, currency_code=?, tax_rate=?, receipt_footer=? WHERE id=1");
        $stmt->execute([$name, $address, $phone, $email, $website, $logo_path, $currency, $currency_code, $tax_rate, $receipt_footer]);
    } else {
        $stmt = $pdo->prepare("UPDATE company_settings SET name=?, address=?, phone=?, email=?, website=?, currency=?, currency_code=?, tax_rate=?, receipt_footer=? WHERE id=1");
        $stmt->execute([$name, $address, $phone, $email, $website, $currency, $currency_code, $tax_rate, $receipt_footer]);
    }
    
    $success = "Settings saved successfully!";
}

$settings = $pdo->query("SELECT * FROM company_settings WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
if (!$settings) {
    $pdo->exec("INSERT INTO company_settings (id, name) VALUES (1, 'Restaurant POS')");
    $settings = $pdo->query("SELECT * FROM company_settings WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
}

$currencies = [
    'USD' => ['symbol' => '$', 'code' => 'USD', 'name' => 'US Dollar'],
    'EUR' => ['symbol' => '€', 'code' => 'EUR', 'name' => 'Euro'],
    'GBP' => ['symbol' => '£', 'code' => 'GBP', 'name' => 'British Pound'],
    'BDT' => ['symbol' => '৳', 'code' => 'BDT', 'name' => 'Bangladeshi Taka'],
    'INR' => ['symbol' => '₹', 'code' => 'INR', 'name' => 'Indian Rupee'],
    'AED' => ['symbol' => 'د.إ', 'code' => 'AED', 'name' => 'UAE Dirham'],
    'SAR' => ['symbol' => '﷼', 'code' => 'SAR', 'name' => 'Saudi Riyal']
];
?>

<style>
    .settings-form {
        max-width: 800px;
        margin: 0 auto;
    }
    .form-card {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .logo-preview {
        max-width: 150px;
        max-height: 80px;
        margin-bottom: 10px;
    }
    .currency-symbol {
        font-size: 1.2rem;
        font-weight: bold;
        color: #667eea;
    }
</style>

<div class="settings-form">
    <div class="form-card">
        <h4 class="mb-4"><i class="fas fa-building"></i> Company Profile Settings</h4>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">✅ <?= $success ?></div>
        <?php endif; ?>
        
        <form method="post" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label>Company Name *</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($settings['name']) ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label>Company Logo</label>
                        <input type="file" name="logo" class="form-control" accept="image/*">
                        <?php if ($settings['logo'] && file_exists($settings['logo'])): ?>
                            <img src="<?= $settings['logo'] ?>" class="logo-preview mt-2">
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label>Address</label>
                <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($settings['address'] ?? '') ?></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Phone Number</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($settings['phone'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($settings['email'] ?? '') ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label>Website</label>
                <input type="url" name="website" class="form-control" value="<?= htmlspecialchars($settings['website'] ?? '') ?>">
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Currency Symbol</label>
                    <select name="currency" class="form-control">
                        <?php foreach ($currencies as $curr): ?>
                            <option value="<?= $curr['symbol'] ?>" <?= ($settings['currency'] ?? '€') == $curr['symbol'] ? 'selected' : '' ?>>
                                <?= $curr['symbol'] ?> - <?= $curr['name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Currency Code</label>
                    <select name="currency_code" class="form-control">
                        <?php foreach ($currencies as $curr): ?>
                            <option value="<?= $curr['code'] ?>" <?= ($settings['currency_code'] ?? 'EUR') == $curr['code'] ? 'selected' : '' ?>>
                                <?= $curr['code'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label>Tax Rate (%)</label>
                <input type="number" name="tax_rate" class="form-control" step="0.01" value="<?= $settings['tax_rate'] ?? 10 ?>">
            </div>
            
            <div class="mb-3">
                <label>Receipt Footer Message</label>
                <textarea name="receipt_footer" class="form-control" rows="3" placeholder="Thank you for your business!"><?= htmlspecialchars($settings['receipt_footer'] ?? 'Thank you for visiting us!') ?></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-save"></i> Save Settings
            </button>
        </form>
    </div>
</div>

<?php require_once 'footer.php'; ?>