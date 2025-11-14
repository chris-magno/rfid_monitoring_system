<?php
require_once __DIR__ . '/../config/db.php';

$userId = $_GET['user_id'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Missing user_id']);
    exit;
}

$stmt = $pdo->prepare("SELECT otp_verified FROM users WHERE id = ?");
$stmt->execute([$userId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row && $row['otp_verified'] == 1) {
    // Reset verification so it’s one-time use
    $pdo->prepare("UPDATE users SET otp_verified = 0 WHERE id = ?")->execute([$userId]);

    echo json_encode(['verified' => true]);
} else {
    echo json_encode(['verified' => false]);
}
