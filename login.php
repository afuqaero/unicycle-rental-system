<?php
session_start();
require_once "config.php";

$error = '';
$activeTab = isset($_POST['login_type']) ? $_POST['login_type'] : 'email';

// Handle Email/ID login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_type']) && $_POST['login_type'] === 'email') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        // Check against students table (includes role for admin detection)
        $stmt = $pdo->prepare("SELECT student_id, name, email, password, role FROM students WHERE email = ? OR student_staff_id = ?");
        $stmt->execute([$email, $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Login successful - set common session variables
            $_SESSION['student_id'] = $user['student_id'];
            $_SESSION['student_name'] = $user['name'];
            $_SESSION['logged_in'] = true;
            $_SESSION['user_role'] = $user['role'];

            // Role-based routing: Admin/Super Admin go to admin dashboard
            if ($user['role'] === 'admin' || $user['role'] === 'super_admin') {
                $_SESSION['is_admin'] = true;
                $_SESSION['admin_role'] = $user['role'];
                header('Location: admin/dashboard.php');
                exit;
            }

            // Regular users (student/staff) go to normal dashboard
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid email/ID or password.';
        }
    }
}

// Handle Phone login (OTP - placeholder for now)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_type']) && $_POST['login_type'] === 'phone') {
    $phone = trim($_POST['phone'] ?? '');

    if (empty($phone)) {
        $error = 'Please enter your phone number.';
    } else {
        // For now, just show a message that OTP is not implemented
        $error = 'OTP login is coming soon! Please use Email/ID login for now.';
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

                <!-- Tab Switcher -->
                <div class="auth-tabs">
                    <button type="button" class="auth-tab <?= $activeTab === 'email' ? 'active' : '' ?>"
                        data-tab="email">Email / ID</button>
                    <button type="button" class="auth-tab <?= $activeTab === 'phone' ? 'active' : '' ?>"
                        data-tab="phone">Phone</button>
                </div>

                <!-- Error Message -->
                <?php if ($error): ?>
                    <div class="auth-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <!-- Email/ID Login Form -->
                <form method="POST" class="auth-form <?= $activeTab === 'email' ? 'active' : '' ?>" id="email-form">
                    <input type="hidden" name="login_type" value="email">

                    <div class="form-group">
                        <label for="email">Email / Student ID / Staff ID</label>
                        <input type="text" id="email" name="email" placeholder="Enter your email or ID" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <a href="#" class="forgot-link">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn-auth-primary">Login</button>
                </form>

                <!-- Phone Login Form -->
                <form method="POST" class="auth-form <?= $activeTab === 'phone' ? 'active' : '' ?>" id="phone-form">
                    <input type="hidden" name="login_type" value="phone">

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <div class="phone-input-group">
                            <div class="country-code">+60</div>
                            <input type="tel" id="phone" name="phone" placeholder="12 345 6789">
                        </div>
                    </div>

                    <button type="submit" class="btn-auth-primary">Send OTP</button>
                </form>

                <!-- Divider -->
                <div class="auth-divider">
                    <span>Or continue with</span>
                </div>

                <!-- Social Login -->
                <div class="social-buttons">
                    <button type="button" class="btn-social">
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
                    </button>
                    <button type="button" class="btn-social">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                            fill="#1877F2">
                            <path
                                d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                        </svg>
                        Continue with Facebook
                    </button>
                </div>

                <!-- Register Link -->
                <p class="auth-footer">
                    Don't have an account? <a href="register.php">Register</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        // Tab switching
        const tabs = document.querySelectorAll('.auth-tab');
        const forms = document.querySelectorAll('.auth-form');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const targetTab = tab.dataset.tab;

                // Update active tab
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                // Show corresponding form
                forms.forEach(form => {
                    form.classList.remove('active');
                    if (form.id === targetTab + '-form') {
                        form.classList.add('active');
                    }
                });
            });
        });
    </script>
</body>

</html>