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
$day   = $_GET['day'] ?? date('Y-m-d');

// --- Prepare query ---
$sql = "
    SELECT tl.id, tl.uid, u.name AS user_name, tl.time_in, tl.time_out
    FROM time_logs tl
    LEFT JOIN users u ON tl.user_id = u.id
    WHERE DATE(tl.time_in) = :day
";
$params = ['day' => $day];

if ($search !== '') {
    $sql .= " AND (tl.uid LIKE :search OR u.name LIKE :search)";
    $params['search'] = "%$search%";
}

$sql .= " ORDER BY tl.time_in DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$timeLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Prepare previous/next day ---
$prevDay = date('Y-m-d', strtotime("$day -1 day"));
$nextDay = date('Y-m-d', strtotime("$day +1 day"));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Time Logs - RFID Monitoring</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <h2>Time Logs</h2>
    <a href="dashboard.php" class="btn btn-secondary mb-3">Back to Dashboard</a>

    <!-- Search & Date Navigation -->
    <form class="row mb-3" method="GET">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Search UID or Name..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-4 d-flex align-items-center gap-2">
            <a href="?day=<?= $prevDay ?>&search=<?= urlencode($search) ?>" class="btn btn-outline-success">&larr; Previous</a>
            <input type="date" name="day" value="<?= $day ?>" class="form-control">
            <a href="?day=<?= $nextDay ?>&search=<?= urlencode($search) ?>" class="btn btn-outline-success">Next &rarr;</a>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-success w-100">Filter</button>
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
                    <th>Time In</th>
                    <th>Time Out</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($timeLogs as $log): ?>
                    <tr>
                        <td><?= $log['id'] ?></td>
                        <td><?= htmlspecialchars($log['uid']) ?></td>
                        <td><?= htmlspecialchars($log['user_name'] ?? 'Unknown') ?></td>
                        <td><?= (new DateTime($log['time_in']))->format('M d, Y h:i A') ?></td>
                        <td>
                            <?= $log['time_out'] ? (new DateTime($log['time_out']))->format('M d, Y h:i A') : '-' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
