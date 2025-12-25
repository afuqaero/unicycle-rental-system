<?php
/**
 * Admin Logout
 * Clear session and redirect to main login
 */
session_start();
session_destroy();
header('Location: ../login.php');
exit;
