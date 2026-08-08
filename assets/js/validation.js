// Courier Management System - Form Validation
// Created: 2026-08-08

document.addEventListener('DOMContentLoaded', function() {
    // Login form validation
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(event) {
            if (!loginForm.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                
                // Add Bootstrap validation classes
                loginForm.classList.add('was-validated');
                
                // Focus on first invalid field
                const firstInvalid = loginForm.querySelector(':invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                }
            }
        });
    }
    
    // Profile form validation
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', function(event) {
            const fullName = document.getElementById('full_name').value.trim();
            const email = document.getElementById('email').value.trim();
            
            let isValid = true;
            
            // Reset previous error states
            document.querySelectorAll('.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });
            
            // Validate full name
            if (!fullName) {
                document.getElementById('full_name').classList.add('is-invalid');
                isValid = false;
            }
            
            // Validate email
            if (!email || !isValidEmail(email)) {
                document.getElementById('email').classList.add('is-invalid');
                isValid = false;
            }
            
            if (!isValid) {
                event.preventDefault();
                event.stopPropagation();
            }
        });
    }
    
    // Password form validation
    const passwordForm = document.getElementById('passwordForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(event) {
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            let isValid = true;
            
            // Reset previous error states
            document.querySelectorAll('.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });
            
            // Validate current password
            if (!currentPassword) {
                document.getElementById('current_password').classList.add('is-invalid');
                isValid = false;
            }
            
            // Validate new password
            if (!newPassword || newPassword.length < 6) {
                document.getElementById('new_password').classList.add('is-invalid');
                isValid = false;
            }
            
            // Validate confirm password
            if (!confirmPassword || confirmPassword !== newPassword) {
                document.getElementById('confirm_password').classList.add('is-invalid');
                isValid = false;
            }
            
            if (!isValid) {
                event.preventDefault();
                event.stopPropagation();
            }
        });
    }
    
    // Create user form validation
    const createUserForm = document.getElementById('createUserForm');
    if (createUserForm) {
        createUserForm.addEventListener('submit', function(event) {
            const fullName = document.getElementById('full_name').value.trim();
            const email = document.getElementById('email').value.trim();
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const roleId = document.getElementById('role_id').value;
            
            let isValid = true;
            
            // Reset previous error states
            document.querySelectorAll('.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });
            
            // Validate full name
            if (!fullName) {
                document.getElementById('full_name').classList.add('is-invalid');
                isValid = false;
            }
            
            // Validate email
            if (!email || !isValidEmail(email)) {
                document.getElementById('email').classList.add('is-invalid');
                isValid = false;
            }
            
            // Validate username
            if (!username) {
                document.getElementById('username').classList.add('is-invalid');
                isValid = false;
            }
            
            // Validate password
            if (!password || password.length < 6) {
                document.getElementById('password').classList.add('is-invalid');
                isValid = false;
            }
            
            // Validate confirm password
            if (!confirmPassword || confirmPassword !== password) {
                document.getElementById('confirm_password').classList.add('is-invalid');
                isValid = false;
            }
            
            // Validate role
            if (!roleId) {
                document.getElementById('role_id').classList.add('is-invalid');
                isValid = false;
            }
            
            if (!isValid) {
                event.preventDefault();
                event.stopPropagation();
            }
        });
    }
    
    // Edit user form validation
    const editUserForm = document.getElementById('editUserForm');
    if (editUserForm) {
        editUserForm.addEventListener('submit', function(event) {
            const fullName = document.getElementById('full_name').value.trim();
            const email = document.getElementById('email').value.trim();
            const roleId = document.getElementById('role_id').value;
            
            let isValid = true;
            
            // Reset previous error states
            document.querySelectorAll('.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });
            
            // Validate full name
            if (!fullName) {
                document.getElementById('full_name').classList.add('is-invalid');
                isValid = false;
            }
            
            // Validate email
            if (!email || !isValidEmail(email)) {
                document.getElementById('email').classList.add('is-invalid');
                isValid = false;
            }
            
            // Validate role
            if (!roleId) {
                document.getElementById('role_id').classList.add('is-invalid');
                isValid = false;
            }
            
            if (!isValid) {
                event.preventDefault();
                event.stopPropagation();
            }
        });
    }
});

// Email validation helper function
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Real-time validation feedback
document.addEventListener('input', function(event) {
    if (event.target.classList.contains('form-control') || event.target.classList.contains('form-select')) {
        if (event.target.checkValidity()) {
            event.target.classList.remove('is-invalid');
            event.target.classList.add('is-valid');
        } else {
            event.target.classList.remove('is-valid');
            event.target.classList.add('is-invalid');
        }
    }
});
