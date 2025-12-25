<?php
/**
 * Admin Dashboard
 * Main overview page for administrators
 */
require_once 'includes/auth_check.php';

// Dashboard statistics
$available_bikes = $pdo->query("SELECT COUNT(*) FROM bikes WHERE status = 'available'")->fetchColumn();
$active_rentals = $pdo->query("SELECT COUNT(*) FROM rentals WHERE status = 'active'")->fetchColumn();
$total_rentals = $pdo->query("SELECT COUNT(*) FROM rentals")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM students WHERE role IN ('student', 'staff')")->fetchColumn();
$pending_complaints = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status = 'open'")->fetchColumn();
$total_revenue = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'paid'")->fetchColumn();

// Recent rentals
$recent_rentals = $pdo->query("
    SELECT r.*, s.name as student_name, b.bike_code 
    FROM rentals r 
    JOIN students s ON r.student_id = s.student_id 
    JOIN bikes b ON r.bike_id = b.bike_id 
    ORDER BY r.created_at DESC 
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - UniCycle</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-header">
            <h1>Dashboard</h1>
            <p>Welcome back, <?= htmlspecialchars($_SESSION['student_name'] ?? 'Admin') ?>! Here's your system overview.
            </p>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon"><i class="fas fa-bicycle"></i></div>
                <h3>Available Bikes</h3>
                <div class="value"><?= $available_bikes ?></div>
            </div>

            <div class="stat-card success">
                <div class="icon"><i class="fas fa-bolt"></i></div>
                <h3>Active Rentals</h3>
                <div class="value"><?= $active_rentals ?></div>
            </div>

            <div class="stat-card secondary">
                <div class="icon"><i class="fas fa-users"></i></div>
                <h3>Total Users</h3>
                <div class="value"><?= $total_users ?></div>
            </div>

            <div class="stat-card warning">
                <div class="icon"><i class="fas fa-comment-dots"></i></div>
                <h3>Open Complaints</h3>
                <div class="value"><?= $pending_complaints ?></div>
            </div>
        </div>

        <!-- Revenue Card -->
        <div class="stats-grid" style="margin-bottom: 30px;">
            <div class="stat-card" style="grid-column: span 2;">
                <div class="icon"><i class="fas fa-chart-line"></i></div>
                <h3>Total Revenue</h3>
                <div class="value">RM <?= number_format($total_revenue, 2) ?></div>
            </div>
            <div class="stat-card secondary" style="grid-column: span 2;">
                <div class="icon"><i class="fas fa-clock-rotate-left"></i></div>
                <h3>Total Rentals</h3>
                <div class="value"><?= $total_rentals ?></div>
            </div>
        </div>

        <!-- Recent Rentals -->
        <div class="content-card">
            <div class="card-header">
                <h2><i class="fas fa-clock" style="margin-right: 10px; color: var(--primary);"></i>Recent Rentals</h2>
                <a href="rentals.php" class="btn btn-primary btn-sm">View All</a>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (count($recent_rentals) > 0): ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Rental Code</th>
                                <th>User</th>
                                <th>Bike</th>
                                <th>Start Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_rentals as $rental): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($rental['rental_code']) ?></strong></td>
                                    <td><?= htmlspecialchars($rental['student_name']) ?></td>
                                    <td><?= htmlspecialchars($rental['bike_code']) ?></td>
                                    <td><?= date('d M Y, h:i A', strtotime($rental['start_time'])) ?></td>
                                    <td><span class="badge <?= $rental['status'] ?>"><?= ucfirst($rental['status']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No rentals yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <div class="action-card primary">
                <i class="fas fa-bicycle"></i>
                <h3>Manage Bikes</h3>
                <p>Add, edit, or remove bikes from the system</p>
                <a href="bikes.php">Go to Bikes →</a>
            </div>

            <div class="action-card secondary">
                <i class="fas fa-users"></i>
                <h3>Manage Users</h3>
                <p>View and manage registered users</p>
                <a href="users.php">Go to Users →</a>
            </div>
        </div>
    </main>
</body>

</html>