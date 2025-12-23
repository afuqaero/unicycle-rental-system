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

$stmt = $pdo->prepare("
  SELECT bike_id, bike_name, status, location
  FROM bikes
  WHERE bike_id=?
");
$stmt->execute([$bike_id]);
$bike = $stmt->fetch();
if (!$bike || $bike['status'] !== 'available') die("Bike not available.");

$rate = 3.00;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Confirm Rental</title>
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
  <h1>Confirm Rental</h1>
  <p class="sub">Rate: RM 3 per hour</p>

  <div class="card">
    <form action="payment.php" method="post">
      <input type="hidden" name="bike_id" value="<?= (int)$bike['bike_id'] ?>">

      <p><strong>Bike:</strong> <?= htmlspecialchars($bike['bike_name']) ?></p>
      <p><strong>Location:</strong> <?= htmlspecialchars($bike['location'] ?? 'Main Bike Area') ?></p>

      <div style="margin-top:14px;">
        <label for="hours"><strong>Hours:</strong></label>
        <select name="hours" id="hours" required>
          <?php for ($i=1; $i<=8; $i++): ?>
            <option value="<?= $i ?>"><?= $i ?> hour(s)</option>
          <?php endfor; ?>
        </select>
      </div>

      <p style="margin-top:14px;">
        <strong>Date/Time:</strong> <?= date("Y-m-d H:i") ?>
      </p>

      <div style="margin-top:18px; display:flex; gap:10px;">
        <button type="submit">Confirm & Proceed to Payment</button>
        <a class="view-btn" style="background:#6091D4" href="available-bikes.php">Cancel</a>
      </div>
    </form>
  </div>
</div>

</body>
</html>
