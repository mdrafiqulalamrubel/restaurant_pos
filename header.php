<?php
// header.php - At the very top
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get current page name
$current_page = basename($_SERVER['PHP_SELF']);
$page_title = $page_title ?? 'Restaurant POS System';
$page_icon = $page_icon ?? 'home';

// Get company settings with error handling
$company = null;
try {
    $stmt = $pdo->query("SELECT * FROM company_settings WHERE id = 1");
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $company = null;
}

if (!$company) {
    $company = [
        'name' => 'Restaurant POS',
        'currency' => '€',
        'currency_code' => 'EUR',
        'logo' => '',
        'address' => '',
        'phone' => '',
        'email' => '',
        'tax_rate' => 10,
        'receipt_footer' => 'Thank you for your business!'
    ];
}

// Check if user is admin
$is_admin = ($_SESSION['role'] ?? '') === 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        .wrapper {
            display: flex;
            width: 100%;
            align-items: stretch;
            min-height: 100vh;
        }
        
        .sidebar {
            min-width: 280px;
            max-width: 280px;
            background: linear-gradient(180deg, #2c3e50 0%, #1a252f 100%);
            color: white;
            transition: all 0.3s;
            position: sticky;
            top: 0;
            left: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
        }
        
        .sidebar.collapsed {
            min-width: 70px;
            max-width: 70px;
        }
        
        .sidebar.collapsed .sidebar-header h3 span,
        .sidebar.collapsed .sidebar-header p,
        .sidebar.collapsed .nav-link span,
        .sidebar.collapsed .nav-section span {
            display: none;
        }
        
        .sidebar.collapsed .nav-link {
            justify-content: center;
            padding: 12px;
        }
        
        .sidebar.collapsed .nav-link i {
            font-size: 1.2rem;
            margin: 0;
        }
        
        .sidebar.collapsed .nav-section {
            text-align: center;
            font-size: 0.6rem;
        }
        
        .sidebar-header {
            padding: 20px;
            background: rgba(0,0,0,0.2);
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            position: relative;
        }
        
        .toggle-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 5px 8px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .toggle-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .sidebar-header h3 {
            margin: 0;
            font-size: 1.3rem;
        }
        
        .sidebar-header p {
            margin: 5px 0 0;
            font-size: 0.8rem;
            opacity: 0.8;
        }
        
        .sidebar .nav-item {
            width: 100%;
        }
        
        .sidebar .nav-link {
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            transition: all 0.3s;
            border-radius: 0;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }
        
        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            padding-left: 25px;
        }
        
        .sidebar .nav-link.active {
            background: #667eea;
            color: white;
            border-left: 4px solid #fff;
        }
        
        .sidebar .nav-link i {
            width: 24px;
            text-align: center;
        }
        
        .nav-section {
            padding: 15px 20px 5px;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255,255,255,0.5);
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: 10px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .nav-section:first-of-type {
            border-top: none;
            margin-top: 0;
        }
        
        .nav-section:hover {
            color: white;
        }
        
        .nav-section .toggle-icon {
            font-size: 10px;
            transition: transform 0.3s;
        }
        
        .nav-section.collapsed .toggle-icon {
            transform: rotate(-90deg);
        }
        
        .nav-section-content {
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        
        .nav-section-content.collapsed {
            max-height: 0 !important;
        }
        
        .content {
            flex: 1;
            padding: 20px;
            overflow-x: auto;
            width: 100%;
        }
        
        .top-bar {
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: #667eea;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .currency-badge {
            background: #667eea;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .logout-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .logout-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        
        .footer {
            text-align: center;
            padding: 20px;
            margin-top: 30px;
            background: white;
            border-radius: 10px;
            color: #666;
            font-size: 12px;
        }
        
        /* Access Denied Modal */
        .modal-access-denied .modal-content {
            border-radius: 15px;
            text-align: center;
            padding: 20px;
        }
        .modal-access-denied .modal-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 15px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                min-width: 70px;
                max-width: 70px;
            }
            .sidebar .nav-link span,
            .sidebar-header h3 span,
            .sidebar-header p {
                display: none;
            }
            .sidebar .nav-link {
                justify-content: center;
                padding: 15px;
            }
            .sidebar .nav-link i {
                font-size: 1.2rem;
            }
            .nav-section span {
                display: none;
            }
        }
        
        .card {
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: none;
            margin-bottom: 20px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.4);
        }
        
        .logo-img {
            max-width: 120px;
            max-height: 50px;
            margin-bottom: 10px;
        }
        
        .disabled-link {
            opacity: 0.6;
            cursor: not-allowed;
            pointer-events: none;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <!-- Sidebar Navigation -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <button class="toggle-btn" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <?php if ($company['logo'] && file_exists($company['logo'])): ?>
                <img src="<?= $company['logo'] ?>" class="logo-img" alt="Logo">
            <?php endif; ?>
            <h3><i class="fas fa-utensils"></i> <span><?= htmlspecialchars(substr($company['name'], 0, 15)) ?></span></h3>
            <p><span><?= htmlspecialchars($_SESSION['role'] ?? 'Staff') ?></span></p>
        </div>
        
        <!-- MAIN Section -->
        <div class="nav-section" onclick="toggleSection(this)">
            <span><i class="fas fa-tachometer-alt"></i> MAIN</span>
            <i class="fas fa-chevron-down toggle-icon"></i>
        </div>
        <div class="nav-section-content">
            <div class="nav-item">
                <a href="index.php" class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="pos.php" class="nav-link <?= $current_page == 'pos.php' ? 'active' : '' ?>">
                    <i class="fas fa-cash-register"></i> <span>Point of Sale</span>
                </a>
            </div>
        </div>
        
        <!-- INVENTORY Section -->
        <div class="nav-section" onclick="toggleSection(this)">
            <span><i class="fas fa-boxes"></i> INVENTORY</span>
            <i class="fas fa-chevron-down toggle-icon"></i>
        </div>
        <div class="nav-section-content">
            <div class="nav-item">
                <a href="items.php" class="nav-link <?= $current_page == 'items.php' ? 'active' : '' ?>">
                    <i class="fas fa-utensils"></i> <span>Menu Items</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="suppliers.php" class="nav-link <?= $current_page == 'suppliers.php' ? 'active' : '' ?>">
                    <i class="fas fa-building"></i> <span>Suppliers</span>
                </a>
            </div>
        </div>
        
        <!-- CUSTOMERS Section -->
        <div class="nav-section" onclick="toggleSection(this)">
            <span><i class="fas fa-users"></i> CUSTOMERS</span>
            <i class="fas fa-chevron-down toggle-icon"></i>
        </div>
        <div class="nav-section-content">
            <div class="nav-item">
                <a href="customers.php" class="nav-link <?= $current_page == 'customers.php' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i> <span>All Customers</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="customer_add.php" class="nav-link">
                    <i class="fas fa-user-plus"></i> <span>Add Customer</span>
                </a>
            </div>
        </div>
        
        <!-- BOOKINGS Section -->
        <div class="nav-section" onclick="toggleSection(this)">
            <span><i class="fas fa-calendar-alt"></i> BOOKINGS</span>
            <i class="fas fa-chevron-down toggle-icon"></i>
        </div>
        <div class="nav-section-content">
            <div class="nav-item">
                <a href="bookings.php" class="nav-link <?= $current_page == 'bookings.php' ? 'active' : '' ?>">
                    <i class="fas fa-building"></i> <span>Hall/Resources</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="tables.php" class="nav-link <?= $current_page == 'tables.php' ? 'active' : '' ?>">
                    <i class="fas fa-chair"></i> <span>Dining Tables</span>
                </a>
            </div>
        </div>
        
        <!-- FINANCE Section -->
        <div class="nav-section" onclick="toggleSection(this)">
            <span><i class="fas fa-chart-line"></i> FINANCE</span>
            <i class="fas fa-chevron-down toggle-icon"></i>
        </div>
        <div class="nav-section-content">
            <div class="nav-item">
                <a href="expenses.php" class="nav-link <?= $current_page == 'expenses.php' ? 'active' : '' ?>">
                    <i class="fas fa-money-bill-wave"></i> <span>Expenses</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="cash_balance.php" class="nav-link <?= $current_page == 'cash_balance.php' ? 'active' : '' ?>">
                    <i class="fas fa-chart-line"></i> <span>Cash Balance</span>
                </a>
            </div>
        </div>
        
        <!-- TRANSACTIONS Section -->
        <div class="nav-section" onclick="toggleSection(this)">
            <span><i class="fas fa-receipt"></i> TRANSACTIONS</span>
            <i class="fas fa-chevron-down toggle-icon"></i>
        </div>
        <div class="nav-section-content">
            <div class="nav-item">
                <a href="transactions.php" class="nav-link <?= $current_page == 'transactions.php' ? 'active' : '' ?>">
                    <i class="fas fa-receipt"></i> <span>All Sales</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="reports.php" class="nav-link <?= $current_page == 'reports.php' ? 'active' : '' ?>">
                    <i class="fas fa-chart-line"></i> <span>Reports</span>
                </a>
            </div>
        </div>
        
        <!-- SETTINGS Section - Only visible to Admin -->
        <?php if ($is_admin): ?>
        <div class="nav-section" onclick="toggleSection(this)">
            <span><i class="fas fa-cog"></i> SETTINGS</span>
            <i class="fas fa-chevron-down toggle-icon"></i>
        </div>
        <div class="nav-section-content">
            <div class="nav-item">
                <a href="users.php" class="nav-link <?= in_array($current_page, ['users.php', 'user_add.php', 'user_edit.php', 'user_permissions.php', 'user_activity.php']) ? 'active' : '' ?>">
                    <i class="fas fa-users-cog"></i> <span>User Management</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="company_settings.php" class="nav-link <?= $current_page == 'company_settings.php' ? 'active' : '' ?>">
                    <i class="fas fa-building"></i> <span>Company Settings</span>
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Main Content -->
    <div class="content">
        <div class="top-bar">
            <h4 class="page-title"><i class="fas fa-<?= $page_icon ?>"></i> <?= $page_title ?></h4>
            <div class="user-info">
                <span class="currency-badge"><i class="fas fa-money-bill"></i> <?= $company['currency'] ?> (<?= $company['currency_code'] ?>)</span>
                <span><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username']) ?></span>
                <div class="user-avatar">
                    <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                </div>
                <a href="logout.php" class="logout-btn" onclick="return confirm('Are you sure you want to logout?')">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>