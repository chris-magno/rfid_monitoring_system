<?php
require_once __DIR__ . '/users_function.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Management - RFID Monitoring</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<style>
/* ===== BODY & BACKGROUND ===== */
body {
    position: relative;
    min-height: 100vh;
    font-family: 'Segoe UI', sans-serif;
    margin: 0;
    padding: 20px;
    background: linear-gradient(
            rgba(0, 38, 99, 0.6),
            rgba(204, 0, 0, 0.6)
        ),
        url('../delapaazletranBackground.jpg') no-repeat center center fixed;
    background-size: cover;
}

/* ===== HEADER ===== */
.header {
    text-align: center;
    color: #fff;
    margin-bottom: 30px;
}

.header h2 {
    font-weight: 700;
    font-size: 2rem;
    letter-spacing: 1px;
}

/* ===== BUTTONS ===== */
.btn-custom {
    border-radius: 8px;
    padding: 8px 20px;
    font-weight: 600;
    transition: 0.3s;
}

.btn-success {
    background-color: #0040A0;
    border-color: #0040A0;
}

.btn-success:hover {
    background-color: #002663;
    border-color: #002663;
}

.btn-secondary {
    background-color: rgba(255,255,255,0.85);
    border-color: rgba(255,255,255,0.85);
    color: #002663;
}

.btn-secondary:hover {
    background-color: rgba(255,255,255,1);
}

/* ===== CARD ===== */
.card {
    border-radius: 1rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.25);
    background: rgba(255,255,255,0.9);
    padding: 20px;
    margin-bottom: 30px;
}

/* ===== TABLE ===== */
.table thead {
    background-color: #002663;
    color: #fff;
    font-weight: 600;
}

.table th, .table td {
    vertical-align: middle;
    padding: 12px 15px;
}

.table tbody tr:hover {
    background-color: rgba(0, 38, 99, 0.1);
}

.table-responsive {
    overflow-x: auto;
}

/* ===== MODALS ===== */
.modal-content {
    border-radius: 12px;
    box-shadow: 0 12px 30px rgba(0,0,0,0.25);
}

.modal-header {
    background-color: #002663;
    color: #fff;
    border-bottom: none;
    font-weight: 600;
}

.modal-footer {
    border-top: none;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .btn-group {
        flex-direction: column;
    }
    .btn-group .btn {
        margin-bottom: 8px;
    }
}
</style>
</head>
<body>

<div class="container">
    <!-- HEADER -->
    <div class="header">
        <h2>User Management</h2>
    </div>

    <!-- ALERTS -->
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- ACTION BUTTONS -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <a href="register_user.php" class="btn btn-success btn-custom"><i class="bi bi-person-plus"></i> Register New User</a>
        <a href="dashboard.php" class="btn btn-secondary btn-custom"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
    </div>

    <!-- USERS TABLE CARD -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>UID</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Password</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td><?= htmlspecialchars($user['email'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($user['uid'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($user['category_name'] ?? '-') ?></td>
                            <td><?= $user['is_active'] ? 'Active' : 'Inactive' ?></td>
                            <td><?= $user['created_at'] ?></td>
                            <td>
                                <?php if (isset($lastTempPassword[$user['id']])): ?>
                                    <span class="text-success"><?= htmlspecialchars($lastTempPassword[$user['id']]) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">********</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $user['id'] ?>"><i class="bi bi-pencil-square"></i></button>
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#resetModal<?= $user['id'] ?>"><i class="bi bi-key"></i></button>
                                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure?')">
                                        <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <!-- Modals remain unchanged, they inherit the modal styling -->
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="9" class="text-center">No users found</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
