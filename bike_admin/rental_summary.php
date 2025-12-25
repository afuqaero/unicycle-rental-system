<?php
include 'db.php';
session_start();

$user_id = $_SESSION['user_id'] ?? 1;

/* TOTAL RENTALS */
$totalRentals = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT COUNT(*) total FROM rentals WHERE user_id=$user_id
"))['total'];

/* COMPLETED */
$completed = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT COUNT(*) completed FROM rentals
    WHERE user_id=$user_id AND status='Completed'
"))['completed'];

/* TOTAL PENALTY */
$totalPenalty = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT IFNULL(SUM(penalty),0) penalty FROM rentals WHERE user_id=$user_id
"))['penalty'];

/* ACTIVE RENTAL */
$activeRental = mysqli_query($conn,"
    SELECT r.*, b.bike_code
    FROM rentals r
    JOIN bikes b ON r.bike_id=b.bike_id
    WHERE r.user_id=$user_id AND r.status='Active'
    LIMIT 1
");

/* HISTORY */
$history = mysqli_query($conn,"
    SELECT r.*, b.bike_code
    FROM rentals r
    JOIN bikes b ON r.bike_id=b.bike_id
    WHERE r.user_id=$user_id
    ORDER BY r.start_time DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Rental Summary</title>
<meta charset="UTF-8">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
*{box-sizing:border-box;font-family:'Segoe UI',sans-serif}
body{margin:0;background:#f4f6fb;display:flex}

/* SIDEBAR */
.sidebar{
    width:240px;
    background:linear-gradient(180deg,#2b2e9f,#1f1c6b);
    height:100vh;
    padding:22px;
    position:fixed;
    display:flex;
    flex-direction:column;
    color:#fff;
}
.sidebar h2{
    display:flex;
    align-items:center;
    gap:10px;
    margin-bottom:35px;
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
.sidebar a i{width:20px;text-align:center}
.sidebar a:hover,
.sidebar a.active{
    background:rgba(255,255,255,.15);
    color:#fff;
}
.logout{
    margin-top:auto;
    background:rgba(255,255,255,.12);
}

/* MAIN */
.main{
    margin-left:260px;
    padding:35px;
    width:calc(100% - 260px);
}
h1{margin:0}
.subtitle{color:#666;margin:6px 0 30px}

/* CARDS */
.cards{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:20px;
}
.card{
    background:#fff;
    border-radius:18px;
    padding:22px;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
}
.card span{font-size:13px;color:#666}
.card h2{margin-top:10px}

/* ACTIVE RENTAL */
.section-title{margin:35px 0 15px}
.active-box{
    background:#fff;
    border-left:6px solid #2b2e9f;
    border-radius:16px;
    padding:22px;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
}

/* TABLE */
table{
    width:100%;
    background:#fff;
    border-radius:16px;
    border-collapse:collapse;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
    overflow:hidden;
}
th,td{padding:14px;text-align:left}
th{background:#f2f4fa}
tr:not(:last-child) td{border-bottom:1px solid #eee}

/* STATUS */
.badge{
    padding:6px 14px;
    border-radius:20px;
    font-size:13px;
    font-weight:600;
}
.badge.ok{background:#dff7e3;color:#1a7f37}
.badge.active{background:#e0e9ff;color:#1d3cff}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <h2><i class="fa-solid fa-bicycle"></i> BikeRental</h2>

    <a href="dashboard.php">
        <i class="fa-solid fa-gauge"></i> Dashboard
    </a>

    <a href="bikes.php">
        <i class="fa-solid fa-bicycle"></i> Available Bikes
    </a>

    <a href="rental_summary.php" class="active">
        <i class="fa-solid fa-clock-rotate-left"></i> Rental Summary
    </a>

    <a href="complaints.php">
        <i class="fa-solid fa-comment-dots"></i> Complaints
    </a>

    <a href="logout.php" class="logout">
        <i class="fa-solid fa-right-from-bracket"></i> Logout
    </a>
</div>

<!-- MAIN -->
<div class="main">
    <h1>Rental Summary</h1>
    <p class="subtitle">View your rental history and penalties</p>

    <!-- CARDS -->
    <div class="cards">
        <div class="card">
            <span>Total Rentals</span>
            <h2><?= $totalRentals ?></h2>
        </div>
        <div class="card">
            <span>Completed</span>
            <h2><?= $completed ?></h2>
        </div>
        <div class="card">
            <span>Total Penalties</span>
            <h2>RM <?= number_format($totalPenalty,2) ?></h2>
        </div>
    </div>

    <!-- ACTIVE RENTAL -->
    <h3 class="section-title">Active Rental</h3>
    <?php if(mysqli_num_rows($activeRental)>0):
        $a=mysqli_fetch_assoc($activeRental);
        $duration = round((time()-strtotime($a['start_time']))/60);
    ?>
    <div class="active-box">
        <strong><?= $a['bike_code'] ?></strong><br><br>
        Started: <?= date("d/m/Y, h:i:s A",strtotime($a['start_time'])) ?><br>
        Duration: <?= $duration ?> minutes
    </div>
    <?php else: ?>
        <p>No active rental.</p>
    <?php endif; ?>

    <!-- HISTORY -->
    <h3 class="section-title">Rental History</h3>
    <table>
        <tr>
            <th>Bike</th>
            <th>Start Time</th>
            <th>Return Time</th>
            <th>Duration</th>
            <th>Cost</th>
            <th>Penalty</th>
            <th>Status</th>
        </tr>

        <?php while($r=mysqli_fetch_assoc($history)):
            $dur = $r['end_time']
                ? round((strtotime($r['end_time'])-strtotime($r['start_time']))/60)
                : '-';
        ?>
        <tr>
            <td><?= $r['bike_code'] ?></td>
            <td><?= date("d/m/Y, h:i:s A",strtotime($r['start_time'])) ?></td>
            <td><?= $r['end_time'] ? date("d/m/Y, h:i:s A",strtotime($r['end_time'])) : '-' ?></td>
            <td><?= $dur ?> min</td>
            <td>RM <?= number_format($r['cost'],2) ?></td>
            <td>RM <?= number_format($r['penalty'],2) ?></td>
            <td>
                <span class="badge <?= $r['status']=='Completed'?'ok':'active' ?>">
                    <?= $r['status'] ?>
                </span>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

</body>
</html>
