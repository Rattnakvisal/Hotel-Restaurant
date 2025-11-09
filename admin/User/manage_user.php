<?php
session_start();
require_once '../../config/connect.php';

// --- Filter logic ---
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$where = '';
if ($filter === 'admin') {
    $where = "WHERE role = 'admin'";
} elseif ($filter === 'active') {
    $where = "WHERE status = 'active'";
} elseif ($filter === 'inactive') {
    $where = "WHERE status = 'inactive'";
}

// Handle activate/deactivate user
$activate_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_user_id'])) {
    $toggle_user_id = intval($_POST['toggle_user_id']);
    $toggle_action = $_POST['toggle_action'];
    // Fix: Use admin session for user management, not user session
    $current_user_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 0;
    if ($toggle_user_id == $current_user_id) {
        $activate_msg = "You cannot change your own activation status.";
    } else {
        $new_status = ($toggle_action === 'activate') ? 'active' : 'inactive';
        $sql = "UPDATE users SET status = :status WHERE user_id = :user_id";
        $stmt = oci_parse($connection, $sql);
        oci_bind_by_name($stmt, ':status', $new_status);
        oci_bind_by_name($stmt, ':user_id', $toggle_user_id);
        if (oci_execute($stmt)) {
            $activate_msg = "User status updated.";
        } else {
            $activate_msg = "Failed to update user status.";
        }
        oci_free_statement($stmt);
    }
}

// Handle delete user
$delete_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    $delete_user_id = intval($_POST['delete_user_id']);
    // Fix: Use admin_id for self-check, not user_id
    $current_admin_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 0;
    if ($delete_user_id == $current_admin_id) {
        $delete_msg = "You cannot delete your own account.";
    } else {
        $error_occurred = false;
        $tables = [
            "booking_payments" => "user_id = :user_id",
            "order_payments" => "user_id = :user_id",
            "bookings" => "user_id = :user_id",
            "restaurant_orders" => "user_id = :user_id",
            "system_logs" => "user_id = :user_id"
        ];
        $first = true;
        foreach ($tables as $table => $cond) {
            $sql_del = "DELETE FROM $table WHERE $cond";
            $stmt_del = oci_parse($connection, $sql_del);
            oci_bind_by_name($stmt_del, ':user_id', $delete_user_id);
            $exec_flag = $first ? OCI_NO_AUTO_COMMIT : OCI_DEFAULT;
            if (!@oci_execute($stmt_del, $exec_flag)) {
                $error_occurred = true;
                break;
            }
            oci_free_statement($stmt_del);
            $first = false;
        }
        if (!$error_occurred) {
            $sql = "DELETE FROM users WHERE user_id = :user_id";
            $stmt = oci_parse($connection, $sql);
            oci_bind_by_name($stmt, ':user_id', $delete_user_id);
            $result = @oci_execute($stmt, OCI_NO_AUTO_COMMIT);
            if ($result) {
                oci_commit($connection);
                $delete_msg = "User and all related data deleted successfully.";
            } else {
                $error = oci_error($stmt);
                oci_rollback($connection);
                if (strpos($error['message'], 'ORA-02292') !== false) {
                    $delete_msg = "Cannot delete user: This user has related records. Please remove related data first.";
                } else {
                    $delete_msg = "Failed to delete user.";
                }
            }
            oci_free_statement($stmt);
        } else {
            oci_rollback($connection);
            $delete_msg = "Failed to delete user or related data.";
        }
    }
}

// --- Handle Edit User (Save Changes) ---
$edit_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user_id'])) {
    $edit_user_id = intval($_POST['edit_user_id']);
    $edit_name = trim($_POST['edit_name']);
    $edit_email = trim($_POST['edit_email']);
    $edit_role = trim($_POST['edit_role']);

    // Prevent editing own role if not admin (optional, for safety)
    if ($edit_user_id == $_SESSION['user_id'] && $_SESSION['role'] !== 'admin') {
        $edit_msg = "You cannot change your own role.";
    } else {
        $sql = "UPDATE users SET name = :name, email = :email, role = :role WHERE user_id = :user_id";
        $stmt = oci_parse($connection, $sql);
        oci_bind_by_name($stmt, ':name', $edit_name);
        oci_bind_by_name($stmt, ':email', $edit_email);
        oci_bind_by_name($stmt, ':role', $edit_role);
        oci_bind_by_name($stmt, ':user_id', $edit_user_id);
        if (oci_execute($stmt)) {
            $edit_msg = "User updated successfully.";
        } else {
            $edit_msg = "Failed to update user.";
        }
        oci_free_statement($stmt);
    }
}

