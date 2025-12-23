<?php
require_once "config.php";
require_once "guard_active_rental.php";

$student_id = 1;

$penalty_id = isset($_POST['penalty_id']) ? (int)$_POST['penalty_id'] : 0;
if ($penalty_id <= 0) die("Invalid request.");

// security: ensure penalty belongs to this student
$stmt = $pdo->prepare("
  SELECT p.penalty_id
  FROM penalties p
  JOIN rentals r ON r.rental_id = p.rental_id
  WHERE p.penalty_id = ? AND r.student_id = ? AND p.status='unpaid'
  LIMIT 1
");
$stmt->execute([$penalty_id, $student_id]);
$ok = $stmt->fetch();

if (!$ok) die("Penalty not found / already paid.");

// mark paid
$stmt = $pdo->prepare("UPDATE penalties SET status='paid' WHERE penalty_id=?");
$stmt->execute([$penalty_id]);

header("Location: pay-penalty.php");
exit;
