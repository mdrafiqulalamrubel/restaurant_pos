<?php
$page_title = 'Resource & Hall Booking';
$page_icon = 'building';
require_once 'config.php';
require_once 'header.php';

// Handle booking creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_booking') {
        $resource_id = $_POST['resource_id'];
        $customer_id = $_POST['customer_id'];
        $booking_date = $_POST['booking_date'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $event_type = $_POST['event_type'];
        $guests = $_POST['number_of_guests'];
        $special_requests = $_POST['special_requests'];
        
        // Calculate duration and amount
        $start = new DateTime($start_time);
        $end = new DateTime($end_time);
        $duration = $end->diff($start)->h + ($end->diff($start)->i / 60);
        
        $stmt = $pdo->prepare("SELECT price_per_hour FROM resources WHERE id = ?");
        $stmt->execute([$resource_id]);
        $price_per_hour = $stmt->fetchColumn();
        $total_amount = $price_per_hour * $duration;
        
        $stmt = $pdo->prepare("INSERT INTO reservations (resource_id, customer_id, booking_date, start_time, end_time, duration_hours, event_type, number_of_guests, special_requests, total_amount) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$resource_id, $customer_id, $booking_date, $start_time, $end_time, $duration, $event_type, $guests, $special_requests, $total_amount]);
        
        header('Location: bookings.php?success=1');
        exit;
    }
}

// Get resources by type
$type_id = $_GET['type'] ?? 0;
$booking_date = $_GET['date'] ?? date('Y-m-d');

