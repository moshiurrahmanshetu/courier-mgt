<?php
// Courier Management System - User Profile
// Created: 2026-08-08

$pageTitle = 'My Profile';
require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/db.php';

$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    // Validate input
    if (empty($fullName) || empty($email)) {
        setFlashMessage('error', 'Full name and email are required');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlashMessage('error', 'Invalid email format');
    } else {
        try {
            // Check if email is already taken by another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
            $stmt->execute(['email' => $email, 'id' => $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                setFlashMessage('error', 'Email is already in use by another user');
            } else {
                // Update user profile
                $stmt = $pdo->prepare("UPDATE users SET full_name = :full_name, email = :email, phone = :phone WHERE id = :id");
                $stmt->execute([
                    'full_name' => $fullName,
                    'email' => $email,
                    'phone' => $phone ?: null,
                    'id' => $_SESSION['user_id']
                ]);
                
                // Update session
                $_SESSION['full_name'] = $fullName;
                $_SESSION['email'] = $email;
                $_SESSION['phone'] = $phone;
                
                setFlashMessage('success', 'Profile updated successfully');
            }
        } catch (PDOException $e) {
            error_log("Profile Update Error: " . $e->getMessage());
            setFlashMessage('error', 'An error occurred while updating your profile');
        }
    }
}

// Handle avatar upload
if (isset($_POST['upload_avatar']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        $fileType = $_FILES['avatar']['type'];
        $fileSize = $_FILES['avatar']['size'];
        
        if (!in_array($fileType, $allowedTypes)) {
            setFlashMessage('error', 'Only JPG, JPEG, and PNG files are allowed');
        } elseif ($fileSize > $maxSize) {
            setFlashMessage('error', 'File size must be less than 2MB');
        } else {
            $fileExt = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $newFileName = 'user_' . $_SESSION['user_id'] . '_' . time() . '.' . $fileExt;
            $uploadPath = $_SERVER['DOCUMENT_ROOT'] . BASE_URL . 'assets/images/avatars/' . $newFileName;
            
            // Create directory if it doesn't exist
            $uploadDir = dirname($uploadPath);
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadPath)) {
                try {
                    // Update database
                    $stmt = $pdo->prepare("UPDATE users SET avatar = :avatar WHERE id = :id");
                    $stmt->execute(['avatar' => $newFileName, 'id' => $_SESSION['user_id']]);
                    
                    // Update session
                    $_SESSION['avatar'] = $newFileName;
                    
                    setFlashMessage('success', 'Avatar updated successfully');
                } catch (PDOException $e) {
                    error_log("Avatar Upload Error: " . $e->getMessage());
                    setFlashMessage('error', 'An error occurred while updating your avatar');
                }
            } else {
                setFlashMessage('error', 'Failed to upload file');
            }
        }
    } else {
        setFlashMessage('error', 'Please select a file to upload');
    }
}

// Get current user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Profile Fetch Error: " . $e->getMessage());
    setFlashMessage('error', 'An error occurred while loading your profile');
    $user = [
        'full_name' => $_SESSION['full_name'],
        'email' => $_SESSION['email'],
        'phone' => $_SESSION['phone'] ?? ''
    ];
}

$avatarPath = (!empty($user['avatar'])) ? BASE_URL . 'assets/images/avatars/' . $user['avatar'] : BASE_URL . 'assets/images/avatars/default-avatar.svg';

require_once '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title mb-4">Profile Picture</h5>
                    <img src="<?php echo $avatarPath; ?>" alt="Profile Avatar" class="profile-avatar-large mb-3">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <input type="file" class="form-control" id="avatar" name="avatar" accept="image/jpeg,image/jpg,image/png">
                        </div>
                        <button type="submit" name="upload_avatar" class="btn btn-primary">Change Avatar</button>
                    </form>
                    <small class="text-muted d-block mt-2">Allowed: JPG, PNG (Max 2MB)</small>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Personal Information</h5>
                    
                    <?php echo displayFlashMessages(); ?>
                    
                    <form method="POST" id="profileForm">
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" disabled>
                            <small class="text-muted">Username cannot be changed</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['role_name']); ?>" disabled>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
