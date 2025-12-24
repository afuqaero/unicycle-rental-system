<?php
session_start();
require_once "config.php";

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $student_id = trim($_POST['student_id'] ?? '');
    $role = $_POST['role'] ?? 'student';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($name) || empty($email) || empty($student_id) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Check if email or student_id already exists
        $stmt = $pdo->prepare("SELECT student_id FROM students WHERE email = ? OR student_staff_id = ?");
        $stmt->execute([$email, $student_id]);

        if ($stmt->fetch()) {
            $error = 'Email or Student/Staff ID already registered.';
        } else {
            // Insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO students (name, email, student_staff_id, role, password) VALUES (?, ?, ?, ?, ?)");

            try {
                $stmt->execute([$name, $email, $student_id, $role, $hashed_password]);
                $success = true;
            } catch (PDOException $e) {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - UniCycle</title>
    <meta name="description" content="Create your UniCycle account">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="auth.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="auth-body auth-body-split">

    <!-- Video Background -->
    <div class="video-background">
        <video autoplay muted loop playsinline id="bg-video">
            <source src="assets/campus-cycling.mp4" type="video/mp4">
        </video>
        <div class="video-overlay"></div>
    </div>

    <!-- Success Popup -->
    <?php if ($success): ?>
        <div class="popup-overlay" id="success-popup">
            <div class="popup-card">
                <div class="popup-icon success">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <h2>Registration Successful!</h2>
                <p>Your account has been created. You will be redirected to the login page.</p>
                <a href="login.php" class="btn-auth-primary">Go to Login</a>
            </div>
        </div>
        <script>
            // Auto redirect after 3 seconds
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 3000);
        </script>
    <?php endif; ?>

    <div class="auth-split-container">
        <!-- Left side - Welcome text -->
        <div class="auth-welcome">
            <h1>Join<br>UniCycle!</h1>
            <p>Start your campus ride today.</p>
        </div>

        <!-- Right side - Register card -->
        <div class="auth-container">
            <div class="auth-card">
                <!-- Header -->
                <h1 class="auth-title">Create Account</h1>

                <!-- Error Message -->
                <?php if ($error): ?>
                    <div class="auth-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <!-- Registration Form -->
                <form method="POST" class="auth-form active">
                    <div class="form-group">
                        <label for="name">Username *</label>
                        <input type="text" id="name" name="name" placeholder="Enter your username"
                            value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="student_id">Student ID / Staff ID *</label>
                        <input type="text" id="student_id" name="student_id" placeholder="e.g. AI220001"
                            value="<?= htmlspecialchars($_POST['student_id'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="role">Role *</label>
                        <select id="role" name="role" class="role-select">
                            <option value="student" <?= ($_POST['role'] ?? 'student') === 'student' ? 'selected' : '' ?>>
                                Student</option>
                            <option value="staff" <?= ($_POST['role'] ?? '') === 'staff' ? 'selected' : '' ?>>Staff
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" placeholder="At least 6 characters"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password"
                            placeholder="Re-enter your password" required>
                    </div>

                    <button type="submit" class="btn-auth-primary">Create Account</button>
                </form>

                <!-- Login Link -->
                <p class="auth-footer">
                    Already have an account? <a href="login.php">Sign in</a>
                </p>
            </div>
        </div>
    </div>

</body>

</html>