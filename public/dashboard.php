<?php
require_once __DIR__ . '/../core/auth.php';
requireLogin();

if (!isAdmin()) {
    header('Location: user_dashboard.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

// -------------------- Functions --------------------
function getTotalUsers($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM users");
    return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
}

function getTotalAlerts($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM alerts");
    return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
}

function getRecentAccessLogs($pdo, $limit = 10) {
    $stmt = $pdo->prepare("
        SELECT al.id, al.uid, u.name AS user_name, u.email, al.status, al.attempts, al.log_time
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

// -------------------- Fetch Data --------------------
$totalUsers = getTotalUsers($pdo);
$totalAlerts = getTotalAlerts($pdo);
$accessLogs = getRecentAccessLogs($pdo);
$timeLogs = getRecentTimeLogs($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - RFID Monitoring</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .table-responsive { max-height: 400px; overflow-y: auto; }
        .card { border-radius: 1rem; }
    </style>
</head>
<body class="bg-light">

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
        <div class="col-md-4">
            <div class="card text-center shadow">
                <div class="card-body">
                    <h5 class="card-title">Failed Alerts</h5>
                    <p class="display-6"><?= $totalAlerts ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center shadow">
                <div class="card-body">
                    <h5 class="card-title">Recent Access Logs</h5>
                    <p class="display-6"><?= count($accessLogs) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Access Logs -->
    <h3>Recent Access Logs</h3>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
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
                            <?php if ($log['status'] === 'granted'): ?>
                                <span class="badge bg-success">Granted</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Denied</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $dt = new DateTime($log['log_time'], new DateTimeZone('Asia/Manila'));
                            echo $dt->format('M d, Y h:i A');
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="text-end mb-3">
            <a href="access_logs.php" class="btn btn-primary btn-sm">View All Access Logs</a>
        </div>
    </div>

    <!-- Recent Time Logs -->
    <h3 class="mt-5">Recent Time Logs</h3>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="table-success">
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
                        <td>
                            <?php
                            $timeIn = new DateTime($log['time_in'], new DateTimeZone('Asia/Manila'));
                            echo $timeIn->format('M d, Y h:i A');
                            ?>
                        </td>
                        <td>
                            <?php
                            if ($log['time_out']) {
                                $timeOut = new DateTime($log['time_out'], new DateTimeZone('Asia/Manila'));
                                echo $timeOut->format('M d, Y h:i A');
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="text-end mb-3">
            <a href="time_logs.php" class="btn btn-success btn-sm">View All Time Logs</a>
        </div>
    </div>

    <!-- Quick Access -->
    <div class="mt-4 text-center">
        <a href="users.php" class="btn btn-warning btn-lg">Go to User Management</a>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>



</body>
</html>

<!-- etss -->