$resources = $pdo->query("
    SELECT r.*, rt.name as type_name, rt.icon 
    FROM resources r 
    JOIN resource_types rt ON r.resource_type_id = rt.id 
    WHERE r.status = 'available'
    ORDER BY rt.sort_order, r.name
")->fetchAll(PDO::FETCH_ASSOC);

$customers = $pdo->query("SELECT id, name, phone FROM customers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$resource_types = $pdo->query("SELECT * FROM resource_types ORDER BY sort_order")->fetchAll(PDO::FETCH_ASSOC);

// Get today's bookings
$today_bookings = $pdo->prepare("
    SELECT r.*, res.name as resource_name, c.name as customer_name, rt.name as type_name
    FROM reservations r
    JOIN resources res ON r.resource_id = res.id
    JOIN customers c ON r.customer_id = c.id
    JOIN resource_types rt ON res.resource_type_id = rt.id
    WHERE r.booking_date = CURDATE()
    ORDER BY r.start_time
");
$today_bookings->execute();
$bookings_today = $today_bookings->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .resource-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s;
        cursor: pointer;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .resource-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    }
    .resource-image {
        height: 180px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 3rem;
    }
    .resource-info {
        padding: 15px;
    }
    .booking-modal {
        max-width: 600px;
    }
    .booking-slot {
        background: #e9ecef;
        padding: 5px 10px;
        border-radius: 5px;
        margin: 2px;
        display: inline-block;
        font-size: 0.8rem;
    }
    .status-pending { background: #ffc107; color: #000; }
    .status-confirmed { background: #28a745; color: #fff; }
    .status-cancelled { background: #dc3545; color: #fff; }
</style>

<div class="row">
    <!-- Sidebar - Booking Form -->
    <div class="col-md-4">
        <div class="card sticky-top" style="top: 20px;">
            <div class="card-header">
                <h5><i class="fas fa-calendar-plus"></i> New Booking</h5>
            </div>
            <div class="card-body">
                <form method="post" id="bookingForm">
                    <input type="hidden" name="action" value="create_booking">
                    <input type="hidden" name="resource_id" id="selected_resource_id">
                    
                    <div class="mb-3">
                        <label>Select Resource *</label>
                        <div id="resource_selection">
                            <p class="text-muted">Click on any resource card to select</p>
                        </div>
                        <input type="text" id="selected_resource_name" class="form-control mt-2" readonly placeholder="No resource selected">
                    </div>
                    
                    <div class="mb-3">
                        <label>Customer *</label>
                        <select name="customer_id" class="form-control" required>
                            <option value="">Select Customer</option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= $c['phone'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <a href="customer_add.php" target="_blank" class="btn btn-sm btn-link">+ Add New Customer</a>
                    </div>
                    
                    <div class="mb-3">
                        <label>Booking Date</label>
                        <input type="date" name="booking_date" class="form-control" value="<?= $booking_date ?>" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label>Start Time</label>
                            <input type="time" name="start_time" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label>End Time</label>
                            <input type="time" name="end_time" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="mb-3 mt-2">
                        <label>Event Type</label>
                        <select name="event_type" class="form-control">
                            <option value="birthday">Birthday Party</option>
                            <option value="wedding">Wedding</option>
                            <option value="anniversary">Anniversary</option>
                            <option value="corporate">Corporate Event</option>
                            <option value="conference">Conference</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label>Number of Guests</label>
                        <input type="number" name="number_of_guests" class="form-control" value="50">
                    </div>
                    
                    <div class="mb-3">
                        <label>Special Requests</label>
                        <textarea name="special_requests" class="form-control" rows="3" placeholder="Any special requirements..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-check-circle"></i> Create Booking
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Resources Grid -->
    <div class="col-md-8">
        <!-- Resource Types Filter -->
        <div class="btn-group mb-3 flex-wrap">
            <a href="?type=0" class="btn btn-outline-primary <?= $type_id == 0 ? 'active' : '' ?>">All</a>
            <?php foreach ($resource_types as $type): ?>
                <a href="?type=<?= $type['id'] ?>" class="btn btn-outline-primary <?= $type_id == $type['id'] ? 'active' : '' ?>">
                    <i class="fas fa-<?= $type['icon'] ?>"></i> <?= $type['name'] ?>
                </a>
            <?php endforeach; ?>
        </div>
        
        <!-- Resources Grid -->
        <div class="row">
            <?php foreach ($resources as $r): 
                if ($type_id > 0 && $r['resource_type_id'] != $type_id) continue;
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="resource-card" onclick="selectResource(<?= $r['id'] ?>, '<?= htmlspecialchars($r['name']) ?>', <?= $r['price_per_hour'] ?>)">
                        <div class="resource-image">
                            <i class="fas fa-<?= $r['icon'] ?? 'building' ?> fa-4x"></i>
                        </div>
                        <div class="resource-info">
                            <h6><?= htmlspecialchars($r['name']) ?></h6>
                            <small class="text-muted"><?= $r['type_name'] ?></small>
                            <p class="mt-2 mb-1">
                                <i class="fas fa-users"></i> Capacity: <?= $r['capacity'] ?> people<br>
                                <i class="fas fa-clock"></i> â‚<?= money($r['price_per_hour']) ?>/hour
                            </p>
                            <div class="features small text-muted"><?= $r['features'] ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Today's Bookings -->
        <div class="card mt-4">
            <div class="card-header">
                <h5><i class="fas fa-calendar-day"></i> Today's Bookings (<?= date('Y-m-d') ?>)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr><th>Time</th><th>Resource</th><th>Customer</th><th>Event</th><th>Guests</th><th>Status</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings_today as $b): ?>
                            <tr>
                                <td><?= date('H:i', strtotime($b['start_time'])) ?> - <?= date('H:i', strtotime($b['end_time'])) ?></td>
                                <td><?= htmlspecialchars($b['resource_name']) ?> <small>(<?= $b['type_name'] ?>)</small></td>
                                <td><?= htmlspecialchars($b['customer_name']) ?></td>
                                <td><?= ucfirst($b['event_type'] ?? '-') ?></td>
                                <td><?= $b['number_of_guests'] ?></td>
                                <td><span class="badge bg-<?= $b['status'] == 'confirmed' ? 'success' : ($b['status'] == 'pending' ? 'warning' : 'danger') ?>"><?= $b['status'] ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let selectedResourceId = null;

function selectResource(id, name, price) {
    selectedResourceId = id;
    document.getElementById('selected_resource_id').value = id;
    document.getElementById('selected_resource_name').value = name + ' (â‚¬' + price.toFixed(2) + '/hour)';
    
    // Highlight selected card
    document.querySelectorAll('.resource-card').forEach(card => {
        card.style.border = '2px solid transparent';
    });
    event.currentTarget.style.border = '2px solid #667eea';
}
</script>

<?php require_once 'footer.php'; ?>