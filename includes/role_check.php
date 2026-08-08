<?php
// Courier Management System - Role Check Helper Functions
// Created: 2026-08-08

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/config.php';
}

/**
 * Check if current user has one of the allowed roles
 * @param array $allowedRoles Array of role names that are allowed access
 * @return bool True if user has permission, false otherwise
 */
function hasRole($allowedRoles) {
    if (!isset($_SESSION['role_name'])) {
        return false;
    }
    
    return in_array($_SESSION['role_name'], $allowedRoles);
}

/**
 * Check if current user is Admin
 * @return bool True if user is Admin, false otherwise
 */
function isAdmin() {
    return hasRole(['Admin']);
}

/**
 * Check if current user is Staff or Admin
 * @return bool True if user is Staff or Admin, false otherwise
 */
function isStaffOrAdmin() {
    return hasRole(['Admin', 'Staff']);
}

/**
 * Get current user's role name
 * @return string|null Current role name or null if not set
 */
function getCurrentRole() {
    return $_SESSION['role_name'] ?? null;
}

/**
 * Get current user's ID
 * @return int|null Current user ID or null if not set
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}
