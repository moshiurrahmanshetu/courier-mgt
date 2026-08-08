<?php
// Courier Management System - Installer: Cleanup (AJAX)
// Created: 2026-08-08

require_once 'lock-check.php';

header('Content-Type: application/json');

// Verify lock file exists (extra safety)
$lockFile = __DIR__ . '/../config/installed.lock';
if (!file_exists($lockFile)) {
    echo json_encode(['success' => false, 'message' => 'System not installed']);
    exit;
}

$installerDir = __DIR__;

try {
    // Delete all files and folders inside installer directory except this script
    $files = array_diff(scandir($installerDir), ['.', '..']);
    
    foreach ($files as $file) {
        $filePath = $installerDir . '/' . $file;
        
        if (is_dir($filePath)) {
            // Recursively delete directory
            deleteDirectory($filePath);
        } elseif ($file !== 'cleanup.php') {
            // Delete file
            unlink($filePath);
        }
    }
    
    // Create minimal index.php that redirects to login
    $redirectContent = '<?php
// Courier Management System - Installer Redirect
header("Location: ../auth/login.php");
exit;
';
    file_put_contents($installerDir . '/index.php', $redirectContent);
    
    // Delete this cleanup.php file itself
    unlink(__FILE__);
    
    echo json_encode(['success' => true, 'message' => 'Installer files removed successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Cleanup failed: ' . $e->getMessage()]);
}

function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }
    
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            deleteDirectory($path);
        } else {
            unlink($path);
        }
    }
    
    return rmdir($dir);
}
