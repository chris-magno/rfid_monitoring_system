<?php
// api/read_tag_api.php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/db.php';

try {
    $stmt = $pdo->query("
        SELECT uc.uid, uc.scanned_at, u.name, u.email
        FROM uid_container uc
        LEFT JOIN users u ON uc.uid = u.uid
        ORDER BY uc.scanned_at DESC
        LIMIT 1
    ");

    $last = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;

    if ($last) {
        echo json_encode([
            "uid" => $last['uid'],
            "scanned_at" => $last['scanned_at'],
            "name" => $last['name'] ?? null,
            "email" => $last['email'] ?? null
        ]);
    } else {
        echo json_encode([]);
    }
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
