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

// fetch unpaid penalties
$stmt = $pdo->prepare("
  SELECT p.penalty_id, p.amount, p.minutes_late, p.created_at,
         r.rental_id, r.rental_code, b.bike_name, b.bike_type
  FROM penalties p
  JOIN rentals r ON r.rental_id = p.rental_id
  JOIN bikes b ON b.bike_id = r.bike_id
  WHERE r.student_id = ? AND p.status = 'unpaid'
  ORDER BY p.created_at DESC
");
$stmt->execute([$student_id]);
$penalties = $stmt->fetchAll();

// total
$total = 0.0;
foreach ($penalties as $p)
  $total += (float) $p['amount'];

$currentDate = date('l, F j, Y');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Pay Penalty - UniCycle</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="dashboard.css?v=7">
  <style>
    /* Pay Penalty Styles */
    .penalty-total {
      background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
      border-radius: 20px;
      padding: 32px;
      color: white;
      text-align: center;
      margin-bottom: 24px;
    }

    .penalty-total .warning-icon {
      font-size: 48px;
      margin-bottom: 12px;
    }

    .penalty-total h3 {
      font-size: 16px;
      font-weight: 500;
      opacity: 0.9;
      margin-bottom: 8px;
    }

    .penalty-total .amount {
      font-size: 42px;
      font-weight: 700;
    }

    .penalty-total p {
      margin-top: 12px;
      font-size: 14px;
      opacity: 0.9;
    }

    /* Penalty List */
    .penalty-list {
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .penalty-card {
      background: white;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    }

    .penalty-card-header {
      padding: 20px 24px;
      display: flex;
      align-items: center;
      gap: 16px;
      border-bottom: 1px solid #f1f5f9;
    }

    .penalty-card-header .bike-icon {
      width: 48px;
      height: 48px;
      background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
    }

    .penalty-card-header .info {
      flex: 1;
    }

    .penalty-card-header .info h4 {
      font-size: 16px;
      font-weight: 600;
      color: #1e293b;
      margin-bottom: 4px;
    }

    .penalty-card-header .info p {
      font-size: 13px;
      color: #64748b;
    }

    .penalty-card-header .amount {
      font-size: 20px;
      font-weight: 700;
      color: #dc2626;
    }

    .penalty-card-body {
      padding: 16px 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: #fef2f2;
    }

    .penalty-card-body .details {
      display: flex;
      gap: 20px;
    }

    .penalty-card-body .detail-item {
      font-size: 13px;
    }

    .penalty-card-body .detail-item .label {
      color: #64748b;
    }

    .penalty-card-body .detail-item .value {
      color: #1e293b;
      font-weight: 600;
    }

    .pay-penalty-btn {
      padding: 12px 24px;
      background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
      color: white;
      border: none;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
    }

    .pay-penalty-btn:hover {
      transform: scale(1.05);
      box-shadow: 0 8px 20px rgba(220, 38, 38, 0.3);
    }

    /* Empty State */
    .no-penalty {
      background: white;
      border-radius: 20px;
      padding: 60px 20px;
      text-align: center;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    }

    .no-penalty .icon {
      font-size: 64px;
      margin-bottom: 16px;
    }

    .no-penalty h3 {
      font-size: 20px;
      color: #1e293b;
      margin-bottom: 8px;
    }

    .no-penalty p {
      color: #64748b;
      margin-bottom: 20px;
    }

    .no-penalty .back-btn {
      display: inline-block;
      padding: 14px 28px;
      background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
      color: white;
      border-radius: 12px;
      text-decoration: none;
      font-weight: 600;
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
      <a href="rental-summary.php" class="nav-item active">
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
    <div class="header-banner" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 50%, #b91c1c 100%);">
      <div class="banner-pattern"></div>
      <div class="banner-content">
        <div class="banner-dots">
          <span></span><span></span><span></span><span></span>
        </div>
        <h1>Pay Penalty</h1>
        <p class="banner-date"><?= $currentDate ?></p>
      </div>
    </div>

    <!-- Content -->
    <div class="dashboard-content" style="display: block;">

      <?php if (empty($penalties)): ?>
        <!-- No Penalties -->
        <div class="no-penalty">
          <div class="icon">✅</div>
          <h3>No Unpaid Penalties</h3>
          <p>You're all clear! You can rent bikes normally.</p>
          <a href="available-bikes.php" class="back-btn">Browse Bikes →</a>
        </div>

      <?php else: ?>
        <!-- Total Penalty -->
        <div class="penalty-total">
          <div class="warning-icon">⚠️</div>
          <h3>Total Unpaid Penalties</h3>
          <div class="amount">RM <?= number_format($total, 2) ?></div>
          <p>Pay your penalties to continue renting bikes</p>
        </div>

        <!-- Penalty List -->
        <div class="penalty-list">
          <?php foreach ($penalties as $p): ?>
            <?php $bikeIcon = ($p['bike_type'] ?? 'city') === 'mountain' ? '🚵' : '🚲'; ?>
            <div class="penalty-card">
              <div class="penalty-card-header">
                <div class="bike-icon"><?= $bikeIcon ?></div>
                <div class="info">
                  <h4><?= htmlspecialchars($p['bike_name']) ?></h4>
                  <p>Code: <?= htmlspecialchars($p['rental_code']) ?></p>
                </div>
                <div class="amount">RM <?= number_format((float) $p['amount'], 2) ?></div>
              </div>
              <div class="penalty-card-body">
                <div class="details">
                  <div class="detail-item">
                    <span class="label">Late by</span>
                    <span class="value"><?= (int) $p['minutes_late'] ?> min</span>
                  </div>
                  <div class="detail-item">
                    <span class="label">Date</span>
                    <span class="value"><?= date('M j, Y', strtotime($p['created_at'])) ?></span>
                  </div>
                </div>
                <form method="post" action="pay-penalty-process.php" style="margin:0;">
                  <input type="hidden" name="penalty_id" value="<?= (int) $p['penalty_id'] ?>">
                  <button type="submit" class="pay-penalty-btn">Pay Now</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

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