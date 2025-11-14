<?php
// Temporarily store scanned UID
header("Content-Type: application/json");
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "POST required"]);
    exit;
}

$uid = $_POST['uid'] ?? null;
if (!$uid) {
    http_response_code(400);
    echo json_encode(["error" => "UID missing"]);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO uid_container (uid, scanned_at) VALUES (:uid, NOW())");
    $stmt->execute(['uid' => $uid]);
    echo json_encode(["status" => "ok", "uid" => $uid]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
