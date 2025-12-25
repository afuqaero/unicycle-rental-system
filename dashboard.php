<?php
session_start();
require_once "config.php";

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['student_name'] ?? 'User';

// Get user's role and profile pic from database
$stmt = $pdo->prepare("SELECT role, profile_pic FROM students WHERE student_id = ?");
$stmt->execute([$student_id]);
$user = $stmt->fetch();
$user_role = $user['role'] ?? 'student';
$profile_pic = $user['profile_pic'] ?? null;

// Counts
$availableBikes = (int) $pdo->query("SELECT COUNT(*) FROM bikes WHERE status='available'")->fetchColumn();
$totalBikes = (int) $pdo->query("SELECT COUNT(*) FROM bikes")->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE student_id=?");
$stmt->execute([$student_id]);
$totalRentals = (int) $stmt->fetchColumn();

// Get bike type usage for pie chart
try {
    $stmt = $pdo->prepare("
      SELECT b.bike_type, COUNT(*) as count
      FROM rentals r
      JOIN bikes b ON b.bike_id = r.bike_id
      WHERE r.student_id = ?
      GROUP BY b.bike_type
    ");
    $stmt->execute([$student_id]);
    $bikeTypeUsage = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $bikeTypeUsage = [];
}

// Calculate percentages for pie chart
$totalTypeCount = array_sum(array_column($bikeTypeUsage, 'count'));
$mountainCount = 0;
$cityCount = 0;
$otherCount = 0;

foreach ($bikeTypeUsage as $type) {
    switch ($type['bike_type']) {
        case 'mountain':
            $mountainCount = $type['count'];
            break;
        case 'city':
            $cityCount = $type['count'];
            break;
        default:
            $otherCount += $type['count'];
    }
}

$mountainPct = $totalTypeCount > 0 ? round(($mountainCount / $totalTypeCount) * 100) : 0;
$cityPct = $totalTypeCount > 0 ? round(($cityCount / $totalTypeCount) * 100) : 0;
$otherPct = $totalTypeCount > 0 ? round(($otherCount / $totalTypeCount) * 100) : 0;

// Latest active rental (if any)
$stmt = $pdo->prepare("
  SELECT r.rental_id, r.start_time, r.expected_return_time, r.planned_hours, b.bike_name
  FROM rentals r
  JOIN bikes b ON b.bike_id = r.bike_id
  WHERE r.student_id=? AND r.status='active'
  ORDER BY r.start_time DESC
  LIMIT 1
");
$stmt->execute([$student_id]);
$active = $stmt->fetch();

// Sync session with database - if there's an active rental in DB but not in session, set it
// This fixes the issue where session was lost but rental exists
if ($active && empty($_SESSION['active_rental_id'])) {
    $_SESSION['active_rental_id'] = $active['rental_id'];
}

// Get recent activity (combined rentals + penalties)
try {
    // Get rentals
    $stmt = $pdo->prepare("
      SELECT 
        'rental' as activity_type,
        r.rental_id,
        r.start_time as activity_date,
        r.status,
        b.bike_name,
        b.bike_type,
        COALESCE(pay.amount, 0) as amount,
        0 as penalty_amount
      FROM rentals r
      JOIN bikes b ON b.bike_id = r.bike_id
      LEFT JOIN payments pay ON pay.rental_id = r.rental_id
      WHERE r.student_id=?
      
      UNION ALL
      
      SELECT 
        'penalty' as activity_type,
        r.rental_id,
        pen.created_at as activity_date,
        pen.status,
        b.bike_name,
        b.bike_type,
        0 as amount,
        pen.amount as penalty_amount
      FROM penalties pen
      JOIN rentals r ON r.rental_id = pen.rental_id
      JOIN bikes b ON b.bike_id = r.bike_id
      WHERE r.student_id=?
      
      ORDER BY activity_date DESC
      LIMIT 5
    ");
    $stmt->execute([$student_id, $student_id]);
    $recentActivity = $stmt->fetchAll();
} catch (Exception $e) {
    $recentActivity = [];
}

// Get user initials for avatar
$nameParts = explode(' ', $student_name);
$initials = strtoupper(substr($nameParts[0], 0, 1));
if (isset($nameParts[1])) {
    $initials .= strtoupper(substr($nameParts[1], 0, 1));
}

// Get current date info
$currentDate = date('l, F j, Y');
$greeting = (date('H') < 12) ? 'Good Morning' : ((date('H') < 17) ? 'Good Afternoon' : 'Good Evening');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Dashboard - UniCycle</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="dashboard.css?v=8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <!-- Logo -->
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-logo">
                <div class="logo-icon">U</div>
                <span class="logo-text">UniCycle</span>
            </a>
        </div>

        <!-- User Profile - Top of Sidebar -->
        <div class="user-section">
            <div class="user-avatar">
                <?php if ($profile_pic && file_exists('assets/uploads/' . $profile_pic)): ?>
                    <img src="assets/uploads/<?= htmlspecialchars($profile_pic) ?>" alt="Profile"
                        style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                <?php else: ?>
                    <?= htmlspecialchars($initials) ?>
                <?php endif; ?>
            </div>
            <div class="user-welcome">
                <span class="welcome-label">Welcome back,</span>
                <span class="user-name"><?= htmlspecialchars($student_name) ?></span>
            </div>
            <div class="user-stats">
                <div class="user-stat">
                    <span class="stat-number"><?= $totalRentals ?></span>
                    <span class="stat-text">Total Rides</span>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item active">
                <span class="nav-icon"><i class="fas fa-gauge-high"></i></span>
                <span>Dashboard</span>
            </a>
            <a href="available-bikes.php" class="nav-item">
                <span class="nav-icon"><i class="fas fa-bicycle"></i></span>
                <span>Available Bikes</span>
            </a>
            <a href="rental-summary.php" class="nav-item">
                <span class="nav-icon"><i class="fas fa-clock-rotate-left"></i></span>
                <span>Rental Summary</span>
            </a>
            <a href="complaints.php" class="nav-item">
                <span class="nav-icon"><i class="fas fa-comment-dots"></i></span>
                <span>Complaints</span>
            </a>
            <a href="settings.php" class="nav-item">
                <span class="nav-icon"><i class="fas fa-cog"></i></span>
                <span>Settings</span>
            </a>
        </nav>

        <!-- Sign Out -->
        <div class="sidebar-footer">
            <button class="logout-btn" onclick="showLogoutModal()">
                <span>Sign out</span>
            </button>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Colorful Header Banner -->
        <div class="header-banner">
            <div class="banner-pattern"></div>
            <div class="banner-content">
                <h1>Dashboard</h1>
                <p class="banner-date"><?= $currentDate ?></p>
            </div>
        </div>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <!-- Left Column -->
            <div class="content-left">
                <?php if ($active): ?>
                    <!-- Active Rental Card - Most Important -->
                    <div class="current-rental-card">
                        <div class="rental-badge">
                            <span class="pulse"></span>
                            Active Rental
                        </div>
                        <h4><?= htmlspecialchars($active['bike_name']) ?></h4>
                        <p class="rental-time">Due: <?= date('g:i A', strtotime($active['expected_return_time'])) ?></p>
                        <a href="active-rental.php" class="rental-link">View Details →</a>
                    </div>
                <?php endif; ?>

                <!-- Available Bikes Card -->
                <div class="featured-card">
                    <div class="featured-header">
                        <span class="featured-label">Available Bikes</span>
                        <span class="featured-badge"><?= $availableBikes ?> / <?= $totalBikes ?></span>
                    </div>
                    <div class="featured-value"><?= $availableBikes ?></div>
                    <div class="featured-subtitle">Ready to rent</div>

                    <a href="available-bikes.php" class="rent-now-btn">
                        <span><i class="fas fa-bicycle"></i></span> Rent a Bike
                    </a>
                </div>

                <!-- Stats Row -->
                <div class="stats-row">
                    <div class="mini-stat-card">
                        <div class="mini-stat-icon blue"><i class="fas fa-person-biking"></i></div>
                        <div class="mini-stat-info">
                            <span class="mini-stat-label">Your Rides</span>
                            <span class="mini-stat-value"><?= $totalRentals ?></span>
                        </div>
                    </div>
                    <div class="mini-stat-card">
                        <div class="mini-stat-icon green"><i class="fas fa-check"></i></div>
                        <div class="mini-stat-info">
                            <span class="mini-stat-label">Available</span>
                            <span class="mini-stat-value"><?= $availableBikes ?></span>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="activity-card">
                    <div class="card-header">
                        <h3>Recent Activity</h3>
                        <a href="rental-summary.php" class="view-all">View all</a>
                    </div>
                    <div class="activity-list">
                        <?php if (count($recentActivity) > 0): ?>
                            <?php foreach (array_slice($recentActivity, 0, 4) as $activity): ?>
                                <div class="activity-row">
                                    <div class="activity-icon-wrap"
                                        style="<?= $activity['activity_type'] === 'penalty' ? 'background: #fee2e2;' : '' ?>">
                                        <?php if ($activity['activity_type'] === 'penalty'): ?>
                                            <span><i class="fas fa-exclamation-triangle" style="color: #dc2626;"></i></span>
                                        <?php else: ?>
                                            <span><i
                                                    class="fas <?= $activity['bike_type'] === 'mountain' ? 'fa-mountain' : 'fa-bicycle' ?>"></i></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="activity-details">
                                        <?php if ($activity['activity_type'] === 'penalty'): ?>
                                            <span class="activity-name" style="color: #dc2626;">Penalty -
                                                <?= htmlspecialchars($activity['bike_name']) ?></span>
                                        <?php else: ?>
                                            <span class="activity-name"><?= htmlspecialchars($activity['bike_name']) ?></span>
                                        <?php endif; ?>
                                        <span
                                            class="activity-date"><?= date('M j, Y \a\t g:i A', strtotime($activity['activity_date'])) ?></span>
                                    </div>
                                    <div class="activity-amount">
                                        <?php if ($activity['activity_type'] === 'penalty'): ?>
                                            <span style="color: #dc2626; font-weight: 600;">-RM
                                                <?= number_format($activity['penalty_amount'], 2) ?></span>
                                        <?php elseif ($activity['amount'] > 0): ?>
                                            RM <?= number_format($activity['amount'], 2) ?>
                                        <?php else: ?>
                                            <span
                                                class="status-badge <?= $activity['status'] ?>"><?= ucfirst($activity['status']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-activity">
                                <span class="empty-icon"><i class="fas fa-inbox"></i></span>
                                <p>No activity yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="content-right">
                <!-- Bike Usage Chart -->
                <div class="chart-card">
                    <div class="card-header">
                        <h3>Bike Preferences</h3>
                        <span class="chart-period">All time</span>
                    </div>
                    <?php if ($totalTypeCount > 0): ?>
                        <div class="chart-container">
                            <div class="pie-chart" id="bikeTypeChart">
                                <div class="chart-center">
                                    <span class="chart-total"><?= $totalTypeCount ?></span>
                                    <span class="chart-label">Rides</span>
                                </div>
                            </div>
                            <div class="chart-legend">
                                <?php if ($mountainCount > 0): ?>
                                    <div class="legend-item">
                                        <span class="legend-color mountain"></span>
                                        <span class="legend-text">Mountain</span>
                                        <span class="legend-value"><?= $mountainPct ?>%</span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($cityCount > 0): ?>
                                    <div class="legend-item">
                                        <span class="legend-color city"></span>
                                        <span class="legend-text">City</span>
                                        <span class="legend-value"><?= $cityPct ?>%</span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($otherCount > 0): ?>
                                    <div class="legend-item">
                                        <span class="legend-color other"></span>
                                        <span class="legend-text">Other</span>
                                        <span class="legend-value"><?= $otherPct ?>%</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="chart-empty">
                            <div class="chart-empty-icon"><i class="fas fa-chart-pie"></i></div>
                            <p>No data yet</p>
                            <span>Start renting to see your preferences</span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions -->
                <div class="actions-card">
                    <h3>Quick Actions</h3>
                    <div class="quick-actions-list">
                        <a href="available-bikes.php" class="action-card primary">
                            <div class="action-icon-wrap"><i class="fas fa-bicycle"></i></div>
                            <div class="action-text">
                                <span class="action-title">Browse Bikes</span>
                                <span class="action-desc">Find and rent available bikes</span>
                            </div>
                        </a>
                        <a href="rental-summary.php" class="action-card secondary">
                            <div class="action-icon-wrap"><i class="fas fa-clock-rotate-left"></i></div>
                            <div class="action-text">
                                <span class="action-title">Rental History</span>
                                <span class="action-desc">View your past rentals</span>
                            </div>
                        </a>
                    </div>
                </div>


            </div>
        </div>
    </main>

    <!-- Logout Confirmation Modal -->
    <div class="modal-overlay" id="logoutModal">
        <div class="modal-box">
            <div class="modal-icon"><i class="fas fa-exclamation-circle"></i></div>
            <h3>Confirm Logout</h3>
            <p>Are you sure you want to sign out?</p>
            <div class="modal-actions">
                <button class="modal-btn cancel" onclick="hideLogoutModal()">Cancel</button>
                <button class="modal-btn confirm" onclick="confirmLogout()">Sign out</button>
            </div>
        </div>
    </div>

    <script>
        // Draw Pie Chart with CSS
        function drawPieChart() {
            const mountainPct = <?= $mountainPct ?>;
            const cityPct = <?= $cityPct ?>;
            const otherPct = <?= $otherPct ?>;

            const chart = document.getElementById('bikeTypeChart');
            if (chart && (mountainPct + cityPct + otherPct) > 0) {
                const mountainDeg = (mountainPct / 100) * 360;
                const cityDeg = (cityPct / 100) * 360;
                const otherDeg = (otherPct / 100) * 360;

                let gradient = 'conic-gradient(';
                let currentDeg = 0;

                if (mountainPct > 0) {
                    gradient += `#10b981 ${currentDeg}deg ${currentDeg + mountainDeg}deg`;
                    currentDeg += mountainDeg;
                    if (cityPct > 0 || otherPct > 0) gradient += ', ';
                }
                if (cityPct > 0) {
                    gradient += `#f59e0b ${currentDeg}deg ${currentDeg + cityDeg}deg`;
                    currentDeg += cityDeg;
                    if (otherPct > 0) gradient += ', ';
                }
                if (otherPct > 0) {
                    gradient += `#8b5cf6 ${currentDeg}deg ${currentDeg + otherDeg}deg`;
                }
                gradient += ')';

                chart.style.background = gradient;
            }
        }

        // Animate numbers
        function animateNumber(el, target) {
            let current = 0;
            const step = target / 30;
            const timer = setInterval(() => {
                current += step;
                if (current >= target) {
                    el.textContent = target;
                    clearInterval(timer);
                } else {
                    el.textContent = Math.floor(current);
                }
            }, 20);
        }

        // Run on load
        document.addEventListener('DOMContentLoaded', function () {
            drawPieChart();

            // Animate featured value
            const featuredValue = document.querySelector('.featured-value');
            if (featuredValue) {
                const target = parseInt(featuredValue.textContent);
                featuredValue.textContent = '0';
                animateNumber(featuredValue, target);
            }
        });

        // Logout Modal
        function showLogoutModal() {
            document.getElementById('logoutModal').classList.add('active');
        }

        function hideLogoutModal() {
            document.getElementById('logoutModal').classList.remove('active');
        }

        function confirmLogout() {
            window.location.href = 'logout.php';
        }

        document.getElementById('logoutModal').addEventListener('click', function (e) {
            if (e.target === this) hideLogoutModal();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') hideLogoutModal();
        });
    </script>

</body>

</html>