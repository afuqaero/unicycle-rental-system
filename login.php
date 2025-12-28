<?php
session_start();
require_once "config.php";

$error = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        // Check against students table by username (name field)
        $stmt = $pdo->prepare("SELECT student_id, name, email, password, role FROM students WHERE name = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Login successful - set common session variables
            $_SESSION['student_id'] = $user['student_id'];
            $_SESSION['student_name'] = $user['name'];
            $_SESSION['logged_in'] = true;
            $_SESSION['user_role'] = $user['role'];

            // Role-based routing: Admin/Super Admin go to admin dashboard
            if ($user['role'] === 'admin' || $user['role'] === 'superadmin') {
                $_SESSION['is_admin'] = true;
                $_SESSION['admin_role'] = $user['role'];
                header('Location: admin/dashboard.php');
                exit;
            }

            // Regular users go to normal dashboard
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - UniCycle</title>
    <meta name="description" content="Sign in to your UniCycle account">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="auth.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="auth-body auth-body-split">

    <!-- Video Background -->
    <div class="video-background">
        <video autoplay muted loop playsinline id="bg-video">
            <source src="assets/campus-cycling.mp4" type="video/mp4">
            <!-- Fallback to image if video doesn't load -->
        </video>
        <div class="video-overlay"></div>
    </div>

    <div class="auth-split-container">
        <!-- Left side - Welcome text -->
        <div class="auth-welcome">
            <h1>Welcome<br>Back!</h1>
            <p>Your convenience, our priority.</p>
        </div>

        <!-- Right side - Login card -->
        <div class="auth-container">
            <div class="auth-card">
                <!-- Header -->
                <h1 class="auth-title">Sign In</h1>
                <p class="auth-subtitle">Sign in to your account to continue</p>

                <!-- Error Message -->
                <?php if ($error): ?>
                    <div class="auth-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <!-- Login Form -->
                <form method="POST" class="auth-form active" id="login-form">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" placeholder="Enter your username" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <a href="forgot-password.php" class="forgot-link">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn-auth-primary">Login</button>
                </form>

                <!-- Divider -->
                <div class="auth-divider">
                    <span>Or continue with</span>
                </div>

                <!-- Social Login -->
                <div class="social-buttons">
                    <a href="google-login.php" class="btn-social">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                            <path fill="#4285F4"
                                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                            <path fill="#34A853"
                                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                            <path fill="#FBBC05"
                                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                            <path fill="#EA4335"
                                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                        </svg>
                        Continue with Google
                    </a>
                </div>

                <!-- Register Link -->
                <p class="auth-footer">
                    Don't have an account? <a href="register.php">Register</a>
                </p>
            </div>
        </div>
    </div>

</body>

</html>