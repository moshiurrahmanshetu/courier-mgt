<?php
// Courier Management System - Sidebar
// Created: 2026-08-08
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/role_check.php';

// Get current page filename for active state highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar" id="sidebar">
    <nav class="sidebar-nav">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
            
            <?php if (hasRole(['Admin', 'Staff'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($currentPage, 'modules/users') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>modules/users/list.php">
                    <i class="fas fa-users"></i>
                    <span class="nav-text">Users</span>
                </a>
            </li>
            <?php endif; ?>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'profile.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>modules/users/profile.php">
                    <i class="fas fa-user"></i>
                    <span class="nav-text">Profile</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'change-password.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>modules/users/change-password.php">
                    <i class="fas fa-key"></i>
                    <span class="nav-text">Change Password</span>
                </a>
            </li>
            
            <li class="nav-item mt-3">
                <a class="nav-link text-danger" href="<?php echo BASE_URL; ?>auth/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="nav-text">Logout</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>
