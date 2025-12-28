<?php
session_start();
require_once "config.php";

$error = '';
$success = false;
$validToken = false;
$token = $_GET['token'] ?? '';

// Validate token
if (!empty($token)) {
    $stmt = $pdo->prepare("
        SELECT pr.*, s.name, s.email 
        FROM password_resets pr 
        JOIN students s ON pr.student_id = s.student_id 
        WHERE pr.token = ? AND pr.expires_at > NOW()
    ");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();

    if ($reset) {
        $validToken = true;
    }
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Update password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE students SET password = ? WHERE student_id = ?");
        $stmt->execute([$hashedPassword, $reset['student_id']]);

        // Delete the used token
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
        $stmt->execute([$token]);

        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - UniCycle</title>
    <meta name="description" content="Create a new password for your UniCycle account">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="auth.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .password-strength {
            margin-top: 8px;
            display: none;
        }

        .password-strength.visible {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .strength-bar {
            flex: 1;
            height: 6px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            border-radius: 3px;
            transition: width 0.3s ease, background 0.3s ease;
        }

        .strength-fill.weak {
            width: 33%;
            background: linear-gradient(90deg, #ff4d4d, #ff6b6b);
        }

        .strength-fill.medium {
            width: 66%;
            background: linear-gradient(90deg, #ffa500, #ffb833);
        }

        .strength-fill.strong {
            width: 100%;
            background: linear-gradient(90deg, #00c853, #69f0ae);
        }

        .strength-text {
            font-size: 12px;
            font-weight: 500;
            min-width: 60px;
            text-align: right;
        }

        .strength-text.weak {
            color: #ff6b6b;
        }

        .strength-text.medium {
            color: #ffb833;
        }

        .strength-text.strong {
            color: #69f0ae;
        }
    </style>
</head>

<body class="auth-body auth-body-split">

    <!-- Video Background -->
    <div class="video-background">
        <video autoplay muted loop playsinline id="bg-video">
            <source src="assets/campus-cycling.mp4" type="video/mp4">
        </video>
        <div class="video-overlay"></div>
    </div>

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
                <h2>Password Reset Successful!</h2>
                <p>Your password has been updated. You can now login with your new password.</p>
                <a href="login.php" class="btn-auth-primary">Go to Login</a>
            </div>
        </div>
        <script>
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 3000);
        </script>
    <?php endif; ?>

    <div class="auth-split-container">
        <!-- Left side - Welcome text -->
        <div class="auth-welcome">
            <h1>Create New<br>Password</h1>
            <p>Choose a strong password to protect your account.</p>
        </div>

        <!-- Right side - Form card -->
        <div class="auth-container">
            <div class="auth-card">
                <?php if (!$validToken && !$success): ?>
                    <!-- Invalid/Expired Token -->
                    <div style="text-align: center; padding: 20px 0;">
                        <div style="font-size: 64px; margin-bottom: 20px;">⚠️</div>
                        <h2 class="auth-title">Invalid or Expired Link</h2>
                        <p class="auth-subtitle">This password reset link is invalid or has expired.</p>
                        <a href="forgot-password.php" class="btn-auth-primary"
                            style="margin-top: 20px; display: inline-block;">Request New Link</a>
                    </div>
                <?php else: ?>
                    <!-- Header -->
                    <h1 class="auth-title">New Password</h1>
                    <p class="auth-subtitle">Enter your new password for <?= htmlspecialchars($reset['email'] ?? '') ?></p>

                    <!-- Error Message -->
                    <?php if ($error): ?>
                        <div class="auth-error"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <!-- Form -->
                    <form method="POST" class="auth-form active">
                        <div class="form-group">
                            <label for="password">New Password</label>
                            <input type="password" id="password" name="password" placeholder="At least 6 characters"
                                required oninput="checkPasswordStrength(this.value)">
                            <div class="password-strength" id="password-strength">
                                <div class="strength-bar">
                                    <div class="strength-fill" id="strength-fill"></div>
                                </div>
                                <span class="strength-text" id="strength-text"></span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password"
                                placeholder="Re-enter your new password" required>
                        </div>

                        <button type="submit" class="btn-auth-primary">Reset Password</button>
                    </form>
                <?php endif; ?>

                <!-- Back to Login -->
                <p class="auth-footer">
                    Remember your password? <a href="login.php">Sign in</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        function checkPasswordStrength(password) {
            const strengthDiv = document.getElementById('password-strength');
            const fill = document.getElementById('strength-fill');
            const text = document.getElementById('strength-text');

            if (password.length === 0) {
                strengthDiv.classList.remove('visible');
                return;
            }

            strengthDiv.classList.add('visible');

            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;

            fill.className = 'strength-fill';
            text.className = 'strength-text';

            if (strength <= 2) {
                fill.classList.add('weak');
                text.classList.add('weak');
                text.textContent = 'Weak';
            } else if (strength <= 3) {
                fill.classList.add('medium');
                text.classList.add('medium');
                text.textContent = 'Medium';
            } else {
                fill.classList.add('strong');
                text.classList.add('strong');
                text.textContent = 'Strong';
            }
        }
    </script>

</body>

</html>