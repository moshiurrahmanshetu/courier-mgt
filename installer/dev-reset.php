<?php
// Courier Management System - Installer Developer Reset Tool
// DEV TOOL ONLY - DO NOT INCLUDE IN FINAL MARKETPLACE PACKAGE
// Created: 2026-08-08
// Purpose: Reset installer for testing purposes during development

$lockFile = __DIR__ . '/../config/installed.lock';
$configFile = __DIR__ . '/../config/config.php';
$configSampleFile = __DIR__ . '/../config/config.sample.php';

// Handle confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_reset'])) {
    // Delete installed.lock
    if (file_exists($lockFile)) {
        unlink($lockFile);
    }
    
    // Delete or backup config.php
    if (file_exists($configFile)) {
        // Backup existing config.php as config.php.bak
        $backupFile = __DIR__ . '/../config/config.php.bak';
        copy($configFile, $backupFile);
        unlink($configFile);
    }
    
    // Redirect to installer
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Developer Reset - Courier Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f0f2f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .reset-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        .warning-icon {
            font-size: 64px;
            color: #ffc107;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="reset-card">
        <div class="text-center">
            <i class="fas fa-exclamation-triangle warning-icon"></i>
            <h2 class="mb-4">Developer Reset Tool</h2>
        </div>
        
        <div class="alert alert-warning">
            <strong>⚠️ Development Tool Only</strong><br>
            This tool is for testing purposes during development. DO NOT include this file in the final marketplace package.
        </div>
        
        <div class="alert alert-info">
            <strong>What this will do:</strong>
            <ul class="mb-0 mt-2">
                <li>Delete <code>/config/installed.lock</code> if it exists</li>
                <li>Backup existing <code>/config/config.php</code> to <code>/config/config.php.bak</code></li>
                <li>Delete <code>/config/config.php</code></li>
                <li>Redirect to the installer</li>
            </ul>
        </div>
        
        <div class="alert alert-danger">
            <strong>⚠️ Important:</strong><br>
            Your database tables will NOT be touched automatically. You'll need to re-upload your SQL dump during the installer, or manually drop tables if you want a truly clean database.
        </div>
        
        <form method="POST">
            <div class="d-grid gap-2">
                <button type="submit" name="confirm_reset" class="btn btn-warning">
                    <i class="fas fa-sync-alt"></i> Yes, Reset Installer
                </button>
                <a href="../index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
        
        <hr>
        
        <small class="text-muted">
            <strong>Current Status:</strong><br>
            installed.lock: <?php echo file_exists($lockFile) ? '<span class="text-success">EXISTS</span>' : '<span class="text-danger">NOT FOUND</span>'; ?><br>
            config.php: <?php echo file_exists($configFile) ? '<span class="text-success">EXISTS</span>' : '<span class="text-danger">NOT FOUND</span>'; ?><br>
            config.sample.php: <?php echo file_exists($configSampleFile) ? '<span class="text-success">EXISTS</span>' : '<span class="text-danger">NOT FOUND</span>'; ?>
        </small>
    </div>
</body>
</html>
