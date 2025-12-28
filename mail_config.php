<?php
/**
 * Mail Configuration
 * Gmail SMTP settings for sending emails
 * 
 * SETUP INSTRUCTIONS:
 * 1. Go to your Google Account → Security
 * 2. Enable 2-Step Verification
 * 3. Go to App Passwords (search in Google Account)
 * 4. Generate a new App Password for "Mail"
 * 5. Copy the 16-character password below
 */

// Gmail SMTP Configuration
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'afiq.sksbu@gmail.com');
define('MAIL_PASSWORD', 'zeof kuof bbck wirw');
define('MAIL_FROM_EMAIL', 'afiq.sksbu@gmail.com');
define('MAIL_FROM_NAME', 'UniCycle');

// Site URL (change for production)
define('SITE_URL', 'http://localhost/webproject');
// For InfinityFree, use: define('SITE_URL', 'https://your-subdomain.infinityfreeapp.com');
