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
    <title>Login - Letran RFID Monitoring</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link href="https://fonts.googleapis.com/css2?family=UnifrakturMaguntia&display=swap" rel="stylesheet">

    <style>
        font-family: 'UnifrakturMaguntia';

        /* Load the Cloister Black BT font */
        @font-face {
            font-family: 'Cloister Black BT';
            src: url('fonts/CloisterBlackBT.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        /* Background */
        body {
            position: relative;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
        
            /* Letran-style gradient overlay */
            background: linear-gradient(
                    rgba(0, 38, 99, 0.6),   /* dark blue with opacity */
                    rgba(204, 0, 0, 0.6)     /* red with opacity */
                ),
                url('../delapaazletranBackground.jpg') no-repeat center center fixed;
            background-size: cover;
        }

        /* Glassmorphic login card */
        .login-card {
            background: rgba(255, 255, 255, 0.9); /* semi-transparent white */
            backdrop-filter: blur(10px);
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: 0 12px 30px rgba(0,0,0,0.35); /* slightly darker shadow for contrast */
            width: 100%;
            max-width: 420px;
            text-align: center;
        }

        /* Login title */
        .login-title {
            font-family: 'Cloister Black BT', serif;
            font-size: 1.6rem;
            font-weight: 400;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            color: #2C2C2C;
            margin-bottom: 1.5rem;
        }

        .login-title img.logo {
            height: 50px;
        }

        /* Form styling */
        .login-card .form-control {
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
        }

        .login-card .btn-primary {
            background-color: #002663; /* Letran dark blue */
            border-color: #002663;
            font-weight: 600;
            padding: 0.75rem;
            border-radius: 0.5rem;
            transition: 0.3s;
        }

        .login-card .btn-primary:hover {
            background-color: #0040A0;
            border-color: #0040A0;
        }

        .alert {
            font-size: 0.9rem;
        }

        .login-footer {
            margin-top: 1.5rem;
            font-size: 0.85rem;
            color: #555;
        }

        @media (max-width: 576px) {
            .login-card {
                padding: 2rem;
            }
            .login-title {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-card shadow">
        <h4 class="login-title" style="font-family: 'UnifrakturMaguntia', cursive;">
            <img src="../letran_logo.png" alt="Letran Logo" class="logo">
            Colegio de San Juan de Letran Manaoag
        </h4>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="mb-3 text-start">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>
            <div class="mb-3 text-start">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
            </div>
            <button class="btn btn-primary w-100">Login</button>
        </form>

        <div class="login-footer">
            RFID Monitoring System © <?= date('Y') ?>
        </div>
    </div>
</body>
</html>
