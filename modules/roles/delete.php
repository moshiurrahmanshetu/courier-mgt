<?php
// Courier Management System - Delete Role
// Created: 2026-08-08

require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/db.php';

// Check if user has permission (Admin only)
if (!hasRole(['Admin'])) {
    setFlashMessage('error', 'Access denied. You do not have permission to perform this action.');
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$roleId = $_GET['id'] ?? 0;

if (!$roleId) {
    setFlashMessage('error', 'Invalid role ID.');
    header('Location: ' . BASE_URL . 'modules/roles/list.php');
    exit;
}

try {
    // Check if role exists
    $stmt = $pdo->prepare("SELECT * FROM roles WHERE id = :id");
    $stmt->execute(['id' => $roleId]);
    $role = $stmt->fetch();
    
    if (!$role) {
        setFlashMessage('error', 'Role not found.');
        header('Location: ' . BASE_URL . 'modules/roles/list.php');
        exit;
    }
    
    // Check if any users are assigned to this role
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role_id = :role_id");
    $stmt->execute(['role_id' => $roleId]);
    $userCount = $stmt->fetch()['count'];
    
    if ($userCount > 0) {
        setFlashMessage('error', "Cannot delete role, {$userCount} user(s) still assigned to it.");
        header('Location: ' . BASE_URL . 'modules/roles/list.php');
        exit;
    }
    
    // Delete role (this will cascade delete role_permissions due to FK constraint)
    $stmt = $pdo->prepare("DELETE FROM roles WHERE id = :id");
    $stmt->execute(['id' => $roleId]);
    
    setFlashMessage('success', 'Role deleted successfully.');
    header('Location: ' . BASE_URL . 'modules/roles/list.php');
    exit;
    
} catch (PDOException $e) {
    error_log("Delete Role Error: " . $e->getMessage());
    setFlashMessage('error', 'An error occurred while deleting the role.');
    header('Location: ' . BASE_URL . 'modules/roles/list.php');
    exit;
}
