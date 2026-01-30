<?php
/**
 * Database Configuration
 * Update these settings according to your phpMyAdmin setup
 */

// Database connection settings
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'hucappdb');
define('DB_USER', 'root');  // Change this to your MySQL username
define('DB_PASS', '');      // Change this to your MySQL password
define('DB_CHARSET', 'utf8mb4');

// Application settings
define('APP_ENV', 'development'); // development or production

// Error reporting based on environment
if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}
