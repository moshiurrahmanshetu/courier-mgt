<?php
// Courier Management System - Installer Step 3: SQL Import
// Created: 2026-08-08

require_once 'lock-check.php';

// Re-validate session credentials
if (!isset($_SESSION['db_host']) || !isset($_SESSION['db_name']) || !isset($_SESSION['db_user'])) {
    header('Location: step2-database.php?error=session_expired');
    exit;
}

$host = $_SESSION['db_host'];
$port = $_SESSION['db_port'];
$dbname = $_SESSION['db_name'];
$username = $_SESSION['db_user'];
$password = $_SESSION['db_pass'];

// Re-test connection server-side
try {
    $mysqli = new mysqli($host, $username, $password, $dbname, $port);
    
    if ($mysqli->connect_error) {
        throw new Exception('Connection failed: ' . $mysqli->connect_error);
    }
} catch (Exception $e) {
    header('Location: step2-database.php?error=connection_failed');
    exit;
}

// Validate uploaded file
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['sql_file'])) {
    header('Location: step2-database.php?error=no_file');
    exit;
}

$file = $_FILES['sql_file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    header('Location: step2-database.php?error=upload_failed');
    exit;
}

$fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if ($fileExt !== 'sql') {
    header('Location: step2-database.php?error=invalid_file');
    exit;
}

// Check file size
$maxSize = min(ini_get('upload_max_filesize'), ini_get('post_max_size'));
$maxSizeBytes = return_bytes($maxSize);
if ($file['size'] > $maxSizeBytes) {
    header('Location: step2-database.php?error=file_too_large');
    exit;
}

// Move to temp directory
$tmpPath = __DIR__ . '/tmp/' . uniqid('import_') . '.sql';
if (!move_uploaded_file($file['tmp_name'], $tmpPath)) {
    header('Location: step2-database.php?error=move_failed');
    exit;
}

// Read SQL file
$sqlContent = file_get_contents($tmpPath);
if ($sqlContent === false) {
    unlink($tmpPath);
    header('Location: step2-database.php?error=read_failed');
    exit;
}

// Execute SQL import
try {
    // Use multi_query for executing multiple statements
    if (!$mysqli->multi_query($sqlContent)) {
        throw new Exception('SQL import failed: ' . $mysqli->error);
    }
    
    // Process all result sets
    do {
        if ($result = $mysqli->store_result()) {
            $result->free();
        }
    } while ($mysqli->more_results() && $mysqli->next_result());
    
    // Check for any final error
    if ($mysqli->error) {
        throw new Exception('SQL import error: ' . $mysqli->error);
    }
    
    $importSuccess = true;
    $importError = null;
    
} catch (Exception $e) {
    $importSuccess = false;
    $importError = $e->getMessage();
    
    // Log error for debugging
    error_log("SQL Import Error: " . $e->getMessage());
    file_put_contents(__DIR__ . '/tmp/import-error.log', date('Y-m-d H:i:s') . " - " . $e->getMessage() . "\n", FILE_APPEND);
}

// Clean up temp file
unlink($tmpPath);
$mysqli->close();

if (!$importSuccess) {
    header('Location: step2-database.php?error=import_failed');
    exit;
}

// Success - proceed to step 4
header('Location: step4-admin.php');
exit;

function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch($last) {
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
    }
    return $val;
}
