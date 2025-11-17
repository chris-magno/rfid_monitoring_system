<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/mailer.php'; // your mailer wrapper

define('ADMIN_EMAIL', 'admin@example.com'); // set your admin email

try {
    $uid = $_POST['uid'] ?? '';
    if (!$uid) {
        echo json_encode(['error' => 'UID not provided']);
        exit;
    }

    // Fetch user info if exists
    $stmt = $pdo->prepare("SELECT name FROM users WHERE uid = ? LIMIT 1");
    $stmt->execute([$uid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $userName = $user['name'] ?? "Unknown User";

    $subject = "ALERT: 3 Failed RFID Attempts";
    $body = "
        <h3>Attention Admin,</h3>
        <p>The following RFID card has failed 3 access attempts:</p>
        <ul>
            <li>Name: {$userName}</li>
            <li>UID: {$uid}</li>
            <li>Time: " . date('Y-m-d H:i:s') . "</li>
        </ul>
        <p>Please check the system immediately.</p>
    ";

    // Send email to admin
    $sent = sendMail(ADMIN_EMAIL, $subject, $body);

    // Log alert in database
    $logStmt = $pdo->prepare("
        INSERT INTO admin_alerts (uid, user_name, alert_type, message, created_at)
        VALUES (:uid, :user_name, :alert_type, :message, NOW())
    ");
    $logStmt->execute([
        'uid' => $uid,
        'user_name' => $userName,
        'alert_type' => '3_failed_attempts',
        'message' => "3 failed access attempts detected for UID $uid ($userName)"
    ]);

    echo json_encode([
        'success' => $sent ? true : false,
        'message' => $sent ? 'Admin alerted and logged' : 'Failed to send email, but logged'
    ]);

} catch (\Exception $e) {
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
