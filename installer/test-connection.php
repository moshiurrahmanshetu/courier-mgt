<?php
// Courier Management System - Installer: Test Database Connection (AJAX)
// Created: 2026-08-08

require_once 'lock-check.php';

// Start session for installer (lock-check.php handles this)
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$host = $_POST['db_host'] ?? '';
$port = $_POST['db_port'] ?? '3306';
$dbname = $_POST['db_name'] ?? '';
$username = $_POST['db_user'] ?? '';
$password = $_POST['db_pass'] ?? '';

if (empty($host) || empty($port) || empty($dbname) || empty($username)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

try {
    // First test: connect to MySQL server without selecting database
    $mysqli = new mysqli($host, $username, $password, '', $port);
    
    if ($mysqli->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Could not connect: ' . $mysqli->connect_error]);
        exit;
    }
    
    // Second test: select the specific database
    if (!$mysqli->select_db($dbname)) {
        echo json_encode(['success' => false, 'message' => 'Connected to server, but database "' . $dbname . '" does not exist or is not accessible. Please create this database first via your hosting control panel, then test again.']);
        $mysqli->close();
        exit;
    }
    
    // Connection successful - store credentials in session
    $_SESSION['db_host'] = $host;
    $_SESSION['db_port'] = $port;
    $_SESSION['db_name'] = $dbname;
    $_SESSION['db_user'] = $username;
    $_SESSION['db_pass'] = $password;
    $_SESSION['connection_tested'] = true;
    
    $mysqli->close();
    
    echo json_encode(['success' => true, 'message' => 'Connection successful!']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Connection error: ' . $e->getMessage()]);
}
