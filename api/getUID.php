<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["error" => "Invalid request method"]);
    exit;
}

$uid = trim($_POST['UIDresult'] ?? '');
if (!$uid) {
    echo json_encode(["error" => "UID not provided"]);
    exit;
}

try {
    // Insert UID scan record
    $stmt = $pdo->prepare("INSERT INTO uid_container (uid, scanned_at) VALUES (:uid, NOW())");
    $stmt->execute(['uid' => $uid]);

    // Check if user exists
    $userStmt = $pdo->prepare("SELECT id, name, email FROM users WHERE uid = :uid LIMIT 1");
    $userStmt->execute(['uid' => $uid]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    $status = 'denied';
    $userId = $user['id'] ?? null;

    if ($user) {
        // Check if there is any active TIME IN (time_out IS NULL)
        $activeStmt = $pdo->query("SELECT * FROM time_logs WHERE time_out IS NULL ORDER BY time_in DESC LIMIT 1");
        $activeLog = $activeStmt->fetch(PDO::FETCH_ASSOC);

        if ($activeLog) {
            if ($activeLog['uid'] == $uid) {
                // Same card → allow TIME OUT
                $updateTime = $pdo->prepare("UPDATE time_logs SET time_out = NOW() WHERE id = :id");
                $updateTime->execute(['id' => $activeLog['id']]);
                $status = 'granted';
            } else {
                // Another card tapped → deny access
                $status = 'denied';
            }
        } else {
            // No active session → TIME IN
            $insertTime = $pdo->prepare("INSERT INTO time_logs (user_id, uid, time_in) VALUES (:user_id, :uid, NOW())");
            $insertTime->execute(['user_id' => $userId, 'uid' => $uid]);
            $status = 'granted';
        }
    }

    // Log the access attempt
    $accessStmt = $pdo->prepare("INSERT INTO access_logs (uid, user_id, status, attempts, log_time) VALUES (:uid, :user_id, :status, 1, NOW())");
    $accessStmt->execute([
        'uid' => $uid,
        'user_id' => $userId,
        'status' => $status
    ]);

    echo json_encode([
        "uid" => $uid,
        "name" => $user['name'] ?? null,
        "email" => $user['email'] ?? null,
        "status" => $status
    ]);

} catch (PDOException $e) {
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
