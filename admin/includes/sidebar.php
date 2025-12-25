<?php
/**
 * Admin Sidebar Component
 * Reusable navigation sidebar for all admin pages
 */
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<div class="admin-sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <i class="fas fa-bicycle"></i>
            <span>UniCycle</span>
        </div>
        <span class="admin-badge">Admin</span>
    </div>

    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-item <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-gauge-high"></i>
            <span>Dashboard</span>
        </a>
        <a href="bikes.php" class="nav-item <?= $currentPage === 'bikes.php' ? 'active' : '' ?>">
            <i class="fas fa-bicycle"></i>
            <span>Bike Management</span>
        </a>
        <a href="users.php" class="nav-item <?= $currentPage === 'users.php' ? 'active' : '' ?>">
            <i class="fas fa-users"></i>
            <span>User Management</span>
        </a>
        <a href="rentals.php" class="nav-item <?= $currentPage === 'rentals.php' ? 'active' : '' ?>">
            <i class="fas fa-clock-rotate-left"></i>
            <span>All Rentals</span>
        </a>
        <a href="complaints.php" class="nav-item <?= $currentPage === 'complaints.php' ? 'active' : '' ?>">
            <i class="fas fa-comment-dots"></i>
            <span>Complaints</span>
        </a>
        <a href="revenue.php" class="nav-item <?= $currentPage === 'revenue.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-line"></i>
            <span>Revenue</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="admin-info">
            <i class="fas fa-user-shield"></i>
            <div>
                <span class="admin-name"><?= htmlspecialchars($_SESSION['student_name'] ?? 'Admin') ?></span>
                <span class="admin-role"><?= ucfirst($_SESSION['admin_role'] ?? 'admin') ?></span>
            </div>
        </div>
        <a href="logout.php" class="nav-item logout">
            <i class="fas fa-right-from-bracket"></i>
            <span>Sign Out</span>
        </a>
    </div>
</div>