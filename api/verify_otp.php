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

// Fetch user's UID (for logs)
$stmtUser = $pdo->prepare("SELECT uid FROM users WHERE id = ?");
$stmtUser->execute([$userId]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);
$uid = $user['uid'] ?? null;

if ($otpRecord) {

    // ✅ Check if user already has an open session (time_in but no time_out)
    $sessionStmt = $pdo->prepare("
        SELECT * FROM time_logs
        WHERE user_id = :user_id
          AND otp_code = :otp
          AND time_out IS NULL
        ORDER BY id DESC LIMIT 1
    ");
    $sessionStmt->execute([
        'user_id' => $userId,
        'otp' => $otp
    ]);
    $openSession = $sessionStmt->fetch(PDO::FETCH_ASSOC);

    if ($openSession) {
        // 🔹 If time_out is not set, close the session now
        $updateStmt = $pdo->prepare("UPDATE time_logs SET time_out = NOW() WHERE id = :id");
        $updateStmt->execute(['id' => $openSession['id']]);

        $action = 'time_out';
        $message = 'Time-out recorded. Door locked.';
    } else {
        // 🔹 If no open session, start a new one (time_in)
        $insertStmt = $pdo->prepare("
            INSERT INTO time_logs (user_id, uid, otp_code, time_in)
            VALUES (:user_id, :uid, :otp, NOW())
        ");
        $insertStmt->execute([
            'user_id' => $userId,
            'uid' => $uid,
            'otp' => $otp
        ]);

        $action = 'time_in';
        $message = 'Time-in recorded. Door unlocked.';
    }

    // ✅ Update access logs
    $accessStmt = $pdo->prepare("
        INSERT INTO access_logs (uid, user_id, status, attempts, access_type, log_time)
        VALUES (:uid, :user_id, 'granted', 1, 'otp', NOW())
    ");
    $accessStmt->execute([
        'uid' => $uid,
        'user_id' => $userId
    ]);

    // ✅ Send unlock signal to ESP
    $esp_ip = "http://10.104.17.80/unlock"; // Replace with actual ESP IP
    $ch = curl_init($esp_ip);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    echo json_encode([
        'success' => true,
<<<<<<< HEAD
        'action' => $action,
        'message' => $message,
        'esp_response' => $response,
        'curl_error' => $curl_error
    ]);
    exit;
}

// ❌ INVALID OR EXPIRED OTP
$stmt = $pdo->prepare("
    INSERT INTO access_logs (uid, user_id, status, attempts, access_type, log_time)
    VALUES (:uid, :user_id, 'denied', 1, 'otp', NOW())
");
$stmt->execute([
    'uid' => $uid,
    'user_id' => $userId
]);
=======
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
>>>>>>> e0819aecccea21e01e16e4d97be9759f6e3fe34a

echo json_encode([
    'success' => false,
    'message' => 'Invalid or expired OTP.'
]);
