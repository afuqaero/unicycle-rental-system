<?php
require_once "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['active_rental_id'])) {
    header("Location: available-bikes.php");
    exit;
}

$rental_id = (int)$_SESSION['active_rental_id'];

$stmt = $pdo->prepare("
  SELECT r.rental_id, r.start_time, r.expected_return_time,
         b.bike_name, b.location
  FROM rentals r
  JOIN bikes b ON b.bike_id = r.bike_id
  WHERE r.rental_id=? AND r.status='active'
  LIMIT 1
");
$stmt->execute([$rental_id]);
$r = $stmt->fetch();

if (!$r) {
    unset($_SESSION['active_rental_id']);
    header("Location: available-bikes.php");
    exit;
}

$expected_ts = strtotime($r['expected_return_time']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Active Rental</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="main" style="width:100%;">
  <h1>Active Rental</h1>
  <p class="sub">You cannot access other pages until you return the bike.</p>

  <div class="card" style="max-width:560px;">
    <p><strong>Bike:</strong> <?= htmlspecialchars($r['bike_name']) ?></p>
    <p><strong>Location:</strong> <?= htmlspecialchars($r['location'] ?? 'Main Bike Area') ?></p>
    <p><strong>Started:</strong> <?= htmlspecialchars($r['start_time']) ?></p>
    <p><strong>Expected Return:</strong> <?= htmlspecialchars($r['expected_return_time']) ?></p>

    <p style="margin-top:14px;"><strong>Time Remaining</strong></p>
    <h2 id="countdown" style="margin-top:8px;">--:--:--</h2>

    <!-- Return goes to condition report form -->
    <form action="return-form.php" method="get" style="margin-top:16px;">
      <button type="submit">Return Bike</button>
    </form>
  </div>
</div>

<script>
const expected = <?= (int)$expected_ts ?> * 1000;

function tick(){
  const now = Date.now();
  let diff = expected - now;
  if (diff < 0) diff = 0;

  const h = Math.floor(diff / 3600000);
  diff %= 3600000;
  const m = Math.floor(diff / 60000);
  diff %= 60000;
  const s = Math.floor(diff / 1000);

  document.getElementById("countdown").textContent =
    String(h).padStart(2,'0') + ":" + String(m).padStart(2,'0') + ":" + String(s).padStart(2,'0');
}

tick();
setInterval(tick, 1000);
</script>

</body>
</html>
