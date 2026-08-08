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

/**
 * Check if current user has a specific permission for a module and action
 * @param PDO $pdo Database connection
 * @param string $moduleKey Module key (e.g., 'customers', 'users')
 * @param string $action Action (e.g., 'view', 'create', 'edit', 'delete')
 * @return bool True if user has permission, false otherwise
 */
function hasPermission($pdo, $moduleKey, $action) {
    // Load config if not already loaded
    if (!defined('BASE_URL')) {
        require_once __DIR__ . '/../config/config.php';
    }
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id'])) {
        return false;
    }
    
    // Construct the permission key
    $permissionKey = $moduleKey . '.' . $action;
    
    // First check session-cached permissions
    if (isset($_SESSION['permissions']) && is_array($_SESSION['permissions'])) {
        return in_array($permissionKey, $_SESSION['permissions']);
    }
    
    // Fallback to DB query if session data is missing
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM role_permissions rp
            JOIN permissions p ON rp.permission_id = p.id
            WHERE rp.role_id = :role_id 
            AND p.permission_key = :permission_key
        ");
        $stmt->execute([
            'role_id' => $_SESSION['role_id'],
            'permission_key' => $permissionKey
        ]);
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
    } catch (PDOException $e) {
        error_log("Permission Check Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Refresh the current user's permissions in session
 * Call this after permission changes or if session data becomes stale
 * @param PDO $pdo Database connection
 * @return bool True if successful, false otherwise
 */
function refreshPermissions($pdo) {
    if (!isset($_SESSION['role_id'])) {
        return false;
    }
    if (!isset($_SESSION['role_id'])) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT p.permission_key 
            FROM role_permissions rp
            JOIN permissions p ON rp.permission_id = p.id
            WHERE rp.role_id = :role_id
        ");
        $stmt->execute(['role_id' => $_SESSION['role_id']]);
        $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $_SESSION['permissions'] = $permissions;
        return true;
    } catch (PDOException $e) {
        error_log("Refresh Permissions Error: " . $e->getMessage());
        return false;
    }
}
