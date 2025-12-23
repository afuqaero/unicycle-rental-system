<?php
require_once "config.php";
require_once "guard_active_rental.php";

$student_id = 1;

// unpaid penalties block renting
$stmt = $pdo->prepare("
  SELECT COUNT(*)
  FROM penalties p
  JOIN rentals r ON r.rental_id = p.rental_id
  WHERE r.student_id = ? AND p.status = 'unpaid'
");
$stmt->execute([$student_id]);
$hasUnpaidPenalty = ((int)$stmt->fetchColumn() > 0);

// fetch bikes (NO stations table)
$sql = "
SELECT 
  bike_id,
  bike_name,
  status,
  last_maintained_date,
  location
FROM bikes
ORDER BY bike_id
";
$bikes = $pdo->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Available Bikes</title>
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
    <h1>Available Bikes</h1>
    <p class="sub">Browse and rent bikes across campus</p>

    <?php if ($hasUnpaidPenalty): ?>
      <div class="card" style="border-color:#FFDADA; margin:20px 0;">
        <strong>⚠ Rental Blocked</strong><br>
        You have unpaid penalties. Please settle them before renting a bike.
      </div>
    <?php endif; ?>

    <div class="filters">
        <button class="active" onclick="filterBikes('all', this)">All</button>
        <button onclick="filterBikes('available', this)">Available</button>
        <button onclick="filterBikes('pending', this)">Pending</button>
        <button onclick="filterBikes('maintenance', this)">Maintenance</button>
        <button onclick="filterBikes('rented', this)">Rented</button>
    </div>

    <div class="bikes">
        <?php if (empty($bikes)): ?>
            <div class="card">
                <h3>No bikes found</h3>
                <p>Insert bikes into <strong>bikes</strong> table.</p>
            </div>
        <?php endif; ?>

        <?php foreach ($bikes as $bike): ?>
            <?php
                $status = $bike['status']; // available | rented | maintenance | pending
                $location = $bike['location'] ?? 'Main Bike Area';
                $maint = $bike['last_maintained_date'] ?? '-';

                if ($status === 'available') {
                    $statusText = "Available";
                    $statusClass = "available";
                } elseif ($status === 'pending') {
                    $statusText = "Pending";
                    $statusClass = "pending";
                } elseif ($status === 'maintenance') {
                    $statusText = "Under Maintenance";
                    $statusClass = "maintenance";
                } else {
                    $statusText = "Not Available";
                    $statusClass = "rented";
                }
            ?>

            <div class="bike-card" data-status="<?= htmlspecialchars($status) ?>">
            <div class="bike-header">
                <h3><?= htmlspecialchars($bike['bike_name']) ?></h3>
                <span class="status <?= $statusClass ?>"><?= $statusText ?></span>
            </div>

            <p>📍 <?= htmlspecialchars($location) ?></p>
            <p>🔧 Last maintained: <?= htmlspecialchars($maint) ?></p>

            <div class="button-wrapper">
                <?php if ($status === 'available' && !$hasUnpaidPenalty): ?>
                    <a class="view-btn" href="rent-instructions.php?bike_id=<?= (int)$bike['bike_id'] ?>">Rent Now</a>
                <?php elseif ($status === 'available' && $hasUnpaidPenalty): ?>
                    <button disabled>Penalty Pending</button>
                <?php elseif ($status === 'pending'): ?>
                    <button disabled>Pending</button>
                <?php elseif ($status === 'maintenance'): ?>
                    <button disabled>Maintenance</button>
                <?php else: ?>
                    <button disabled>Unavailable</button>
                <?php endif; ?>
            </div>
        </div>

        <?php endforeach; ?>
    </div>
</div>

<script>
function filterBikes(status, btn) {
    const cards = document.querySelectorAll('.bike-card');
    const buttons = document.querySelectorAll('.filters button');

    buttons.forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    cards.forEach(card => {
        card.style.display = (status === 'all' || card.dataset.status === status) ? "" : "none";
    });
}
</script>

</body>
</html>
