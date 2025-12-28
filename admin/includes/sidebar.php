<?php
/**
 * Admin Sidebar Component
 * Reusable navigation sidebar for all admin pages
 */
$currentPage = basename($_SERVER['PHP_SELF']);
$adminName = $_SESSION['student_name'] ?? 'Admin';
$adminRole = ucfirst($_SESSION['admin_role'] ?? 'admin');
$initials = strtoupper(substr($adminName, 0, 1));
if (strpos($adminName, ' ') !== false) {
    $initials .= strtoupper(substr(strstr($adminName, ' '), 1, 1));
}
?>

<!-- Mobile Header (visible only on mobile) -->
<header class="mobile-header">
    <div class="mobile-header-left">
        <a href="dashboard.php" class="mobile-logo">
            <div class="mobile-logo-icon">U</div>
            <span class="mobile-logo-text">UniCycle</span>
        </a>
        <span class="admin-badge-mobile">Admin</span>
    </div>
    <button class="mobile-menu-btn" onclick="openMobileMenu()"><i class="fas fa-bars"></i></button>
</header>

<!-- Mobile Slide Menu -->
<div class="mobile-menu-overlay" id="mobileMenu" onclick="closeMobileMenu(event)">
    <div class="mobile-menu-panel" onclick="event.stopPropagation()">
        <div class="mobile-menu-header">
            <button class="mobile-menu-close" onclick="closeMobileMenu()"><i class="fas fa-times"></i></button>
            <div class="mobile-menu-user">
                <div class="mobile-menu-avatar">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div>
                    <div class="mobile-menu-name"><?= htmlspecialchars($adminName) ?></div>
                    <div class="mobile-menu-role"><?= $adminRole ?></div>
                </div>
            </div>
        </div>
        <nav class="mobile-menu-nav">
            <a href="dashboard.php" class="mobile-menu-item <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                <i class="fas fa-gauge-high"></i> Dashboard
            </a>
            <a href="bikes.php" class="mobile-menu-item <?= $currentPage === 'bikes.php' ? 'active' : '' ?>">
                <i class="fas fa-bicycle"></i> Bike Management
            </a>
            <a href="users.php" class="mobile-menu-item <?= $currentPage === 'users.php' ? 'active' : '' ?>">
                <i class="fas fa-users"></i> User Management
            </a>
            <a href="rentals.php" class="mobile-menu-item <?= $currentPage === 'rentals.php' ? 'active' : '' ?>">
                <i class="fas fa-clock-rotate-left"></i> All Rentals
            </a>
            <a href="complaints.php" class="mobile-menu-item <?= $currentPage === 'complaints.php' ? 'active' : '' ?>">
                <i class="fas fa-comment-dots"></i> Complaints
            </a>
            <a href="revenue.php" class="mobile-menu-item <?= $currentPage === 'revenue.php' ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i> Revenue
            </a>
        </nav>
        <div class="mobile-menu-footer">
            <a href="logout.php" class="mobile-logout-btn"><i class="fas fa-right-from-bracket"></i> Sign Out</a>
        </div>
    </div>
</div>

<!-- Desktop Sidebar -->
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
                <span class="admin-name"><?= htmlspecialchars($adminName) ?></span>
                <span class="admin-role"><?= $adminRole ?></span>
            </div>
        </div>
        <a href="logout.php" class="nav-item logout">
            <i class="fas fa-right-from-bracket"></i>
            <span>Sign Out</span>
        </a>
    </div>
</div>

<script>
    function openMobileMenu() {
        document.getElementById('mobileMenu').classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    function closeMobileMenu(event) {
        if (event && event.target !== event.currentTarget) return;
        document.getElementById('mobileMenu').classList.remove('active');
        document.body.style.overflow = '';
    }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeMobileMenu();
    });
</script>