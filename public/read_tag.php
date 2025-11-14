<?php
// public/read_tag.php
require_once __DIR__ . '/../core/auth.php';
requireLogin();
require_once __DIR__ . '/../config/db.php';

// Initialize variables
$lastUID = '';
$userName = '';
$userEmail = '';
$scannedAt = '';
$isRegistered = false;
$error = '';

try {
    $stmt = $pdo->query("
        SELECT uc.uid, uc.scanned_at, u.name, u.email
        FROM uid_container uc
        LEFT JOIN users u ON uc.uid = u.uid
        ORDER BY uc.scanned_at DESC
        LIMIT 1
    ");

    $last = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;

    if ($last) {
        $lastUID = $last['uid'];
        $scannedAt = $last['scanned_at'];
        $userName = $last['name'] ?? 'Unknown';
        $userEmail = $last['email'] ?? 'Not registered';
        $isRegistered = isset($last['name']);
    } else {
        $error = "No UID scanned yet.";
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Last Scanned UID - RFID Monitoring</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta http-equiv="refresh" content="5">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow p-4 text-center">
                <h4 class="mb-3">Last Scanned RFID UID</h4>

                <?php if ($error): ?>
                    <div class="alert alert-warning"><?= htmlspecialchars($error) ?></div>
                <?php else: ?>
                    <h2 class="text-primary" id="uid"><?= htmlspecialchars($lastUID) ?></h2>

                    <div id="card-info">
                        <?php if ($isRegistered): ?>
                            <p><strong>Name:</strong> <?= htmlspecialchars($userName) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($userEmail) ?></p>
                            <div class="alert alert-success">UID is registered ✅</div>
                        <?php else: ?>
                            <div class="alert alert-danger">UID not registered ❌</div>
                            <p><strong>Name:</strong> <?= htmlspecialchars($userName) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($userEmail) ?></p>
                            <!-- Register Button -->
                            <a href="register_user.php?uid=<?= urlencode($lastUID) ?>" class="btn btn-primary mt-3">
                                Register this UID
                            </a>
                        <?php endif; ?>

                        <p class="text-muted">Scanned at: <?= htmlspecialchars($scannedAt) ?></p>
                    </div>
                <?php endif; ?>

                <a href="dashboard.php" class="btn btn-secondary mt-3">&laquo; Back to Dashboard</a>
            </div>
        </div>
    </div>
</div>

<script>
// Fetch latest UID every 2 seconds and update the card
async function updateUIDCard() {
    try {
        const res = await fetch('../api/read_tag_api.php');
        const data = await res.json();
        if (!data.uid) return;

        document.getElementById('uid').textContent = data.uid;

        const cardInfo = document.getElementById('card-info');
        let html = '';

        if (data.name) {
            html += `<p><strong>Name:</strong> ${data.name}</p>`;
            html += `<p><strong>Email:</strong> ${data.email}</p>`;
            html += `<div class="alert alert-success">UID is registered ✅</div>`;
        } else {
            html += `<div class="alert alert-danger">UID not registered ❌</div>`;
            html += `<p><strong>Name:</strong> Unknown</p>`;
            html += `<p><strong>Email:</strong> Not registered</p>`;
            html += `<a href="register_user.php?uid=${encodeURIComponent(data.uid)}" class="btn btn-primary mt-3">Register this UID</a>`;
        }

        html += `<p class="text-muted">Scanned at: ${data.scanned_at || ''}</p>`;
        cardInfo.innerHTML = html;

    } catch (err) {
        console.error('Error fetching latest UID:', err);
    }
}

// Poll every 2 seconds
setInterval(updateUIDCard, 2000);
</script>
</body>
</html>
