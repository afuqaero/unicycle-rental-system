<?php
session_start();
require_once "guard_active_rental.php";
require_once "config.php";

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

$bike_id = isset($_POST['bike_id']) ? (int) $_POST['bike_id'] : 0;
$hours = isset($_POST['hours']) ? (int) $_POST['hours'] : 0;

if ($bike_id <= 0 || $hours <= 0) {
  header("Location: available-bikes.php");
  exit;
}

// Only validate bike availability - DO NOT create rental yet
// Rental will be created in payment-success.php after payment is confirmed
$stmt = $pdo->prepare("SELECT bike_id, bike_name, bike_type, status FROM bikes WHERE bike_id=?");
$stmt->execute([$bike_id]);
$bike = $stmt->fetch();

if (!$bike || $bike['status'] !== 'available') {
  header("Location: available-bikes.php?error=unavailable");
  exit;
}

// Calculate amounts for display only
$rate = 3.00;
$amount = $rate * $hours;

// Generate a preview rental code (actual code created on payment success)
$rental_code = "RENT" . str_pad((string) random_int(1, 999999), 6, "0", STR_PAD_LEFT);

$bikeIcon = ($bike['bike_type'] ?? 'city') === 'mountain' ? '🚵' : '🚲';
$currentDate = date('l, F j, Y');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Payment - UniCycle</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="dashboard.css?v=9">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    /* Payment Page Styles */
    .payment-container {
      max-width: 480px;
      margin: 0 auto;
    }

    .payment-card {
      background: white;
      border-radius: 24px;
      overflow: hidden;
      box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
    }

    .payment-header {
      background: linear-gradient(135deg, #1a6dff 0%, #0052d4 100%);
      padding: 40px 32px;
      text-align: center;
      color: white;
    }

    .payment-header .success-icon {
      width: 72px;
      height: 72px;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 36px;
      margin: 0 auto 16px;
    }

    .payment-header h2 {
      font-size: 22px;
      font-weight: 600;
      margin-bottom: 4px;
    }

    .payment-header p {
      opacity: 0.9;
      font-size: 14px;
    }

    .payment-body {
      padding: 32px;
    }

    /* Order Summary */
    .order-summary {
      background: #f8fafc;
      border-radius: 16px;
      padding: 20px;
      margin-bottom: 24px;
    }

    .order-summary h4 {
      font-size: 14px;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 16px;
    }

    .order-item {
      display: flex;
      justify-content: space-between;
      padding: 10px 0;
      border-bottom: 1px dashed #e2e8f0;
    }

    .order-item:last-child {
      border-bottom: none;
    }

    .order-item .label {
      color: #64748b;
      font-size: 14px;
    }

    .order-item .value {
      color: #1e293b;
      font-weight: 600;
      font-size: 14px;
    }

    .order-item.total {
      margin-top: 12px;
      padding-top: 16px;
      border-top: 2px solid #e2e8f0;
      border-bottom: none;
    }

    .order-item.total .label {
      font-size: 16px;
      color: #1e293b;
      font-weight: 600;
    }

    .order-item.total .value {
      font-size: 24px;
      color: #10b981;
    }

    /* Rental Code */
    .rental-code-box {
      background: linear-gradient(135deg, #e8f4ff 0%, #dbeafe 100%);
      border: 2px dashed #93c5fd;
      border-radius: 12px;
      padding: 16px;
      text-align: center;
      margin-bottom: 24px;
    }

    .rental-code-box .code-label {
      font-size: 12px;
      color: #1a6dff;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 4px;
    }

    .rental-code-box .code {
      font-size: 24px;
      font-weight: 700;
      color: #0052d4;
      letter-spacing: 2px;
    }

    /* Pay Button */
    .pay-btn {
      width: 100%;
      padding: 18px;
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      color: white;
      border: none;
      border-radius: 14px;
      font-size: 17px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .pay-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3);
    }

    /* Security Note */
    .security-note {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      margin-top: 16px;
      font-size: 13px;
      color: #64748b;
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
        <h1>Payment</h1>
        <p class="banner-date"><?= $currentDate ?></p>
      </div>
    </div>

    <!-- Content -->
    <div class="dashboard-content" style="display: block;">
      <div class="payment-container">
        <div class="payment-card">
          <div class="payment-header">
            <div class="success-icon"><i
                class="fas <?= ($bike['bike_type'] ?? 'city') === 'mountain' ? 'fa-mountain' : 'fa-bicycle' ?>"></i>
            </div>
            <h2><?= htmlspecialchars($bike['bike_name']) ?></h2>
            <p>Complete your payment to start riding</p>
          </div>

          <div class="payment-body">
            <!-- Rental Code -->
            <div class="rental-code-box">
              <div class="code-label">Rental Code</div>
              <div class="code"><?= htmlspecialchars($rental_code) ?></div>
            </div>

            <!-- Order Summary -->
            <div class="order-summary">
              <h4>Order Summary</h4>
              <div class="order-item">
                <span class="label">Duration</span>
                <span class="value"><?= (int) $hours ?> hour<?= $hours > 1 ? 's' : '' ?></span>
              </div>
              <div class="order-item">
                <span class="label">Rate</span>
                <span class="value">RM <?= number_format($rate, 2) ?> / hour</span>
              </div>
              <div class="order-item total">
                <span class="label">Total</span>
                <span class="value">RM <?= number_format($amount, 2) ?></span>
              </div>
            </div>

            <!-- Pay Button -->
            <form action="payment-success.php" method="post">
              <input type="hidden" name="bike_id" value="<?= (int) $bike_id ?>">
              <input type="hidden" name="hours" value="<?= (int) $hours ?>">
              <input type="hidden" name="amount" value="<?= htmlspecialchars((string) $amount) ?>">
              <button type="submit" class="pay-btn">
                <span><i class="fas fa-credit-card"></i></span> Pay RM <?= number_format($amount, 2) ?>
              </button>
            </form>

            <div class="security-note">
              <span><i class="fas fa-lock"></i></span> Secure simulated payment
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