<?php
require_once __DIR__ . '/../core/auth.php';
requireLogin();
require_once __DIR__ . '/../config/db.php';

$success = '';
$error = '';
$disableForm = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $category_id = (int) $_POST['category_id'];
    $uid = trim($_POST['uid']);

    if (empty($name) || empty($category_id) || empty($uid)) {
        $error = "Name, category, and UID are required!";
    } else {
        try {
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE uid = :uid LIMIT 1");
            $checkStmt->execute(['uid' => $uid]);
            $existingUser = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($existingUser) {
                $error = "This UID is already registered!";
                $disableForm = true;
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (name, email, uid, password, category_id, is_active, created_at, updated_at) VALUES (:name, :email, :uid, :password, :category_id, 1, NOW(), NOW())");
                $defaultPassword = bin2hex(random_bytes(4));
                $passwordHash = password_hash($defaultPassword, PASSWORD_BCRYPT);

                $stmt->execute([
                    'name' => $name,
                    'email' => $email ?: null,
                    'uid' => $uid,
                    'password' => $passwordHash,
                    'category_id' => $category_id
                ]);

                $success = "User registered successfully! Default password: <b>$defaultPassword</b>";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch categories
try {
    $stmt = $pdo->query("SELECT id, category_name FROM user_categories ORDER BY id ASC");
    $categories = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (PDOException $e) {
    $categories = [];
    $error = "Failed to fetch categories: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register User - RFID Monitoring</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow p-4">
                <h4 class="text-center mb-4">Register New User</h4>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" id="registerForm" <?= $disableForm ? 'class="disabled-form"' : '' ?>>
                    <div class="mb-3">
                        <label>Name</label>
                        <input type="text" id="name" name="name" class="form-control" required <?= $disableForm ? 'disabled' : '' ?>>
                    </div>

                    <div class="mb-3">
                        <label>Email (optional)</label>
                        <input type="email" id="email" name="email" class="form-control" <?= $disableForm ? 'disabled' : '' ?>>
                    </div>

                    <div class="mb-3">
                        <label>Category</label>
                        <select name="category_id" class="form-select" required <?= $disableForm ? 'disabled' : '' ?>>
                            <option value="">-- Select Category --</option>
                            <?php if (!empty($categories)): ?>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="">No categories found</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>RFID UID</label>
                        <input type="text" id="uid" name="uid" class="form-control" placeholder="Scan tag to auto-fill" <?= $disableForm ? 'readonly' : '' ?>>
                        <div id="uid-status" class="mt-1"></div>
                    </div>

                    <button class="btn btn-primary w-100" <?= $disableForm ? 'disabled' : '' ?>>Register User</button>
                </form>

                <div class="mt-3">
                    <a href="dashboard.php">&laquo; Back to Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Poll read_tag_api.php every 2 seconds to auto-fill UID and show status
async function fetchLatestUID() {
    try {
        const response = await fetch('../api/read_tag_api.php');
        const data = await response.json();

        const uidInput = document.getElementById('uid');
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        const statusDiv = document.getElementById('uid-status');
        const form = document.getElementById('registerForm');

        if (data.uid) {
            if (uidInput.value !== data.uid) {
                uidInput.value = data.uid;
            }

            if (data.name) {
                nameInput.value = data.name;
                emailInput.value = data.email || '';
                statusDiv.innerHTML = '<span class="text-success">UID already registered ✅</span>';
                // Disable form if UID is registered
                form.querySelectorAll('input, select, button').forEach(el => el.disabled = true);
            } else {
                statusDiv.innerHTML = '<span class="text-danger">UID not registered ❌</span>';
                form.querySelectorAll('input, select, button').forEach(el => el.disabled = false);
            }
        } else {
            statusDiv.innerHTML = '';
        }
    } catch (err) {
        console.error('Error fetching UID:', err);
    }
}

// Poll every 2 seconds
setInterval(fetchLatestUID, 2000);
</script>

</body>
</html>
