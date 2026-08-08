<?php
// Courier Management System - Authentication Check
// Created: 2026-08-08
// Include this at the top of every protected page

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/config.php';
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}
