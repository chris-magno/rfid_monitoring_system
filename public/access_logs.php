<?php
require_once __DIR__ . '/../core/auth.php';
requireLogin();

if (!isAdmin()) {
    header('Location: user_dashboard.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

// --- Handle search & date filters ---
$search = trim($_GET['search'] ?? '');
$day   = $_GET['day'] ?? date('Y-m-d'); // default: today

// --- Prepare query with filters ---
$sql = "
    SELECT al.id, al.uid, u.name AS user_name, u.email, al.status, al.attempts, al.log_time, al.access_type
    FROM access_logs al
    LEFT JOIN users u ON al.user_id = u.id
    WHERE DATE(al.log_time) = :day
";

$params = ['day' => $day];

if ($search !== '') {
    $sql .= " AND (al.uid LIKE :search OR u.name LIKE :search OR u.email LIKE :search)";
    $params['search'] = "%$search%";
}

$sql .= " ORDER BY al.log_time DESC";

// Initialize to avoid undefined variable warnings
$accessLogs = [];

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $fetched = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ensure $accessLogs is an array
    if (is_array($fetched)) {
        $accessLogs = $fetched;
    } else {
        $accessLogs = [];
    }

} catch (PDOException $e) {
    // Log error to your error log — don't expose DB errors to users
    error_log('Access Logs query error: ' . $e->getMessage());
    $accessLogs = [];
}

// --- Prepare previous/next day ---
$prevDay = date('Y-m-d', strtotime("$day -1 day"));
$nextDay = date('Y-m-d', strtotime("$day +1 day"));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Access Logs - RFID Monitoring</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f5f6fa;
        }
        .page-title {
            font-weight: 700;
            font-size: 1.8rem;
        }
        .card-modern {
            border: none;
            border-radius: 16px;
            padding: 22px;
            background: #ffffff;
            box-shadow: 0 4px 18px rgba(0,0,0,0.08);
        }
        .filter-card {
            border-radius: 16px;
            padding: 20px;
            background: #ffffff;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
        }
        .btn-modern {
            border-radius: 12px;
            padding: 10px 18px;
        }
        table {
            border-radius: 12px;
            overflow: hidden;
        }
        thead {
            background: #2c3e50;
            color: white;
        }
    </style>
</head>
<body>

<div class="container py-4">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="page-title">Access Logs</h2>
        <a href="dashboard.php" class="btn btn-secondary btn-modern">← Back to Dashboard</a>
    </div>

    <!-- Search & Filters Card -->
    <div class="filter-card mb-4">
        <form class="row g-3" method="GET">

            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-lg"
                       placeholder="Search UID, Name, Email..."
                       value="<?= htmlspecialchars($search) ?>">
            </div>

            <div class="col-md-4 d-flex align-items-center gap-2">
                <a href="?day=<?= $prevDay ?>&search=<?= urlencode($search) ?>"
                   class="btn btn-outline-primary btn-modern">← Previous</a>

                <input type="date" name="day" value="<?= $day ?>" class="form-control form-control-lg">

                <a href="?day=<?= $nextDay ?>&search=<?= urlencode($search) ?>"
                   class="btn btn-outline-primary btn-modern">Next →</a>
            </div>

            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-modern w-100">Filter</button>
            </div>
        </form>
    </div>

    <!-- Access Logs Table (Inside a modern card) -->
    <div class="card-modern">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>UID</th>
                    <th>User</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Attempts</th>
                    <th>Type</th>
                    <th>Time</th>
                </tr>
                </thead>

                <tbody>
                <?php foreach ((array)$accessLogs as $log): ?>
                    <tr>
                        <td><?= htmlspecialchars($log['id'] ?? '') ?></td>
                        <td><?= htmlspecialchars($log['uid'] ?? '') ?></td>
                        <td><?= htmlspecialchars($log['user_name'] ?? 'Unknown') ?></td>
                        <td><?= htmlspecialchars($log['email'] ?? '-') ?></td>

                        <td>
                            <?php if (($log['status'] ?? '') === 'granted'): ?>
                                <span class="badge bg-success px-3 py-2">Granted</span>
                            <?php else: ?>
                                <span class="badge bg-danger px-3 py-2">Denied</span>
                            <?php endif; ?>
                        </td>

                        <td><?= htmlspecialchars($log['attempts'] ?? '0') ?></td>

                        <td>
                            <?php if (($log['access_type'] ?? '') === 'otp'): ?>
                                <span class="badge bg-info px-3 py-2">OTP</span>
                            <?php else: ?>
                                <span class="badge bg-primary px-3 py-2">RFID</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php
                                if (!empty($log['log_time'])) {
                                    echo (new DateTime($log['log_time']))->format('M d, Y h:i A');
                                } else {
                                    echo '-';
                                }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($accessLogs)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            No access logs found for this day.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>

            </table>
        </div>
    </div>

</div>

</body>
</html>
