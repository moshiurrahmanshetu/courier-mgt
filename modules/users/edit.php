<?php
// Courier Management System - Edit User
// Created: 2026-08-08

$pageTitle = 'Edit User';
require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/db.php';

// Check if user has permission
if (!hasRole(['Admin'])) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$userId = $_GET['id'] ?? 0;

if (!$userId) {
    header('Location: ' . BASE_URL . 'modules/users/list.php');
    exit;
}

$success = '';
$error = '';

// Handle user update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $roleId = $_POST['role_id'] ?? '';
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate input
    if (empty($fullName) || empty($email) || empty($roleId)) {
        $error = 'Please fill in all required fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } else {
        try {
            // Check if email is already taken by another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
            $stmt->execute(['email' => $email, 'id' => $userId]);
            if ($stmt->fetch()) {
                $error = 'Email is already in use by another user';
            } else {
                // Update user
                $stmt = $pdo->prepare("UPDATE users SET full_name = :full_name, email = :email, phone = :phone, role_id = :role_id, is_active = :is_active WHERE id = :id");
                $stmt->execute([
                    'full_name' => $fullName,
                    'email' => $email,
                    'phone' => $phone ?: null,
                    'role_id' => $roleId,
                    'is_active' => $isActive,
                    'id' => $userId
                ]);
                
                $success = 'User updated successfully';
            }
        } catch (PDOException $e) {
            error_log("Edit User Error: " . $e->getMessage());
            $error = 'An error occurred while updating the user';
        }
    }
}

// Handle password reset
if (isset($_POST['reset_password']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($newPassword) || empty($confirmPassword)) {
        $error = 'Please fill in both password fields';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Password and confirm password do not match';
    } else {
        try {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
            $stmt->execute(['password' => $passwordHash, 'id' => $userId]);
            
            $success = 'Password reset successfully';
        } catch (PDOException $e) {
            error_log("Password Reset Error: " . $e->getMessage());
            $error = 'An error occurred while resetting the password';
        }
    }
}

// Get user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header('Location: ' . BASE_URL . 'modules/users/list.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Fetch User Error: " . $e->getMessage());
    $error = 'An error occurred while loading user data';
    $user = [];
}

// Get roles for dropdown
try {
    $stmt = $pdo->query("SELECT * FROM roles ORDER BY role_name");
    $roles = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Fetch Roles Error: " . $e->getMessage());
    $roles = [];
}

// Use POST data if available, otherwise use user data
$fullName = $_POST['full_name'] ?? $user['full_name'] ?? '';
$email = $_POST['email'] ?? $user['email'] ?? '';
$phone = $_POST['phone'] ?? $user['phone'] ?? '';
$roleId = $_POST['role_id'] ?? $user['role_id'] ?? '';
$isActive = isset($_POST['is_active']) ? $_POST['is_active'] : ($user['is_active'] ?? 1);

$avatarPath = (!empty($user['avatar'])) ? BASE_URL . 'assets/images/avatars/' . $user['avatar'] : BASE_URL . 'assets/images/avatars/default-avatar.svg';

require_once '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="page-title">Edit User</h2>
            <a href="<?php echo BASE_URL; ?>modules/users/list.php" class="btn btn-secondary">Back to Users</a>
        </div>
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title mb-4">Profile Picture</h5>
                    <img src="<?php echo $avatarPath; ?>" alt="Profile Avatar" class="profile-avatar-large mb-3">
                    <p class="text-muted">Username: <?php echo htmlspecialchars($user['username']); ?></p>
                    <p class="text-muted">User ID: <?php echo $user['id']; ?></p>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-body">
                    <h5 class="card-title mb-4">Reset Password</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" minlength="6">
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6">
                        </div>
                        
                        <button type="submit" name="reset_password" class="btn btn-warning">Reset Password</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">User Information</h5>
                    
                    <form method="POST" id="editUserForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="full_name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($fullName); ?>" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="role_id" class="form-label">Role *</label>
                                    <select class="form-select" id="role_id" name="role_id" required>
                                        <option value="">Select Role</option>
                                        <?php foreach ($roles as $role): ?>
                                            <option value="<?php echo $role['id']; ?>" <?php echo $roleId == $role['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($role['role_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" <?php echo $isActive ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_active">
                                    Active User
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update User</button>
                        <a href="<?php echo BASE_URL; ?>modules/users/list.php" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
