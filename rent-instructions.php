<?php
require_once "config.php";
require_once "guard_active_rental.php";

$student_id = 1;

// block if unpaid penalties
$stmt = $pdo->prepare("
  SELECT COUNT(*)
  FROM penalties p
  JOIN rentals r ON r.rental_id = p.rental_id
  WHERE r.student_id=? AND p.status='unpaid'
");
$stmt->execute([$student_id]);
if ((int)$stmt->fetchColumn() > 0) {
    die("You have unpaid penalties. Rental blocked.");
}

$bike_id = isset($_GET['bike_id']) ? (int)$_GET['bike_id'] : 0;
if ($bike_id <= 0) die("Invalid bike.");

// ensure bike exists + available
$stmt = $pdo->prepare("SELECT bike_id, bike_name, status FROM bikes WHERE bike_id=?");
$stmt->execute([$bike_id]);
$bike = $stmt->fetch();
if (!$bike || $bike['status'] !== 'available') die("Bike not available.");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>How to Rent</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="sidebar">
  <h2>BikeRental</h2>
  <a href="dashboard.php">Dashboard</a>
  <a href="available-bikes.php" class="active">Available Bikes</a>
  <a href="rental-summary.php">Rental Summary</a>
  <a href="complaints.php">Complaints</a>
  <div class="logout">Logout</div>
</div>

<div class="main">
  <h1>How to Rent a Bike</h1>
  <p class="sub">Read before you continue.</p>

  <div class="card">
      <p><strong>Steps:</strong></p>
      <p>1) Confirm rental details (hours, price).</p>
      <p>2) Payment is simulated (no API).</p>
      <p>3) After success, you will be locked to Active Rental until return.</p>

      <p style="margin-top:20px; margin-bottom:20px;"><strong>Selected Bike:</strong> <?= htmlspecialchars($bike['bike_name']) ?></p>

      <a class="view-btn" href="rent-confirm.php?bike_id=<?= (int)$bike['bike_id'] ?>" style="margin-right:10px;">Continue</a>
      <a class="view-btn" style="background:#6091D4;" href="available-bikes.php">Back</a>
  </div>


</body>
</html>
