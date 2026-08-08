<?php
// Courier Management System - Role List
// Created: 2026-08-08

$pageTitle = 'Manage Roles';
require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/db.php';

// Check if user has permission (Admin only)
if (!hasRole(['Admin'])) {
    setFlashMessage('error', 'Access denied. You do not have permission to access this module.');
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

try {
    // Get all roles with user counts and permission counts
    $sql = "SELECT r.*, 
            (SELECT COUNT(*) FROM users WHERE role_id = r.id AND is_active = 1) as user_count,
            (SELECT COUNT(*) FROM role_permissions WHERE role_id = r.id) as permission_count
            FROM roles r 
            ORDER BY r.id";
    
    $stmt = $pdo->query($sql);
    $roles = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Role List Error: " . $e->getMessage());
    setFlashMessage('error', 'An error occurred while loading roles.');
    $roles = [];
}

require_once '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="page-title">Manage Roles</h2>
            <a href="<?php echo BASE_URL; ?>dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
    
    <?php echo displayFlashMessages(); ?>
    
    <div class="card">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-12 text-end">
                    <a href="<?php echo BASE_URL; ?>modules/roles/create.php" class="btn btn-success">
                        <i class="fas fa-plus"></i> Add New Role
                    </a>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Role Name</th>
                            <th>Description</th>
                            <th>Users Assigned</th>
                            <th>Permissions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($roles)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No roles found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($roles as $role): ?>
                                <tr>
                                    <td><?php echo $role['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($role['role_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($role['description'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $role['user_count']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo $role['permission_count']; ?></span>
                                    </td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>modules/roles/edit.php?id=<?php echo $role['id']; ?>" 
                                           class="btn btn-sm btn-primary" title="Edit Role">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>modules/roles/assign-permissions.php?role_id=<?php echo $role['id']; ?>" 
                                           class="btn btn-sm btn-warning" title="Assign Permissions">
                                            <i class="fas fa-key"></i>
                                        </a>
                                        <?php if ($role['user_count'] == 0): ?>
                                            <a href="<?php echo BASE_URL; ?>modules/roles/delete.php?id=<?php echo $role['id']; ?>" 
                                               class="btn btn-sm btn-danger" title="Delete Role"
                                               onclick="return confirm('Are you sure you want to delete this role?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-danger" disabled title="Cannot delete: users assigned">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> 
                    Roles with assigned users cannot be deleted. Remove users from the role first before deletion.
                </small>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
