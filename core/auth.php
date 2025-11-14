<?php
require_once __DIR__ . '/../config/db.php';
session_start();

/** Attempt login using bcrypt password verification */
function login($email, $password) {
    global $pdo;

    $query = "SELECT u.*, uc.category_name 
              FROM users u 
              LEFT JOIN user_categories uc ON u.category_id = uc.id
              WHERE u.email = :email AND u.is_active = 1
              LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => strtolower($user['category_name']), // 'admin', 'instructor', or 'guest'
            'logged_in' => true
        ];
        return true;
    }

    return false;
}

function isLoggedIn() {
    return isset($_SESSION['user']['logged_in']) && $_SESSION['user']['logged_in'] === true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

function logout() {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}

/** Role helpers */
function isAdmin() { return isLoggedIn() && $_SESSION['user']['role'] === 'admin'; }
function isInstructor() { return isLoggedIn() && $_SESSION['user']['role'] === 'instructor'; }
function isGuest() { return isLoggedIn() && $_SESSION['user']['role'] === 'guest'; }
