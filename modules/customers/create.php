<?php
// Courier Management System - Create Customer
// Created: 2026-08-08

$pageTitle = 'Add New Customer';
require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/db.php';
require_once 'helpers.php';

// Check if user has permission (Admin or Staff only)
if (!hasRole(['Admin', 'Staff'])) {
    setFlashMessage('error', 'Access denied. You do not have permission to access this module.');
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $customerData = [
        'full_name' => trim($_POST['full_name'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'city_area' => trim($_POST['city_area'] ?? ''),
        'status' => $_POST['status'] ?? 'active'
    ];
    
    // Validate data
    $validation = validateCustomerData($customerData);
    
    if (!$validation['valid']) {
        setFlashMessage('error', implode('<br>', $validation['errors']));
    } else {
        try {
            // Generate customer code
            $customerCode = generateCustomerCode($pdo);
            
            // Insert customer
            $sql = "INSERT INTO customers (customer_code, full_name, phone, email, address, city_area, status, created_by) 
                    VALUES (:customer_code, :full_name, :phone, :email, :address, :city_area, :status, :created_by)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'customer_code' => $customerCode,
                'full_name' => $customerData['full_name'],
                'phone' => $customerData['phone'],
                'email' => $customerData['email'] ?: null,
                'address' => $customerData['address'],
                'city_area' => $customerData['city_area'],
                'status' => $customerData['status'],
                'created_by' => $_SESSION['user_id']
            ]);
            
            setFlashMessage('success', 'Customer created successfully with code: ' . $customerCode);
            header('Location: ' . BASE_URL . 'modules/customers/list.php');
            exit;
            
        } catch (PDOException $e) {
            error_log("Create Customer Error: " . $e->getMessage());
            setFlashMessage('error', 'An error occurred while creating the customer.');
        }
    }
}

require_once '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="page-title">Add New Customer</h2>
            <a href="<?php echo BASE_URL; ?>modules/customers/list.php" class="btn btn-secondary">Back to Customers</a>
        </div>
    </div>
    
    <?php echo displayFlashMessages(); ?>
    
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" id="createCustomerForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="full_name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                           value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">Full name is required</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone *</label>
                                    <input type="text" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">Phone is required</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                    <div class="invalid-feedback">Please enter a valid email</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="city_area" class="form-label">City/Area *</label>
                                    <input type="text" class="form-control" id="city_area" name="city_area" 
                                           value="<?php echo htmlspecialchars($_POST['city_area'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">City/Area is required</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address *</label>
                            <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                            <div class="invalid-feedback">Address is required</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] === 'active') ? 'selected' : 'selected'; ?>>Active</option>
                                <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Customer code will be auto-generated (e.g., CUS-0001, CUS-0002)
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Create Customer</button>
                        <a href="<?php echo BASE_URL; ?>modules/customers/list.php" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
