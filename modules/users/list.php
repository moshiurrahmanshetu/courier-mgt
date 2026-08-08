<?php
// Courier Management System - User List
// Created: 2026-08-08

$pageTitle = 'Manage Users';
require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/db.php';

// Check if user has permission
if (!hasRole(['Admin'])) {
    setFlashMessage('error', 'Access denied. You do not have permission to access this module.');
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

// Handle search/filter
$search = trim($_GET['search'] ?? '');
$roleFilter = $_GET['role'] ?? '';

try {
    // Build query
    $sql = "SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE 1=1";
    $params = [];
    
    if ($search) {
        $sql .= " AND (u.full_name LIKE :search OR u.email LIKE :search OR u.username LIKE :search)";
        $params['search'] = "%$search%";
    }
    
    if ($roleFilter) {
        $sql .= " AND u.role_id = :role_id";
        $params['role_id'] = $roleFilter;
    }
    
    $sql .= " ORDER BY u.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
    // Get roles for filter dropdown
    $stmt = $pdo->query("SELECT * FROM roles ORDER BY role_name");
    $roles = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("User List Error: " . $e->getMessage());
    setFlashMessage('error', 'An error occurred while loading users');
    $users = [];
    $roles = [];
}

require_once '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="page-title">Manage Users</h2>
        </div>
    </div>
    
    <?php echo displayFlashMessages(); ?>
    
    <div class="card">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <form method="GET" class="d-flex">
                        <input type="text" class="form-control me-2" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary">Search</button>
                        <?php if ($search || $roleFilter): ?>
                            <a href="<?php echo BASE_URL; ?>modules/users/list.php" class="btn btn-secondary ms-2">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
                <div class="col-md-6 text-end">
                    <a href="<?php echo BASE_URL; ?>modules/users/create.php" class="btn btn-success">
                        <i class="fas fa-plus"></i> Add New User
                    </a>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-3">
                    <select class="form-select" id="roleFilter" onchange="filterByRole()">
                        <option value="">All Roles</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['id']; ?>" <?php echo $roleFilter == $role['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($role['role_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Avatar</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="8" class="text-center">No users found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <?php 
                                    $avatarPath = (!empty($user['avatar'])) ? BASE_URL . 'assets/images/avatars/' . $user['avatar'] : BASE_URL . 'assets/images/avatars/default-avatar.svg';
                                ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo $avatarPath; ?>" alt="Avatar" class="table-avatar">
                                    </td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($user['role_name']); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($user['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>modules/users/edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <a href="<?php echo BASE_URL; ?>modules/users/delete.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to <?php echo $user['is_active'] ? 'deactivate' : 'activate'; ?> this user?');">
                                                <?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function filterByRole() {
    const roleFilter = document.getElementById('roleFilter').value;
    const searchParams = new URLSearchParams(window.location.search);
    
    if (roleFilter) {
        searchParams.set('role', roleFilter);
    } else {
        searchParams.delete('role');
    }
    
    window.location.href = '<?php echo BASE_URL; ?>modules/users/list.php?' + searchParams.toString();
}
</script>

<?php require_once '../../includes/footer.php'; ?>
