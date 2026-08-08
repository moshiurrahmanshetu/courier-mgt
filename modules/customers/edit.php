<?php
// Courier Management System - Edit Customer
// Created: 2026-08-08

$pageTitle = 'Edit Customer';
require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/db.php';
require_once 'helpers.php';

// Check if user has permission
if (!hasPermission($pdo, 'customers', 'edit')) {
    setFlashMessage('error', 'Access denied. You do not have permission to access this module.');
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$customerId = $_GET['id'] ?? 0;

if (!$customerId) {
    setFlashMessage('error', 'Invalid customer ID.');
    header('Location: ' . BASE_URL . 'modules/customers/list.php');
    exit;
}

// Fetch customer data
try {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = :id");
    $stmt->execute(['id' => $customerId]);
    $customer = $stmt->fetch();
    
    if (!$customer) {
        setFlashMessage('error', 'Customer not found.');
        header('Location: ' . BASE_URL . 'modules/customers/list.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Fetch Customer Error: " . $e->getMessage());
    setFlashMessage('error', 'An error occurred while loading customer data.');
    header('Location: ' . BASE_URL . 'modules/customers/list.php');
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
            // Update customer
            $sql = "UPDATE customers SET full_name = :full_name, phone = :phone, email = :email, 
                    address = :address, city_area = :city_area, status = :status 
                    WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'full_name' => $customerData['full_name'],
                'phone' => $customerData['phone'],
                'email' => $customerData['email'] ?: null,
                'address' => $customerData['address'],
                'city_area' => $customerData['city_area'],
                'status' => $customerData['status'],
                'id' => $customerId
            ]);
            
            setFlashMessage('success', 'Customer updated successfully.');
            header('Location: ' . BASE_URL . 'modules/customers/list.php');
            exit;
            
        } catch (PDOException $e) {
            error_log("Update Customer Error: " . $e->getMessage());
            setFlashMessage('error', 'An error occurred while updating the customer.');
        }
    }
}

// Use POST data if available, otherwise use customer data
$fullName = $_POST['full_name'] ?? $customer['full_name'];
$phone = $_POST['phone'] ?? $customer['phone'];
$email = $_POST['email'] ?? $customer['email'];
$address = $_POST['address'] ?? $customer['address'];
$cityArea = $_POST['city_area'] ?? $customer['city_area'];
$status = $_POST['status'] ?? $customer['status'];

require_once '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="page-title">Edit Customer</h2>
            <a href="<?php echo BASE_URL; ?>modules/customers/list.php" class="btn btn-secondary">Back to Customers</a>
        </div>
    </div>
    
    <?php echo displayFlashMessages(); ?>
    
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" id="editCustomerForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="customer_code" class="form-label">Customer Code</label>
                                    <input type="text" class="form-control" id="customer_code" 
                                           value="<?php echo htmlspecialchars($customer['customer_code']); ?>" readonly>
                                    <small class="text-muted">Customer code cannot be changed</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="full_name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                           value="<?php echo htmlspecialchars($fullName); ?>" required>
                                    <div class="invalid-feedback">Full name is required</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone *</label>
                                    <input type="text" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($phone); ?>" required>
                                    <div class="invalid-feedback">Phone is required</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($email); ?>">
                                    <div class="invalid-feedback">Please enter a valid email</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="city_area" class="form-label">City/Area *</label>
                                    <input type="text" class="form-control" id="city_area" name="city_area" 
                                           value="<?php echo htmlspecialchars($cityArea); ?>" required>
                                    <div class="invalid-feedback">City/Area is required</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address *</label>
                            <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($address); ?></textarea>
                            <div class="invalid-feedback">Address is required</div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Created: <?php echo date('M d, Y g:i A', strtotime($customer['created_at'])); ?>
                                <?php if ($customer['updated_at'] != $customer['created_at']): ?>
                                    | Last Updated: <?php echo date('M d, Y g:i A', strtotime($customer['updated_at'])); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update Customer</button>
                        <a href="<?php echo BASE_URL; ?>modules/customers/view.php?id=<?php echo $customer['id']; ?>" class="btn btn-info">View</a>
                        <a href="<?php echo BASE_URL; ?>modules/customers/list.php" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
