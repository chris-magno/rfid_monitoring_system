<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../core/auth.php';
requireLogin();
require_once __DIR__ . '/../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);

$otp = $input['otp'] ?? null;
$userId = $input['user_id'] ?? $_SESSION['user']['id'] ?? null;

if (!$otp || !$userId) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing OTP or User ID.'
    ]);
    exit;
}

// Fetch OTP in database
$stmt = $pdo->prepare("
    SELECT * FROM otp_codes
    WHERE user_id = :user_id
      AND otp_code = :otp
      AND expires_at >= NOW()
    ORDER BY created_at DESC
    LIMIT 1
");
$stmt->execute([
    'user_id' => $userId,
    'otp' => $otp
]);
$otpRecord = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch user's UID for logging
$stmtUser = $pdo->prepare("SELECT uid FROM users WHERE id = ?");
$stmtUser->execute([$userId]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);
$uid = $user['uid'] ?? null;

if ($otpRecord) {
    // ✅ OTP valid
    $stmt = $pdo->prepare("UPDATE otp_codes SET is_used = 1 WHERE id = ?");
    $stmt->execute([$otpRecord['id']]);

    // Log success
    $stmt = $pdo->prepare("
        INSERT INTO access_logs (uid, user_id, status, attempts, access_type, log_time)
        VALUES (:uid, :user_id, 'granted', 1, 'otp', NOW())
    ");
    $accessStmt->execute([
        'uid' => $uid,
        'user_id' => $userId
    ]);

    // ✅ Send unlock command to ESP
    $esp_ip = "http://192.168.100.35/unlock"; // your ESP's local IP
    $ch = curl_init($esp_ip);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    echo json_encode([
        'success' => true,
        'message' => 'Access granted via OTP. Door unlock signal sent.',
        'esp_response' => $response
    ]);
} else {
    // ❌ OTP invalid
    $stmt = $pdo->prepare("
        INSERT INTO access_logs (uid, user_id, status, attempts, access_type, log_time)
        VALUES (:uid, :user_id, 'denied', 1, 'otp', NOW())
    ");
    $stmt->execute([
        'uid' => $uid,
        'user_id' => $userId
    ]);

echo json_encode([
    'success' => false,
    'message' => 'Invalid or expired OTP.'
]);
