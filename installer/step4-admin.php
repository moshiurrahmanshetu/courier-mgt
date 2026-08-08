<?php
// Courier Management System - Installer Step 4: Admin Account Setup
// Created: 2026-08-08

require_once 'lock-check.php';

// Re-check session credentials
if (!isset($_SESSION['db_host']) || !isset($_SESSION['db_name']) || !isset($_SESSION['db_user'])) {
    header('Location: step2-database.php?error=session_expired');
    exit;
}

$host = $_SESSION['db_host'];
$port = $_SESSION['db_port'];
$dbname = $_SESSION['db_name'];
$username = $_SESSION['db_user'];
$password = $_SESSION['db_pass'];

// Connect to database
try {
    $mysqli = new mysqli($host, $username, $password, $dbname, $port);
    
    if ($mysqli->connect_error) {
        throw new Exception('Connection failed: ' . $mysqli->connect_error);
    }
} catch (Exception $e) {
    header('Location: step2-database.php?error=connection_failed');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $usernameInput = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $passwordInput = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($fullName) || empty($usernameInput) || empty($email) || empty($passwordInput)) {
        $error = 'All fields are required';
    } elseif (strlen($passwordInput) < 8) {
        $error = 'Password must be at least 8 characters';
    } elseif ($passwordInput !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        try {
            // Start transaction
            $mysqli->begin_transaction();
            
            // Get Admin role ID
            $stmt = $mysqli->prepare("SELECT id FROM roles WHERE role_name = 'Admin'");
            $stmt->execute();
            $result = $stmt->get_result();
            $adminRole = $result->fetch_assoc();
            
            if (!$adminRole) {
                throw new Exception('Admin role not found in database');
            }
            
            $adminRoleId = $adminRole['id'];
            
            // Delete existing admin users
            $stmt = $mysqli->prepare("DELETE FROM users WHERE role_id = ?");
            $stmt->bind_param('i', $adminRoleId);
            $stmt->execute();
            
            // Insert new admin user
            $hashedPassword = password_hash($passwordInput, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("INSERT INTO users (full_name, username, email, password, role_id, is_active) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->bind_param('ssssi', $fullName, $usernameInput, $email, $hashedPassword, $adminRoleId);
            $stmt->execute();
            
            // Commit transaction
            $mysqli->commit();
            
            // Store admin info for display in step 5
            $_SESSION['admin_username'] = $usernameInput;
            
            header('Location: step5-finish.php');
            exit;
            
        } catch (Exception $e) {
            $mysqli->rollback();
            $error = 'Failed to create admin account: ' . $e->getMessage();
        }
    }
}

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courier Management System - Setup Wizard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="assets/installer.css" rel="stylesheet">
</head>
<body class="installer-body">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="installer-card">
                    <div class="installer-header">
                        <h1><i class="fas fa-truck"></i> Courier Management System</h1>
                        <p class="text-muted">Setup Wizard</p>
                    </div>
                    
                    <!-- Step Progress -->
                    <div class="step-progress">
                        <div class="step step-completed" data-step="1">
                            <div class="step-number"><i class="fas fa-check"></i></div>
                            <div class="step-label">Welcome</div>
                        </div>
                        <div class="step step-completed" data-step="2">
                            <div class="step-number"><i class="fas fa-check"></i></div>
                            <div class="step-label">Database</div>
                        </div>
                        <div class="step step-completed" data-step="3">
                            <div class="step-number"><i class="fas fa-check"></i></div>
                            <div class="step-label">Import</div>
                        </div>
                        <div class="step step-active" data-step="4">
                            <div class="step-number">4</div>
                            <div class="step-label">Admin</div>
                        </div>
                        <div class="step" data-step="5">
                            <div class="step-number">5</div>
                            <div class="step-label">Finish</div>
                        </div>
                    </div>
                    
                    <div class="installer-body">
                        <h2>Create Admin Account</h2>
                        <p class="text-muted mb-4">
                            Create your administrator account. This will replace the default admin account from the database dump.
                        </p>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required minlength="8">
                                    <small class="text-muted">Minimum 8 characters</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                Create Admin Account <i class="fas fa-arrow-right"></i>
                            </button>
                        </form>
                    </div>
                    
                    <div class="installer-footer">
                        <a href="step2-database.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
