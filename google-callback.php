<?php
/**
 * Google OAuth Callback
 * Handles the response from Google after user logs in
 */
session_start();
require_once 'config.php';
require_once 'google_config.php';

$error = '';

// Check for errors from Google
if (isset($_GET['error'])) {
    header('Location: login.php?error=google_denied');
    exit;
}

// Verify state token (CSRF protection)
if (!isset($_GET['state']) || $_GET['state'] !== ($_SESSION['google_oauth_state'] ?? '')) {
    header('Location: login.php?error=invalid_state');
    exit;
}

// Check for authorization code
if (!isset($_GET['code'])) {
    header('Location: login.php?error=no_code');
    exit;
}

$code = $_GET['code'];

// Exchange code for access token
$tokenUrl = 'https://oauth2.googleapis.com/token';
$tokenData = [
    'code' => $code,
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'grant_type' => 'authorization_code'
];

$ch = curl_init($tokenUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
$tokenResponse = curl_exec($ch);
curl_close($ch);

$tokenInfo = json_decode($tokenResponse, true);

if (!isset($tokenInfo['access_token'])) {
    header('Location: login.php?error=token_failed');
    exit;
}

$accessToken = $tokenInfo['access_token'];

// Get user info from Google
$userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . $accessToken;
$ch = curl_init($userInfoUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$userResponse = curl_exec($ch);
curl_close($ch);

$googleUser = json_decode($userResponse, true);

if (!isset($googleUser['email'])) {
    header('Location: login.php?error=user_info_failed');
    exit;
}

// Extract user info
$googleId = $googleUser['id'];
$email = $googleUser['email'];
$name = $googleUser['name'] ?? explode('@', $email)[0];
$picture = $googleUser['picture'] ?? null;

// Check if user exists by email or google_id
$stmt = $pdo->prepare("SELECT * FROM students WHERE email = ? OR google_id = ?");
$stmt->execute([$email, $googleId]);
$user = $stmt->fetch();

if ($user) {
    // Existing user - update google_id if not set
    if (empty($user['google_id'])) {
        $stmt = $pdo->prepare("UPDATE students SET google_id = ? WHERE student_id = ?");
        $stmt->execute([$googleId, $user['student_id']]);
    }
} else {
    // New user - create account
    $stmt = $pdo->prepare("INSERT INTO students (name, email, password, google_id, role) VALUES (?, ?, '', ?, 'user')");
    $stmt->execute([$name, $email, $googleId]);

    // Get the new user
    $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
}

// Set session variables
$_SESSION['student_id'] = $user['student_id'];
$_SESSION['student_name'] = $user['name'];
$_SESSION['logged_in'] = true;
$_SESSION['user_role'] = $user['role'];

// Clear OAuth state
unset($_SESSION['google_oauth_state']);

// Role-based redirect
if ($user['role'] === 'admin' || $user['role'] === 'superadmin') {
    $_SESSION['is_admin'] = true;
    $_SESSION['admin_role'] = $user['role'];
    header('Location: admin/dashboard.php');
} else {
    header('Location: dashboard.php');
}
exit;
