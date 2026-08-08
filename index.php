<?php
// Courier Management System - Index (Entry Point)
// Created: 2026-08-08

require_once 'config/config.php';

// Redirect based on authentication status
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'dashboard.php');
} else {
    header('Location: ' . BASE_URL . 'auth/login.php');
}
exit;
