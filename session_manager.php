<?php
$page_title = 'POS Session Manager';
$page_icon = 'clock';
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$branch_id = $_SESSION['branch_id'] ?? 1;
$is_admin = ($_SESSION['role'] ?? '') === 'admin';

// Verify user exists in database
$stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    // User doesn't exist, logout
    session_destroy();
    header('Location: login.php');
    exit;
}

// Check for active session - MUST be before any output
$stmt = $pdo->prepare("
    SELECT * FROM pos_sessions 
    WHERE user_id = ? AND status = 'open' 
    ORDER BY id DESC LIMIT 1
");
$stmt->execute([$user_id]);
$active_session = $stmt->fetch();

// Start new session - Process BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_session'])) {
    $opening_balance = $_POST['opening_balance'] ?? 0;
    $session_token = bin2hex(random_bytes(16));
    
    // Check if branch exists
    $stmt = $pdo->prepare("SELECT id FROM branches WHERE id = ?");
    $stmt->execute([$branch_id]);
    if (!$stmt->fetch()) {
        // Create default branch if not exists
        $pdo->exec("INSERT INTO branches (id, name, code, status) VALUES (1, 'Main Branch', 'MB001', 'active') ON DUPLICATE KEY UPDATE name=name");
        $branch_id = 1;
        $_SESSION['branch_id'] = 1;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO pos_sessions (user_id, branch_id, session_token, opening_balance, opening_time) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$user_id, $branch_id, $session_token, $opening_balance]);
    
    header('Location: pos.php');
    exit;
}

// Close session - Process BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close_session'])) {
    $session_id = $_POST['session_id'];
    $actual_cash = $_POST['actual_cash'];
    $notes = $_POST['notes'] ?? '';
    
    // Get session data
    $stmt = $pdo->prepare("
        SELECT * FROM pos_sessions WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$session_id, $user_id]);
    $session = $stmt->fetch();
    
    if ($session) {
        // Calculate sales during session
        $stmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(CASE WHEN payment_method = 'cash' THEN total ELSE 0 END), 0) as cash_sales,
                COALESCE(SUM(CASE WHEN payment_method = 'card' THEN total ELSE 0 END), 0) as card_sales,
                COALESCE(SUM(CASE WHEN payment_method = 'bkash' THEN total ELSE 0 END), 0) as bkash_sales,
                COALESCE(SUM(CASE WHEN payment_method = 'nagad' THEN total ELSE 0 END), 0) as nagad_sales,
                COUNT(*) as total_transactions,
                COALESCE(SUM(total), 0) as total_sales
            FROM sales 
            WHERE session_id = ? AND branch_id = ?
        ");
        $stmt->execute([$session_id, $branch_id]);
        $sales_data = $stmt->fetch();
        
        // Get expenses during session
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) as total_expenses
            FROM expenses 
            WHERE session_id = ? AND branch_id = ?
        ");
        $stmt->execute([$session_id, $branch_id]);
        $expenses_data = $stmt->fetch();
        
        $expected_cash = $session['opening_balance'] + $sales_data['cash_sales'] - $expenses_data['total_expenses'];
        $cash_difference = $actual_cash - $expected_cash;
        
        // Update session
        $stmt = $pdo->prepare("
            UPDATE pos_sessions SET 
                closing_time = NOW(),
                closing_balance = ?,
                cash_sales = ?,
                card_sales = ?,
                bkash_sales = ?,
                nagad_sales = ?,
                total_sales = ?,
                total_expenses = ?,
                expected_cash = ?,
                actual_cash = ?,
                cash_difference = ?,
                status = 'closed',
                notes = ?,
                closed_by = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $actual_cash,
            $sales_data['cash_sales'],
            $sales_data['card_sales'],
            $sales_data['bkash_sales'],
            $sales_data['nagad_sales'],
            $sales_data['total_sales'],
            $expenses_data['total_expenses'],
            $expected_cash,
            $actual_cash,
            $cash_difference,
            $notes,
            $user_id,
            $session_id
        ]);
        
        header('Location: session_report.php?id=' . $session_id);
        exit;
    }
}

// Now include header AFTER all redirects
require_once 'header.php';

