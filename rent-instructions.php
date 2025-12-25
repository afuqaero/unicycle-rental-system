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

// block if unpaid penalties
$stmt = $pdo->prepare("
  SELECT COUNT(*)
  FROM penalties p
  JOIN rentals r ON r.rental_id = p.rental_id
  WHERE r.student_id=? AND p.status='unpaid'
");
$stmt->execute([$student_id]);
if ((int) $stmt->fetchColumn() > 0) {
  header("Location: pay-penalty.php");
  exit;
}

$bike_id = isset($_GET['bike_id']) ? (int) $_GET['bike_id'] : 0;
if ($bike_id <= 0) {
  header("Location: available-bikes.php");
  exit;
}

// ensure bike exists + available
$stmt = $pdo->prepare("SELECT bike_id, bike_name, bike_type, status, location FROM bikes WHERE bike_id=?");
$stmt->execute([$bike_id]);
$bike = $stmt->fetch();
if (!$bike || $bike['status'] !== 'available') {
  header("Location: available-bikes.php");
  exit;
}

$bikeIcon = ($bike['bike_type'] ?? 'city') === 'mountain' ? '🚵' : '🚲';
$currentDate = date('l, F j, Y');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Rent Bike - UniCycle</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="dashboard.css?v=8">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    /* Rent Instructions Styles */
    .rent-container {
      max-width: 600px;
      margin: 0 auto;
    }

    .rent-card {
      background: white;
      border-radius: 24px;
      overflow: hidden;
      box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
    }

    .rent-header {
      background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
      padding: 32px;
      text-align: center;
      color: white;
    }

    .rent-header .bike-icon {
      font-size: 64px;
      margin-bottom: 12px;
    }

    .rent-header h2 {
      font-size: 24px;
      font-weight: 600;
      margin-bottom: 4px;
    }

    .rent-header p {
      opacity: 0.9;
      font-size: 14px;
    }

    .rent-body {
      padding: 32px;
    }

    /* Bike Card */
    .selected-bike {
      display: flex;
      align-items: center;
      gap: 16px;
      padding: 20px;
      background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
      border: 2px solid #bae6fd;
      border-radius: 16px;
      margin-bottom: 24px;
    }

    .selected-bike .bike-icon-wrap {
      width: 56px;
      height: 56px;
      background: white;
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 28px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .selected-bike .bike-info h4 {
      font-size: 18px;
      font-weight: 600;
      color: #0c4a6e;
      margin-bottom: 4px;
    }

    .selected-bike .bike-info p {
      font-size: 14px;
      color: #0369a1;
    }

    /* Instructions */
    .instructions-section h3 {
      font-size: 18px;
      font-weight: 600;
      color: #1e293b;
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .step-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
      margin-bottom: 24px;
    }

    .step-item {
      display: flex;
      align-items: flex-start;
      gap: 16px;
      padding: 16px;
      background: #f8fafc;
      border-radius: 12px;
    }

    .step-number {
      width: 32px;
      height: 32px;
      background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
      color: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
      font-weight: 700;
      flex-shrink: 0;
    }

    .step-content {
      flex: 1;
    }

    .step-content h4 {
      font-size: 15px;
      font-weight: 600;
      color: #1e293b;
      margin-bottom: 4px;
    }

    .step-content p {
      font-size: 14px;
      color: #64748b;
    }

    /* Buttons */
    .action-buttons {
      display: flex;
      gap: 12px;
    }

    .action-buttons .back-btn {
      flex: 1;
      padding: 16px;
      background: #f1f5f9;
      color: #64748b;
      border: none;
      border-radius: 12px;
      font-size: 15px;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      text-align: center;
      transition: all 0.2s;
    }

    .action-buttons .back-btn:hover {
      background: #e2e8f0;
    }

    .action-buttons .continue-btn {
      flex: 2;
      padding: 16px;
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      color: white;
      border: none;
      border-radius: 12px;
      font-size: 15px;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      text-align: center;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .action-buttons .continue-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3);
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
        <h1>Rent a Bike</h1>
        <p class="banner-date"><?= $currentDate ?></p>
      </div>
    </div>

    <!-- Content -->
    <div class="dashboard-content" style="display: block;">
      <div class="rent-container">
        <div class="rent-card">
          <div class="rent-header">
            <div class="bike-icon"><i
                class="fas <?= ($bike['bike_type'] ?? 'city') === 'mountain' ? 'fa-mountain' : 'fa-bicycle' ?>"></i>
            </div>
            <h2>Ready to Ride?</h2>
            <p>Review the steps before confirming</p>
          </div>

          <div class="rent-body">
            <!-- Selected Bike -->
            <div class="selected-bike">
              <div class="bike-icon-wrap"><i
                  class="fas <?= ($bike['bike_type'] ?? 'city') === 'mountain' ? 'fa-mountain' : 'fa-bicycle' ?>"></i>
              </div>
              <div class="bike-info">
                <h4><?= htmlspecialchars($bike['bike_name']) ?></h4>
                <p><i class="fas fa-location-dot"></i> <?= htmlspecialchars($bike['location'] ?? 'Main Bike Area') ?>
                </p>
              </div>
            </div>

            <!-- Instructions -->
            <div class="instructions-section">
              <h3><i class="fas fa-clipboard-list"></i> How It Works</h3>
              <div class="step-list">
                <div class="step-item">
                  <div class="step-number">1</div>
                  <div class="step-content">
                    <h4>Confirm Details</h4>
                    <p>Select rental duration and review the price on the next page.</p>
                  </div>
                </div>
                <div class="step-item">
                  <div class="step-number">2</div>
                  <div class="step-content">
                    <h4>Complete Payment</h4>
                    <p>Payment is simulated - no real API integration.</p>
                  </div>
                </div>
                <div class="step-item">
                  <div class="step-number">3</div>
                  <div class="step-content">
                    <h4>Start Your Ride</h4>
                    <p>You'll be locked to Active Rental until you return the bike.</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Buttons -->
            <div class="action-buttons">
              <a href="available-bikes.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
              <a href="rent-confirm.php?bike_id=<?= (int) $bike['bike_id'] ?>" class="continue-btn">
                Continue <i class="fas fa-arrow-right"></i>
              </a>
            </div>
          </div>
        </div>
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