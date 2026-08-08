<?php
// Courier Management System - Installer Entry Point
// Created: 2026-08-08

// Global lock check - this is included at the top of EVERY installer file
$lockFile = __DIR__ . '/../config/installed.lock';
$configFile = __DIR__ . '/../config/config.php';

// If both lock file and config exist, system is already installed
if (file_exists($lockFile) && file_exists($configFile) && is_readable($configFile)) {
    header('Location: ../auth/login.php');
    exit;
}

// Not installed - proceed to step 1
header('Location: step1-welcome.php');
exit;
