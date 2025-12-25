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
  SELECT r.rental_id, r.bike_id, b.bike_name, b.bike_type
  FROM rentals r
  JOIN bikes b ON b.bike_id = r.bike_id
  WHERE r.rental_id=? AND r.status='active'
  LIMIT 1
");
$stmt->execute([$rental_id]);
$r = $stmt->fetch();
if (!$r) {
  header("Location: available-bikes.php");
  exit;
}

$bikeIcon = ($r['bike_type'] ?? 'city') === 'mountain' ? '🚵' : '🚲';
$currentDate = date('l, F j, Y');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Return Bike - UniCycle</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="dashboard.css?v=8">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    /* Return Form Styles */
    .return-container {
      max-width: 560px;
      margin: 0 auto;
    }

    .return-card {
      background: white;
      border-radius: 24px;
      overflow: hidden;
      box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
    }

    .return-header {
      background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
      padding: 32px;
      text-align: center;
      color: white;
    }

    .return-header .bike-icon {
      font-size: 56px;
      margin-bottom: 12px;
    }

    .return-header h2 {
      font-size: 22px;
      font-weight: 600;
      margin-bottom: 4px;
    }

    .return-header p {
      opacity: 0.9;
      font-size: 14px;
    }

    .return-body {
      padding: 32px;
    }

    .bike-info-banner {
      display: flex;
      align-items: center;
      gap: 16px;
      padding: 16px;
      background: #f8fafc;
      border-radius: 12px;
      margin-bottom: 24px;
    }

    .bike-info-banner .bike-icon-small {
      width: 48px;
      height: 48px;
      background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
    }

    .bike-info-banner .bike-details {
      flex: 1;
    }

    .bike-info-banner .bike-name {
      font-size: 16px;
      font-weight: 600;
      color: #1e293b;
    }

    .bike-info-banner .bike-label {
      font-size: 13px;
      color: #64748b;
    }

    /* Form Styles */
    .form-section {
      margin-bottom: 20px;
    }

    .form-section label {
      display: block;
      font-size: 14px;
      font-weight: 600;
      color: #374151;
      margin-bottom: 8px;
    }

    .form-section select,
    .form-section textarea {
      width: 100%;
      padding: 14px 16px;
      border: 2px solid #e5e7eb;
      border-radius: 12px;
      font-size: 15px;
      transition: border-color 0.2s;
      font-family: inherit;
      background: white;
    }

    .form-section select:focus,
    .form-section textarea:focus {
      outline: none;
      border-color: #2563eb;
    }

    .form-section textarea {
      min-height: 100px;
      resize: vertical;
    }

    .form-section .hint {
      font-size: 13px;
      color: #64748b;
      margin-top: 6px;
    }

    /* Buttons */
    .form-buttons {
      display: flex;
      gap: 12px;
      margin-top: 24px;
    }

    .form-buttons .back-btn {
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

    .form-buttons .back-btn:hover {
      background: #e2e8f0;
    }

    .form-buttons .submit-btn {
      flex: 2;
      padding: 16px;
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      color: white;
      border: none;
      border-radius: 12px;
      font-size: 15px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
    }

    .form-buttons .submit-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3);
    }

    /* Info Note */
    .info-note {
      margin-top: 20px;
      padding: 12px 16px;
      background: #f0f9ff;
      border-radius: 10px;
      font-size: 13px;
      color: #0369a1;
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
        <h1>Return Bike</h1>
        <p class="banner-date"><?= $currentDate ?></p>
      </div>
    </div>

    <!-- Content -->
    <div class="dashboard-content" style="display: block;">
      <div class="return-container">
        <div class="return-card">
          <div class="return-header">
            <div class="bike-icon"><i
                class="fas <?= ($r['bike_type'] ?? 'city') === 'mountain' ? 'fa-mountain' : 'fa-bicycle' ?>"></i></div>
            <h2>Return Your Bike</h2>
            <p>Report condition before returning</p>
          </div>

          <div class="return-body">
            <!-- Bike Info -->
            <div class="bike-info-banner">
              <div class="bike-icon-small"><i
                  class="fas <?= ($r['bike_type'] ?? 'city') === 'mountain' ? 'fa-mountain' : 'fa-bicycle' ?>"></i>
              </div>
              <div class="bike-details">
                <div class="bike-label">Currently Rented</div>
                <div class="bike-name"><?= htmlspecialchars($r['bike_name']) ?></div>
              </div>
            </div>

            <form action="return-bike.php" method="post">
              <input type="hidden" name="rental_id" value="<?= (int) $rental_id ?>">

              <div class="form-section">
                <label>Bike Condition</label>
                <select name="condition_status">
                  <option value="">Select condition...</option>
                  <option value="good"><i class="fas fa-check"></i> Good - No issues</option>
                  <option value="minor_issue"><i class="fas fa-exclamation"></i> Minor Issue</option>
                  <option value="needs_repair"><i class="fas fa-wrench"></i> Needs Repair</option>
                </select>
                <div class="hint">Optional: Help us maintain our bikes</div>
              </div>

              <div class="form-section">
                <label>Additional Notes</label>
                <textarea name="note" placeholder="Example: brakes slightly loose, chain noisy..."></textarea>
                <div class="hint">Optional: Describe any issues you noticed</div>
              </div>

              <div class="form-buttons">
                <a href="active-rental.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
                <button type="submit" class="submit-btn"><i class="fas fa-check"></i> Confirm Return</button>
              </div>
            </form>

            <div class="info-note">
              <i class="fas fa-lightbulb"></i> For complaints about service quality, please use the Complaints page
              after returning.
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