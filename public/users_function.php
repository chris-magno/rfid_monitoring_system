<?php
require_once __DIR__ . '/../core/auth.php';
requireLogin();
require_once __DIR__ . '/../config/db.php';

$success = '';
$error = '';

// ==========================
// Handle POST actions
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'update':
            $id = (int) $_POST['id'];
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $category_id = (int) $_POST['category_id'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            try {
                $stmt = $pdo->prepare("UPDATE users 
                    SET name=:name, email=:email, category_id=:category_id, 
                        is_active=:is_active, updated_at=NOW() 
                    WHERE id=:id");
                $stmt->execute([
                    'name' => $name,
                    'email' => $email ?: null,
                    'category_id' => $category_id,
                    'is_active' => $is_active,
                    'id' => $id
                ]);
                $success = "User updated successfully!";
            } catch (PDOException $e) {
                $error = "Update failed: " . $e->getMessage();
            }
            break;

        case 'delete':
            $id = (int) $_POST['id'];
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id=:id");
                $stmt->execute(['id' => $id]);
                $success = "User deleted successfully!";
            } catch (PDOException $e) {
                $error = "Delete failed: " . $e->getMessage();
            }
            break;

        case 'reset_password':
            $id = (int) $_POST['id'];
            $newPassword = trim($_POST['new_password'] ?? '');

            if (empty($newPassword)) {
                $error = "Please enter a new password.";
                break;
            }

            $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);

            try {
                $stmt = $pdo->prepare("UPDATE users SET password=:password, updated_at=NOW() WHERE id=:id");
                $stmt->execute(['password' => $passwordHash, 'id' => $id]);
                $success = "Password updated successfully!";
            } catch (PDOException $e) {
                $error = "Password update failed: " . $e->getMessage();
            }
            break;
    }
}

// ==========================
// Fetch users & categories
// ==========================
try {
    $stmt = $pdo->query("
        SELECT u.id, u.name, u.email, u.uid, uc.category_name, 
               u.category_id, u.is_active, u.created_at 
        FROM users u 
        LEFT JOIN user_categories uc ON u.category_id = uc.id 
        ORDER BY u.id DESC
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
    $error = $e->getMessage();
}

try {
    $stmt = $pdo->query("SELECT id, category_name FROM user_categories ORDER BY id ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}
?>
