<?php
/**
 * Admin User Management
 * View and manage registered users
 */
require_once 'includes/auth_check.php';

$message = '';
$messageType = '';

// Handle Delete User
if (isset($_POST['delete_user'])) {
    $student_id = $_POST['student_id'];

    // Prevent self-deletion
    if ($student_id == $_SESSION['student_id']) {
        $message = 'You cannot delete your own account!';
        $messageType = 'error';
    } else {
        $stmt = $pdo->prepare("DELETE FROM students WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $message = 'User deleted successfully!';
        $messageType = 'success';
    }
}

// Handle Role Update (Super Admin only)
if (isset($_POST['update_role']) && ($_SESSION['admin_role'] ?? '') === 'super_admin') {
    $student_id = $_POST['student_id'];
    $new_role = $_POST['role'];

    // Prevent self-demotion
    if ($student_id == $_SESSION['student_id']) {
        $message = 'You cannot change your own role!';
        $messageType = 'error';
    } else {
        $stmt = $pdo->prepare("UPDATE students SET role = ? WHERE student_id = ?");
        $stmt->execute([$new_role, $student_id]);
        $message = 'User role updated successfully!';
        $messageType = 'success';
    }
}

// Filter
$filter = $_GET['filter'] ?? 'all';
$where = '';
switch ($filter) {
    case 'student':
        $where = "WHERE role = 'student'";
        break;
    case 'staff':
        $where = "WHERE role = 'staff'";
        break;
    case 'admin':
        $where = "WHERE role IN ('admin', 'super_admin')";
        break;
}

$users = $pdo->query("SELECT * FROM students $where ORDER BY created_at DESC")->fetchAll();

// Counts
$counts = [
    'all' => $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn(),
    'student' => $pdo->query("SELECT COUNT(*) FROM students WHERE role = 'student'")->fetchColumn(),
    'staff' => $pdo->query("SELECT COUNT(*) FROM students WHERE role = 'staff'")->fetchColumn(),
    'admin' => $pdo->query("SELECT COUNT(*) FROM students WHERE role IN ('admin', 'super_admin')")->fetchColumn(),
];

$isSuperAdmin = ($_SESSION['admin_role'] ?? '') === 'super_admin';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - UniCycle Admin</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .message {
            padding: 14px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .message.success {
            background: rgba(34, 197, 94, 0.1);
            color: #16a34a;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .message.error {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-name {
            font-weight: 600;
            color: var(--gray-800);
        }

        .user-email {
            font-size: 0.85rem;
            color: var(--gray-500);
        }

        .action-cell {
            display: flex;
            gap: 8px;
            align-items: center;
        }
    </style>
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-header">
            <h1>User Management</h1>
            <p>View and manage all registered users</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon"><i class="fas fa-users"></i></div>
                <h3>Total Users</h3>
                <div class="value"><?= $counts['all'] ?></div>
            </div>
            <div class="stat-card success">
                <div class="icon"><i class="fas fa-graduation-cap"></i></div>
                <h3>Students</h3>
                <div class="value"><?= $counts['student'] ?></div>
            </div>
            <div class="stat-card secondary">
                <div class="icon"><i class="fas fa-briefcase"></i></div>
                <h3>Staff</h3>
                <div class="value"><?= $counts['staff'] ?></div>
            </div>
            <div class="stat-card warning">
                <div class="icon"><i class="fas fa-user-shield"></i></div>
                <h3>Admins</h3>
                <div class="value"><?= $counts['admin'] ?></div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-bar">
            <a href="?filter=all" class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">All</a>
            <a href="?filter=student" class="filter-btn <?= $filter === 'student' ? 'active' : '' ?>">Students</a>
            <a href="?filter=staff" class="filter-btn <?= $filter === 'staff' ? 'active' : '' ?>">Staff</a>
            <a href="?filter=admin" class="filter-btn <?= $filter === 'admin' ? 'active' : '' ?>">Admins</a>
        </div>

        <!-- Users Table -->
        <div class="content-card">
            <div class="card-body" style="padding: 0;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>ID</th>
                            <th>Role</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
                                        <div>
                                            <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
                                            <div class="user-email"><?= htmlspecialchars($user['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($user['student_staff_id'] ?? '-') ?></td>
                                <td>
                                    <span
                                        class="badge <?= in_array($user['role'], ['admin', 'super_admin']) ? 'active' : 'available' ?>">
                                        <?= ucfirst(str_replace('_', ' ', $user['role'])) ?>
                                    </span>
                                </td>
                                <td><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                                <td>
                                    <form method="POST" class="action-cell">
                                        <input type="hidden" name="student_id" value="<?= $user['student_id'] ?>">

                                        <?php if ($isSuperAdmin && $user['student_id'] != $_SESSION['student_id']): ?>
                                            <select name="role" class="form-control"
                                                style="padding: 6px 10px; font-size: 0.85rem;">
                                                <option value="student" <?= $user['role'] === 'student' ? 'selected' : '' ?>>
                                                    Student</option>
                                                <option value="staff" <?= $user['role'] === 'staff' ? 'selected' : '' ?>>Staff
                                                </option>
                                                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin
                                                </option>
                                                <option value="super_admin" <?= $user['role'] === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                                            </select>
                                            <button type="submit" name="update_role"
                                                class="btn btn-primary btn-sm">Update</button>
                                        <?php endif; ?>

                                        <?php if ($user['student_id'] != $_SESSION['student_id']): ?>
                                            <button type="submit" name="delete_user" class="btn btn-danger btn-sm"
                                                onclick="return confirm('Delete this user? This will also delete their rentals and complaints.')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <span class="badge"
                                                style="background: var(--gray-100); color: var(--gray-500);">You</span>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if (count($users) === 0): ?>
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <p>No users found</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>

</html>