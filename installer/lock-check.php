<?php
// Courier Management System - Installer Lock Check Helper
// Created: 2026-08-08
// This file is included at the top of every installer step file

$lockFile = __DIR__ . '/../config/installed.lock';
$configFile = __DIR__ . '/../config/config.php';

// If both lock file and config exist, system is already installed
if (file_exists($lockFile) && file_exists($configFile) && is_readable($configFile)) {
    header('Location: ../auth/login.php');
    exit;
}

// Start session for installer
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
