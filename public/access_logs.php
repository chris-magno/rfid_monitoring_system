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

$accessLogs = [];

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $fetched = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $accessLogs = is_array($fetched) ? $fetched : [];

} catch (PDOException $e) {
    error_log('Access Logs query error: ' . $e->getMessage());
    $accessLogs = [];
}

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
        /* ---------- GLOBAL ----------- */
        body {
            position: relative;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
        
            /* Letran-style gradient overlay */
            background: linear-gradient(
                    rgba(0, 38, 99, 0.6),   /* dark blue with opacity */
                    rgba(204, 0, 0, 0.6)     /* red with opacity */
                ),
                url('../delapaazletranBackground.jpg') no-repeat center center fixed;
            background-size: cover;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: white;
        }

        /* ---------- MODERN CARDS ----------- */
        .card-modern {
            border: none;
            border-radius: 18px;
            padding: 25px;
            background: #ffffff;
            box-shadow: 0 8px 28px rgba(0,0,0,0.08);
        }

        .filter-card {
            border: none;
            border-radius: 18px;
            background: #ffffff;
            box-shadow: 0 8px 25px rgba(0,0,0,0.06);
            padding: 25px;
        }

        /* ---------- BUTTONS ----------- */
        .btn-modern {
            border-radius: 14px;
            padding: 10px 18px;
            font-weight: 500;
        }

        .btn-outline-primary {
            border-width: 2px;
        }

        /* ---------- TABLE ----------- */
        table {
            border-radius: 16px;
            overflow: hidden;
        }

        thead {
            background: #283747;
            color: white;
        }

        tbody tr {
            transition: all 0.15s ease-in-out;
        }

        tbody tr:hover {
            background: #f0f3f9 !important;
        }

        /* ---------- BADGES ----------- */
        .badge {
            font-size: 0.85rem;
            padding: 8px 14px;
            border-radius: 12px;
        }

        /* ---------- INPUTS ----------- */
        input[type="text"], input[type="date"] {
            border-radius: 12px !important;
            padding: 12px !important;
            height: auto !important;
        }
    </style>
</head>

<body>

<div class="container py-4">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="page-title">📋 Access Logs</h2>
        <a href="dashboard.php" class="btn btn-secondary btn-modern">← Back to Dashboard</a>
    </div>

    <!-- FILTERS -->
    <div class="filter-card mb-4">
        <form class="row g-3" method="GET">

            <div class="col-md-4">
                <input type="text" name="search" class="form-control"
                       placeholder="Search UID, Name, Email..."
                       value="<?= htmlspecialchars($search) ?>">
            </div>

            <div class="col-md-5 d-flex align-items-center gap-2">
                <a href="?day=<?= $prevDay ?>&search=<?= urlencode($search) ?>"
                   class="btn btn-outline-primary btn-modern">← Previous</a>

                <input type="date" name="day" value="<?= $day ?>" class="form-control">

                <a href="?day=<?= $nextDay ?>&search=<?= urlencode($search) ?>"
                   class="btn btn-outline-primary btn-modern">Next →</a>
            </div>

            <div class="col-md-3">
                <button type="submit" class="btn btn-primary btn-modern w-100">Apply Filters</button>
            </div>
        </form>
    </div>

    <!-- TABLE CARD -->
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
                <?php foreach ($accessLogs as $log): ?>
                    <tr>
                        <td><?= htmlspecialchars($log['id']) ?></td>
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

                        <td><?= htmlspecialchars($log['attempts']) ?></td>

                        <td>
                            <?php if (($log['access_type'] ?? '') === 'otp'): ?>
                                <span class="badge bg-info">OTP</span>
                            <?php else: ?>
                                <span class="badge bg-primary">RFID</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= !empty($log['log_time'])
                                ? (new DateTime($log['log_time']))->format('M d, Y • h:i A')
                                : '-' ?>
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
