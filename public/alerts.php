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

// Unread alerts count
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
        .table-responsive { max-height: 500px; overflow-y: auto; }
        .fw-bold { font-weight: bold; }
    </style>
</head>
<body class="bg-light">

<div class="container mt-4">
    <h3>Alerts <span class="badge bg-warning"><?= $totalAlerts ?></span></h3>

    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="table-danger">
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
                        <td><?= htmlspecialchars($alert['alert_type']) ?></td>
                        <td><?= htmlspecialchars($alert['message']) ?></td>
                        <td><?= (new DateTime($alert['created_at'], new DateTimeZone('Asia/Manila')))->format('M d, Y h:i A') ?></td>
                        <td>
                            <?php if (!$alert['is_read']): ?>
                                <button class="btn btn-sm btn-success mark-read-btn" data-id="<?= $alert['id'] ?>">Mark as Read</button>
                            <?php else: ?>
                                <span class="text-success">Read</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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
