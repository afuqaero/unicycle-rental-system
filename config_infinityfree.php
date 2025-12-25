<?php
// Set Malaysian timezone (UTC+8)
date_default_timezone_set('Asia/Kuala_Lumpur');

$host = "sql100.infinityfree.com";
$port = 3306;
$db = "if0_40742282_unicycle_db";
$user = "if0_40742282";
$pass = "airbus1818";

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed.");
}
