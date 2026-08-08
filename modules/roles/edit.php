<?php
// Courier Management System - Edit Role
// Created: 2026-08-08

$pageTitle = 'Edit Role';
require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/db.php';

// Check if user has permission (Admin only)
if (!hasRole(['Admin'])) {
    setFlashMessage('error', 'Access denied. You do not have permission to access this module.');
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$roleId = $_GET['id'] ?? 0;

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roleName = trim($_POST['role_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    // Validate input
    if (empty($roleName)) {
        setFlashMessage('error', 'Role name is required');
    } elseif (strlen($roleName) > 50) {
        setFlashMessage('error', 'Role name must not exceed 50 characters');
    } else {
        try {
            // Check if role name already exists (excluding current role)
            $stmt = $pdo->prepare("SELECT id FROM roles WHERE role_name = :role_name AND id != :id");
            $stmt->execute(['role_name' => $roleName, 'id' => $roleId]);
            if ($stmt->fetch()) {
                setFlashMessage('error', 'Role name already exists');
            } else {
                // Update role
                $stmt = $pdo->prepare("UPDATE roles SET role_name = :role_name, description = :description WHERE id = :id");
                $stmt->execute([
                    'role_name' => $roleName,
                    'description' => $description ?: null,
                    'id' => $roleId
                ]);
                
                setFlashMessage('success', 'Role updated successfully.');
                header('Location: ' . BASE_URL . 'modules/roles/list.php');
                exit;
            }
        } catch (PDOException $e) {
            error_log("Update Role Error: " . $e->getMessage());
            setFlashMessage('error', 'An error occurred while updating the role.');
        }
    }
}

// Use POST data if available, otherwise use role data
$roleName = $_POST['role_name'] ?? $role['role_name'];
$description = $_POST['description'] ?? $role['description'];

require_once '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="page-title">Edit Role</h2>
            <a href="<?php echo BASE_URL; ?>modules/roles/list.php" class="btn btn-secondary">Back to Roles</a>
        </div>
    </div>
    
    <?php echo displayFlashMessages(); ?>
    
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Role Information</h5>
                    
                    <form method="POST" id="editRoleForm">
                        <div class="mb-3">
                            <label for="role_id" class="form-label">Role ID</label>
                            <input type="text" class="form-control" id="role_id" value="<?php echo $role['id']; ?>" readonly>
                            <small class="text-muted">Role ID cannot be changed</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="role_name" class="form-label">Role Name *</label>
                            <input type="text" class="form-control" id="role_name" name="role_name" 
                                   value="<?php echo htmlspecialchars($roleName); ?>" required maxlength="50">
                            <div class="invalid-feedback">Role name is required</div>
                            <small class="text-muted">Max 50 characters, must be unique</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($description); ?></textarea>
                            <small class="text-muted">Optional description of the role's purpose</small>
                        </div>
                        
                        <div class="mb-3">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> 
                                To change permissions for this role, use the "Assign Permissions" button on the roles list page.
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update Role</button>
                        <a href="<?php echo BASE_URL; ?>modules/roles/assign-permissions.php?role_id=<?php echo $role['id']; ?>" class="btn btn-warning">Assign Permissions</a>
                        <a href="<?php echo BASE_URL; ?>modules/roles/list.php" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
