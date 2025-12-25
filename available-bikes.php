<?php
session_start();
require_once "config.php";
require_once "guard_active_rental.php";

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['student_name'] ?? 'User';

// Get user initials for avatar
$nameParts = explode(' ', $student_name);
$initials = strtoupper(substr($nameParts[0], 0, 1));
if (isset($nameParts[1])) {
    $initials .= strtoupper(substr($nameParts[1], 0, 1));
}

// Get total rides count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE student_id=?");
$stmt->execute([$student_id]);
$totalRentals = (int) $stmt->fetchColumn();

// Get profile picture
$stmt = $pdo->prepare("SELECT profile_pic FROM students WHERE student_id=?");
$stmt->execute([$student_id]);
$profile_pic = $stmt->fetchColumn() ?: null;

// unpaid penalties block renting
$stmt = $pdo->prepare("
  SELECT COUNT(*)
  FROM penalties p
  JOIN rentals r ON r.rental_id = p.rental_id
  WHERE r.student_id = ? AND p.status = 'unpaid'
");
$stmt->execute([$student_id]);
$hasUnpaidPenalty = ((int) $stmt->fetchColumn() > 0);

// Count bikes by status
$bikeCounts = $pdo->query("
    SELECT status, COUNT(*) as count 
    FROM bikes 
    GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

$availableCount = $bikeCounts['available'] ?? 0;
$rentedCount = $bikeCounts['rented'] ?? 0;
$maintenanceCount = $bikeCounts['maintenance'] ?? 0;
$pendingCount = $bikeCounts['pending'] ?? 0;
$totalBikes = array_sum($bikeCounts);

// fetch bikes
$sql = "
SELECT 
  bike_id,
  bike_name,
  bike_type,
  status,
  last_maintained_date,
  location
FROM bikes
ORDER BY 
  CASE status 
    WHEN 'available' THEN 1 
    WHEN 'pending' THEN 2 
    WHEN 'maintenance' THEN 3 
    WHEN 'rented' THEN 4 
  END,
  bike_id
";
$bikes = $pdo->query($sql)->fetchAll();

// Current date
$currentDate = date('l, F j, Y');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Available Bikes - UniCycle</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="dashboard.css?v=8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Available Bikes Page Specific Styles */
        .bikes-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        }

        .stat-card.active {
            border: 2px solid #2563eb;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stat-icon.available {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        }

        .stat-icon.rented {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
        }

        .stat-icon.maintenance {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        }

        .stat-icon.pending {
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
        }

        .stat-info {
            flex: 1;
        }

        .stat-label {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 4px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
        }

        /* Penalty Warning */
        .penalty-warning {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border: 1px solid #fecaca;
            border-radius: 16px;
            padding: 20px 24px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .penalty-warning .warning-icon {
            width: 48px;
            height: 48px;
            background: #ef4444;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .penalty-warning .warning-text h4 {
            color: #dc2626;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .penalty-warning .warning-text p {
            color: #7f1d1d;
            font-size: 14px;
        }

        .penalty-warning .pay-btn {
            margin-left: auto;
            background: #ef4444;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.2s;
        }

        .penalty-warning .pay-btn:hover {
            background: #dc2626;
        }

        /* Filter Tabs */
        .filter-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            background: white;
            padding: 8px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .filter-tab {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            background: transparent;
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-tab:hover {
            background: #f1f5f9;
            color: #1e293b;
        }

        .filter-tab.active {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
        }

        .filter-tab .count {
            background: rgba(0, 0, 0, 0.1);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
        }

        .filter-tab.active .count {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Bikes Grid */
        .bikes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        .bike-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .bike-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.1);
        }

        .bike-card-header {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 24px;
            text-align: center;
            position: relative;
        }

        .bike-card-header .bike-type-icon {
            font-size: 56px;
            margin-bottom: 8px;
            display: block;
        }

        .bike-card-header .bike-type-img {
            width: 120px;
            height: 80px;
            object-fit: contain;
            margin-bottom: 8px;
        }

        .bike-card-header .bike-status {
            position: absolute;
            top: 16px;
            right: 16px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .bike-status.available {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .bike-status.rented {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .bike-status.maintenance {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }

        .bike-status.pending {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
        }

        .bike-card-body {
            padding: 24px;
        }

        .bike-card-body h3 {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 16px;
        }

        .bike-info-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 20px;
        }

        .bike-info-item {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            color: #64748b;
        }

        .bike-info-item .info-icon {
            width: 32px;
            height: 32px;
            background: #f1f5f9;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .bike-card-action {
            width: 100%;
        }

        .rent-btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }

        .rent-btn.available {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
        }

        .rent-btn.available:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            transform: scale(1.02);
        }

        .rent-btn.disabled {
            background: #f1f5f9;
            color: #94a3b8;
            cursor: not-allowed;
        }

        .rent-btn.penalty {
            background: #fee2e2;
            color: #dc2626;
            cursor: not-allowed;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 20px;
        }

        .empty-state .empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }

        .empty-state h3 {
            font-size: 20px;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .empty-state p {
            color: #64748b;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .bikes-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .bikes-stats {
                grid-template-columns: 1fr;
            }

            .bikes-grid {
                grid-template-columns: 1fr;
            }

            .filter-tabs {
                flex-wrap: wrap;
            }
        }
    </style>
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

        <!-- User Profile -->
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
            <a href="dashboard.php" class="nav-item">
                <span class="nav-icon"><i class="fas fa-gauge-high"></i></span>
                <span>Dashboard</span>
            </a>
            <a href="available-bikes.php" class="nav-item active">
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
        <!-- Header Banner -->
        <div class="header-banner">
            <div class="banner-pattern"></div>
            <div class="banner-content">
                <h1>Available Bikes</h1>
                <p class="banner-date"><?= $currentDate ?></p>
            </div>
        </div>

        <!-- Dashboard Content -->
        <div class="dashboard-content" style="display: block;">

            <?php if ($hasUnpaidPenalty): ?>
                <!-- Penalty Warning -->
                <div class="penalty-warning">
                    <div class="warning-icon"><i class="fas fa-exclamation-triangle" style="color: white;"></i></div>
                    <div class="warning-text">
                        <h4>Rental Blocked</h4>
                        <p>You have unpaid penalties. Please settle them before renting a bike.</p>
                    </div>
                    <a href="pay-penalty.php" class="pay-btn">Pay Now</a>
                </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="bikes-stats">
                <div class="stat-card" onclick="filterBikes('all', this)" data-filter="all">
                    <div class="stat-icon available"><i class="fas fa-bicycle"></i></div>
                    <div class="stat-info">
                        <div class="stat-label">Total Bikes</div>
                        <div class="stat-value"><?= $totalBikes ?></div>
                    </div>
                </div>
                <div class="stat-card" onclick="filterBikes('available', this)" data-filter="available">
                    <div class="stat-icon available"><i class="fas fa-check"></i></div>
                    <div class="stat-info">
                        <div class="stat-label">Available</div>
                        <div class="stat-value"><?= $availableCount ?></div>
                    </div>
                </div>
                <div class="stat-card" onclick="filterBikes('rented', this)" data-filter="rented">
                    <div class="stat-icon rented"><i class="fas fa-lock"></i></div>
                    <div class="stat-info">
                        <div class="stat-label">Rented</div>
                        <div class="stat-value"><?= $rentedCount ?></div>
                    </div>
                </div>
                <div class="stat-card" onclick="filterBikes('maintenance', this)" data-filter="maintenance">
                    <div class="stat-icon maintenance"><i class="fas fa-wrench"></i></div>
                    <div class="stat-info">
                        <div class="stat-label">Maintenance</div>
                        <div class="stat-value"><?= $maintenanceCount ?></div>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <button class="filter-tab active" onclick="filterBikes('all', this)">
                    All <span class="count"><?= $totalBikes ?></span>
                </button>
                <button class="filter-tab" onclick="filterBikes('available', this)">
                    Available <span class="count"><?= $availableCount ?></span>
                </button>
                <button class="filter-tab" onclick="filterBikes('pending', this)">
                    Pending <span class="count"><?= $pendingCount ?></span>
                </button>
                <button class="filter-tab" onclick="filterBikes('maintenance', this)">
                    Maintenance <span class="count"><?= $maintenanceCount ?></span>
                </button>
                <button class="filter-tab" onclick="filterBikes('rented', this)">
                    Rented <span class="count"><?= $rentedCount ?></span>
                </button>
            </div>

            <!-- Bikes Grid -->
            <div class="bikes-grid">
                <?php if (empty($bikes)): ?>
                    <div class="empty-state">
                        <span class="empty-icon"><i class="fas fa-bicycle"></i></span>
                        <h3>No bikes found</h3>
                        <p>There are no bikes in the system yet.</p>
                    </div>
                <?php endif; ?>

                <?php foreach ($bikes as $bike): ?>
                    <?php
                    $status = $bike['status'];
                    $bikeType = $bike['bike_type'] ?? 'other';
                    $location = $bike['location'] ?? 'Main Bike Area';
                    $maint = $bike['last_maintained_date'] ?? '-';

                    $statusText = match ($status) {
                        'available' => 'Available',
                        'pending' => 'Pending',
                        'maintenance' => 'Maintenance',
                        default => 'Rented'
                    };

                    // Use actual bike images
                    $bikeImage = match ($bikeType) {
                        'mountain' => 'assets/mountain-bike.png',
                        'city' => 'assets/city-bike.png',
                        default => 'assets/city-bike.png'
                    };
                    ?>

                    <div class="bike-card" data-status="<?= htmlspecialchars($status) ?>">
                        <div class="bike-card-header">
                            <img src="<?= $bikeImage ?>" alt="<?= ucfirst($bikeType) ?> Bike" class="bike-type-img">
                            <span class="bike-status <?= $status ?>"><?= $statusText ?></span>
                        </div>
                        <div class="bike-card-body">
                            <h3><?= htmlspecialchars($bike['bike_name']) ?></h3>
                            <div class="bike-info-list">
                                <div class="bike-info-item">
                                    <span class="info-icon"><i class="fas fa-location-dot"></i></span>
                                    <span><?= htmlspecialchars($location) ?></span>
                                </div>
                                <div class="bike-info-item">
                                    <span class="info-icon"><i class="fas fa-wrench"></i></span>
                                    <span>Last maintained: <?= htmlspecialchars($maint) ?></span>
                                </div>
                                <div class="bike-info-item">
                                    <span class="info-icon"><i class="fas fa-tag"></i></span>
                                    <span><?= ucfirst($bikeType) ?> Bike</span>
                                </div>
                            </div>
                            <div class="bike-card-action">
                                <?php if ($status === 'available' && !$hasUnpaidPenalty): ?>
                                    <a class="rent-btn available"
                                        href="rent-instructions.php?bike_id=<?= (int) $bike['bike_id'] ?>">
                                        <span><i class="fas fa-rocket"></i></span> Rent Now
                                    </a>
                                <?php elseif ($status === 'available' && $hasUnpaidPenalty): ?>
                                    <button class="rent-btn penalty" disabled>
                                        <span><i class="fas fa-exclamation-triangle"></i></span> Penalty Pending
                                    </button>
                                <?php elseif ($status === 'pending'): ?>
                                    <button class="rent-btn disabled" disabled>
                                        <span><i class="fas fa-hourglass-half"></i></span> Pending Review
                                    </button>
                                <?php elseif ($status === 'maintenance'): ?>
                                    <button class="rent-btn disabled" disabled>
                                        <span><i class="fas fa-wrench"></i></span> Under Maintenance
                                    </button>
                                <?php else: ?>
                                    <button class="rent-btn disabled" disabled>
                                        <span><i class="fas fa-lock"></i></span> Currently Rented
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <!-- Logout Modal -->
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
        function filterBikes(status, el) {
            const cards = document.querySelectorAll('.bike-card');
            const tabs = document.querySelectorAll('.filter-tab');
            const statCards = document.querySelectorAll('.stat-card');

            // Update active tab
            tabs.forEach(t => t.classList.remove('active'));
            statCards.forEach(s => s.classList.remove('active'));

            if (el.classList.contains('filter-tab')) {
                el.classList.add('active');
            } else if (el.classList.contains('stat-card')) {
                el.classList.add('active');
                // Also update the matching filter tab
                tabs.forEach(t => {
                    if (t.textContent.toLowerCase().includes(status)) {
                        t.classList.add('active');
                    }
                });
            }

            // Filter cards
            cards.forEach(card => {
                if (status === 'all' || card.dataset.status === status) {
                    card.style.display = '';
                    card.style.animation = 'fadeIn 0.3s ease';
                } else {
                    card.style.display = 'none';
                }
            });
        }

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

        // Add fade in animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }
        `;
        document.head.appendChild(style);
    </script>

</body>

</html>