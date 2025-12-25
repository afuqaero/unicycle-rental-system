<?php
include 'db.php';
session_start();

/* ASSUME LOGGED IN USER */
$user_id = $_SESSION['user_id'] ?? 1;

/* =========================
   CONFIRM RENT
========================= */
if (isset($_POST['confirm_rent'])) {

    $bike_id  = $_POST['bike_id'];
    $duration = $_POST['duration'];

    $start_time = date("Y-m-d H:i:s");
    $end_time   = date("Y-m-d H:i:s", strtotime("+$duration minutes"));

    mysqli_query($conn,"
        INSERT INTO rentals (user_id, bike_id, start_time, end_time, status, penalty)
        VALUES ($user_id,$bike_id,'$start_time','$end_time','Active',0)
    ");

    mysqli_query($conn,"
        UPDATE bikes SET status='NotAvailable' WHERE bike_id=$bike_id
    ");

    header("Location: rental_summary.php");
    exit();
}

/* =========================
   UPDATE BIKE
========================= */
if (isset($_POST['update_bike'])) {
    mysqli_query($conn,"
        UPDATE bikes SET
            `condition`='{$_POST['condition']}',
            status='{$_POST['status']}'
        WHERE bike_id={$_POST['bike_id']}
    ");
    header("Location: bikes.php");
    exit();
}

/* =========================
   DELETE BIKE ✅
========================= */
if (isset($_POST['delete_bike'])) {
    mysqli_query($conn,"DELETE FROM bikes WHERE bike_id={$_POST['bike_id']}");
    header("Location: bikes.php");
    exit();
}

/* =========================
   ADD BIKE
========================= */
if (isset($_POST['add_bike'])) {
    mysqli_query($conn,"
        INSERT INTO bikes (bike_code,`condition`,status)
        VALUES ('{$_POST['bike_code']}','{$_POST['condition']}','{$_POST['status']}')
    ");
    header("Location: bikes.php");
    exit();
}

/* FILTER */
$filter = $_GET['filter'] ?? 'all';
$where = "";
if ($filter=='available') $where="WHERE status='Available'";
if ($filter=='maintenance') $where="WHERE status='Maintenance'";
if ($filter=='rented') $where="WHERE status='NotAvailable'";

$bikes = mysqli_query($conn,"SELECT * FROM bikes $where");

/* COUNTS */
$available = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM bikes WHERE status='Available'"))['c'];
$maintenance = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM bikes WHERE status='Maintenance'"))['c'];
$rented = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM bikes WHERE status='NotAvailable'"))['c'];
?>

<!DOCTYPE html>
<html>
<head>
<title>Bike Management</title>
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
.sidebar a:hover,
.sidebar a.active{
    background:rgba(255,255,255,.15);
    color:#fff;
}
.logout{margin-top:auto;background:rgba(255,255,255,.12)}

/* MAIN */
.main{
    margin-left:260px;
    padding:35px;
    width:calc(100% - 260px);
}
h1{margin-top:0}

/* FILTERS */
.filters{margin-bottom:20px}
.filters a{
    padding:8px 14px;
    border-radius:10px;
    background:#fff;
    border:1px solid #ccc;
    margin-right:6px;
    text-decoration:none;
    color:#333;
}
.filters a.active{
    background:#2b2e9f;
    color:#fff;
}

/* ADD FORM */
.form-box{
    display:flex;
    gap:10px;
    background:#fff;
    padding:20px;
    border-radius:16px;
    margin-bottom:20px;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
}
.form-box input,
.form-box select,
.form-box button{
    padding:10px;
    border-radius:10px;
    border:1px solid #ccc;
}
.form-box button{
    background:#2b2e9f;
    color:#fff;
    border:none;
    cursor:pointer;
}

/* GRID */
.grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(240px,1fr));
    gap:20px;
}
.card{
    background:#fff;
    padding:20px;
    border-radius:18px;
    position:relative;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
    transition:.25s;
}
.card:hover{
    transform:translateY(-4px);
}

/* STATUS */
.status{
    position:absolute;
    top:15px;
    right:15px;
    padding:4px 12px;
    border-radius:20px;
    font-size:12px;
    font-weight:bold;
}
.Available{background:#d4f8df;color:#1e7e34}
.NotAvailable{background:#f8d7da;color:#842029}
.Maintenance{background:#fff3cd;color:#856404}

/* BUTTONS */
.rent-btn{
    margin-top:10px;
    padding:10px;
    background:#2b2e9f;
    color:#fff;
    border:none;
    border-radius:10px;
    cursor:pointer;
    width:100%;
}
.rent-btn:hover{background:#1f227a}
.rent-btn.disabled{
    background:#aaa;
    pointer-events:none;
}
.delete-btn{
    background:#c0392b;
}
.delete-btn:hover{
    background:#a93226;
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <h2><i class="fa-solid fa-bicycle"></i> BikeRental</h2>

    <a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
    <a href="bikes.php" class="active"><i class="fa-solid fa-bicycle"></i> Available Bikes</a>
    <a href="rental_summary.php"><i class="fa-solid fa-clock-rotate-left"></i> Rental Summary</a>
    <a href="complaints.php"><i class="fa-solid fa-comment-dots"></i> Complaints</a>
    <a href="logout.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<!-- MAIN -->
<div class="main">

<h1>Available Bikes</h1>

<div class="filters">
    <a href="?filter=all" class="<?= $filter=='all'?'active':'' ?>">All</a>
    <a href="?filter=available" class="<?= $filter=='available'?'active':'' ?>">Available (<?= $available ?>)</a>
    <a href="?filter=maintenance" class="<?= $filter=='maintenance'?'active':'' ?>">Maintenance</a>
    <a href="?filter=rented" class="<?= $filter=='rented'?'active':'' ?>">Rented</a>
</div>

<form class="form-box" method="POST">
    <input name="bike_code" placeholder="Bike Code" required>
    <input name="condition" placeholder="Condition" required>
    <select name="status">
        <option>Available</option>
        <option>Maintenance</option>
        <option>NotAvailable</option>
    </select>
    <button name="add_bike">Add Bike</button>
</form>

<div class="grid">
<?php while($row=mysqli_fetch_assoc($bikes)){ ?>
<div class="card">
<span class="status <?= $row['status'] ?>"><?= $row['status'] ?></span>

<h3><?= $row['bike_code'] ?></h3>
<p>Condition: <strong><?= $row['condition'] ?></strong></p>

<?php if($row['status']=="Available"){ ?>
<button class="rent-btn" onclick="openRentModal(<?= $row['bike_id'] ?>,'<?= $row['bike_code'] ?>')">
    Rent Now
</button>
<?php } else { ?>
<span class="rent-btn disabled">Not Available</span>
<?php } ?>

<form method="POST">
<input type="hidden" name="bike_id" value="<?= $row['bike_id'] ?>">

<select name="condition" style="width:100%;margin-top:6px">
<option <?= $row['condition']=="Good"?'selected':'' ?>>Good</option>
<option <?= $row['condition']=="Average"?'selected':'' ?>>Average</option>
<option <?= $row['condition']=="Poor"?'selected':'' ?>>Poor</option>
</select>

<select name="status" style="width:100%;margin-top:6px">
<option <?= $row['status']=="Available"?'selected':'' ?>>Available</option>
<option <?= $row['status']=="Maintenance"?'selected':'' ?>>Maintenance</option>
<option <?= $row['status']=="NotAvailable"?'selected':'' ?>>NotAvailable</option>
</select>

<button name="update_bike" class="rent-btn">
    <i class="fa-solid fa-pen"></i> Update
</button>

<button name="delete_bike" class="rent-btn delete-btn"
onclick="return confirm('Delete this bike permanently?')">
    <i class="fa-solid fa-trash"></i> Delete
</button>
</form>

</div>
<?php } ?>
</div>

</div>

<!-- RENT MODAL (UNCHANGED) -->
<div id="rentModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);align-items:center;justify-content:center;">
<form method="POST" style="background:#fff;padding:25px;width:420px;border-radius:16px;">
<h2>Proceed with Payment</h2>

<p style="background:#f3f5ff;padding:12px;border-radius:10px">
<strong>Selected Bike</strong><br>
<span id="bikeName"></span>
</p>

<input type="hidden" name="bike_id" id="bikeId">

<select name="duration" onchange="updatePrice(this.value)" style="width:100%;padding:10px">
<option value="120">2 Hours - RM 8.00</option>
<option value="240">4 Hours - RM 15.00</option>
<option value="480">8 Hours - RM 25.00</option>
</select>

<div style="background:#2b2e9f;color:#fff;padding:15px;border-radius:12px;margin:15px 0">
Total: RM <span id="total">8.00</span>
</div>

<button name="confirm_rent" class="rent-btn">Confirm Payment</button>
<button type="button" onclick="closeRentModal()" class="rent-btn" style="background:#aaa">Cancel</button>
</form>
</div>

<script>
function openRentModal(id,name){
    rentModal.style.display='flex';
    bikeId.value=id;
    bikeName.innerText=name;
}
function closeRentModal(){
    rentModal.style.display='none';
}
function updatePrice(v){
    let p=8;
    if(v==240)p=15;
    if(v==480)p=25;
    total.innerText=p.toFixed(2);
}
</script>

</body>
</html>
