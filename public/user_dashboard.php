<?php
require_once __DIR__ . '/../core/auth.php';
requireLogin();
require_once __DIR__ . '/../config/db.php';

// Get user info
$userId = $_SESSION['user']['id'];
$stmt = $pdo->prepare("
    SELECT u.name, u.email, u.uid, u.created_at, uc.category_name
    FROM users u
    LEFT JOIN user_categories uc ON u.category_id = uc.id
    WHERE u.id = ? LIMIT 1
");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<div class='alert alert-danger text-center mt-5'>User not found.</div>";
    exit;
}

$role = strtolower($user['category_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard - RFID Monitoring</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="#">RFID Monitoring</a>
        <div class="ms-auto">
            <a href="logout.php" class="btn btn-sm btn-danger">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <div class="text-center mb-4">
        <h2>Welcome, <?= htmlspecialchars($user['name']) ?>!</h2>
        <p class="text-muted">Here is your profile information.</p>
    </div>

    <div class="card shadow mx-auto" style="max-width: 500px;">
        <div class="card-body">
            <h4 class="card-title text-center mb-4">User ID Card</h4>
            <div class="text-center mb-3">
                <img src="https://cdn-icons-png.flaticon.com/512/847/847969.png"
                     alt="User Icon" width="100" class="rounded-circle mb-3">
            </div>
            <p><strong>Name:</strong> <?= htmlspecialchars($user['name']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($user['email'] ?? '-') ?></p>
            <p><strong>UID:</strong> <?= htmlspecialchars($user['uid'] ?? '-') ?></p>
            <p><strong>Role:</strong> <?= htmlspecialchars(ucfirst($role)) ?></p>
            <p><strong>Registered:</strong> <?= htmlspecialchars($user['created_at']) ?></p>

            <hr>
            <div class="text-center mt-3">
                <!-- Send OTP -->
                <button id="sendOtpBtn" class="btn btn-primary mb-2">Send OTP (Forgot Card)</button>

                <!-- OTP input & verify -->
                <div class="input-group mb-2" style="max-width: 300px; margin: 0 auto;">
                    <input type="text" id="otpInput" class="form-control" placeholder="Enter OTP">
                    <button id="verifyOtpBtn" class="btn btn-success">Verify OTP</button>
                </div>

                <div id="otpResult" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<script>
const userId = <?= json_encode($userId) ?>;
const sendBtn = document.querySelector('#sendOtpBtn');
const verifyBtn = document.querySelector('#verifyOtpBtn');
const otpInput = document.querySelector('#otpInput');
const resultDiv = document.querySelector('#otpResult');

// --- Send OTP ---
sendBtn.addEventListener('click', async function() {
    sendBtn.disabled = true;
    sendBtn.textContent = 'Sending...';
    resultDiv.innerHTML = '';

    try {
        const res = await fetch('/rfid_monitoring_system/api/send_mail.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId }),
            credentials: 'same-origin'
        });

        if (!res.ok) throw new Error('HTTP error ' + res.status);
        const data = await res.json();

        resultDiv.innerHTML = `<div class="alert ${data.success ? 'alert-success' : 'alert-danger'}">
            ${data.message}
        </div>`;
    } catch (err) {
        console.error(err);
        resultDiv.innerHTML = `<div class="alert alert-danger">Network or server error.</div>`;
    } finally {
        sendBtn.disabled = false;
        sendBtn.textContent = 'Send OTP (Forgot Card)';
    }
});

// --- Verify OTP ---
verifyBtn.addEventListener('click', async function() {
    const otpValue = otpInput.value.trim();
    if (!otpValue) {
        resultDiv.innerHTML = `<div class="alert alert-warning">Please enter the OTP.</div>`;
        return;
    }

    verifyBtn.disabled = true;
    verifyBtn.textContent = 'Verifying...';
    resultDiv.innerHTML = '';

    try {
        const res = await fetch('/rfid_monitoring_system/api/verify_otp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ otp: otpValue, user_id: userId }),
            credentials: 'same-origin'
        });

        if (!res.ok) throw new Error('HTTP error ' + res.status);
        const data = await res.json();

        resultDiv.innerHTML = `<div class="alert ${data.success ? 'alert-success' : 'alert-danger'}">
            ${data.message}
        </div>`;

    } catch (err) {
        console.error(err);
        resultDiv.innerHTML = `<div class="alert alert-danger">Network or server error.</div>`;
    } finally {
        verifyBtn.disabled = false;
        verifyBtn.textContent = 'Verify OTP';
    }
});
</script>
</body>
</html>
