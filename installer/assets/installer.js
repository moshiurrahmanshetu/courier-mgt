// Courier Management System - Installer JavaScript
// Created: 2026-08-08

document.addEventListener('DOMContentLoaded', function() {
    // Test Connection AJAX
    const testConnectionBtn = document.getElementById('test-connection-btn');
    const connectionResult = document.getElementById('connection-result');
    const importBtn = document.getElementById('import-btn');
    const sqlFileInput = document.getElementById('sql_file');
    const fileNameDisplay = document.getElementById('file-name');
    
    if (testConnectionBtn) {
        testConnectionBtn.addEventListener('click', function() {
            const host = document.getElementById('db_host').value;
            const port = document.getElementById('db_port').value;
            const dbname = document.getElementById('db_name').value;
            const username = document.getElementById('db_user').value;
            const password = document.getElementById('db_pass').value;
            
            if (!host || !port || !dbname || !username) {
                showConnectionResult('error', 'Please fill in all database fields');
                return;
            }
            
            // Disable button and show loading
            testConnectionBtn.disabled = true;
            testConnectionBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
            showConnectionResult('', '');
            
            const formData = new FormData();
            formData.append('db_host', host);
            formData.append('db_port', port);
            formData.append('db_name', dbname);
            formData.append('db_user', username);
            formData.append('db_pass', password);
            
            fetch('test-connection.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                showConnectionResult(data.success ? 'success' : 'error', data.message);
                
                if (data.success) {
                    checkImportButtonState();
                }
            })
            .catch(error => {
                showConnectionResult('error', 'Connection test failed: ' + error.message);
            })
            .finally(() => {
                testConnectionBtn.disabled = false;
                testConnectionBtn.innerHTML = '<i class="fas fa-plug"></i> Test Connection';
            });
        });
    }
    
    // File upload display
    if (sqlFileInput) {
        sqlFileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                fileNameDisplay.textContent = 'Selected: ' + file.name + ' (' + formatFileSize(file.size) + ')';
                
                // Validate extension
                const fileExt = file.name.split('.').pop().toLowerCase();
                if (fileExt !== 'sql') {
                    fileNameDisplay.textContent = 'Error: Please select a .sql file';
                    fileNameDisplay.style.color = '#dc3545';
                    this.value = '';
                } else {
                    fileNameDisplay.style.color = '#198754';
                }
                
                checkImportButtonState();
            } else {
                fileNameDisplay.textContent = '';
                checkImportButtonState();
            }
        });
    }
    
    // Cleanup button
    const cleanupBtn = document.getElementById('cleanup-btn');
    if (cleanupBtn) {
        cleanupBtn.addEventListener('click', function() {
            if (!confirm('This will delete all installer files for security. Are you sure?')) {
                return;
            }
            
            cleanupBtn.disabled = true;
            cleanupBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cleaning up...';
            
            fetch('cleanup.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Installer files removed successfully. Redirecting to login...');
                    window.location.href = '../auth/login.php';
                } else {
                    alert('Cleanup failed: ' + data.message);
                    cleanupBtn.disabled = false;
                    cleanupBtn.innerHTML = '<i class="fas fa-trash-alt"></i> Secure My Installation (Recommended)';
                }
            })
            .catch(error => {
                alert('Cleanup failed: ' + error.message);
                cleanupBtn.disabled = false;
                cleanupBtn.innerHTML = '<i class="fas fa-trash-alt"></i> Secure My Installation (Recommended)';
            });
        });
    }
    
    function showConnectionResult(type, message) {
        if (!connectionResult) return;
        
        connectionResult.className = type;
        connectionResult.innerHTML = type ? '<i class="fas ' + (type === 'success' ? 'fa-check-circle' : 'fa-times-circle') + '"></i> ' + message : '';
    }
    
    function checkImportButtonState() {
        if (!importBtn || !sqlFileInput) return;
        
        const hasFile = sqlFileInput.files && sqlFileInput.files[0];
        const fileExt = hasFile ? sqlFileInput.files[0].name.split('.').pop().toLowerCase() : '';
        const isValidFile = hasFile && fileExt === 'sql';
        
        // Check if connection was tested successfully (would be indicated by success message)
        const connectionSuccess = connectionResult && connectionResult.classList.contains('success');
        
        // Enable only if both connection succeeded and valid file is selected
        if (isValidFile && connectionSuccess) {
            importBtn.disabled = false;
        } else {
            importBtn.disabled = true;
        }
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
});
