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
/* ===== BODY & BACKGROUND ===== */
body {
    min-height: 100vh;
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(
            rgba(0, 38, 99, 0.7),
            rgba(204, 0, 0, 0.7)
        ),
        url('../delapaazletranBackground.jpg') no-repeat center center fixed;
    background-size: cover;
}

/* ===== NAVBAR ===== */
.navbar {
    border-bottom: 1px solid #222;
}

.navbar-brand {
    font-weight: 700;
    color: #fff !important;
}

/* ===== HEADER ===== */
.dashboard-title {
    font-size: 2.2rem;
    font-weight: 700;
    color: #fff;
    text-shadow: 0 2px 6px rgba(0,0,0,0.4);
}

.info-label {
    font-weight: 600;
    color: #002663;
}

/* ===== DASHBOARD CARDS ===== */
.card-modern {
    background: rgba(255, 255, 255, 0.92);
    padding: 25px;
    border-radius: 1.5rem;
    box-shadow: 0 12px 28px rgba(0,0,0,0.18);
    border: none;
}

.card-stat {
    background: rgba(255,255,255,0.95);
    border-radius: 1.2rem;
    box-shadow: 0 6px 18px rgba(0,0,0,0.12);
    padding: 20px;
    text-align: center;
    transition: transform 0.2s;
}

.card-stat:hover {
    transform: translateY(-5px);
}

.card-stat h5 {
    font-weight: 700;
    color: #002663;
    margin-bottom: 8px;
}

.card-stat p {
    font-weight: 500;
    color: #333;
    margin: 0;
}

/* ===== AVATAR ===== */
.avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 4px solid rgba(255,255,255,0.6);
    box-shadow: 0 6px 18px rgba(0,0,0,0.15);
    margin-bottom: 15px;
}

/* ===== BUTTONS ===== */
.btn-modern {
    border-radius: 12px;
    font-weight: 600;
    padding: 10px 16px;
    transition: 0.3s;
}

.btn-primary {
    background-color: #002663;
    border-color: #002663;
}

.btn-primary:hover {
    background-color: #001f4d;
    border-color: #001f4d;
}

.btn-success {
    background-color: #cc0000;
    border-color: #cc0000;
}

.btn-success:hover {
    background-color: #990000;
    border-color: #990000;
}

/* ===== OTP SECTION ===== */
.otp-group {
    max-width: 350px;
    margin: 0 auto;
}

#otpInput {
    border-radius: 12px !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .dashboard-title { font-size: 1.8rem; }
}
</style>
</head>

<body>

<!-- Navbar -->
<nav class="navbar navbar-dark bg-dark w-100">
    <div class="container">
        <a class="navbar-brand" href="#">RFID Monitoring</a>
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
        <p class="text-light">Your profile and security access panel.</p>
    </div>

    <!-- Top Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card-stat">
                <h5>User Role</h5>
                <p><?= htmlspecialchars(ucfirst($role)) ?></p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-stat">
                <h5>Registered On</h5>
                <p><?= htmlspecialchars($user['created_at']) ?></p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-stat">
                <h5>UID</h5>
                <p><?= htmlspecialchars($user['uid'] ?? '-') ?></p>
            </div>
        </div>
    </div>

    <!-- User Card -->
    <div class="card-modern text-center">
        <img src="https://cdn-icons-png.flaticon.com/512/847/847969.png" class="avatar" alt="User Avatar">
        <h4 class="mb-4">Your ID Profile</h4>

        <p><span class="info-label">Name:</span> <?= htmlspecialchars($user['name']) ?></p>
        <p><span class="info-label">Email:</span> <?= htmlspecialchars($user['email'] ?? '-') ?></p>

        <hr class="my-4">

        <!-- OTP Section -->
        <button id="sendOtpBtn" class="btn btn-primary btn-modern w-100 mb-3">
            Send OTP (Forgot Card)
        </button>
        <div class="input-group otp-group mb-3">
            <input type="text" id="otpInput" class="form-control" placeholder="Enter OTP">
            <button id="verifyOtpBtn" class="btn btn-success btn-modern">Verify</button>
        </div>
        <div id="otpResult" class="mt-3"></div>
    </div>

</div>

<script>
const userId = <?= json_encode($userId) ?>;
const sendBtn = document.querySelector('#sendOtpBtn');
const verifyBtn = document.querySelector('#verifyOtpBtn');
const otpInput = document.querySelector('#otpInput');
const resultDiv = document.querySelector('#otpResult');

// --- Helper function for messages ---
function showMessage(message, type = 'info') {
    let alertClass = 'alert-info';
    if(type === 'success') alertClass = 'alert-success';
    if(type === 'error') alertClass = 'alert-danger';
    if(type === 'warning') alertClass = 'alert-warning';

    resultDiv.innerHTML = `<div class="alert ${alertClass} text-center">${message}</div>`;
}

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

        if(data.success){
            showMessage(`✅ OTP has been sent to your registered email. It is valid for 5 minutes.`, 'success');
        } else {
            showMessage(`❌ Failed to send OTP. ${data.message || ''}`, 'error');
        }

    } catch (e) {
        showMessage("⚠️ Network error. Please check your connection and try again.", 'error');
    }

    sendBtn.disabled = false;
    sendBtn.textContent = "Send OTP (Forgot Card)";
});

// --- Verify OTP ---
verifyBtn.addEventListener('click', async function () {
    const otp = otpInput.value.trim();
    if (!otp) {
        showMessage("⚠️ Please enter the OTP you received.", 'warning');
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

        if(data.success){
            showMessage(`✅ OTP verified successfully. Access granted.`, 'success');
            otpInput.value = '';
        } else {
            showMessage(`❌ Invalid OTP. Please try again.`, 'error');
        }

    } catch (err) {
        showMessage("⚠️ Verification failed due to network error. Try again.", 'error');
    }

    verifyBtn.disabled = false;
    verifyBtn.textContent = "Verify OTP";
});

</script>

</body>
</html>
