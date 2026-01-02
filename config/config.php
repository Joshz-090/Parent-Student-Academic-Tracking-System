<?php
/**
 * Global Configuration
 * Multi-School Parent-Student Tracer System
 */

// Timezone
date_default_timezone_set('Africa/Addis_Ababa');

// Application Settings
define('APP_NAME', 'Parent-Student Tracer');
define('APP_VERSION', '1.0.0');

// Dynamic APP_URL to handle ports like 8080 automatically
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
define('APP_URL', $protocol . '://' . $host . '/PS-tracer/public');

// Session Settings
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_NAME', 'PS_TRACER_SESSION');

// Upload Settings
define('UPLOAD_DIR', __DIR__ . '/../public/assets/uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Pagination
define('RECORDS_PER_PAGE', 20);

// Security
define('HASH_ALGO', PASSWORD_BCRYPT);
define('HASH_COST', 10);

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
