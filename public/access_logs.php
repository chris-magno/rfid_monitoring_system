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

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$accessLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
</head>
<body class="bg-light">
<div class="container mt-4">
    <h2>Access Logs</h2>
    <a href="dashboard.php" class="btn btn-secondary mb-3">Back to Dashboard</a>

    <!-- Search & Date Navigation -->
    <form class="row mb-3" method="GET">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Search UID, Name, Email..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-4 d-flex align-items-center gap-2">
            <a href="?day=<?= $prevDay ?>&search=<?= urlencode($search) ?>" class="btn btn-outline-primary">&larr; Previous</a>
            <input type="date" name="day" value="<?= $day ?>" class="form-control">
            <a href="?day=<?= $nextDay ?>&search=<?= urlencode($search) ?>" class="btn btn-outline-primary">Next &rarr;</a>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
    </form>

    <!-- Table -->
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
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
                <?php foreach ($accessLogs as $log): ?>
                    <tr>
                        <td><?= $log['id'] ?></td>
                        <td><?= htmlspecialchars($log['uid']) ?></td>
                        <td><?= htmlspecialchars($log['user_name'] ?? 'Unknown') ?></td>
                        <td><?= htmlspecialchars($log['email'] ?? '-') ?></td>
                        <td>
                            <?php if ($log['status'] === 'granted'): ?>
                                <span class="badge bg-success">Granted</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Denied</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $log['attempts'] ?></td>
                        <td>
                            <?php if ($log['access_type'] === 'otp'): ?>
                                <span class="badge bg-info">OTP</span>
                            <?php else: ?>
                                <span class="badge bg-primary">RFID</span>
                            <?php endif; ?>
                        </td>
                        <td><?= (new DateTime($log['log_time']))->format('M d, Y h:i A') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($accessLogs)): ?>
                    <tr>
                        <td colspan="8" class="text-center">No access logs found for this day.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
