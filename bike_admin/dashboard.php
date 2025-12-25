<?php
include 'db.php';
session_start();

/* FALLBACK USER */
$username = "Admin";

/* DASHBOARD COUNTS */
$available_bikes = mysqli_fetch_assoc(
    mysqli_query($conn,"SELECT COUNT(*) total FROM bikes WHERE status='Available'")
)['total'];

$active_rentals = mysqli_fetch_assoc(
    mysqli_query($conn,"SELECT COUNT(*) total FROM rentals WHERE status='Active'")
)['total'];

$total_rentals = mysqli_fetch_assoc(
    mysqli_query($conn,"SELECT COUNT(*) total FROM rentals")
)['total'];
?>

<!DOCTYPE html>
<html>
<head>
<title>Bike Rental Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- FONT AWESOME ICONS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
*{
    box-sizing:border-box;
    font-family:'Segoe UI',sans-serif;
}
body{
    margin:0;
    background:#f4f6f9;
    display:flex;
}

/* SIDEBAR */
.sidebar{
    width:240px;
    background:linear-gradient(180deg,#2b2e9f,#1f1c6b);
    color:#fff;
    height:100vh;
    padding:22px;
    display:flex;
    flex-direction:column;
}
.sidebar h2{
    margin-bottom:35px;
    display:flex;
    align-items:center;
    gap:10px;
}
.sidebar a{
    display:flex;
    align-items:center;
    gap:14px;
    color:#e5e7ff;
    text-decoration:none;
    padding:12px 14px;
    border-radius:12px;
    margin-bottom:10px;
    transition:.25s;
}
.sidebar a i{
    width:20px;
    text-align:center;
}
.sidebar a:hover,
.sidebar a.active{
    background:rgba(255,255,255,.15);
    color:#fff;
}

/* LOGOUT */
.logout{
    margin-top:auto;
    background:rgba(255,255,255,.12);
}

/* MAIN */
.main{
    flex:1;
    padding:35px;
}

/* HEADER */
.welcome h1{
    margin:0;
    font-size:28px;
}
.welcome p{
    color:#666;
    margin-top:6px;
}

/* STATS CARDS */
.cards{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(240px,1fr));
    gap:22px;
    margin-top:35px;
}
.card{
    background:#fff;
    padding:24px;
    border-radius:20px;
    box-shadow:0 12px 30px rgba(0,0,0,.08);
    position:relative;
}
.card i{
    position:absolute;
    right:20px;
    top:20px;
    font-size:28px;
    color:#e0e4ff;
}
.card h3{
    margin:0;
    font-size:16px;
    color:#555;
}
.card .number{
    font-size:34px;
    margin-top:10px;
    color:#2b2e9f;
    font-weight:700;
}

/* ACTION BOXES */
.actions{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(320px,1fr));
    gap:22px;
    margin-top:35px;
}
.action-box{
    padding:28px;
    border-radius:22px;
    color:#fff;
    position:relative;
}
.action-box i{
    position:absolute;
    right:25px;
    top:25px;
    font-size:34px;
    opacity:.25;
}
.blue{
    background:linear-gradient(135deg,#2b2e9f,#1f1c6b);
}
.red{
    background:linear-gradient(135deg,#b33939,#8e1f1f);
}
.action-box h2{
    margin-top:0;
}
.action-box a{
    display:inline-block;
    margin-top:15px;
    color:#fff;
    font-weight:600;
    text-decoration:none;
}
.action-box a:hover{
    text-decoration:underline;
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <h2><i class="fa-solid fa-bicycle"></i> BikeRental</h2>

    <a href="dashboard.php" class="active">
        <i class="fa-solid fa-gauge"></i> Dashboard
    </a>

    <a href="bikes.php">
        <i class="fa-solid fa-bicycle"></i> Available Bikes
    </a>

    <a href="rental_summary.php">
        <i class="fa-solid fa-clock-rotate-left"></i> Rental Summary
    </a>

    <a href="complaints.php">
        <i class="fa-solid fa-comment-dots"></i> Complaints
    </a>

    <!-- LOGOUT -->
    <a href="logout.php" class="logout">
        <i class="fa-solid fa-right-from-bracket"></i> Logout
    </a>
</div>

<!-- MAIN CONTENT -->
<div class="main">

<div class="welcome">
    <h1>Welcome back, <?= $username ?> 👋</h1>
    <p>Here's your rental overview</p>
</div>

<div class="cards">
    <div class="card">
        <i class="fa-solid fa-bicycle"></i>
        <h3>Available Bikes</h3>
        <div class="number"><?= $available_bikes ?></div>
    </div>

    <div class="card">
        <i class="fa-solid fa-bolt"></i>
        <h3>Active Rentals</h3>
        <div class="number"><?= $active_rentals ?></div>
    </div>

    <div class="card">
        <i class="fa-solid fa-chart-line"></i>
        <h3>Total Rentals</h3>
        <div class="number"><?= $total_rentals ?></div>
    </div>
</div>

<div class="actions">
    <div class="action-box blue">
        <i class="fa-solid fa-bicycle"></i>
        <h2>Browse Bikes</h2>
        <p>Check bikes available on campus</p>
        <a href="bikes.php">View Bikes →</a>
    </div>

    <div class="action-box red">
        <i class="fa-solid fa-clock-rotate-left"></i>
        <h2>Rental History</h2>
        <p>View all past and active rentals</p>
        <a href="rental_summary.php">View History →</a>
    </div>
</div>

</div>

</body>
</html>