// Get session history
$history_stmt = $pdo->prepare("
    SELECT s.*, u.username as user_name, u2.username as closed_by_name
    FROM pos_sessions s
    LEFT JOIN users u ON s.user_id = u.id
    LEFT JOIN users u2 ON s.closed_by = u2.id
    WHERE s.branch_id = ? 
    ORDER BY s.id DESC 
    LIMIT 20
");
$history_stmt->execute([$branch_id]);
$session_history = $history_stmt->fetchAll();
?>

<style>
    .session-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .session-status-open {
        background: #28a745;
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        display: inline-block;
    }
    .session-status-closed {
        background: #6c757d;
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        display: inline-block;
    }
    .stats-number {
        font-size: 1.5rem;
        font-weight: bold;
        color: #667eea;
    }
</style>

<div class="row">
    <div class="col-md-12">
        <div class="session-card">
            <h4><i class="fas fa-clock"></i> POS Session</h4>
            <hr>
            
            <?php if ($active_session): ?>
                <!-- Active Session Info -->
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> 
                    <strong>Session Open!</strong> Started at: <?= date('d/m/Y H:i:s', strtotime($active_session['opening_time'])) ?>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="stats-number"><?= number_format($active_session['opening_balance'], 2) ?></div>
                        <div>Opening Balance</div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-number"><?= number_format($active_session['opening_balance'], 2) ?></div>
                        <div>Current Balance</div>
                    </div>
                    <div class="col-md-4">
                        <a href="pos.php" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-cash-register"></i> Go to POS
                        </a>
                    </div>
                </div>
                
                <hr>
                <h5>Close Session</h5>
                <form method="post" onsubmit="return confirm('Are you sure you want to close this session? This will generate a closing report.')">
                    <input type="hidden" name="close_session" value="1">
                    <input type="hidden" name="session_id" value="<?= $active_session['id'] ?>">
                    <div class="row">
                        <div class="col-md-4">
                            <label>Actual Cash in Hand</label>
                            <input type="number" name="actual_cash" class="form-control" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label>Closing Notes</label>
                            <input type="text" name="notes" class="form-control" placeholder="Any remarks...">
                        </div>
                        <div class="col-md-2">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-danger w-100">Close Session</button>
                        </div>
                    </div>
                </form>
                
            <?php else: ?>
                <!-- Start New Session -->
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    No active session found. Please start a new session before using POS.
                </div>
                
                <form method="post">
                    <input type="hidden" name="start_session" value="1">
                    <div class="row">
                        <div class="col-md-4">
                            <label>Opening Cash Balance</label>
                            <input type="number" name="opening_balance" class="form-control" step="0.01" value="0" required>
                        </div>
                        <div class="col-md-8">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-success w-100">Start New Session</button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-history"></i> Session History</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Session ID</th>
                        <th>Date</th>
                        <th>User</th>
                        <th>Opening</th>
                        <th>Cash Sales</th>
                        <th>Total Sales</th>
                        <th>Expected Cash</th>
                        <th>Actual Cash</th>
                        <th>Difference</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($session_history as $session): ?>
                    <tr>
                        <td>#<?= $session['id'] ?></td>
                        <td><?= date('d/m/Y', strtotime($session['opening_time'])) ?></td>
                        <td><?= htmlspecialchars($session['user_name']) ?></td>
                        <td class="text-end"><?= number_format($session['opening_balance'], 2) ?></td>
                        <td class="text-end"><?= number_format($session['cash_sales'], 2) ?></td>
                        <td class="text-end"><?= number_format($session['total_sales'], 2) ?></td>
                        <td class="text-end"><?= number_format($session['expected_cash'], 2) ?></td>
                        <td class="text-end"><?= number_format($session['actual_cash'], 2) ?></td>
                        <td class="text-end <?= $session['cash_difference'] >= 0 ? 'text-success' : 'text-danger' ?>">
                            <?= number_format($session['cash_difference'], 2) ?>
                        </td>
                        <td>
                            <?php if ($session['status'] == 'open'): ?>
                                <span class="session-status-open">Open</span>
                            <?php else: ?>
                                <span class="session-status-closed">Closed</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="session_report.php?id=<?= $session['id'] ?>" class="btn btn-sm btn-info">Report</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>