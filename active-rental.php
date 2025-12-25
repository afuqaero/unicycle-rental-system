<?php
require_once "config.php";
if (session_status() === PHP_SESSION_NONE)
  session_start();

if (empty($_SESSION['active_rental_id'])) {
  header("Location: available-bikes.php");
  exit;
}

$student_name = $_SESSION['student_name'] ?? 'User';
$student_id = $_SESSION['student_id'] ?? 0;

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

$rental_id = (int) $_SESSION['active_rental_id'];

$stmt = $pdo->prepare("
  SELECT r.rental_id, r.start_time, r.expected_return_time,
         b.bike_name, b.bike_type, b.location
  FROM rentals r
  JOIN bikes b ON b.bike_id = r.bike_id
  WHERE r.rental_id=? AND r.status='active'
  LIMIT 1
");
$stmt->execute([$rental_id]);
$r = $stmt->fetch();

if (!$r) {
  unset($_SESSION['active_rental_id']);
  header("Location: available-bikes.php");
  exit;
}

$expected_ts = strtotime($r['expected_return_time']);
$start_ts = strtotime($r['start_time']);
$duration_hours = round(($expected_ts - $start_ts) / 3600, 1);
$bikeIcon = ($r['bike_type'] ?? 'city') === 'mountain' ? '🚵' : '🚲';

// Current date
$currentDate = date('l, F j, Y');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Active Rental - UniCycle</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="dashboard.css?v=7">
  <style>
    /* Active Rental Page Styles */
    .rental-container {
      max-width: 600px;
      margin: 0 auto;
    }

    .rental-card {
      background: white;
      border-radius: 24px;
      overflow: hidden;
      box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
    }

    .rental-header {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      padding: 32px;
      text-align: center;
      color: white;
    }

    .rental-header .bike-icon {
      font-size: 64px;
      margin-bottom: 16px;
    }

    .rental-header h2 {
      font-size: 24px;
      font-weight: 600;
      margin-bottom: 8px;
    }

    .rental-header .status-badge {
      display: inline-block;
      background: rgba(255, 255, 255, 0.2);
      padding: 6px 16px;
      border-radius: 20px;
      font-size: 14px;
      font-weight: 500;
    }

    .rental-body {
      padding: 32px;
    }

    /* Countdown Section */
    .countdown-section {
      text-align: center;
      padding: 24px;
      background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
      border-radius: 16px;
      margin-bottom: 24px;
    }

    .countdown-label {
      font-size: 14px;
      color: #059669;
      font-weight: 600;
      margin-bottom: 8px;
    }

    .countdown-timer {
      font-size: 48px;
      font-weight: 700;
      color: #065f46;
      font-family: 'Courier New', monospace;
      letter-spacing: 4px;
    }

    .countdown-warning {
      background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    }

    .countdown-warning .countdown-label {
      color: #dc2626;
    }

    .countdown-warning .countdown-timer {
      color: #991b1b;
    }

    /* Info List */
    .rental-info {
      display: flex;
      flex-direction: column;
      gap: 16px;
      margin-bottom: 24px;
    }

    .info-row {
      display: flex;
      align-items: center;
      gap: 16px;
      padding: 16px;
      background: #f8fafc;
      border-radius: 12px;
    }

    .info-row .info-icon {
      width: 44px;
      height: 44px;
      background: white;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    }

    .info-row .info-content {
      flex: 1;
    }

    .info-row .info-label {
      font-size: 12px;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .info-row .info-value {
      font-size: 16px;
      color: #1e293b;
      font-weight: 600;
    }

    /* Return Button */
    .return-btn {
      display: block;
      width: 100%;
      padding: 18px;
      background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
      color: white;
      border: none;
      border-radius: 14px;
      font-size: 17px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      text-align: center;
      text-decoration: none;
    }

    .return-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(37, 99, 235, 0.3);
    }

    /* Warning Note */
    .warning-note {
      margin-top: 16px;
      padding: 12px 16px;
      background: #fef3c7;
      border-radius: 10px;
      font-size: 13px;
      color: #92400e;
      text-align: center;
    }
  </style>
</head>

<body>

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-header">
      <a href="dashboard.php" class="sidebar-logo">
        <div class="logo-icon">U</div>
        <span class="logo-text">UniCycle</span>
      </a>
    </div>

    <div class="user-section">
      <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
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

    <nav class="sidebar-nav">
      <a href="dashboard.php" class="nav-item">
        <span class="nav-icon">📊</span>
        <span>Dashboard</span>
      </a>
      <a href="available-bikes.php" class="nav-item">
        <span class="nav-icon">🚲</span>
        <span>Available Bikes</span>
      </a>
      <a href="rental-summary.php" class="nav-item">
        <span class="nav-icon">📋</span>
        <span>Rental Summary</span>
      </a>
      <a href="complaints.php" class="nav-item">
        <span class="nav-icon">💬</span>
        <span>Complaints</span>
      </a>
    </nav>

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
        <div class="banner-dots">
          <span></span><span></span><span></span><span></span>
        </div>
        <h1>Active Rental</h1>
        <p class="banner-date"><?= $currentDate ?></p>
      </div>
    </div>

    <!-- Content -->
    <div class="dashboard-content" style="display: block;">
      <div class="rental-container">
        <div class="rental-card">
          <div class="rental-header">
            <div class="bike-icon"><?= $bikeIcon ?></div>
            <h2><?= htmlspecialchars($r['bike_name']) ?></h2>
            <span class="status-badge">🟢 Active</span>
          </div>

          <div class="rental-body">
            <!-- Countdown -->
            <div class="countdown-section" id="countdownSection">
              <div class="countdown-label">⏱ Time Remaining</div>
              <div class="countdown-timer" id="countdown">--:--:--</div>
            </div>

            <!-- Info -->
            <div class="rental-info">
              <div class="info-row">
                <div class="info-icon">📍</div>
                <div class="info-content">
                  <div class="info-label">Location</div>
                  <div class="info-value"><?= htmlspecialchars($r['location'] ?? 'Main Bike Area') ?></div>
                </div>
              </div>
              <div class="info-row">
                <div class="info-icon">🕐</div>
                <div class="info-content">
                  <div class="info-label">Started</div>
                  <div class="info-value"><?= date('M j, Y \a\t g:i A', strtotime($r['start_time'])) ?></div>
                </div>
              </div>
              <div class="info-row">
                <div class="info-icon">📅</div>
                <div class="info-content">
                  <div class="info-label">Expected Return</div>
                  <div class="info-value"><?= date('M j, Y \a\t g:i A', strtotime($r['expected_return_time'])) ?></div>
                </div>
              </div>
              <div class="info-row">
                <div class="info-icon">⏳</div>
                <div class="info-content">
                  <div class="info-label">Duration</div>
                  <div class="info-value"><?= $duration_hours ?> hour(s)</div>
                </div>
              </div>
            </div>

            <!-- Return Button -->
            <a href="return-form.php" class="return-btn">🔄 Return Bike</a>

            <div class="warning-note">
              ⚠️ You cannot access other pages until you return the bike.
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Logout Modal -->
  <div class="modal-overlay" id="logoutModal">
    <div class="modal-box">
      <div class="modal-icon">⚠️</div>
      <h3>Confirm Logout</h3>
      <p>Are you sure you want to sign out?</p>
      <div class="modal-actions">
        <button class="modal-btn cancel" onclick="hideLogoutModal()">Cancel</button>
        <button class="modal-btn confirm" onclick="confirmLogout()">Sign out</button>
      </div>
    </div>
  </div>

  <script>
    const expected = <?= (int) $expected_ts ?> * 1000;

    function tick() {
      const now = Date.now();
      let diff = expected - now;
      const section = document.getElementById('countdownSection');

      if (diff < 0) {
        diff = Math.abs(diff);
        section.classList.add('countdown-warning');
        document.querySelector('.countdown-label').textContent = '⚠️ OVERDUE';
      }

      const h = Math.floor(diff / 3600000);
      diff %= 3600000;
      const m = Math.floor(diff / 60000);
      diff %= 60000;
      const s = Math.floor(diff / 1000);

      document.getElementById("countdown").textContent =
        String(h).padStart(2, '0') + ":" + String(m).padStart(2, '0') + ":" + String(s).padStart(2, '0');
    }

    tick();
    setInterval(tick, 1000);

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