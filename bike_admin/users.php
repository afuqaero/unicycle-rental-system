<?php
include 'db.php';

/* DELETE USER */
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM users WHERE user_id = $id");
    header("Location: users.php");
    exit();
}

/* FETCH USERS */
$users = mysqli_query($conn, "SELECT * FROM users");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Management</title>

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
    margin-bottom: 25px;
    font-size: 28px;
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
   DELETE BUTTON
================================ */
.delete-btn {
    background-color: #161616;
    color: #FFFFFF;
    border: none;
    padding: 8px 16px;
    border-radius: 20px;
    cursor: pointer;
    font-weight: bold;
    transition: 0.3s;
}

.delete-btn:hover {
    background-color: #004EBA;
    transform: scale(1.05);
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
    <a href="users.php">Users</a>
</div>

<!-- MAIN CONTENT -->
<div class="main">
    <h1>👤 User Management</h1>

    <div class="table-card">
        <table>
            <tr>
                <th>User ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Action</th>
            </tr>

            <?php while ($u = mysqli_fetch_assoc($users)) { ?>
            <tr>
                <td><?php echo $u['user_id']; ?></td>
                <td><?php echo $u['name']; ?></td>
                <td><?php echo $u['email']; ?></td>
                <td><?php echo $u['phone']; ?></td>
                <td>
                    <a href="users.php?delete=<?php echo $u['user_id']; ?>"
                       onclick="return confirm('Delete this user?');">
                        <button class="delete-btn">Delete</button>
                    </a>
                </td>
            </tr>
            <?php } ?>

        </table>
    </div>
</div>

</body>
</html>
