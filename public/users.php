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

/* ===== HEADER ===== */
.header {
    padding: 25px 0;
    text-align: center;
    color: #111827;
}

.header h2 {
    font-weight: 600;
}

/* ===== BUTTONS ===== */
.btn-custom {
    border-radius: 8px;
    padding: 8px 18px;
    font-weight: 500;
}

/* ===== TABLE CARD ===== */
.card {
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    background: #ffffff;
    padding: 20px;
    margin-bottom: 30px;
}

h2 {
    color: white;
}

.table thead {
    background: #f3f4f6;
}

.table th, .table td {
    vertical-align: middle;
}

/* ===== ACTION BUTTONS ===== */
.btn-sm {
    border-radius: 6px;
}

/* ===== MODALS ===== */
.modal-content {
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

/* ===== RESPONSIVE SPACING ===== */
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
    <div class="header mb-4">
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

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editModal<?= $user['id'] ?>" tabindex="-1">
                          <div class="modal-dialog">
                            <div class="modal-content">
                              <form method="POST">
                                <div class="modal-header">
                                  <h5 class="modal-title">Edit User #<?= $user['id'] ?></h5>
                                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                  <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                  <input type="hidden" name="action" value="update">
                                  <div class="mb-3">
                                    <label>Name</label>
                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                                  </div>
                                  <div class="mb-3">
                                    <label>Email</label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                                  </div>
                                  <div class="mb-3">
                                    <label>Category</label>
                                    <select name="category_id" class="form-select" required>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>" <?= $user['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['category_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                  </div>
                                  <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="is_active" <?= $user['is_active'] ? 'checked' : '' ?>>
                                    <label class="form-check-label">Active</label>
                                  </div>
                                </div>
                                <div class="modal-footer">
                                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                  <button type="submit" class="btn btn-primary">Save Changes</button>
                                </div>
                              </form>
                            </div>
                          </div>
                        </div>

                        <!-- Reset Password Modal -->
                        <div class="modal fade" id="resetModal<?= $user['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                <form method="POST">
                                    <div class="modal-header">
                                    <h5 class="modal-title">Set New Password for <?= htmlspecialchars($user['name']) ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                    <input type="hidden" name="action" value="reset_password">

                                    <div class="mb-3">
                                        <label>New Password</label>
                                        <input type="password" name="new_password" class="form-control" placeholder="Enter new password" required>
                                    </div>
                                </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-warning">Update Password</button>
                                    </div>
                                </form>
                                </div>
                            </div>
                        </div>

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
