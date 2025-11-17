<?php
require_once __DIR__ . '/../core/auth.php';
requireLogin();

if (!isAdmin()) {
    header('Location: user_dashboard.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

// Fetch data functions
function getTotalUsers($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM users");
    return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
}

function getActiveUsers($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM users WHERE is_active = 1");
    return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
}

function getInactiveUsers($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM users WHERE is_active = 0");
    return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
}

function getUnreadAlertsCount($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM admin_alerts WHERE is_read = 0");
    return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
}

function getTotalAlerts($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM alerts");
    return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
}

function getRecentAccessLogs($pdo, $limit = 10) {
    $stmt = $pdo->prepare("
        SELECT al.id, al.uid, u.name AS user_name, al.status, al.log_time
        FROM access_logs al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.log_time DESC
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRecentTimeLogs($pdo, $limit = 10) {
    $stmt = $pdo->prepare("
        SELECT tl.id, u.name AS user_name, tl.uid, tl.time_in, tl.time_out
        FROM time_logs tl
        LEFT JOIN users u ON tl.user_id = u.id
        ORDER BY tl.time_in DESC
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRecentAlerts($pdo, $limit = 5) {
    $stmt = $pdo->prepare("
        SELECT id, uid, user_name, alert_type, message, created_at, is_read
        FROM admin_alerts
        ORDER BY created_at DESC
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch all statistics
$totalUsers = getTotalUsers($pdo);
$totalAlerts = getTotalAlerts($pdo);
$accessLogs = getRecentAccessLogs($pdo);
$timeLogs = getRecentTimeLogs($pdo);
$recentAlerts = getRecentAlerts($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - RFID Monitoring</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .table-responsive { max-height: 400px; overflow-y: auto; }
        .card { border-radius: 1rem; }
    </style>
</head>
<body>

<!-- TOGGLE BUTTON / HAMBURGER -->
<button class="toggle-btn" id="toggleBtn"><i class="bi bi-list"></i></button>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="#">RFID Monitoring</a>
        <div class="ms-auto d-flex gap-2">
            <a href="read_tag.php" class="btn btn-sm btn-primary">Read Tag</a>
            <a href="register_user.php" class="btn btn-sm btn-success">Register User</a>
            <a href="users.php" class="btn btn-sm btn-warning">User Management</a>
            <!-- Logout Button -->
<button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#logoutModal">
    Logout
</button>


        </div>
    </div>
</nav>

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"> <!-- centers the modal -->
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        Are you sure you want to logout?
      </div>
      <div class="modal-footer justify-content-center">
        <a href="logout.php" class="btn btn-danger">Yes</a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </div>
  </div>
</div>


<div class="container mt-4">

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center shadow">
                <div class="card-body">
                    <h5 class="card-title">Total Users</h5>
                    <p class="display-6"><?= $totalUsers ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-modern">
                <h5>Total Time Logs</h5>
                <div class="number"><?= $totalTimeLogs ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-modern">
                <h5>Total Alerts</h5>
                <div class="number"><?= $totalAlertsAll ?></div>
            </div>
        </div>
    </div>

    <!-- CHARTS -->
    <h3 class="mt-5">Statistics Overview</h3>
    <div class="card-modern p-4">
        <canvas id="statsChart" height="100"></canvas>
    </div>

    <!-- ACCESS LOGS -->
    <h3 class="mt-5">Recent Access Logs</h3>
    <div class="table-modern mt-3">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>UID</th>
                    <th>User</th>
                    <th>Status</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($accessLogs as $log): ?>
                <tr>
                    <td><?= htmlspecialchars($log['uid']) ?></td>
                    <td><?= htmlspecialchars($log['user_name'] ?? 'Unknown') ?></td>
                    <td>
                        <span class="badge bg-<?= $log['status'] === 'granted' ? 'success' : 'danger' ?>">
                            <?= ucfirst($log['status']) ?>
                        </span>
                    </td>
                    <td><?= (new DateTime($log['log_time'], new DateTimeZone('Asia/Manila')))->format('M d, Y h:i A') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- TIME LOGS -->
    <h3 class="mt-5">Recent Time Logs</h3>
    <div class="table-modern mt-3">
        <table class="table table-hover mb-0">
            <thead class="table-success text-dark">
                <tr>
                    <th>User</th>
                    <th>UID</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($timeLogs as $log): ?>
                <tr>
                    <td><?= htmlspecialchars($log['user_name'] ?? 'Unknown') ?></td>
                    <td><?= htmlspecialchars($log['uid']) ?></td>
                    <td><?= (new DateTime($log['time_in'], new DateTimeZone('Asia/Manila')))->format('M d, Y h:i A') ?></td>
                    <td>
                        <?= $log['time_out']
                            ? (new DateTime($log['time_out'], new DateTimeZone('Asia/Manila')))->format('M d, Y h:i A')
                            : '-' ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Recent Alerts -->
    <h3 class="mt-5">Recent Alerts</h3>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="table-danger">
                <tr>
                    <th>User</th>
                    <th>UID</th>
                    <th>Type</th>
                    <th>Message</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentAlerts as $alert): ?>
                    <tr class="<?= $alert['is_read'] ? '' : 'fw-bold' ?>">
                        <td><?= htmlspecialchars($alert['user_name'] ?? 'Unknown') ?></td>
                        <td><?= htmlspecialchars($alert['uid']) ?></td>
                        <td><?= htmlspecialchars($alert['alert_type']) ?></td>
                        <td><?= htmlspecialchars($alert['message']) ?></td>
                        <td><?= (new DateTime($alert['created_at'], new DateTimeZone('Asia/Manila')))->format('M d, Y h:i A') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="text-end mb-3">
            <a href="alerts.php" class="btn btn-danger btn-sm">View All Alerts</a>
        </div>
    </div>

    <!-- ALERTS -->
    <h3 class="mt-5">Recent Alerts</h3>
    <div class="table-modern mt-3">
        <table class="table table-hover mb-0">
            <thead class="table-danger text-dark">
                <tr>
                    <th>User</th>
                    <th>UID</th>
                    <th>Type</th>
                    <th>Message</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($recentAlerts as $alert): ?>
                <tr class="<?= $alert['is_read'] ? '' : 'fw-bold' ?>">
                    <td><?= htmlspecialchars($alert['user_name']) ?></td>
                    <td><?= htmlspecialchars($alert['uid']) ?></td>
                    <td><?= htmlspecialchars($alert['alert_type']) ?></td>
                    <td><?= htmlspecialchars($alert['message']) ?></td>
                    <td><?= (new DateTime($alert['created_at'], new DateTimeZone('Asia/Manila')))->format('M d, Y h:i A') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>



</body>
</html>
