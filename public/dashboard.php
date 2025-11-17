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
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM admin_alerts");
    return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
}

function getTotalAccessLogs($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM access_logs");
    return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
}

function getTotalTimeLogs($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM time_logs");
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
$activeUsers = getActiveUsers($pdo);
$inactiveUsers = getInactiveUsers($pdo);
$totalAlertsUnread = getUnreadAlertsCount($pdo);
$totalAlertsAll = getTotalAlerts($pdo);
$totalAccessLogs = getTotalAccessLogs($pdo);
$totalTimeLogs = getTotalTimeLogs($pdo);

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
        body {
            background: #f5f7fa;
            font-family: 'Inter', sans-serif;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: 260px;
            height: 100vh;
            background: #111827;
            position: fixed;
            top: 0;
            left: 0;
            padding: 25px 20px;
            color: white;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            z-index: 1000;
        }
        .sidebar.collapsed {
            transform: translateX(-100%);
        }

        .sidebar h2 {
            font-size: 22px;
            margin-bottom: 20px;
            text-align: center;
            letter-spacing: 1px;
        }

        .nav-link {
            color: #d1d5db !important;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
        }
        .nav-link i {
            margin-right: 10px;
            min-width: 20px;
            text-align: center;
        }
        .nav-link:hover, .nav-link.active {
            background: #1f2937;
            color: #fff !important;
        }

        .sidebar hr {
            border-color: #374151;
        }

        /* ===== MAIN CONTENT ===== */
        .main-content {
            margin-left: 260px;
            padding: 30px;
            transition: margin-left 0.3s ease;
        }
        .main-content.expanded {
            margin-left: 0;
        }

        /* ===== TOGGLE BUTTON ===== */
        .toggle-btn {
            position: fixed;
            top: 15px;
            left: 15px;
            background: #111827;
            border-radius: 6px;
            color: #fff;
            border: none;
            width: 40px;
            height: 40px;
            cursor: pointer;
            z-index: 1100;
            display: none;
        }

        .toggle-btn i {
            font-size: 20px;
        }

        /* ===== CARDS ===== */
        .card-modern {
            border-radius: 18px;
            background: #ffffff;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }

        .card-modern h5 {
            color: #6b7280;
            font-size: 16px;
        }

        .card-modern .number {
            font-size: 42px;
            font-weight: 700;
            margin-top: 10px;
        }

        /* ===== TABLE ===== */
        .table-modern {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }

        thead {
            background: #111827;
            color: white;
        }

        tbody tr:hover {
            background: #f3f4f6;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .toggle-btn {
                display: block;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
<<<<<<< HEAD
=======

<!-- TOGGLE BUTTON / HAMBURGER -->
<button class="toggle-btn" id="toggleBtn"><i class="bi bi-list"></i></button>
>>>>>>> e0819aecccea21e01e16e4d97be9759f6e3fe34a

<!-- TOGGLE BUTTON / HAMBURGER -->
<button class="toggle-btn" id="toggleBtn"><i class="bi bi-list"></i></button>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <h2>Admin Panel</h2>
    <a href="#" class="nav-link active"><i class="bi bi-speedometer2"></i> <span>Dashboard</span></a>
    <a href="read_tag.php" class="nav-link"><i class="bi bi-card-text"></i> <span>Read Tag</span></a>
    <a href="register_user.php" class="nav-link"><i class="bi bi-person-plus"></i> <span>Register User</span></a>
    <a href="users.php" class="nav-link"><i class="bi bi-people"></i> <span>User Management</span></a>
    <a href="access_logs.php" class="nav-link"><i class="bi bi-journal-text"></i> <span>Access Logs</span></a>
    <a href="time_logs.php" class="nav-link"><i class="bi bi-clock-history"></i> <span>Time Logs</span></a>
    <a href="alerts.php" class="nav-link"><i class="bi bi-bell"></i> <span>Alerts</span></a>
    <hr>
    <a href="logout.php" class="btn btn-danger w-100 mt-2"><i class="bi bi-box-arrow-right"></i> <span>Logout</span></a>
</div>

<!-- MAIN CONTENT -->
<div class="main-content" id="mainContent">

    <!-- STATISTICS CARDS ROW 1 -->
    <div class="row mb-4 g-4">
        <div class="col-md-3">
            <div class="card-modern">
                <h5>Total Users</h5>
                <div class="number"><?= $totalUsers ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-modern">
<<<<<<< HEAD
                <h5>Active Users</h5>
                <div class="number text-success"><?= $activeUsers ?></div>
=======
                <h5>Total Time Logs</h5>
                <div class="number"><?= $totalTimeLogs ?></div>
>>>>>>> e0819aecccea21e01e16e4d97be9759f6e3fe34a
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-modern">
<<<<<<< HEAD
                <h5>Inactive Users</h5>
                <div class="number text-danger"><?= $inactiveUsers ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-modern">
                <h5>Unread Alerts</h5>
                <div class="number"><?= $totalAlertsUnread ?></div>
=======
                <h5>Total Alerts</h5>
                <div class="number"><?= $totalAlertsAll ?></div>
>>>>>>> e0819aecccea21e01e16e4d97be9759f6e3fe34a
            </div>
        </div>
    </div>

<<<<<<< HEAD
    <!-- STATISTICS CARDS ROW 2 -->
    <div class="row mb-4 g-4">
        <div class="col-md-3">
            <div class="card-modern">
                <h5>Total Access Logs</h5>
                <div class="number"><?= $totalAccessLogs ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-modern">
                <h5>Access Logs Today</h5>
                <div class="number"><?= count($accessLogs) ?></div>
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

=======
>>>>>>> e0819aecccea21e01e16e4d97be9759f6e3fe34a
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
<<<<<<< HEAD
            </tbody>
        </table>
=======
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
>>>>>>> e0819aecccea21e01e16e4d97be9759f6e3fe34a
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

<script>
const sidebar = document.getElementById('sidebar');
const toggleBtn = document.getElementById('toggleBtn');

toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('show');
});

// ===== Chart.js Bar Chart =====
const ctx = document.getElementById('statsChart').getContext('2d');

const statsChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Total Users', 'Active Users', 'Inactive Users', 'Total Alerts', 'Unread Alerts', 'Access Logs', 'Time Logs'],
        datasets: [{
            label: 'Counts',
            data: [
                <?= $totalUsers ?>,
                <?= $activeUsers ?>,
                <?= $inactiveUsers ?>,
                <?= $totalAlertsAll ?>,
                <?= $totalAlertsUnread ?>,
                <?= $totalAccessLogs ?>,
                <?= $totalTimeLogs ?>
            ],
            backgroundColor: [
                '#3B82F6', // blue
                '#10B981', // green
                '#EF4444', // red
                '#F59E0B', // yellow
                '#8B5CF6', // purple
                '#14B8A6', // teal
                '#F97316'  // orange
            ],
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ' + context.raw;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { precision:0 }
            }
        }
    }
});
</script>

</body>
</html>
