<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';

$success = "";
$error = "";

if (isset($_POST['register'])) {

    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm'];

    if ($password !== $confirm) {
        $error = "Passwords do not match";
    } else {

        $check = mysqli_query($conn, "SELECT * FROM admin WHERE username='$username'");

        if (!$check) {
            die("Query failed: " . mysqli_error($conn));
        }

        if (mysqli_num_rows($check) > 0) {
            $error = "Username already exists";
        } else {

            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $insert = mysqli_query(
                $conn,
                "INSERT INTO admin (username, password) VALUES ('$username', '$hashed')"
            );

            if ($insert) {
                $success = "Admin registered successfully";
            } else {
                $error = "Insert failed: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Admin Register</title>
  <style>
    body { background:#f4f4f4; font-family: Arial; }
    .box {
      width: 350px;
      margin: 120px auto;
      padding: 25px;
      background: #6091D4;
      border-radius: 10px;
      text-align: center;
    }
    input, button {
      width: 100%;
      padding: 10px;
      margin-bottom: 10px;
    }
    .success { color: #fff; }
    .error { color: #000; }
  </style>
</head>
<body>

<div class="box">
  <h2>Admin Register</h2>

  <?php if ($success) echo "<p class='success'>$success</p>"; ?>
  <?php if ($error) echo "<p class='error'>$error</p>"; ?>

  <form method="POST">
    <input type="text" name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Password" required>
    <input type="password" name="confirm" placeholder="Confirm Password" required>
    <button type="submit" name="register">Register</button>
  </form>

  <a href="login.php">Back to Login</a>
</div>

</body>
</html>
