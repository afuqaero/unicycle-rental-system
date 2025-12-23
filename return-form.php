<?php
require_once "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['active_rental_id'])) {
    header("Location: available-bikes.php");
    exit;
}

$rental_id = (int)$_SESSION['active_rental_id'];

$stmt = $pdo->prepare("
  SELECT r.rental_id, r.bike_id, b.bike_name
  FROM rentals r
  JOIN bikes b ON b.bike_id = r.bike_id
  WHERE r.rental_id=? AND r.status='active'
  LIMIT 1
");
$stmt->execute([$rental_id]);
$r = $stmt->fetch();
if (!$r) { header("Location: available-bikes.php"); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Return Bike</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="main" style="width:100%;">
  <h1>Return Bike</h1>
  <p class="sub">Bike Condition Report (optional). Complaints are separate.</p>

  <div class="card" style="max-width:560px;">
    <p><strong>Bike:</strong> <?= htmlspecialchars($r['bike_name']) ?></p>

    <form action="return-bike.php" method="post"
          onsubmit="return confirm('Confirm return? The bike will be updated based on your condition report.');"
          style="margin-top:14px;">

      <input type="hidden" name="rental_id" value="<?= (int)$rental_id ?>">

      <div class="form-group">
        <label>Bike Condition (optional)</label>
        <select name="condition_status">
          <option value="">Choose...</option>
          <option value="good">Good</option>
          <option value="minor_issue">Minor Issue</option>
          <option value="needs_repair">Needs Repair</option>
        </select>
      </div>

      <div class="form-group">
        <label>Note (optional)</label>
        <textarea name="note" placeholder="Example: brakes slightly loose, chain noisy..."></textarea>
      </div>

      <div class="modal-actions" style="justify-content:flex-start;">
        <button type="submit">Confirm Return</button>
        <a class="view-btn" style="background:#6091D4" href="active-rental.php">Back</a>
      </div>
    </form>
  </div>
</div>

</body>
</html>
