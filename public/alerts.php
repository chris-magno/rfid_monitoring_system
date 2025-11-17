<?php
require_once __DIR__ . '/../core/auth.php';
requireLogin();

if (!isAdmin()) {
    header('Location: user_dashboard.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

// -------------------- Functions --------------------
function getRecentAlerts($pdo) {
    $stmt = $pdo->prepare("
        SELECT id, uid, user_name, alert_type, message, created_at, is_read
        FROM admin_alerts
        ORDER BY created_at DESC
        LIMIT 20
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUnreadAlertsCount($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM admin_alerts WHERE is_read = 0");
    return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
}

// -------------------- Fetch Data --------------------
$recentAlerts = getRecentAlerts($pdo);
$totalAlerts = getUnreadAlertsCount($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Alerts - RFID Monitoring</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
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

        .card-modern {
            background: #fff;
            padding: 25px;
            border-radius: 18px;
            box-shadow: 0 8px 28px rgba(0,0,0,0.08);
            border: none;
        }
        
        h4 {
            color:white;
        }

        /* Table */
        table {
            border-radius: 16px;
            overflow: hidden;
        }

        thead {
            background: #c0392b;
            color: #fff;
        }

        tbody tr {
            transition: 0.15s ease;
        }

        tbody tr:hover {
            background: #f0f3f9 !important;
        }

        .badge-modern {
            font-size: 0.85rem;
            padding: 8px 14px;
            border-radius: 12px;
        }

        .btn-modern {
            border-radius: 12px;
            padding: 8px 14px;
            font-weight: 500;
        }

        .table-responsive {
            max-height: 520px;
        }
    </style>
</head>

<body>

<div class="container py-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="page-title">⚠ Alerts</h2>
        <a href="dashboard.php" class="btn btn-secondary btn-modern">← Back</a>
    </div>

    <!-- Alerts Count -->
    <div class="mb-3">
        <h4 class="fw-semibold">
            Recent Alerts  
            <span class="badge bg-warning text-dark"><?= $totalAlerts ?></span>
        </h4>
    </div>

    <!-- Table Card -->
    <div class="card-modern">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                <tr>
                    <th>User</th>
                    <th>UID</th>
                    <th>Type</th>
                    <th>Message</th>
                    <th>Time</th>
                    <th>Action</th>
                </tr>
                </thead>

                <tbody>
                <?php foreach ($recentAlerts as $alert): ?>
                    <tr id="alert-<?= $alert['id'] ?>" class="<?= $alert['is_read'] ? '' : 'fw-bold' ?>">
                        <td><?= htmlspecialchars($alert['user_name'] ?? 'Unknown') ?></td>
                        <td><?= htmlspecialchars($alert['uid']) ?></td>

                        <td>
                            <span class="badge bg-danger badge-modern">
                                <?= htmlspecialchars($alert['alert_type']) ?>
                            </span>
                        </td>

                        <td><?= htmlspecialchars($alert['message']) ?></td>

                        <td>
                            <?= (new DateTime($alert['created_at'], new DateTimeZone('Asia/Manila')))
                                ->format('M d, Y • h:i A') ?>
                        </td>

                        <td>
                            <?php if (!$alert['is_read']): ?>
                                <button class="btn btn-success btn-sm btn-modern mark-read-btn"
                                        data-id="<?= $alert['id'] ?>">
                                    Mark as Read
                                </button>
                            <?php else: ?>
                                <span class="text-success fw-semibold">Read</span>
                            <?php endif; ?>
                        </td>
                    </tr>

                <?php endforeach; ?>
                </tbody>

            </table>
        </div>
    </div>

</div>

<script>
document.querySelectorAll('.mark-read-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.getAttribute('data-id');

        fetch('../api/mark_read_alert.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ alert_id: id })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const row = document.getElementById(`alert-${id}`);
                row.classList.remove('fw-bold');
                btn.replaceWith(document.createTextNode('Read'));
            } else {
                alert('Failed to mark as read');
            }
        })
        .catch(() => alert('Error connecting to server'));
    });
});
</script>

</body>
</html>