// Fetch all users with filter (now includes status)
$users = [];
$sql = "SELECT user_id, name, email, role, status, created_at FROM users $where ORDER BY user_id DESC";
$stmt = oci_parse($connection, $sql);
oci_execute($stmt);
while ($row = oci_fetch_assoc($stmt)) {
    $users[] = $row;
}
oci_free_statement($stmt);
oci_close($connection);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Manage Users | Admin Dashboard</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css"
        rel="stylesheet" />
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" />
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    <link rel="stylesheet" href="../../assets/Css/admin.css" />
    <style>
        /* Custom styles for user management - Main Content Only */
        .main-content {
            margin-left: 260px;
            padding: 30px;
            transition: all 0.3s ease;
        }

        .main-content h2 {
            font-weight: 700;
            margin-bottom: 24px;
            position: relative;
            padding-bottom: 12px;
            animation: fadeInDown 0.8s ease;
        }

        .main-content h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, #ff7b00, #ff0058);
            border-radius: 4px;
            animation: lineExpand 1s ease-out forwards;
        }

        .alert-info {
            margin-bottom: 18px;
            animation: slideInRight 0.5s ease, pulse 2s 2;
            border-left: 4px solid #0dcaf0;
        }

        /* Table styles */
        .table-responsive {
            overflow-x: auto;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .table thead th {
            background: linear-gradient(135deg, #2c3e50, #4a6491);
            color: #fff;
            vertical-align: middle;
            position: sticky;
            top: 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .table td,
        .table th {
            vertical-align: middle;
            transition: all 0.3s ease;
            padding: 12px 15px;
        }

        .table-hover tbody tr {
            transition: all 0.3s ease;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.08);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.05);
        }

        .btn-edit {
            background: linear-gradient(135deg, #198754, #20c997);
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            margin-right: 6px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            box-shadow: 0 3px 6px rgba(25, 135, 84, 0.2);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-edit:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 10px rgba(25, 135, 84, 0.3);
        }

        .btn-danger.btn-sm {
            padding: 8px 16px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            box-shadow: 0 3px 6px rgba(220, 53, 69, 0.2);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-danger.btn-sm:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 10px rgba(220, 53, 69, 0.3);
        }

        .text-muted {
            font-style: italic;
            opacity: 0.8;
        }

        /* Card view for mobile */
        .user-cards {
            display: none;
            grid-template-columns: 1fr;
            gap: 16px;
            margin-top: 20px;
        }

        .user-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 20px;
            animation: fadeInRow 0.6s ease forwards;
            opacity: 0;
        }

        .user-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .user-id {
            font-weight: bold;
            color: #2c3e50;
            font-size: 0.9rem;
        }

        .user-name {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .user-email {
            color: #666;
            margin-bottom: 12px;
            word-break: break-all;
        }

        .user-role {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            background: rgba(25, 135, 84, 0.15);
            color: #198754;
        }

        .user-role.admin {
            background: rgba(13, 110, 253, 0.15);
            color: #0d6efd;
        }

        .user-created {
            color: #777;
            font-size: 0.85rem;
            margin-top: 12px;
        }

        .user-actions {
            display: flex;
            gap: 10px;
            margin-top: 16px;
        }

        /* Keyframe animations */
        @keyframes fadeInRow {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes lineExpand {
            from {
                width: 0
            }

            to {
                width: 80px
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(13, 202, 240, 0.4);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(13, 202, 240, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(13, 202, 240, 0);
            }
        }

        /* Modal animation override */
        .modal.fade .modal-dialog {
            transform: translate(0, -50px) scale(0.9);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .modal.show .modal-dialog {
            transform: translate(0, 0) scale(1);
        }

        /* Hide table on tablets and small screens */
        @media (max-width: 991.98px) {
            .table-responsive {
                display: none;
            }

            /* Card view for mobile and tablets */
            .user-cards {
                display: grid;
                grid-template-columns: 1fr;
                gap: 16px;
                margin-top: 20px;
            }

            .main-content {
                margin-left: 0 !important;
                padding: 15px !important;
            }
        }

        /* Show table on larger screens */
        @media (min-width: 992px) {
            .user-cards {
                display: none;
            }

            .main-content {
                margin-left: 260px;
                padding: 30px;
            }
        }

        /* Adjustments for filter dropdown */
        @media (max-width: 991.98px) {
            .mb-3 {
                max-width: 100% !important;
            }
        }

        /* Button adjustments for mobile and tablets */
        @media (max-width: 991.98px) {
            .btn {
                padding: 8px 12px;
                font-size: 0.9rem;
            }

            .btn-sm {
                padding: 6px 10px;
                font-size: 0.85rem;
            }
        }

        /* Modal adjustments for mobile */
        @media (max-width: 767.98px) {
            .modal-dialog {
                margin: 1rem;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <?php require_once '../include/Header.php'; ?>
        <!-- Main Content -->
        <div class="main-content" style="margin-left:260px;padding:30px;">
            <h2>Manage Users</h2>
            <!-- Filter Dropdown -->
            <form method="get" class="mb-3" style="max-width:350px;">
                <label for="filter" class="form-label">Show:</label>
                <select name="filter" id="filter" class="form-select" onchange="this.form.submit()">
                    <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All Users</option>
                    <option value="active" <?= $filter === 'active' ? 'selected' : '' ?>>Active Users</option>
                    <option value="inactive" <?= $filter === 'inactive' ? 'selected' : '' ?>>Inactive Users</option>
                    <option value="admin" <?= $filter === 'admin' ? 'selected' : '' ?>>Admins</option>
                </select>
            </form>
            <?php if (!empty($delete_msg)): ?>
                <div class="alert alert-info"><?= htmlspecialchars($delete_msg) ?></div>
            <?php endif; ?>
            <?php if (!empty($activate_msg)): ?>
                <div class="alert alert-info"><?= htmlspecialchars($activate_msg) ?></div>
            <?php endif; ?>
            <?php if (!empty($edit_msg)): ?>
                <div class="alert alert-info"><?= htmlspecialchars($edit_msg) ?></div>
            <?php endif; ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>User ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= isset($user['USER_ID']) ? htmlspecialchars($user['USER_ID']) : '' ?></td>
                                <td><?= isset($user['NAME']) ? htmlspecialchars($user['NAME']) : '' ?></td>
                                <td><?= isset($user['EMAIL']) ? htmlspecialchars($user['EMAIL']) : '' ?></td>
                                <td>
                                    <?= isset($user['ROLE']) ? htmlspecialchars($user['ROLE']) : '' ?>
                                    <?php if ($user['ROLE'] === 'admin'): ?>
                                        <span class="badge bg-warning text-dark ms-2">Admin</span>
                                    <?php else: ?>
                                        <span class="badge bg-info text-dark ms-2">User</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (strtolower($user['STATUS']) === 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= isset($user['CREATED_AT']) ? htmlspecialchars(date('Y-m-d', strtotime($user['CREATED_AT']))) : '' ?></td>
                                <td>
                                    <?php
                                    // Only show actions if session user is set and is admin
                                    if (isset($_SESSION['admin_id']) && $_SESSION['admin_role'] === 'admin' && $user['USER_ID'] != $_SESSION['admin_id']): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="toggle_user_id" value="<?= $user['USER_ID'] ?>">
                                            <?php if (strtolower($user['STATUS']) === 'active'): ?>
                                                <input type="hidden" name="toggle_action" value="deactivate">
                                                <button type="submit" class="btn btn-warning btn-sm" title="Deactivate User">
                                                    <i class="fas fa-user-slash"></i> Deactivate
                                                </button>
                                            <?php else: ?>
                                                <input type="hidden" name="toggle_action" value="activate">
                                                <button type="submit" class="btn btn-success btn-sm" title="Activate User">
                                                    <i class="fas fa-user-check"></i> Activate
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                        <button
                                            type="button"
                                            class="btn btn-edit btn-edit-user"
                                            data-user-id="<?= htmlspecialchars($user['USER_ID']) ?>"
                                            data-name="<?= htmlspecialchars($user['NAME']) ?>"
                                            data-email="<?= htmlspecialchars($user['EMAIL']) ?>"
                                            data-role="<?= htmlspecialchars($user['ROLE']) ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editUserModal">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                            <input type="hidden" name="delete_user_id" value="<?= htmlspecialchars($user['USER_ID']) ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    <?php elseif (!isset($_SESSION['admin_id'])): ?>
                                        <span class="text-muted">Session admin not set</span>
                                    <?php else: ?>
                                        <span class="text-muted">Current Admin</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No users found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Responsive User Cards for Mobile -->
            <div class="user-cards">
                <?php foreach ($users as $user): ?>
                    <div class="user-card">
                        <div class="user-header">
                            <span class="user-id">#<?= htmlspecialchars($user['USER_ID']) ?></span>
                            <span class="user-role<?= ($user['ROLE'] === 'admin') ? ' admin' : '' ?>">
                                <?= htmlspecialchars(ucfirst($user['ROLE'])) ?>
                            </span>
                        </div>
                        <div class="user-name"><?= htmlspecialchars($user['NAME']) ?></div>
                        <div class="user-email"><?= htmlspecialchars($user['EMAIL']) ?></div>
                        <div class="user-status <?= strtolower($user['STATUS']) ?>">
                            <span class="badge-dot <?= (strtolower($user['STATUS']) === 'active') ? 'bg-success' : 'bg-secondary' ?>"></span>
                            <?= htmlspecialchars(ucfirst($user['STATUS'])) ?>
                        </div>
                        <div class="user-created">
                            Created: <?= htmlspecialchars(date('Y-m-d', strtotime($user['CREATED_AT']))) ?>
                        </div>
                        <div class="user-actions">
                            <?php if ($user['USER_ID'] != $_SESSION['user_id']): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="toggle_user_id" value="<?= $user['USER_ID'] ?>">
                                    <?php if (strtolower($user['STATUS']) === 'active'): ?>
                                        <input type="hidden" name="toggle_action" value="deactivate">
                                        <button type="submit" class="btn btn-warning btn-sm" title="Deactivate User">
                                            <i class="fas fa-user-slash"></i> Deactivate
                                        </button>
                                    <?php else: ?>
                                        <input type="hidden" name="toggle_action" value="activate">
                                        <button type="submit" class="btn btn-success btn-sm" title="Activate User">
                                            <i class="fas fa-user-check"></i> Activate
                                        </button>
                                    <?php endif; ?>
                                </form>
                                <button
                                    type="button"
                                    class="btn btn-edit btn-edit-user"
                                    data-user-id="<?= htmlspecialchars($user['USER_ID']) ?>"
                                    data-name="<?= htmlspecialchars($user['NAME']) ?>"
                                    data-email="<?= htmlspecialchars($user['EMAIL']) ?>"
                                    data-role="<?= htmlspecialchars($user['ROLE']) ?>"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editUserModal">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                    <input type="hidden" name="delete_user_id" value="<?= htmlspecialchars($user['USER_ID']) ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted">Current Admin</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                    <div class="user-card text-center">
                        No users found.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- End Responsive User Cards -->
        <!-- Edit User Modal -->
        <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="editUserForm" method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="edit_user_id" id="editUserId">
                            <div class="mb-3">
                                <label class="form-label">User ID</label>
                                <input type="text" id="editUserIdDisplay" class="form-control" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="edit_name" id="editUserName" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="edit_email" id="editUserEmail" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <select name="edit_role" id="editUserRole" class="form-control" required>
                                    <option value="user">User</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- End Edit User Modal -->
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fill modal with user data on Edit click
        document.querySelectorAll('.btn-edit-user').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.getElementById('editUserId').value = this.getAttribute('data-user-id');
                document.getElementById('editUserIdDisplay').value = this.getAttribute('data-user-id');
                document.getElementById('editUserName').value = this.getAttribute('data-name');
                document.getElementById('editUserEmail').value = this.getAttribute('data-email');
                document.getElementById('editUserRole').value = this.getAttribute('data-role');
                // No need to set form action, submit to same page
            });
        });
    </script>
    <script src="../../assets/Js/app.js"></script>
</body>

</html>