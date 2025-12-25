<?php
include 'db.php';
session_start();

$user_id   = $_SESSION['user_id'] ?? 1;
$user_name = $_SESSION['user_name'] ?? 'User';
$role      = $_SESSION['role'] ?? 'user'; // user | admin

/* UPDATE STATUS (ADMIN ONLY) */
if (isset($_POST['update_status']) && $role === 'admin') {
    $complaint_id = (int)$_POST['complaint_id'];
    $status       = $_POST['status'];

    mysqli_query($conn, "
        UPDATE complaints 
        SET status='$status'
        WHERE complaint_id=$complaint_id
    ");

    header("Location: complaints.php");
    exit();
}

/* FETCH COMPLAINTS */
$complaints = ($role === 'admin')
    ? mysqli_query($conn,"SELECT * FROM complaints ORDER BY created_at DESC")
    : mysqli_query($conn,"SELECT * FROM complaints WHERE user_id=$user_id ORDER BY created_at DESC");

/* FETCH USER RENTALS FOR MODAL */
$rentals = mysqli_query($conn, "
    SELECT DISTINCT b.bike_code
    FROM rentals r
    JOIN bikes b ON r.bike_id = b.bike_id
    WHERE r.user_id = $user_id
");

/* SUBMIT COMPLAINT */
if (isset($_POST['submit_complaint'])) {
    $bike_code = $_POST['bike_code'];
    $complaint = mysqli_real_escape_string($conn, $_POST['complaint']);

    mysqli_query($conn, "
        INSERT INTO complaints
        (user_name, bike_code, complaint, status, created_at, user_id)
        VALUES
        ('$user_name','$bike_code','$complaint','Pending',NOW(),$user_id)
    ");

    header("Location: complaints.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Complaints</title>
<meta charset="UTF-8">

<style>
*{box-sizing:border-box;font-family:'Segoe UI',sans-serif}
body{margin:0;background:#f5f7fb;display:flex}

/* SIDEBAR */
.sidebar{
    width:260px;
    background:linear-gradient(180deg,#2b2e9f,#1f1c6b);
    height:100vh;
    padding:25px;
    position:fixed;
    color:#fff;
    display:flex;
    flex-direction:column;
}
.sidebar h2{margin-bottom:35px}
.sidebar a{
    color:#e5e7ff;
    text-decoration:none;
    padding:12px 16px;
    border-radius:12px;
    margin-bottom:10px;
}
.sidebar a.active,.sidebar a:hover{
    background:rgba(255,255,255,.15);
    color:#fff;
}
.logout{margin-top:auto}

/* MAIN */
.main{
    margin-left:280px;
    padding:40px;
    width:calc(100% - 280px);
}
.header{
    display:flex;
    justify-content:space-between;
    align-items:center;
}
.subtitle{color:#666;margin-top:6px}

/* BUTTON */
.lodge-btn{
    background:#1d4cff;
    color:#fff;
    padding:14px 20px;
    border-radius:14px;
    font-weight:600;
    border:none;
    cursor:pointer;
}

/* CARD */
.card{
    background:#fff;
    border-radius:18px;
    padding:25px;
    margin-top:30px;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
}

/* STATUS */
.status{
    padding:6px 16px;
    border-radius:20px;
    font-size:13px;
    font-weight:bold;
    display:inline-block;
}
.Pending{background:#fff3cd;color:#856404}
.Resolved{background:#d4f7dc;color:#1a7f37}

/* ADMIN FORM */
.admin-form{
    margin-top:15px;
    display:flex;
    gap:10px;
}
.admin-form select{
    padding:8px 12px;
    border-radius:10px;
    border:1px solid #ccc;
}
.admin-form button{
    padding:8px 14px;
    border:none;
    border-radius:10px;
    background:#1d4cff;
    color:#fff;
    cursor:pointer;
}

/* MODAL */
.modal{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.55);
    display:flex;
    align-items:center;
    justify-content:center;
    z-index:999;
}
.modal-box{
    background:#fff;
    width:520px;
    padding:32px;
    border-radius:22px;
}
.modal-box label{font-weight:600;margin-bottom:6px;display:block}
.modal-box select,
.modal-box textarea{
    width:100%;
    padding:14px;
    border-radius:14px;
    border:1px solid #cfd6ff;
    margin-bottom:20px;
    background:#f9faff;
}
.modal-box textarea{height:140px;resize:none}
.actions{display:flex;gap:15px}
.actions button{
    flex:1;
    padding:14px;
    border-radius:14px;
    border:none;
}
.cancel{background:#eef1f7}
.submit{background:#1d4cff;color:#fff}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <h2>🚲 BikeRental</h2>
    <a href="dashboard.php">Dashboard</a>
    <a href="bikes.php">Available Bikes</a>
    <a href="rental_summary.php">Rental Summary</a>
    <a href="complaints.php" class="active">Complaints</a>
    <a href="logout.php" class="logout">🚪 Logout</a>
</div>

<!-- MAIN -->
<div class="main">

<div class="header">
    <div>
        <h1>Complaints</h1>
        <p class="subtitle">Submit and track complaints</p>
    </div>

    <?php if($role === 'user'): ?>
    <button class="lodge-btn" onclick="openModal()">💬 Lodge Complaint</button>
    <?php endif; ?>
</div>

<?php while($c=mysqli_fetch_assoc($complaints)): ?>
<div class="card">
    <strong><?= $c['bike_code'] ?></strong><br>
    <small><?= date('d/m/Y h:i A',strtotime($c['created_at'])) ?></small><br><br>

    <span class="status <?= $c['status'] ?>"><?= $c['status'] ?></span>

    <p style="margin-top:15px"><?= $c['complaint'] ?></p>

    <!-- ADMIN STATUS UPDATE -->
    <?php if($role === 'admin'): ?>
    <form method="POST" class="admin-form">
        <input type="hidden" name="complaint_id" value="<?= $c['complaint_id'] ?>">
        <select name="status">
            <option <?= $c['status']=='Pending'?'selected':'' ?>>Pending</option>
            <option <?= $c['status']=='Resolved'?'selected':'' ?>>Resolved</option>
        </select>
        <button name="update_status">Update</button>
    </form>
    <?php endif; ?>
</div>
<?php endwhile; ?>

</div>

<!-- MODAL -->
<div class="modal" id="complaintModal">
<form method="POST" class="modal-box">
    <h2>Lodge Complaint</h2>

    <label>Bike</label>
    <select name="bike_code" required>
        <option value="">Select Bike</option>
        <?php while($r=mysqli_fetch_assoc($rentals)): ?>
            <option><?= $r['bike_code'] ?></option>
        <?php endwhile; ?>
    </select>

    <label>Complaint</label>
    <textarea name="complaint" required></textarea>

    <div class="actions">
        <button type="button" class="cancel" onclick="closeModal()">Cancel</button>
        <button type="submit" name="submit_complaint" class="submit">Submit</button>
    </div>
</form>
</div>

<script>
function openModal(){document.getElementById('complaintModal').style.display='flex'}
function closeModal(){document.getElementById('complaintModal').style.display='none'}
</script>

</body>
</html>
