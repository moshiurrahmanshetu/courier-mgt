<?php
// Courier Management System - Logout
// Created: 2026-08-08

if (!defined('BASE_URL')) {
    require_once '../config/config.php';
}

// Destroy session completely
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Redirect to login page
header('Location: ' . BASE_URL . 'auth/login.php');
exit;
