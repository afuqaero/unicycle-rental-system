<?php
require_once "config.php";
require_once "guard_active_rental.php";

$student_id = 1;

// submit complaint
$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rental_id = isset($_POST['rental_id']) ? (int)$_POST['rental_id'] : 0;
    $message   = trim($_POST['message'] ?? "");

    if ($message === "") {
        $error = "Message is required.";
    } else {
        $code = "COMP" . str_pad((string)random_int(1, 999999), 6, "0", STR_PAD_LEFT);

        $stmt = $pdo->prepare("
          INSERT INTO complaints (complaint_code, student_id, rental_id, message, status)
          VALUES (?, ?, ?, ?, 'open')
        ");
        $stmt->execute([$code, $student_id, $rental_id > 0 ? $rental_id : null, $message]);

        header("Location: complaints.php");
        exit;
    }
}

// rentals list for dropdown (completed/late only)
$stmt = $pdo->prepare("
  SELECT r.rental_id, r.start_time, b.bike_name
  FROM rentals r
  JOIN bikes b ON b.bike_id = r.bike_id
  WHERE r.student_id=? AND r.status IN ('completed','late')
  ORDER BY r.start_time DESC
");
$stmt->execute([$student_id]);
$rentals = $stmt->fetchAll();

// complaints list
$stmt = $pdo->prepare("
  SELECT c.complaint_code, c.created_at, c.message, c.status,
         r.rental_code, b.bike_name
  FROM complaints c
  LEFT JOIN rentals r ON r.rental_id = c.rental_id
  LEFT JOIN bikes b ON b.bike_id = r.bike_id
  WHERE c.student_id=?
  ORDER BY c.created_at DESC
");
$stmt->execute([$student_id]);
$complaints = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Bicycle Rental System – Complaints</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="style.css">
</head>

<body>

<div class="sidebar">
    <h2>BikeRental</h2>

    <div class="menu">
        <a href="dashboard.php">Dashboard</a>
        <a href="available-bikes.php">Available Bikes</a>
        <a href="rental-summary.php">Rental Summary</a>
        <a href="complaints.php" class="active">Complaints</a>
    </div>

    <div class="logout">Logout</div>
</div>

<div class="main">

    <div class="header" style="display: flex; align-items: center; margin-bottom: 30px;">
    <div>
        <h1>Complaints</h1>
        <p>Submit and track your complaints</p>
    </div>
    <button onclick="openModal()" style="margin-left: auto;">💬 Lodge Complaint</button>
</div>


    <?php if (!empty($error)): ?>
        <div class="card" style="border-color:#FFDADA;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($complaints)): ?>
        <div class="card">
            <h3>No complaints yet</h3>
            <p>You can lodge a complaint after a completed rental.</p>
        </div>
    <?php endif; ?>

    <?php foreach ($complaints as $c): ?>
        <div class="complaint-card">
            <div class="complaint-header">
                <div>
                    <h3>Complaint #<?= htmlspecialchars($c['complaint_code']) ?></h3>
                    <p>
                      Rental:
                      <?= htmlspecialchars($c['bike_name'] ?? 'N/A') ?>
                      <?= $c['rental_code'] ? "(" . htmlspecialchars($c['rental_code']) . ")" : "" ?>
                    </p>
                    <p>Date: <?= htmlspecialchars($c['created_at']) ?></p>
                </div>
                <span class="status <?= $c['status'] === 'resolved' ? 'available' : 'maintenance' ?>">
                    <?= $c['status'] === 'resolved' ? 'Resolved' : 'Open' ?>
                </span>
            </div>

            <div class="message">
                <?= nl2br(htmlspecialchars($c['message'])) ?>
            </div>
        </div>
    <?php endforeach; ?>

</div>

<!-- MODAL -->
<div class="modal" id="complaintModal">
    <div class="modal-box">
        <h2>Lodge a Complaint</h2>

        <form method="post" action="complaints.php">
            <div class="form-group">
                <label>Select Rental</label>
                <select name="rental_id">
                    <option value="0">Choose a rental...</option>
                    <?php foreach ($rentals as $r): ?>
                        <option value="<?= (int)$r['rental_id'] ?>">
                            <?= htmlspecialchars($r['bike_name']) ?> - <?= htmlspecialchars($r['start_time']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Complaint Message</label>
                <textarea name="message" placeholder="Describe your issue in detail..." required></textarea>
            </div>

            <div class="modal-actions">
                <button type="button" class="cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" class="submit">Submit Complaint</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById("complaintModal").classList.add("active");
}
function closeModal() {
    document.getElementById("complaintModal").classList.remove("active");
}
</script>

</body>
</html>
