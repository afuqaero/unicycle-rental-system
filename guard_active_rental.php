<?php
// guard_active_rental.php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!empty($_SESSION['active_rental_id'])) {
    // allow active rental & return endpoints only
    $allowed = ['active-rental.php', 'return-bike.php'];
    $current = basename($_SERVER['PHP_SELF']);

    if (!in_array($current, $allowed, true)) {
        header("Location: active-rental.php");
        exit;
    }
}
