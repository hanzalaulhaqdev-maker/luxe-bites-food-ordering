<?php
/**
 * Luxe Bites - Configuration File
 * Database and application settings
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'luxe_bites');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application Settings
define('APP_NAME', 'Luxe Bites');
define('APP_URL', 'http://localhost/restaurant');
define('ADMIN_EMAIL', 'admin@luxebites.com');

// Security
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_NAME', 'luxe_bites_session');

// Upload paths
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MENU_UPLOAD_PATH', UPLOAD_PATH . 'menu/');
define('RIDER_UPLOAD_PATH', UPLOAD_PATH . 'riders/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

// Start session with security settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    session_name(SESSION_NAME);
    session_start();
}

// Timezone
date_default_timezone_set('America/New_York');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
