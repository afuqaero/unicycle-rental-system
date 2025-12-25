<?php
require_once "config.php";
if (session_status() === PHP_SESSION_NONE)
  session_start();

// Get bike_id and hours from the payment form
$bike_id = isset($_POST['bike_id']) ? (int) $_POST['bike_id'] : 0;
$hours = isset($_POST['hours']) ? (int) $_POST['hours'] : 0;
$amount = isset($_POST['amount']) ? (float) $_POST['amount'] : 0;

if ($bike_id <= 0 || $hours <= 0) {
  header("Location: available-bikes.php");
  exit;
}

$student_id = $_SESSION['student_id'] ?? 0;
if ($student_id <= 0) {
  header("Location: login.php");
  exit;
}

// Create rental, update bike status, and record payment in one transaction
$pdo->beginTransaction();

try {
  // Lock bike row and verify still available
  $stmt = $pdo->prepare("SELECT bike_id, bike_name, bike_type, status FROM bikes WHERE bike_id=? FOR UPDATE");
  $stmt->execute([$bike_id]);
  $bike = $stmt->fetch();

  if (!$bike || $bike['status'] !== 'available') {
    throw new Exception("Bike is no longer available.");
  }

  $start = date("Y-m-d H:i:s");
  $expected = date("Y-m-d H:i:s", time() + ($hours * 3600));
  $rate = 3.00;
  $amount = $rate * $hours;

  $rental_code = "RENT" . str_pad((string) random_int(1, 999999), 6, "0", STR_PAD_LEFT);

  // Create rental
  $stmt = $pdo->prepare("
    INSERT INTO rentals (rental_code, student_id, bike_id, start_time, expected_return_time, status, hourly_rate, planned_hours)
    VALUES (?, ?, ?, ?, ?, 'active', ?, ?)
  ");
  $stmt->execute([$rental_code, $student_id, $bike_id, $start, $expected, $rate, $hours]);
  $rental_id = (int) $pdo->lastInsertId();

  // Set bike to rented
  $stmt = $pdo->prepare("UPDATE bikes SET status='rented' WHERE bike_id=?");
  $stmt->execute([$bike_id]);

  // Create payment record
  $stmt = $pdo->prepare("INSERT INTO payments (rental_id, amount, method, status) VALUES (?, ?, 'cashless', 'paid')");
  $stmt->execute([$rental_id, $amount]);

  $pdo->commit();
} catch (Exception $e) {
  $pdo->rollBack();
  header("Location: available-bikes.php?error=" . urlencode($e->getMessage()));
  exit;
}

// Set session for active rental
$_SESSION['active_rental_id'] = $rental_id;

$bikeName = $bike['bike_name'] ?? 'Bike';
$bikeIcon = ($bike['bike_type'] ?? 'city') === 'mountain' ? '🚵' : '🚲';
$rentalCode = $rental_code;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Payment Success - UniCycle</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="dashboard.css?v=8">
  <style>
    body {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 20px;
    }

    .success-container {
      max-width: 420px;
      width: 100%;
    }

    .success-card {
      background: white;
      border-radius: 24px;
      overflow: hidden;
      box-shadow: 0 8px 40px rgba(0, 0, 0, 0.12);
      text-align: center;
      animation: slideUp 0.5s ease;
    }

    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .success-header {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      padding: 48px 32px;
      color: white;
    }

    .success-icon {
      width: 80px;
      height: 80px;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
      animation: pulse 1.5s ease infinite;
    }

    @keyframes pulse {

      0%,
      100% {
        transform: scale(1);
      }

      50% {
        transform: scale(1.05);
      }
    }

    .success-icon .checkmark {
      font-size: 40px;
    }

    .success-header h2 {
      font-size: 26px;
      font-weight: 700;
      margin-bottom: 8px;
    }

    .success-header p {
      opacity: 0.9;
      font-size: 15px;
    }

    .success-body {
      padding: 32px;
    }

    .rental-info {
      background: #f8fafc;
      border-radius: 16px;
      padding: 20px;
      margin-bottom: 24px;
    }

    .rental-info .bike-icon {
      font-size: 40px;
      margin-bottom: 12px;
    }

    .rental-info h3 {
      font-size: 18px;
      color: #1e293b;
      margin-bottom: 8px;
    }

    .rental-info .code {
      font-size: 14px;
      color: #64748b;
    }

    .rental-info .code strong {
      color: #10b981;
    }

    .redirect-text {
      color: #64748b;
      font-size: 14px;
      margin-bottom: 16px;
    }

    .progress-bar {
      width: 100%;
      height: 4px;
      background: #e2e8f0;
      border-radius: 2px;
      overflow: hidden;
    }

    .progress-fill {
      height: 100%;
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      animation: progress 1.5s ease forwards;
    }

    @keyframes progress {
      from {
        width: 0;
      }

      to {
        width: 100%;
      }
    }

    .skip-btn {
      display: inline-block;
      margin-top: 20px;
      color: #64748b;
      text-decoration: none;
      font-size: 14px;
    }

    .skip-btn:hover {
      color: #1e293b;
    }
  </style>
</head>

<body>

  <div class="success-container">
    <div class="success-card">
      <div class="success-header">
        <div class="success-icon">
          <span class="checkmark">✓</span>
        </div>
        <h2>Payment Successful!</h2>
        <p>Your rental is now active</p>
      </div>

      <div class="success-body">
        <div class="rental-info">
          <div class="bike-icon"><?= $bikeIcon ?></div>
          <h3><?= htmlspecialchars($bikeName) ?></h3>
          <p class="code">Code: <strong><?= htmlspecialchars($rentalCode) ?></strong></p>
        </div>

        <p class="redirect-text">Redirecting to Active Rental...</p>
        <div class="progress-bar">
          <div class="progress-fill"></div>
        </div>

        <a href="active-rental.php" class="skip-btn">Click here if not redirected →</a>
      </div>
    </div>
  </div>

  <script>
    setTimeout(() => {
      window.location.href = "active-rental.php";
    }, 1800);
  </script>

</body>

</html>