<?php
require_once "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();

$student_id = 1; // no login yet

$rental_id = isset($_POST['rental_id']) ? (int)$_POST['rental_id'] : 0;
if ($rental_id <= 0) die("Invalid request.");

if (empty($_SESSION['active_rental_id']) || (int)$_SESSION['active_rental_id'] !== $rental_id) {
    die("Unauthorized action.");
}

// Optional Bike Condition Report
$condition = trim($_POST['condition_status'] ?? ""); // good | minor_issue | needs_repair | ""
$note      = trim($_POST['note'] ?? "");

$allowed = ["", "good", "minor_issue", "needs_repair"];
if (!in_array($condition, $allowed, true)) die("Invalid condition.");

// Penalty rules
$GRACE_MINUTES = 10;
$RATE_FIRST_2H = 5.00;
$RATE_AFTER_2H = 10.00;

$pdo->beginTransaction();

try {
    // lock rental row
    $stmt = $pdo->prepare("
      SELECT rental_id, bike_id, expected_return_time
      FROM rentals
      WHERE rental_id=? AND status='active'
      FOR UPDATE
    ");
    $stmt->execute([$rental_id]);
    $r = $stmt->fetch();
    if (!$r) throw new Exception("Active rental not found.");

    $bike_id = (int)$r['bike_id'];

    // Close rental
    $now_ts = time();
    $now_dt = date("Y-m-d H:i:s", $now_ts);

    $expected_ts = strtotime($r['expected_return_time']);
    $late_minutes_raw = (int)ceil(($now_ts - $expected_ts) / 60);
    $late_minutes = max(0, $late_minutes_raw);

    // apply grace
    $late_minutes_after_grace = max(0, $late_minutes - $GRACE_MINUTES);

    $rental_status = ($late_minutes_after_grace > 0) ? 'late' : 'completed';

    $stmt = $pdo->prepare("UPDATE rentals SET return_time=?, status=? WHERE rental_id=?");
    $stmt->execute([$now_dt, $rental_status, $rental_id]);

    // Save condition report (only if filled)
    if ($condition !== "" || $note !== "") {
        $stmt = $pdo->prepare("
          INSERT INTO bike_feedback (rental_id, bike_id, student_id, condition_status, note)
          VALUES (?, ?, ?, NULLIF(?,''), NULLIF(?,'')) 
        ");
        $stmt->execute([$rental_id, $bike_id, $student_id, $condition, $note]);
    }

    // Bike status update (minor_issue/needs_repair -> pending, else available)
    $needsReview = ($condition === "minor_issue" || $condition === "needs_repair");
    $newBikeStatus = $needsReview ? "pending" : "available";
    $stmt = $pdo->prepare("UPDATE bikes SET status=? WHERE bike_id=?");
    $stmt->execute([$newBikeStatus, $bike_id]);

    // Penalty calculation (only if late after grace)
    if ($late_minutes_after_grace > 0) {
        $late_hours = (int)ceil($late_minutes_after_grace / 60);

        if ($late_hours <= 2) {
            $penalty_amount = $late_hours * $RATE_FIRST_2H;
        } else {
            // first 2 hours at RM5 + remaining at RM10
            $penalty_amount = (2 * $RATE_FIRST_2H) + (($late_hours - 2) * $RATE_AFTER_2H);
        }

        $stmt = $pdo->prepare("
          INSERT INTO penalties (rental_id, minutes_late, amount, status)
          VALUES (?, ?, ?, 'unpaid')
          ON DUPLICATE KEY UPDATE
            minutes_late=VALUES(minutes_late),
            amount=VALUES(amount),
            status='unpaid'
        ");
        $stmt->execute([$rental_id, $late_minutes_after_grace, $penalty_amount]);
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    die("Return failed: " . $e->getMessage());
}

unset($_SESSION['active_rental_id']);
header("Location: rental-summary.php");
exit;
