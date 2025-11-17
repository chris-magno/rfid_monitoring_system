<?php
require_once __DIR__ . '/../core/auth.php';
requireLogin();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$alertId = $input['alert_id'] ?? null;

if (!$alertId) {
    echo json_encode(['success' => false, 'message' => 'No alert ID provided']);
    exit;
}

$stmt = $pdo->prepare("UPDATE admin_alerts SET is_read = 1 WHERE id = ?");
if ($stmt->execute([$alertId])) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
