<?php
// Courier Management System - Dashboard
// Created: 2026-08-08

$pageTitle = 'Dashboard';
require_once 'includes/auth_check.php';
require_once 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="page-title">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?> (<?php echo htmlspecialchars($_SESSION['role_name']); ?>)</h2>
            <p class="text-muted">This is your dashboard. Use the sidebar to navigate through the system.</p>
        </div>
    </div>
    
    <!-- Placeholder cards for future dashboard stats -->
    <div class="row">
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h5 class="card-title">Total Orders</h5>
                    <p class="card-text stat-number">-</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h5 class="card-title">Pending Deliveries</h5>
                    <p class="card-text stat-number">-</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h5 class="card-title">Completed Today</h5>
                    <p class="card-text stat-number">-</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h5 class="card-title">Active Users</h5>
                    <p class="card-text stat-number">-</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Additional placeholder content for future modules -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Recent Activity</h5>
                    <p class="text-muted">Recent activity log will appear here in future phases.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
