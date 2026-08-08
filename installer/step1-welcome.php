<?php
// Courier Management System - Installer Step 1: Welcome & Requirements
// Created: 2026-08-08

require_once 'lock-check.php';

// Define requirements checks
$requirements = [
    'PHP Version >= 7.4' => [
        'check' => version_compare(PHP_VERSION, '7.4', '>='),
        'pass' => version_compare(PHP_VERSION, '7.4', '>='),
        'message' => 'PHP ' . PHP_VERSION . ' detected'
    ],
    'mysqli Extension' => [
        'check' => extension_loaded('mysqli'),
        'pass' => extension_loaded('mysqli'),
        'message' => extension_loaded('mysqli') ? 'mysqli extension is loaded' : 'mysqli extension is not loaded'
    ],
    'PDO Extension' => [
        'check' => extension_loaded('pdo') && extension_loaded('pdo_mysql'),
        'pass' => extension_loaded('pdo') && extension_loaded('pdo_mysql'),
        'message' => (extension_loaded('pdo') && extension_loaded('pdo_mysql')) ? 'PDO and pdo_mysql extensions are loaded' : 'PDO or pdo_mysql extension is not loaded'
    ],
    'GD Extension' => [
        'check' => extension_loaded('gd'),
        'pass' => extension_loaded('gd'),
        'message' => extension_loaded('gd') ? 'GD extension is loaded' : 'GD extension is not loaded'
    ],
    'config folder writable' => [
        'check' => is_writable(__DIR__ . '/../config'),
        'pass' => is_writable(__DIR__ . '/../config'),
        'message' => is_writable(__DIR__ . '/../config') ? 'config folder is writable' : 'config folder is not writable'
    ],
    'avatars folder writable' => [
        'check' => is_writable(__DIR__ . '/../assets/images/avatars'),
        'pass' => is_writable(__DIR__ . '/../assets/images/avatars'),
        'message' => is_writable(__DIR__ . '/../assets/images/avatars') ? 'avatars folder is writable' : 'avatars folder is not writable'
    ],
    'installer/tmp folder writable' => [
        'check' => is_writable(__DIR__ . '/tmp'),
        'pass' => is_writable(__DIR__ . '/tmp'),
        'message' => is_writable(__DIR__ . '/tmp') ? 'installer/tmp folder is writable' : 'installer/tmp folder is not writable'
    ]
];

// Check if all requirements pass
$allPassed = true;
foreach ($requirements as $req) {
    if (!$req['pass']) {
        $allPassed = false;
        break;
    }
}
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
                        <div class="step step-active" data-step="1">
                            <div class="step-number">1</div>
                            <div class="step-label">Welcome</div>
                        </div>
                        <div class="step" data-step="2">
                            <div class="step-number">2</div>
                            <div class="step-label">Database</div>
                        </div>
                        <div class="step" data-step="3">
                            <div class="step-number">3</div>
                            <div class="step-label">Import</div>
                        </div>
                        <div class="step" data-step="4">
                            <div class="step-number">4</div>
                            <div class="step-label">Admin</div>
                        </div>
                        <div class="step" data-step="5">
                            <div class="step-number">5</div>
                            <div class="step-label">Finish</div>
                        </div>
                    </div>
                    
                    <div class="installer-body">
                        <h2>Welcome to the Setup Wizard</h2>
                        <p class="text-muted mb-4">
                            This wizard will guide you through the installation process. Please ensure all system requirements are met before proceeding.
                        </p>
                        
                        <h4 class="mb-3">System Requirements Check</h4>
                        <div class="requirements-list">
                            <?php foreach ($requirements as $name => $req): ?>
                                <div class="requirement-item <?php echo $req['pass'] ? 'pass' : 'fail'; ?>">
                                    <i class="fas <?php echo $req['pass'] ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                                    <span><?php echo $name; ?></span>
                                    <small class="text-muted d-block"><?php echo $req['message']; ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (!$allPassed): ?>
                            <div class="alert alert-danger mt-3">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Requirements Not Met</strong><br>
                                Please fix the failed requirements above and refresh this page to re-check.
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="installer-footer">
                        <a href="step2-database.php" class="btn btn-primary <?php echo $allPassed ? '' : 'disabled'; ?>" <?php echo $allPassed ? '' : 'disabled'; ?>>
                            Next <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
