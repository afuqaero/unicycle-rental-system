<?php
/**
 * Google Login - Initiates OAuth flow
 * Redirects user to Google's login page
 */
session_start();
require_once 'google_config.php';

// Generate state token for security (prevents CSRF)
$state = bin2hex(random_bytes(16));
$_SESSION['google_oauth_state'] = $state;

// Build Google OAuth URL
$params = [
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'email profile',
    'state' => $state,
    'access_type' => 'online',
    'prompt' => 'select_account'
];

$authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);

// Redirect to Google
header('Location: ' . $authUrl);
exit;
