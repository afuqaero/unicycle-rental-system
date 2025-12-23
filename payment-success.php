<?php
require_once "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();

$rental_id = isset($_POST['rental_id']) ? (int)$_POST['rental_id'] : 0;
$amount    = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;

if ($rental_id <= 0) die("Invalid.");

$stmt = $pdo->prepare("INSERT INTO payments (rental_id, amount, method, status) VALUES (?, ?, 'cashless', 'paid')");
$stmt->execute([$rental_id, $amount]);

$_SESSION['active_rental_id'] = $rental_id;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payment Success</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="main" style="width:100%;">
  <h1>Transaction Successful ✅</h1>
  <p class="sub">Redirecting to Active Rental...</p>
</div>

<script>
setTimeout(() => { window.location.href = "active-rental.php"; }, 1200);
</script>

</body>
</html>
