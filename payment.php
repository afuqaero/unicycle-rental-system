<?php
require_once "guard_active_rental.php";
require_once "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();

$student_id = 1; // no login yet

$bike_id = isset($_POST['bike_id']) ? (int)$_POST['bike_id'] : 0;
$hours   = isset($_POST['hours']) ? (int)$_POST['hours'] : 0;

if ($bike_id <= 0 || $hours <= 0) die("Invalid request.");

$pdo->beginTransaction();

try {
    // lock bike row
    $stmt = $pdo->prepare("SELECT bike_id, status FROM bikes WHERE bike_id=? FOR UPDATE");
    $stmt->execute([$bike_id]);
    $bike = $stmt->fetch();
    if (!$bike || $bike['status'] !== 'available') {
        throw new Exception("Bike not available.");
    }

    $start = date("Y-m-d H:i:s");
    $expected = date("Y-m-d H:i:s", time() + ($hours * 3600));
    $rate = 3.00;
    $amount = $rate * $hours;

    $rental_code = "RENT" . str_pad((string)random_int(1, 999999), 6, "0", STR_PAD_LEFT);

    // create rental
    $stmt = $pdo->prepare("
      INSERT INTO rentals (rental_code, student_id, bike_id, start_time, expected_return_time, status, hourly_rate, planned_hours)
      VALUES (?, ?, ?, ?, ?, 'active', ?, ?)
    ");
    $stmt->execute([$rental_code, $student_id, $bike_id, $start, $expected, $rate, $hours]);
    $rental_id = (int)$pdo->lastInsertId();

    // set bike to rented
    $stmt = $pdo->prepare("UPDATE bikes SET status='rented' WHERE bike_id=?");
    $stmt->execute([$bike_id]);

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    die("Failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payment</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="sidebar">
  <h2>BikeRental</h2>
  <a href="dashboard.php">Dashboard</a>
  <a href="available-bikes.php">Available Bikes</a>
  <a href="rental-summary.php">Rental Summary</a>
  <a href="complaints.php">Complaints</a>
</div>

<div class="main">
  <h1>Payment</h1>
  

  <div class="card">
    <p><strong>Rental Code:</strong> <?= htmlspecialchars($rental_code) ?></p>
    <p><strong>Hours:</strong> <?= (int)$hours ?></p>
    <p><strong>Rate:</strong> RM 3.00 / hour</p>
    <p><strong>Total:</strong> RM <?= number_format($amount, 2) ?></p>

    <form action="payment-success.php" method="post" style="margin-top:16px;">
      <input type="hidden" name="rental_id" value="<?= (int)$rental_id ?>">
      <input type="hidden" name="amount" value="<?= htmlspecialchars((string)$amount) ?>">
      <button type="submit">Pay Now</button>
    </form>
  </div>
</div>

</body>
</html>
