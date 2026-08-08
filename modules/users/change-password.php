<?php
// Courier Management System - Change Password
// Created: 2026-08-08

$pageTitle = 'Change Password';
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate input
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        setFlashMessage('error', 'All fields are required');
    } elseif (strlen($newPassword) < 6) {
        setFlashMessage('error', 'New password must be at least 6 characters');
    } elseif ($newPassword !== $confirmPassword) {
        setFlashMessage('error', 'New password and confirm password do not match');
    } else {
        try {
            // Get current user's password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id");
            $stmt->execute(['id' => $_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($currentPassword, $user['password'])) {
                // Hash new password
                $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                
                // Update password
                $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
                $stmt->execute(['password' => $newPasswordHash, 'id' => $_SESSION['user_id']]);
                
                setFlashMessage('success', 'Password changed successfully');
            } else {
                setFlashMessage('error', 'Current password is incorrect');
            }
        } catch (PDOException $e) {
            error_log("Password Change Error: " . $e->getMessage());
            setFlashMessage('error', 'An error occurred while changing your password');
        }
    }
}

require_once '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Change Password</h5>
                    
                    <?php echo displayFlashMessages(); ?>
                    
                    <form method="POST" id="passwordForm">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Change Password</button>
                        <a href="<?php echo BASE_URL; ?>dashboard.php" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
