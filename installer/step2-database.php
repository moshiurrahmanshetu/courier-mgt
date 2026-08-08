<?php
// Courier Management System - Installer Step 2: Database Configuration
// Created: 2026-08-08

require_once 'lock-check.php';
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
                        <div class="step step-active" data-step="2">
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
                        <h2>Database Configuration</h2>
                        <p class="text-muted mb-4">
                            Please enter your database credentials and upload your SQL dump file.
                        </p>
                        
                        <form id="db-form" method="POST" action="step3-import.php" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="db_host" class="form-label">Database Host</label>
                                    <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="db_port" class="form-label">Database Port</label>
                                    <input type="text" class="form-control" id="db_port" name="db_port" value="3306" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="db_name" class="form-label">Database Name</label>
                                <input type="text" class="form-control" id="db_name" name="db_name" required>
                                <small class="text-muted">The database must already exist on your server.</small>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="db_user" class="form-label">Database Username</label>
                                    <input type="text" class="form-control" id="db_user" name="db_user" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="db_pass" class="form-label">Database Password</label>
                                    <input type="password" class="form-control" id="db_pass" name="db_pass">
                                    <small class="text-muted">Leave empty if no password</small>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="sql_file" class="form-label">SQL Dump File</label>
                                <input type="file" class="form-control" id="sql_file" name="sql_file" accept=".sql" required>
                                <small class="text-muted">Upload your complete database dump file (.sql)</small>
                                <div id="file-name" class="mt-1 text-muted"></div>
                            </div>
                            
                            <div class="mb-3">
                                <button type="button" id="test-connection-btn" class="btn btn-secondary">
                                    <i class="fas fa-plug"></i> Test Connection
                                </button>
                                <div id="connection-result" class="mt-2"></div>
                            </div>
                        </form>
                    </div>
                    
                    <div class="installer-footer">
                        <a href="step1-welcome.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                        <button type="submit" form="db-form" id="import-btn" class="btn btn-primary" disabled>
                            Import & Continue <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/installer.js"></script>
</body>
</html>
