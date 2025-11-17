<?php
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
    $last = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($last) {
        $lastUID = $last['uid'];
        $scannedAt = $last['scanned_at'];
        $userName = $last['name'] ?? 'Unknown';
        $userEmail = $last['email'] ?? 'Not registered';
        $isRegistered = !empty($last['name']);
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

    <style>
        body {
            background: #f4f6fa;
            font-family: 'Inter', sans-serif;
        }

        .card-modern {
            background: #fff;
            border: none;
            padding: 28px;
            border-radius: 20px;
            box-shadow: 0 8px 28px rgba(0,0,0,0.08);
            transition: 0.3s ease;
        }

        .card-modern:hover {
            box-shadow: 0 12px 36px rgba(0,0,0,0.12);
        }

        .uid-display {
            font-size: 3rem;
            font-weight: 700;
            color: #2980b9;
            margin-bottom: 10px;
        }

        .title {
            font-weight: 700;
            font-size: 1.8rem;
            color: #2c3e50;
        }

        .alert-modern {
            border-radius: 14px;
            padding: 12px 18px;
            font-size: 1rem;
        }

        .btn-modern {
            border-radius: 12px;
            padding: 10px 18px;
            font-weight: 500;
        }

        .info-text {
            font-size: 1rem;
            margin-bottom: 6px;
        }

        .time-text {
            font-size: 0.9rem;
            color: #7f8c8d;
        }
    </style>
</head>

<body>

<div class="container mt-5">

    <div class="row justify-content-center">
        <div class="col-md-6">

            <div class="card-modern text-center">

                <h2 class="title mb-3">Last Scanned RFID UID</h2>

                <?php if ($error): ?>

                    <div class="alert alert-warning alert-modern">
                        <?= htmlspecialchars($error) ?>
                    </div>

                <?php else: ?>

                    <!-- UID Display -->
                    <div id="uid" class="uid-display">
                        <?= htmlspecialchars($lastUID) ?>
                    </div>

                    <div id="card-info" class="mt-3">

                        <?php if ($isRegistered): ?>

                            <div class="alert alert-success alert-modern">
                                UID is registered ✅
                            </div>

                            <p class="info-text"><strong>Name:</strong> <?= htmlspecialchars($userName) ?></p>
                            <p class="info-text"><strong>Email:</strong> <?= htmlspecialchars($userEmail) ?></p>

                        <?php else: ?>

                            <div class="alert alert-danger alert-modern">
                                UID not registered ❌
                            </div>

                            <p class="info-text"><strong>Name:</strong> Unknown</p>
                            <p class="info-text"><strong>Email:</strong> Not registered</p>

                            <a href="register_user.php?uid=<?= urlencode($lastUID) ?>" class="btn btn-primary btn-modern mt-3">
                                Register this UID
                            </a>

                        <?php endif; ?>

                        <p class="time-text mt-3">
                            Scanned at: <?= htmlspecialchars($scannedAt) ?>
                        </p>

                    </div>

                <?php endif; ?>

                <a href="dashboard.php" class="btn btn-secondary btn-modern mt-4">&laquo; Back to Dashboard</a>

            </div>

        </div>
    </div>

</div>

<script>
async function updateUIDCard() {
    try {
        const res = await fetch('../api/read_tag_api.php');
        const data = await res.json();
        if (!data.uid) return;

        document.getElementById('uid').textContent = data.uid;

        const cardInfo = document.getElementById('card-info');
        let html = '';

        if (data.name) {
            html += `<div class="alert alert-success alert-modern">UID is registered ✅</div>`;
            html += `<p class="info-text"><strong>Name:</strong> ${data.name}</p>`;
            html += `<p class="info-text"><strong>Email:</strong> ${data.email}</p>`;
        } else {
            html += `<div class="alert alert-danger alert-modern">UID not registered ❌</div>`;
            html += `<p class="info-text"><strong>Name:</strong> Unknown</p>`;
            html += `<p class="info-text"><strong>Email:</strong> Not registered</p>`;
            html += `<a href="register_user.php?uid=${encodeURIComponent(data.uid)}" class="btn btn-primary btn-modern mt-3">Register this UID</a>`;
        }

        html += `<p class="time-text mt-3">Scanned at: ${data.scanned_at || ''}</p>`;
        cardInfo.innerHTML = html;

    } catch (err) {
        console.error('Error fetching latest UID:', err);
    }
}

setInterval(updateUIDCard, 2000);
</script>

</body>
</html>
