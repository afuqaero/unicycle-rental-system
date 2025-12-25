<?php
include 'db.php';

/* TOTAL REVENUE */
$totalResult = mysqli_query($conn, "SELECT SUM(amount) AS total FROM revenue");
$totalRow = mysqli_fetch_assoc($totalResult);
$totalRevenue = $totalRow['total'] ?? 0;

/* FETCH REVENUE RECORDS */
$revenue = mysqli_query($conn, "SELECT * FROM revenue ORDER BY date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Revenue Management</title>

<style>
/* ===============================
   GLOBAL RESET
================================ */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
}

/* ===============================
   BODY
================================ */
body {
    background-color: #FFFFFF;
    color: #161616;
    display: flex;
    min-height: 100vh;
}

/* ===============================
   SIDEBAR
================================ */
.sidebar {
    width: 240px;
    height: 100vh;
    background-color: #004EBA;
    padding: 20px;
    position: fixed;
}

.sidebar h2 {
    color: #FFFFFF;
    text-align: center;
    margin-bottom: 30px;
}

.sidebar a {
    display: block;
    color: #FFFFFF;
    text-decoration: none;
    padding: 12px 15px;
    margin-bottom: 12px;
    border-radius: 8px;
    transition: 0.3s;
    font-weight: 500;
}

.sidebar a:hover {
    background-color: #6091D4;
    transform: translateX(6px);
}

/* ===============================
   MAIN CONTENT
================================ */
.main {
    margin-left: 260px;
    padding: 30px;
    width: calc(100% - 260px);
}

.main h1 {
    color: #004EBA;
    margin-bottom: 20px;
    font-size: 28px;
}

/* ===============================
   TOTAL REVENUE CARD
================================ */
.revenue-card {
    background-color: #FFFFFF;
    border-radius: 18px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    border-left: 8px solid #6091D4;
}

.revenue-card h2 {
    color: #6091D4;
    margin-bottom: 10px;
    font-size: 18px;
}

.revenue-card p {
    font-size: 36px;
    font-weight: bold;
    color: #004EBA;
}

/* ===============================
   TABLE CARD
================================ */
.table-card {
    background-color: #FFFFFF;
    padding: 25px;
    border-radius: 16px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.1);
}

/* ===============================
   TABLE
================================ */
table {
    width: 100%;
    border-collapse: collapse;
}

th {
    background-color: #004EBA;
    color: #FFFFFF;
    padding: 14px;
    text-align: left;
    font-size: 15px;
}

td {
    padding: 14px;
    border-bottom: 1px solid #6091D4;
}

tr:hover {
    background-color: #6091D4;
    color: #FFFFFF;
    transition: 0.3s;
}

/* ===============================
   AMOUNT STYLE
================================ */
.amount {
    font-weight: bold;
    color: #004EBA;
}

/* ===============================
   RESPONSIVE
================================ */
@media (max-width: 768px) {
    .sidebar {
        width: 200px;
    }
    .main {
        margin-left: 220px;
    }
}
</style>

</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="dashboard.php">Dashboard</a>
    <a href="revenue.php">Revenue</a>
</div>

<!-- MAIN CONTENT -->
<div class="main">

    <h1>💰 Revenue Management</h1>

    <!-- TOTAL REVENUE -->
    <div class="revenue-card">
        <h2>Total Revenue</h2>
        <p>RM <?php echo number_format($totalRevenue, 2); ?></p>
    </div>

    <!-- REVENUE TABLE -->
    <div class="table-card">
        <table>
            <tr>
                <th>Revenue ID</th>
                <th>Rental ID</th>
                <th>Amount</th>
                <th>Date</th>
            </tr>

            <?php while ($r = mysqli_fetch_assoc($revenue)) { ?>
            <tr>
                <td><?php echo $r['revenue_id']; ?></td>
                <td><?php echo $r['rental_id']; ?></td>
                <td class="amount">RM <?php echo number_format($r['amount'], 2); ?></td>
                <td><?php echo $r['date']; ?></td>
            </tr>
            <?php } ?>

        </table>
    </div>

</div>

</body>
</html>
