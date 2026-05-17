<?php
$page_title = 'Decorator Management';
$page_icon = 'palette';
require_once 'config.php';
require_once 'header.php';

// Add decorator
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $stmt = $pdo->prepare("INSERT INTO decorators (name, contact_person, phone, email, service_type, price_range) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$_POST['name'], $_POST['contact_person'], $_POST['phone'], $_POST['email'], $_POST['service_type'], $_POST['price_range']]);
        header('Location: decorators.php');
        exit;
    }
}

$decorators = $pdo->query("SELECT * FROM decorators ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-palette"></i> Event Decorators</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr><th>Name</th><th>Contact</th><th>Phone</th><th>Service Type</th><th>Price Range</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($decorators as $d): ?>
                    <tr>
                        <td><?= htmlspecialchars($d['name']) ?></td>
                        <td><?= htmlspecialchars($d['contact_person'] ?? '-') ?></td>
                        <td><?= $d['phone'] ?></td>
                        <td><?= $d['service_type'] ?></td>
                        <td><?= $d['price_range'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>