<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../core/auth.php';
requireLogin();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/mailer.php'; // your mailer wrapper

try {
    if (!isset($_SESSION['user']['id'])) {
        echo json_encode(['error' => 'User not logged in.']);
        exit;
    }

    $userId = $_SESSION['user']['id'];

    // Fetch user email
    $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || empty($user['email'])) {
        echo json_encode(['error' => 'User email not found.']);
        exit;
    }

    // Generate OTP
    $otpCode = rand(100000, 999999);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    // Save OTP in DB
    $stmtOtp = $pdo->prepare("
        INSERT INTO otp_codes (user_id, otp_code, expires_at, created_at)
        VALUES (:user_id, :otp_code, :expires_at, NOW())
    ");
    $stmtOtp->execute([
        'user_id' => $userId,
        'otp_code' => $otpCode,
        'expires_at' => $expiresAt
    ]);

    // Send email
    $subject = "Your One-Time OTP for RFID Access";
    $body = "
        <h3>Hello {$user['name']},</h3>
        <p>Your One-Time Password (OTP) for accessing the RFID system is:</p>
        <h2 style='color: #2E86C1;'>{$otpCode}</h2>
        <p>This OTP will expire at <strong>{$expiresAt}</strong>.</p>
        <p>If you did not request this, please ignore this email.</p>
    ";

    $sent = sendMail($user['email'], $subject, $body);

    if ($sent) {
        echo json_encode([
            'success' => true,
            'expires_at' => $expiresAt
        ]);
    } else {
        echo json_encode(['error' => 'Failed to send OTP. Check logs.']);
    }

} catch (\Exception $e) {
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
