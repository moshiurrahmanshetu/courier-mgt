<?php
// Courier Management System - Create Role
// Created: 2026-08-08

$pageTitle = 'Add New Role';
require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/db.php';

// Check if user has permission (Admin only)
if (!hasRole(['Admin'])) {
    setFlashMessage('error', 'Access denied. You do not have permission to access this module.');
    header('Location: ' . BASE_URL . 'dashboard.php');
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
            // Check if role name already exists
            $stmt = $pdo->prepare("SELECT id FROM roles WHERE role_name = :role_name");
            $stmt->execute(['role_name' => $roleName]);
            if ($stmt->fetch()) {
                setFlashMessage('error', 'Role name already exists');
            } else {
                // Insert new role
                $stmt = $pdo->prepare("INSERT INTO roles (role_name, description) VALUES (:role_name, :description)");
                $stmt->execute([
                    'role_name' => $roleName,
                    'description' => $description ?: null
                ]);
                
                $newRoleId = $pdo->lastInsertId();
                setFlashMessage('success', 'Role created successfully. Please assign permissions.');
                header('Location: ' . BASE_URL . 'modules/roles/assign-permissions.php?role_id=' . $newRoleId);
                exit;
            }
        } catch (PDOException $e) {
            error_log("Create Role Error: " . $e->getMessage());
            setFlashMessage('error', 'An error occurred while creating the role.');
        }
    }
}

require_once '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="page-title">Add New Role</h2>
            <a href="<?php echo BASE_URL; ?>modules/roles/list.php" class="btn btn-secondary">Back to Roles</a>
        </div>
    </div>
    
    <?php echo displayFlashMessages(); ?>
    
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Role Information</h5>
                    
                    <form method="POST" id="createRoleForm">
                        <div class="mb-3">
                            <label for="role_name" class="form-label">Role Name *</label>
                            <input type="text" class="form-control" id="role_name" name="role_name" 
                                   value="<?php echo htmlspecialchars($_POST['role_name'] ?? ''); ?>" required maxlength="50">
                            <div class="invalid-feedback">Role name is required</div>
                            <small class="text-muted">Max 50 characters, must be unique</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            <small class="text-muted">Optional description of the role's purpose</small>
                        </div>
                        
                        <div class="mb-3">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> After creating the role, you will be redirected to assign permissions.
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Create Role</button>
                        <a href="<?php echo BASE_URL; ?>modules/roles/list.php" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
