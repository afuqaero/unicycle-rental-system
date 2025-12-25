<?php
/**
 * Admin Rentals Management
 * View all system rentals
 */
require_once 'includes/auth_check.php';

// Filter
$filter = $_GET['filter'] ?? 'all';
$where = '';
switch ($filter) {
    case 'active':
        $where = "WHERE r.status = 'active'";
        break;
    case 'completed':
        $where = "WHERE r.status = 'completed'";
        break;
    case 'late':
        $where = "WHERE r.status = 'late'";
        break;
}

$rentals = $pdo->query("
    SELECT r.*, s.name as student_name, s.email, s.student_staff_id, b.bike_code, b.bike_name
    FROM rentals r 
    JOIN students s ON r.student_id = s.student_id 
    JOIN bikes b ON r.bike_id = b.bike_id 
    $where
    ORDER BY r.created_at DESC
")->fetchAll();

// Stats
$stats = [
    'all' => $pdo->query("SELECT COUNT(*) FROM rentals")->fetchColumn(),
    'active' => $pdo->query("SELECT COUNT(*) FROM rentals WHERE status = 'active'")->fetchColumn(),
    'completed' => $pdo->query("SELECT COUNT(*) FROM rentals WHERE status = 'completed'")->fetchColumn(),
    'late' => $pdo->query("SELECT COUNT(*) FROM rentals WHERE status = 'late'")->fetchColumn(),
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Rentals - UniCycle Admin</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-header">
            <h1>All Rentals</h1>
            <p>View and track all rental activities system-wide</p>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon"><i class="fas fa-clock-rotate-left"></i></div>
                <h3>Total Rentals</h3>
                <div class="value"><?= $stats['all'] ?></div>
            </div>
            <div class="stat-card secondary">
                <div class="icon"><i class="fas fa-bolt"></i></div>
                <h3>Active</h3>
                <div class="value"><?= $stats['active'] ?></div>
            </div>
            <div class="stat-card success">
                <div class="icon"><i class="fas fa-check-circle"></i></div>
                <h3>Completed</h3>
                <div class="value"><?= $stats['completed'] ?></div>
            </div>
            <div class="stat-card danger">
                <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
                <h3>Late Returns</h3>
                <div class="value"><?= $stats['late'] ?></div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-bar">
            <a href="?filter=all" class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">All
                (<?= $stats['all'] ?>)</a>
            <a href="?filter=active" class="filter-btn <?= $filter === 'active' ? 'active' : '' ?>">Active
                (<?= $stats['active'] ?>)</a>
            <a href="?filter=completed" class="filter-btn <?= $filter === 'completed' ? 'active' : '' ?>">Completed
                (<?= $stats['completed'] ?>)</a>
            <a href="?filter=late" class="filter-btn <?= $filter === 'late' ? 'active' : '' ?>">Late
                (<?= $stats['late'] ?>)</a>
        </div>

        <!-- Rentals Table -->
        <div class="content-card">
            <div class="card-body" style="padding: 0;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Rental Code</th>
                            <th>User</th>
                            <th>Bike</th>
                            <th>Start Time</th>
                            <th>Expected Return</th>
                            <th>Actual Return</th>
                            <th>Hours</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rentals as $rental): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($rental['rental_code']) ?></strong></td>
                                <td>
                                    <div>
                                        <div style="font-weight: 500;"><?= htmlspecialchars($rental['student_name']) ?>
                                        </div>
                                        <div style="font-size: 0.8rem; color: var(--gray-500);">
                                            <?= htmlspecialchars($rental['student_staff_id'] ?? $rental['email']) ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div style="font-weight: 500;"><?= htmlspecialchars($rental['bike_code']) ?></div>
                                        <div style="font-size: 0.8rem; color: var(--gray-500);">
                                            <?= htmlspecialchars($rental['bike_name']) ?></div>
                                    </div>
                                </td>
                                <td><?= date('d M Y, h:i A', strtotime($rental['start_time'])) ?></td>
                                <td><?= date('d M Y, h:i A', strtotime($rental['expected_return_time'])) ?></td>
                                <td>
                                    <?php if ($rental['return_time']): ?>
                                        <?= date('d M Y, h:i A', strtotime($rental['return_time'])) ?>
                                    <?php else: ?>
                                        <span style="color: var(--gray-400);">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $rental['planned_hours'] ?>h</td>
                                <td><span class="badge <?= $rental['status'] ?>"><?= ucfirst($rental['status']) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if (count($rentals) === 0): ?>
                    <div class="empty-state">
                        <i class="fas fa-clock-rotate-left"></i>
                        <p>No rentals found</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>

</html>