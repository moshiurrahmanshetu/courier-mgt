<?php
// Courier Management System - Configuration File
// Created: 2026-08-08

// Site Configuration
define('SITE_NAME', 'Courier Management System');
define('BASE_URL', '/courier-mgt/');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'courier_management_system');
define('DB_USER', 'root');
define('DB_PASS', '');

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
session_name('CMS_SESSION');
session_start();

// Error Reporting (Set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Timezone
date_default_timezone_set('UTC');
