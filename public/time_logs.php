<?php
require_once __DIR__ . '/../core/auth.php';
requireLogin();

if (!isAdmin()) {
    header('Location: user_dashboard.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

// ---------------- Filter Inputs ----------------
$search = trim($_GET['search'] ?? '');
$day    = $_GET['day'] ?? date('Y-m-d');

// ---------------- Query ----------------
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

// ---------------- Navigation Days ----------------
$prevDay = date('Y-m-d', strtotime("$day -1 day"));
$nextDay = date('Y-m-d', strtotime("$day +1 day"));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Time Logs - RFID Monitoring</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f5f7fb;
            font-family: 'Inter', sans-serif;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
        }

        /* Modern Card */
        .card-modern {
            background: #fff;
            padding: 25px;
            border-radius: 18px;
            box-shadow: 0 8px 28px rgba(0,0,0,0.08);
            border: none;
        }

        /* Filters Card */
        .filter-card {
            background: #fff;
            padding: 20px;
            border-radius: 18px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        /* Buttons */
        .btn-modern {
            padding: 10px 18px;
            border-radius: 12px;
            font-weight: 500;
        }

        /* Table */
        table {
            border-radius: 14px;
            overflow: hidden;
        }

        thead {
            background: #2c3e50;
            color: white;
        }

        tbody tr:hover {
            background: #f0f3f8 !important;
        }
    </style>
</head>

<body>

<div class="container py-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="page-title">⏱ Time Logs</h2>
        <a href="dashboard.php" class="btn btn-secondary btn-modern">← Back</a>
    </div>

    <!-- Filters -->
    <div class="filter-card">
        <form class="row g-3" method="GET">

            <div class="col-md-4">
                <input type="text"
                       name="search"
                       class="form-control form-control-lg"
                       placeholder="Search UID or Name..."
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
                <button class="btn btn-primary btn-modern w-100">Filter</button>
            </div>

        </form>
    </div>

    <!-- Table -->
    <div class="card-modern">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>UID</th>
                    <th>User</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                </tr>
                </thead>

                <tbody>
                <?php if (!empty($timeLogs)): ?>
                    <?php foreach ($timeLogs as $log): ?>
                        <tr>
                            <td><?= $log['id'] ?></td>
                            <td><?= htmlspecialchars($log['uid']) ?></td>
                            <td><?= htmlspecialchars($log['user_name'] ?? 'Unknown') ?></td>
                            <td><?= (new DateTime($log['time_in']))->format('M d, Y • h:i A') ?></td>
                            <td>
                                <?= $log['time_out']
                                    ? (new DateTime($log['time_out']))->format('M d, Y • h:i A')
                                    : '<span class="text-muted">-</span>' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-3">
                            No time logs found for this day.
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
