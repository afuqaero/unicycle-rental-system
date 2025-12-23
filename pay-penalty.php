<?php
require_once "config.php";
require_once "guard_active_rental.php";

$student_id = 1;

// fetch unpaid penalties
$stmt = $pdo->prepare("
  SELECT p.penalty_id, p.amount, p.minutes_late, p.created_at,
         r.rental_id, r.rental_code, b.bike_name
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
foreach ($penalties as $p) $total += (float)$p['amount'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Pay Penalty</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="sidebar">
  <h2>BikeRental</h2>
  <div class="menu">
    <a href="dashboard.php">Dashboard</a>
    <a href="available-bikes.php">Available Bikes</a>
    <a href="rental-summary.php" class="active">Rental Summary</a>
    <a href="complaints.php">Complaints</a>
  </div>
  <div class="logout">Logout</div>
</div>

<div class="main">
  <h1>Pay Penalty</h1>
  <p class="sub">Simulation (no API). Paying will unblock renting.</p>

  <?php if (empty($penalties)): ?>
    <div class="card">
      <h3>No unpaid penalties</h3>
      <p>You can rent bikes normally.</p>
      <a class="view-btn" href="available-bikes.php">Back to Bikes</a>
    </div>
  <?php else: ?>

    <div class="card" style="margin-bottom:18px;">
      <p><strong>Total Unpaid:</strong> RM <?= number_format($total, 2) ?></p>
      <p>Choose a penalty to pay.</p>
    </div>

    <div class="table-container">
      <table>
        <tr>
          <th>Rental</th>
          <th>Bike</th>
          <th>Late (min)</th>
          <th>Amount</th>
          <th>Action</th>
        </tr>

        <?php foreach ($penalties as $p): ?>
          <tr>
            <td><?= htmlspecialchars($p['rental_code']) ?></td>
            <td><?= htmlspecialchars($p['bike_name']) ?></td>
            <td><?= (int)$p['minutes_late'] ?></td>
            <td>RM <?= number_format((float)$p['amount'], 2) ?></td>
            <td>
              <form method="post" action="pay-penalty-process.php"
                    onsubmit="return confirm('Confirm pay this penalty?');"
                    style="margin:0;">
                <input type="hidden" name="penalty_id" value="<?= (int)$p['penalty_id'] ?>">
                <button type="submit">Pay</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>

  <?php endif; ?>
</div>

</body>
</html>
