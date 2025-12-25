<?php
/**
 * Admin Authentication Check
 * Include at the top of every admin page to ensure only admins can access
 */
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    // Regular user trying to access admin area
    header('Location: ../dashboard.php');
    exit;
}

// Include database config (go up from includes/ to admin/, then to webproject root)
require_once dirname(__DIR__) . '/../config.php';
