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

        .navbar {
            border-bottom: 1px solid #222;
        }

        .dashboard-title {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
        }

        .card-modern {
            background: white;
            padding: 25px;
            border-radius: 18px;
            box-shadow: 0 8px 28px rgba(0,0,0,0.08);
            border: none;
        }

        .avatar {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            border: 4px solid #e8eef7;
            box-shadow: 0 4px 15px rgba(0,0,0,0.10);
        }

        .btn-modern {
            padding: 10px 16px;
            border-radius: 12px;
            font-weight: 600;
        }

        .input-modern {
            border-radius: 12px !important;
        }

        .otp-group {
            max-width: 330px;
            margin: 0 auto;
        }

        .info-label {
            font-weight: 600;
            color: #2c3e50;
        }
    </style>
</head>

<body>

<!-- Navbar -->
<nav class="navbar navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#">RFID Monitoring</a>
        <div class="ms-auto">
            <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>
</nav>

<!-- Main Container -->
<div class="container mt-5">

    <!-- Header -->
    <div class="text-center mb-4">
        <h2 class="dashboard-title">Welcome, <?= htmlspecialchars($user['name']) ?>!</h2>
        <p class="text-muted">Here is your profile and security access panel.</p>
    </div>

    <!-- User Card -->
    <div class="card-modern mx-auto" style="max-width: 500px;">
        <div class="text-center mb-3">
            <img src="https://cdn-icons-png.flaticon.com/512/847/847969.png"
                 class="avatar" alt="User Avatar">
        </div>

        <h4 class="text-center mb-4">Your ID Profile</h4>

        <p><span class="info-label">Name:</span> <?= htmlspecialchars($user['name']) ?></p>
        <p><span class="info-label">Email:</span> <?= htmlspecialchars($user['email'] ?? '-') ?></p>
        <p><span class="info-label">UID:</span> <?= htmlspecialchars($user['uid'] ?? '-') ?></p>
        <p><span class="info-label">Role:</span> <?= htmlspecialchars(ucfirst($role)) ?></p>
        <p><span class="info-label">Registered:</span> <?= htmlspecialchars($user['created_at']) ?></p>

        <hr class="my-4">

        <!-- OTP Section -->
        <div class="text-center">
            <button id="sendOtpBtn" class="btn btn-primary btn-modern w-100 mb-3">
                Send OTP (Forgot Card)
            </button>

            <div class="input-group otp-group mb-3">
                <input type="text" id="otpInput" class="form-control input-modern" placeholder="Enter OTP">
                <button id="verifyOtpBtn" class="btn btn-success btn-modern">Verify</button>
            </div>

            <div id="otpResult" class="mt-3"></div>
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
sendBtn.addEventListener('click', async function () {
    sendBtn.disabled = true;
    sendBtn.textContent = "Sending...";

    resultDiv.innerHTML = "";

    try {
        const res = await fetch('/rfid_monitoring_system/api/send_mail.php', {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ user_id: userId }),
        });

        const data = await res.json();
        resultDiv.innerHTML = `<div class="alert ${data.success ? 'alert-success' : 'alert-danger'}">${data.message}</div>`;

    } catch (e) {
        resultDiv.innerHTML = `<div class="alert alert-danger">Network error. Try again.</div>`;
    }

    sendBtn.disabled = false;
    sendBtn.textContent = "Send OTP (Forgot Card)";
});

// --- Verify OTP ---
verifyBtn.addEventListener('click', async function () {
    const otp = otpInput.value.trim();
    if (!otp) {
        resultDiv.innerHTML = `<div class="alert alert-warning">Enter your OTP first.</div>`;
        return;
    }

    verifyBtn.disabled = true;
    verifyBtn.textContent = "Verifying...";
    resultDiv.innerHTML = "";

    try {
        const res = await fetch('/rfid_monitoring_system/api/verify_otp.php', {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ otp: otp, user_id: userId }),
        });

        const data = await res.json();
        resultDiv.innerHTML = `<div class="alert ${data.success ? 'alert-success' : 'alert-danger'}">${data.message}</div>`;

    } catch (err) {
        resultDiv.innerHTML = `<div class="alert alert-danger">Verification error. Try again.</div>`;
    }

    verifyBtn.disabled = false;
    verifyBtn.textContent = "Verify OTP";
});
</script>

</body>
</html>
