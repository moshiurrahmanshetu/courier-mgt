<?php
// Courier Management System - Assign Permissions to Role
// Created: 2026-08-08

$pageTitle = 'Assign Permissions';
require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/db.php';

// Check if user has permission (Admin only)
if (!hasRole(['Admin'])) {
    setFlashMessage('error', 'Access denied. You do not have permission to access this module.');
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$roleId = $_GET['role_id'] ?? 0;

if (!$roleId) {
    setFlashMessage('error', 'Invalid role ID.');
    header('Location: ' . BASE_URL . 'modules/roles/list.php');
    exit;
}

// Fetch role data
try {
    $stmt = $pdo->prepare("SELECT * FROM roles WHERE id = :id");
    $stmt->execute(['id' => $roleId]);
    $role = $stmt->fetch();
    
    if (!$role) {
        setFlashMessage('error', 'Role not found.');
        header('Location: ' . BASE_URL . 'modules/roles/list.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Fetch Role Error: " . $e->getMessage());
    setFlashMessage('error', 'An error occurred while loading role data.');
    header('Location: ' . BASE_URL . 'modules/roles/list.php');
    exit;
}

// Handle permission assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Delete existing permissions for this role
        $stmt = $pdo->prepare("DELETE FROM role_permissions WHERE role_id = :role_id");
        $stmt->execute(['role_id' => $roleId]);
        
        // Insert new permissions based on submitted checkboxes
        if (isset($_POST['permissions']) && is_array($_POST['permissions'])) {
            $insertStmt = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)");
            
            foreach ($_POST['permissions'] as $permissionId) {
                $insertStmt->execute([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId
                ]);
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Refresh current user's permissions if they're editing their own role
        if ($_SESSION['role_id'] == $roleId) {
            refreshPermissions($pdo);
        }
        
        setFlashMessage('success', 'Permissions updated successfully for role: ' . htmlspecialchars($role['role_name']));
        header('Location: ' . BASE_URL . 'modules/roles/list.php');
        exit;
        
    } catch (PDOException $e) {
        // Rollback on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        error_log("Assign Permissions Error: " . $e->getMessage());
        setFlashMessage('error', 'An error occurred while updating permissions.');
    }
}

// Fetch all permissions grouped by module
try {
    $stmt = $pdo->query("
        SELECT * FROM permissions 
        ORDER BY module_key, action
    ");
    $allPermissions = $stmt->fetchAll();
    
    // Group by module
    $modules = [];
    foreach ($allPermissions as $permission) {
        if (!isset($modules[$permission['module_key']])) {
            $modules[$permission['module_key']] = [
                'label' => $permission['module_label'],
                'actions' => []
            ];
        }
        $modules[$permission['module_key']]['actions'][$permission['action']] = $permission;
    }
    
    // Fetch existing permissions for this role
    $stmt = $pdo->prepare("
        SELECT permission_id FROM role_permissions WHERE role_id = :role_id
    ");
    $stmt->execute(['role_id' => $roleId]);
    $existingPermissionIds = array_column($stmt->fetchAll(), 'permission_id');
    
} catch (PDOException $e) {
    error_log("Fetch Permissions Error: " . $e->getMessage());
    setFlashMessage('error', 'An error occurred while loading permissions.');
    $modules = [];
    $existingPermissionIds = [];
}

require_once '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="page-title">Assign Permissions: <?php echo htmlspecialchars($role['role_name']); ?></h2>
            <a href="<?php echo BASE_URL; ?>modules/roles/list.php" class="btn btn-secondary">Back to Roles</a>
        </div>
    </div>
    
    <?php echo displayFlashMessages(); ?>
    
    <?php if ($role['role_name'] === 'Admin'): ?>
    <div class="alert alert-warning mb-4">
        <i class="fas fa-exclamation-triangle"></i> 
        <strong>Caution:</strong> Removing permissions from Admin role may lock you out of parts of the system. Be careful when modifying Admin permissions.
    </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form method="POST" id="permissionsForm">
                <div class="table-responsive">
                    <table class="table table-bordered permission-matrix">
                        <thead>
                            <tr>
                                <th style="width: 25%;">Module</th>
                                <th style="width: 15%;">View</th>
                                <th style="width: 15%;">Create</th>
                                <th style="width: 15%;">Edit</th>
                                <th style="width: 15%;">Delete</th>
                                <th style="width: 15%;">Select All</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($modules as $moduleKey => $module): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($module['label']); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($moduleKey); ?></small>
                                </td>
                                
                                <?php foreach (['view', 'create', 'edit', 'delete'] as $action): ?>
                                <td class="text-center">
                                    <?php if (isset($module['actions'][$action])): ?>
                                        <?php 
                                            $permission = $module['actions'][$action];
                                            $isChecked = in_array($permission['id'], $existingPermissionIds);
                                        ?>
                                        <input type="checkbox" 
                                               class="permission-checkbox" 
                                               name="permissions[]" 
                                               value="<?php echo $permission['id']; ?>"
                                               data-module="<?php echo $moduleKey; ?>"
                                               <?php echo $isChecked ? 'checked' : ''; ?>>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <?php endforeach; ?>
                                
                                <td class="text-center">
                                    <input type="checkbox" 
                                           class="select-all-module" 
                                           data-module="<?php echo $moduleKey; ?>">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Permissions
                        </button>
                        <a href="<?php echo BASE_URL; ?>modules/roles/list.php" class="btn btn-secondary">Cancel</a>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="selectAll">
                            <label class="form-check-label" for="selectAll">Select All Modules</label>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="mt-3">
        <small class="text-muted">
            <i class="fas fa-info-circle"></i> 
            Check the boxes to grant permissions. Use "Select All" to quickly enable all permissions for a module or the entire system.
        </small>
    </div>
</div>

<style>
.permission-matrix th {
    background-color: var(--accent-color);
    color: white;
    text-align: center;
}

.permission-matrix td {
    vertical-align: middle;
}

.permission-matrix input[type="checkbox"] {
    transform: scale(1.5);
    cursor: pointer;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select all checkboxes for a specific module
    document.querySelectorAll('.select-all-module').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const module = this.dataset.module;
            const moduleCheckboxes = document.querySelectorAll('.permission-checkbox[data-module="' + module + '"]');
            
            moduleCheckboxes.forEach(function(cb) {
                cb.checked = checkbox.checked;
            });
        });
    });
    
    // Master select all
    document.getElementById('selectAll').addEventListener('change', function() {
        const allCheckboxes = document.querySelectorAll('.permission-checkbox');
        const allModuleSelectors = document.querySelectorAll('.select-all-module');
        
        allCheckboxes.forEach(function(cb) {
            cb.checked = this.checked;
        }.bind(this));
        
        allModuleSelectors.forEach(function(cb) {
            cb.checked = this.checked;
        }.bind(this));
    });
    
    // Update module select-all when individual checkboxes change
    document.querySelectorAll('.permission-checkbox').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const module = this.dataset.module;
            const moduleCheckboxes = document.querySelectorAll('.permission-checkbox[data-module="' + module + '"]');
            const moduleSelector = document.querySelector('.select-all-module[data-module="' + module + '"]');
            
            const allChecked = Array.from(moduleCheckboxes).every(cb => cb.checked);
            moduleSelector.checked = allChecked;
        });
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>
