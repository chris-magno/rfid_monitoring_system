<?php
require_once __DIR__ . '/users_function.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Management - RFID Monitoring</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2 class="mb-4 text-center">User Management</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="mb-3">
        <a href="register_user.php" class="btn btn-success">+ Register New User</a>
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
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
                                <!-- Edit button -->
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $user['id'] ?>">Edit</button>

                                <!-- Reset Password button -->
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#resetModal<?= $user['id'] ?>">Reset</button>

                                <!-- Delete button -->
                                <form method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure?')">
                                    <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button class="btn btn-sm btn-danger">Delete</button>
                                </form>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
