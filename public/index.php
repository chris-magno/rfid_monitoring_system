<?php
require_once __DIR__ . '/../core/auth.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: dashboard.php');
    } else {
        header('Location: user_dashboard.php');
    }
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (login($email, $password)) {
        if (isAdmin()) {
            header('Location: dashboard.php');
        } else {
            header('Location: user_dashboard.php');
        }
        exit;
    } else {
        $error = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - RFID Monitoring</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow p-4 rounded-4">
                <h4 class="text-center mb-3">RFID DOORLOCK SYSTEM</h4>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger text-center py-2"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" autocomplete="off">
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
                    </div>
                    <button class="btn btn-primary w-100">Login</button>
                </form>

                <div class="text-center mt-3">
                    <small class="text-muted">RFID Monitoring System © <?= date('Y') ?></small>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
