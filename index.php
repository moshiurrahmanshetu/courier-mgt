<?php
// Courier Management System - Index (Entry Point)
// Created: 2026-08-08

// Check if system is installed
$lockFile = __DIR__ . '/config/installed.lock';
$configFile = __DIR__ . '/config/config.php';

// If not installed, redirect to installer
if (!file_exists($lockFile) || !file_exists($configFile)) {
    header('Location: installer/index.php');
    exit;
}

require_once 'config/config.php';

// Redirect based on authentication status
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'dashboard.php');
} else {
    header('Location: ' . BASE_URL . 'auth/login.php');
}
exit;
