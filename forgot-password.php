<?php
session_start();
require_once "config.php";
require_once "mail_config.php";
require_once "vendor/PHPMailer/src/PHPMailer.php";
require_once "vendor/PHPMailer/src/SMTP.php";
require_once "vendor/PHPMailer/src/Exception.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT student_id, name FROM students WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Generate unique token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Delete any existing tokens for this user
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE student_id = ?");
            $stmt->execute([$user['student_id']]);

            // Insert new token
            $stmt = $pdo->prepare("INSERT INTO password_resets (student_id, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$user['student_id'], $token, $expires]);

            // Send email
            $resetLink = SITE_URL . "/reset-password.php?token=" . $token;

            $mail = new PHPMailer(true);

            try {
                // SMTP Configuration
                $mail->isSMTP();
                $mail->Host = MAIL_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = MAIL_USERNAME;
                $mail->Password = MAIL_PASSWORD;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = MAIL_PORT;

                // Recipients
                $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
                $mail->addAddress($email, $user['name']);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Reset Your UniCycle Password';
                $mail->Body = '
                    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                        <div style="text-align: center; margin-bottom: 30px;">
                            <h1 style="color: #6366f1; margin: 0;">🚲 UniCycle</h1>
                        </div>
                        <h2 style="color: #333;">Password Reset Request</h2>
                        <p style="color: #666; font-size: 16px;">Hi ' . htmlspecialchars($user['name']) . ',</p>
                        <p style="color: #666; font-size: 16px;">We received a request to reset your password. Click the button below to create a new password:</p>
                        <div style="text-align: center; margin: 30px 0;">
                            <a href="' . $resetLink . '" style="background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; padding: 14px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">Reset Password</a>
                        </div>
                        <p style="color: #999; font-size: 14px;">This link will expire in 1 hour.</p>
                        <p style="color: #999; font-size: 14px;">If you didn\'t request this, you can safely ignore this email.</p>
                        <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
                        <p style="color: #999; font-size: 12px; text-align: center;">© ' . date('Y') . ' UniCycle - UTHM Bike Rental System</p>
                    </div>
                ';
                $mail->AltBody = "Hi {$user['name']},\n\nReset your password using this link: {$resetLink}\n\nThis link expires in 1 hour.";

                $mail->send();
                $success = true;
            } catch (Exception $e) {
                $error = 'Failed to send email. Please try again later.';
                // For debugging: $error = $mail->ErrorInfo;
            }
        } else {
            // Email not found in database
            $error = 'Email not found. Please check your email address or register a new account.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - UniCycle</title>
    <meta name="description" content="Reset your UniCycle password">
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
                <h2>Check Your Email</h2>
                <p>If an account exists with that email, we've sent password reset instructions.</p>
                <a href="login.php" class="btn-auth-primary">Back to Login</a>
            </div>
        </div>
    <?php endif; ?>

    <div class="auth-split-container">
        <!-- Left side - Welcome text -->
        <div class="auth-welcome">
            <h1>Forgot<br>Password?</h1>
            <p>No worries, we'll help you reset it.</p>
        </div>

        <!-- Right side - Form card -->
        <div class="auth-container">
            <div class="auth-card">
                <!-- Header -->
                <h1 class="auth-title">Reset Password</h1>
                <p class="auth-subtitle">Enter your email to receive reset instructions</p>

                <!-- Error Message -->
                <?php if ($error): ?>
                    <div class="auth-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <!-- Form -->
                <form method="POST" class="auth-form active">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="Enter your registered email"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>

                    <button type="submit" class="btn-auth-primary">Send Reset Link</button>
                </form>

                <!-- Back to Login -->
                <p class="auth-footer">
                    Remember your password? <a href="login.php">Sign in</a>
                </p>
            </div>
        </div>
    </div>

</body>

</html>