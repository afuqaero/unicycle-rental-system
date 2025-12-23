<?php
// rental-summary.php
require_once "config.php";
require_once "guard_active_rental.php";

$student_id = 1;

// Penalty rules (must match return-bike.php)
$GRACE_MINUTES = 10;
$RATE_FIRST_2H = 5.00;
$RATE_AFTER_2H = 10.00;

// totals
$stmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE student_id=?");
$stmt->execute([$student_id]);
$totalRentals = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE student_id=? AND status IN ('completed','late')");
$stmt->execute([$student_id]);
$completed = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("
  SELECT COALESCE(SUM(p.amount),0)
  FROM penalties p
  JOIN rentals r ON r.rental_id = p.rental_id
  WHERE r.student_id=? AND p.status='unpaid'
");
$stmt->execute([$student_id]);
$totalPenalties = (float)$stmt->fetchColumn();

// active rental (if any)
$stmt = $pdo->prepare("
  SELECT r.rental_id, r.start_time, r.expected_return_time, b.bike_name,
         TIMESTAMPDIFF(MINUTE, r.start_time, r.expected_return_time) AS planned_minutes
  FROM rentals r
  JOIN bikes b ON b.bike_id = r.bike_id
  WHERE r.student_id=? AND r.status='active'
  ORDER BY r.start_time DESC
  LIMIT 1
");
$stmt->execute([$student_id]);
$active = $stmt->fetch();

// history
$stmt = $pdo->prepare("
  SELECT r.rental_id, r.start_time, r.return_time,
         TIMESTAMPDIFF(MINUTE, r.start_time, COALESCE(r.return_time, NOW())) AS duration_minutes,
         r.status, b.bike_name,
         COALESCE(p.amount,0) AS penalty_amount,
         COALESCE(p.minutes_late,0) AS penalty_minutes_late,
         COALESCE(p.status,'-') AS penalty_status
  FROM rentals r
  JOIN bikes b ON b.bike_id = r.bike_id
  LEFT JOIN penalties p ON p.rental_id = r.rental_id
  WHERE r.student_id=? AND r.status IN ('completed','late')
  ORDER BY r.start_time DESC
");
$stmt->execute([$student_id]);
$history = $stmt->fetchAll();

function penalty_breakdown(int $lateMinutesAfterGrace, float $rateFirst2h, float $rateAfter2h): string {
    if ($lateMinutesAfterGrace <= 0) return "No penalty (within grace).";

    $lateHours = (int)ceil($lateMinutesAfterGrace / 60);
    if ($lateHours <= 2) {
        $amt = $lateHours * $rateFirst2h;
        return "Late: {$lateHours} hour(s) × RM " . number_format($rateFirst2h, 2) .
               " = RM " . number_format($amt, 2);
    }

    $amt = (2 * $rateFirst2h) + (($lateHours - 2) * $rateAfter2h);
    return "Late: 2 hour(s) × RM " . number_format($rateFirst2h, 2) .
           " + " . ($lateHours - 2) . " hour(s) × RM " . number_format($rateAfter2h, 2) .
           " = RM " . number_format($amt, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Rental Summary</title>
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

    <div class="header">
        <h1>Rental Summary</h1>
        <p>View your rental history and manage penalties</p>
    </div>

    <!-- penalty rules info -->
    <div class="card" style="margin-bottom:18px;">
        <h3>Penalty Rules</h3>
        <p>Grace period: <strong><?= (int)$GRACE_MINUTES ?> minutes</strong> (no penalty).</p>
        <p>After grace: <strong>RM <?= number_format($RATE_FIRST_2H,2) ?>/hour</strong> (first 2 hours, rounded up).</p>
        <p>More than 2 hours late: <strong>RM <?= number_format($RATE_AFTER_2H,2) ?>/hour</strong> (rounded up).</p>
    </div>

    <div class="summary">
        <div class="card">
            <h3>📊 Total Rentals</h3>
            <span><?= $totalRentals ?></span>
        </div>

        <div class="card">
            <h3>☑️ Completed</h3>
            <span><?= $completed ?></span>
        </div>

        <div class="card">
            <h3>💲 Unpaid Penalties</h3>
            <span>RM <?= number_format($totalPenalties, 2) ?></span>
        </div>
    </div>

    <?php if ($totalPenalties > 0): ?>
      <div style="margin-bottom:18px;">
        <a class="view-btn" href="pay-penalty.php">Pay Penalty</a>
      </div>
    <?php endif; ?>

    <div class="section-title">Active Rental</div>

    <?php if ($active): ?>
      <div class="active-rental border-left">
          <div>
              <h3><?= htmlspecialchars($active['bike_name']) ?></h3>
              <p>Started: <?= htmlspecialchars($active['start_time']) ?></p>
              <p>Expected Return: <?= htmlspecialchars($active['expected_return_time']) ?></p>
              <p>Planned Duration: <?= (int)$active['planned_minutes'] ?> minutes</p>
          </div>
          <a href="active-rental.php" class="view-btn">View Details</a>
      </div>
    <?php else: ?>
      <div class="active-rental border-left">
          <div>
              <h3>No Active Rental</h3>
              <p>You have no ongoing rental.</p>
          </div>
          <a href="available-bikes.php" class="view-btn">Rent a Bike</a>
      </div>
    <?php endif; ?>

    <div class="section-title">Rental History</div>

    <div class="table-container">
        <table>
            <tr>
                <th>Bike</th>
                <th>Start Time</th>
                <th>Return Time</th>
                <th>Duration</th>
                <th>Penalty</th>
                <th>Status</th>
            </tr>

            <?php if (empty($history)): ?>
              <tr>
                  <td colspan="6">No rental history yet.</td>
              </tr>
            <?php endif; ?>

            <?php foreach ($history as $row): ?>
              <?php
                $penAmt = (float)$row['penalty_amount'];
                $lateMinAfterGrace = (int)$row['penalty_minutes_late']; // stored AFTER grace in return-bike.php
                $breakdown = ($penAmt > 0)
                  ? penalty_breakdown($lateMinAfterGrace, $RATE_FIRST_2H, $RATE_AFTER_2H)
                  : "No penalty (on time / within grace).";
              ?>
              <tr>
                  <td><?= htmlspecialchars($row['bike_name']) ?></td>
                  <td><?= htmlspecialchars($row['start_time']) ?></td>
                  <td><?= htmlspecialchars($row['return_time'] ?? '-') ?></td>
                  <td><?= (int)$row['duration_minutes'] ?> min</td>
                  <td>
                      RM <?= number_format($penAmt, 2) ?>
                      <?php if ($penAmt > 0): ?>
                        (<?= htmlspecialchars($row['penalty_status']) ?>)
                        <div style="margin-top:6px; font-size:13px; color:#6091D4;">
                          <?= htmlspecialchars($breakdown) ?>
                        </div>
                      <?php else: ?>
                        <div style="margin-top:6px; font-size:13px; color:#6091D4;">
                          <?= htmlspecialchars($breakdown) ?>
                        </div>
                      <?php endif; ?>
                  </td>
                  <td>
                      <?php if ($row['status'] === 'late'): ?>
                          <span class="status rented">Late</span>
                      <?php else: ?>
                          <span class="status available">On Time</span>
                      <?php endif; ?>
                  </td>
              </tr>
            <?php endforeach; ?>
        </table>
    </div>

</div>

</body>
</html>
