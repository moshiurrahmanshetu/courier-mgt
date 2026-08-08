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

/**
 * Set a flash message for one-time display
 * @param string $type Message type (success, error, info, warning)
 * @param string $message Message content
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_' . $type] = $message;
}

/**
 * Get and clear a flash message
 * @param string $type Message type (success, error, info, warning)
 * @return string|null Message content or null if not set
 */
function getFlashMessage($type) {
    if (isset($_SESSION['flash_' . $type])) {
        $message = $_SESSION['flash_' . $type];
        unset($_SESSION['flash_' . $type]);
        return $message;
    }
    return null;
}

/**
 * Display all flash messages as Bootstrap alerts
 * Call this function in your views to display pending flash messages
 */
function displayFlashMessages() {
    $types = ['success', 'error', 'info', 'warning'];
    $output = '';
    
    foreach ($types as $type) {
        $message = getFlashMessage($type);
        if ($message) {
            $bootstrapClass = 'success';
            if ($type === 'error') $bootstrapClass = 'danger';
            elseif ($type === 'info') $bootstrapClass = 'info';
            elseif ($type === 'warning') $bootstrapClass = 'warning';
            
            $output .= '<div class="alert alert-' . $bootstrapClass . ' alert-dismissible fade show" role="alert">';
            $output .= htmlspecialchars($message);
            $output .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            $output .= '</div>';
        }
    }
    
    return $output;
}
