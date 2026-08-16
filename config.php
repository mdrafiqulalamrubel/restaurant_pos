<?php
// config.php - Production-safe configuration
// Only show errors in local/dev environments
$is_local = in_array($_SERVER['SERVER_NAME'] ?? 'cli', ['localhost', '127.0.0.1', '::1']);
if ($is_local) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

$host     = 'localhost';
$dbname   = 'restaurant_pos_bookings';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Global currency settings — guard with defined() to prevent duplicate-constant warnings
if (!defined('CURRENCY_SYMBOL')) {
    try {
        $company_settings = $pdo->query("SELECT currency, currency_code FROM company_settings WHERE id = 1")->fetch();
        define('CURRENCY_SYMBOL', (!empty($company_settings['currency'])  ? $company_settings['currency']      : '$'));
        define('CURRENCY_CODE',   (!empty($company_settings['currency_code']) ? $company_settings['currency_code'] : 'USD'));
    } catch (Exception $e) {
        define('CURRENCY_SYMBOL', '$');
        define('CURRENCY_CODE',   'USD');
    }
}

if (!function_exists('format_currency')) {
    function format_currency($amount) {
        return number_format((float)($amount ?? 0), 2);
    }
}

if (!function_exists('money')) {
    function money($amount) {
        return CURRENCY_SYMBOL . format_currency($amount);
    }
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set current branch in session if not set and user is logged in
if (isset($_SESSION['user_id'])) {
    try {
        // Get user's default branch
        $stmt = $pdo->prepare("SELECT u.branch_id, u.role FROM users u WHERE u.id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user_data = $stmt->fetch();
        
        if ($user_data) {
            // Set role from database if not set in session
            if (!isset($_SESSION['role'])) {
                $_SESSION['role'] = $user_data['role'] ?? 'staff';
            }
            
            if (!isset($_SESSION['branch_id']) || empty($_SESSION['branch_id'])) {
                $_SESSION['branch_id'] = $user_data['branch_id'] ?? 1;
            }
            
            // For admin, also ensure they have branch access
            if ($_SESSION['role'] === 'admin') {
                // Check if admin has any branch access
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_branch_access WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $has_access = $stmt->fetchColumn();
                
                if ($has_access == 0) {
                    // Give admin access to all branches
                    $stmt = $pdo->prepare("
                        INSERT INTO user_branch_access (user_id, branch_id)
                        SELECT ?, b.id FROM branches b WHERE b.status = 'active'
                    ");
                    $stmt->execute([$_SESSION['user_id']]);
                }
            }
        }
    } catch (PDOException $e) {
        // Table might not exist yet, continue
    }
}

// Function to get user's accessible branches
if (!function_exists('getUserBranches')) {
    function getUserBranches($pdo, $user_id) {
        try {
            $stmt = $pdo->prepare("
                SELECT b.* FROM branches b 
                JOIN user_branch_access uba ON b.id = uba.branch_id 
                WHERE uba.user_id = ? AND b.status = 'active'
                ORDER BY b.name
            ");
            $stmt->execute([$user_id]);
            $result = $stmt->fetchAll();
            
            // If no branches found, return at least the default branch
            if (empty($result)) {
                $stmt = $pdo->prepare("SELECT * FROM branches WHERE status = 'active' LIMIT 1");
                $stmt->execute();
                $result = $stmt->fetchAll();
            }
            return $result;
        } catch (PDOException $e) {
            return [];
        }
    }
}

// Function to check if user can access a branch
if (!function_exists('canAccessBranch')) {
    function canAccessBranch($pdo, $user_id, $branch_id) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_branch_access WHERE user_id = ? AND branch_id = ?");
            $stmt->execute([$user_id, $branch_id]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return true;
        }
    }
}
?>