<?php
require_once "config.php";
require_once "guard_active_rental.php";


$student_id = 1; // no login yet

// counts
$availableBikes = (int)$pdo->query("SELECT COUNT(*) FROM bikes WHERE status='available'")->fetchColumn();
$maintenanceBikes = (int)$pdo->query("SELECT COUNT(*) FROM bikes WHERE status='maintenance'")->fetchColumn();
$unavailableBikes = (int)$pdo->query("SELECT COUNT(*) FROM bikes WHERE status='rented'")->fetchColumn();

$activeRentals = (int)$pdo->prepare("SELECT COUNT(*) FROM rentals WHERE student_id=? AND status='active'")
    ->execute([$student_id]) ?: 0; // avoid warning
$stmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE student_id=? AND status='active'");
$stmt->execute([$student_id]);
$activeRentals = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE student_id=?");
$stmt->execute([$student_id]);
$totalRentals = (int)$stmt->fetchColumn();

// latest active rental (if any)
$stmt = $pdo->prepare("
  SELECT r.rental_id, r.start_time, r.expected_return_time, r.planned_hours, b.bike_name
  FROM rentals r
  JOIN bikes b ON b.bike_id = r.bike_id
  WHERE r.student_id=? AND r.status='active'
  ORDER BY r.start_time DESC
  LIMIT 1
");
$stmt->execute([$student_id]);
$active = $stmt->fetch();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Bicycle Rental System</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="style.css">
</head>

<body>

<div class="sidebar">
    <h2>BikeRental</h2>

    <div class="menu">
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="available-bikes.php">Available Bikes</a>
        <a href="rental-summary.php">Rental Summary</a>
        <a href="complaints.php">Complaints</a>
    </div>

    <div class="logout">Logout</div>
</div>

<div class="main">

    <div class="header">
        <h1>Welcome back, Ahmad Firdaus!</h1>
        <p>Here's your rental overview</p>
    </div>

    <!-- Stats cards -->
    <div class="stats">
        <div class="card">
            <p class="emoji">🚲</p>
            <h3>Available Bikes</h3>
            <span><?= $availableBikes ?></span>
        </div>

        <div class="card">
            <p class="emoji">🛠️</p>
            <h3>Under Maintenance</h3>
            <span><?= $maintenanceBikes ?></span>
        </div>

        <div class="card">
            <p class="emoji">⛔</p>
            <h3>Unavailable Bikes</h3>
            <span><?= $unavailableBikes ?></span>
        </div>

        <div class="card">
            <p class="emoji">💸</p>
            <h3>Active Rentals</h3>
            <span><?= $activeRentals ?></span>
        </div>

        <div class="card">
            <p class="emoji">📊</p>
            <h3>Total Rentals</h3>
            <span><?= $totalRentals ?></span>
        </div>
    </div>

    <!-- Active Rental -->
    <?php if ($active): ?>
        <div class="active-rental border-left">
            <div>
                <h3><?= htmlspecialchars($active['bike_name']) ?></h3>
                <p>Started: <?= htmlspecialchars($active['start_time']) ?></p>
                <p>Expected Return: <?= htmlspecialchars($active['expected_return_time']) ?></p>
                <p>Planned Duration: <?= (int)$active['planned_hours'] * 60 ?> minutes</p>
            </div>
          <a href="active-rental.php" class="view-btn">View Details</a>
        </div>
    <?php else: ?>
        <div class="active-rental border-left">
            <p>No active rentals currently.</p>
        </div>
    <?php endif; ?>

    <!-- Buttons for browsing and history -->
    <div class="actions">
        <a href="available-bikes.php" class="action-card blue">
            <p class="emoji">🚲</p>
            <h3> Browse Bikes</h3>
            <p>Find and rent available bikes on campus</p>
        </a>
        <a href="rental-summary.php" class="action-card light">
            <p class="emoji">⏱</p>
            <h3> Rental History</h3>
            <p>View your past and current rentals</p>
        </a>
    </div>



</div>
