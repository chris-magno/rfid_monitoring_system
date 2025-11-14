<?php
// api/getUID.php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["error" => "Invalid request method"]);
    exit;
}

// Get UID from Arduino POST
$uid = trim($_POST['UIDresult'] ?? '');
if (!$uid) {
    echo json_encode(["error" => "UID not provided"]);
    exit;
}

try {
    // 1️⃣ Insert UID into uid_container (latest scan)
    $stmt = $pdo->prepare("INSERT INTO uid_container (uid, scanned_at) VALUES (:uid, NOW())");
    $stmt->execute(['uid' => $uid]);

    // 2️⃣ Check if UID is registered
    $userStmt = $pdo->prepare("SELECT id, name, email FROM users WHERE uid = :uid LIMIT 1");
    $userStmt->execute(['uid' => $uid]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    $status = 'denied';
    $userId = $user['id'] ?? null;

    // 3️⃣ Access control & logging
    if ($user) {
        $status = 'granted';

        // Insert / update time_logs
        $timeLogStmt = $pdo->prepare("
            SELECT * FROM time_logs 
            WHERE user_id = :user_id AND DATE(time_in) = CURDATE() 
            ORDER BY id DESC LIMIT 1
        ");
        $timeLogStmt->execute(['user_id' => $userId]);
        $timeLog = $timeLogStmt->fetch(PDO::FETCH_ASSOC);

        if (!$timeLog) {
            // First scan today → time_in
            $insertTime = $pdo->prepare("INSERT INTO time_logs (user_id, uid, time_in) VALUES (:user_id, :uid, NOW())");
            $insertTime->execute(['user_id' => $userId, 'uid' => $uid]);
        } elseif ($timeLog['time_out'] === null) {
            // Already checked in → time_out
            $updateTime = $pdo->prepare("UPDATE time_logs SET time_out = NOW() WHERE id = :id");
            $updateTime->execute(['id' => $timeLog['id']]);
        } else {
            // Already has time_in & time_out → new time_in
            $insertTime = $pdo->prepare("INSERT INTO time_logs (user_id, uid, time_in) VALUES (:user_id, :uid, NOW())");
            $insertTime->execute(['user_id' => $userId, 'uid' => $uid]);
        }
    }

    // 4️⃣ Insert into access_logs
    $attempts = 1; // default, you can enhance with failed attempt counter if needed
    $accessStmt = $pdo->prepare("
        INSERT INTO access_logs (uid, user_id, status, attempts, log_time) 
        VALUES (:uid, :user_id, :status, :attempts, NOW())
    ");
    $accessStmt->execute([
        'uid' => $uid,
        'user_id' => $userId,
        'status' => $status,
        'attempts' => $attempts
    ]);

    // 5️⃣ Return JSON for Arduino
    echo json_encode([
        "uid" => $uid,
        "name" => $user['name'] ?? null,
        "email" => $user['email'] ?? null,
        "status" => $status
    ]);

} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
