<?php
// Courier Management System - Create Parcel
// Created: 2026-08-08

$pageTitle = 'Book New Parcel';
require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/db.php';
require_once 'helpers.php';

// Check if user has permission
if (!hasPermission($pdo, 'parcels', 'create')) {
    setFlashMessage('error', 'Access denied. You do not have permission to access this module.');
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

// Get active customers for dropdown
try {
    $stmt = $pdo->query("SELECT id, customer_code, full_name FROM customers WHERE is_deleted = 0 AND status = 'active' ORDER BY full_name");
    $customers = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Fetch Customers Error: " . $e->getMessage());
    setFlashMessage('error', 'An error occurred while loading customers.');
    $customers = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $parcelData = [
        'customer_id' => $_POST['customer_id'] ?? '',
        'receiver_name' => trim($_POST['receiver_name'] ?? ''),
        'receiver_phone' => trim($_POST['receiver_phone'] ?? ''),
        'receiver_address' => trim($_POST['receiver_address'] ?? ''),
        'parcel_type' => $_POST['parcel_type'] ?? '',
        'parcel_description' => trim($_POST['parcel_description'] ?? ''),
        'weight' => $_POST['weight'] ?? '',
        'delivery_charge' => $_POST['delivery_charge'] ?? 0,
        'cod_amount' => $_POST['cod_amount'] ?? 0,
        'booking_date' => $_POST['booking_date'] ?? date('Y-m-d'),
        'expected_delivery_date' => $_POST['expected_delivery_date'] ?? ''
    ];
    
    // Validate data
    $validation = validateParcelData($parcelData);
    
    if (!$validation['valid']) {
        setFlashMessage('error', implode('<br>', $validation['errors']));
    } else {
        try {
            // Generate tracking number
            $trackingNumber = generateTrackingNumber($pdo);
            
            // Insert parcel
            $sql = "INSERT INTO parcels (tracking_number, customer_id, receiver_name, receiver_phone, receiver_address, 
                    parcel_type, parcel_description, weight, delivery_charge, cod_amount, booking_date, 
                    expected_delivery_date, current_status, created_by) 
                    VALUES (:tracking_number, :customer_id, :receiver_name, :receiver_phone, :receiver_address, 
                    :parcel_type, :parcel_description, :weight, :delivery_charge, :cod_amount, :booking_date, 
                    :expected_delivery_date, 'pending', :created_by)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'tracking_number' => $trackingNumber,
                'customer_id' => $parcelData['customer_id'],
                'receiver_name' => $parcelData['receiver_name'],
                'receiver_phone' => $parcelData['receiver_phone'],
                'receiver_address' => $parcelData['receiver_address'],
                'parcel_type' => $parcelData['parcel_type'],
                'parcel_description' => $parcelData['parcel_description'] ?: null,
                'weight' => $parcelData['weight'] ?: null,
                'delivery_charge' => $parcelData['delivery_charge'],
                'cod_amount' => $parcelData['cod_amount'],
                'booking_date' => $parcelData['booking_date'],
                'expected_delivery_date' => $parcelData['expected_delivery_date'] ?: null,
                'created_by' => $_SESSION['user_id']
            ]);
            
            $parcelId = $pdo->lastInsertId();
            
            // Insert initial status log
            $logSql = "INSERT INTO parcel_status_log (parcel_id, status, note, changed_by) 
                      VALUES (:parcel_id, 'pending', 'Parcel booked', :changed_by)";
            $logStmt = $pdo->prepare($logSql);
            $logStmt->execute([
                'parcel_id' => $parcelId,
                'changed_by' => $_SESSION['user_id']
            ]);
            
            setFlashMessage('success', 'Parcel booked successfully. Tracking Number: ' . $trackingNumber);
            header('Location: ' . BASE_URL . 'modules/parcels/list.php');
            exit;
            
        } catch (PDOException $e) {
            error_log("Create Parcel Error: " . $e->getMessage());
            setFlashMessage('error', 'An error occurred while booking the parcel.');
        }
    }
}

require_once '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="page-title">Book New Parcel</h2>
            <a href="<?php echo BASE_URL; ?>modules/parcels/list.php" class="btn btn-secondary">Back to Parcels</a>
        </div>
    </div>
    
    <?php echo displayFlashMessages(); ?>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Parcel Information</h5>
                    
                    <form method="POST" id="createParcelForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="customer_id" class="form-label">Sender (Customer) *</label>
                                    <select class="form-select" id="customer_id" name="customer_id" required>
                                        <option value="">Select Customer</option>
                                        <?php foreach ($customers as $customer): ?>
                                            <option value="<?php echo $customer['id']; ?>" <?php echo (isset($_POST['customer_id']) && $_POST['customer_id'] == $customer['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($customer['customer_code'] . ' - ' . $customer['full_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">Please select a customer</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="booking_date" class="form-label">Booking Date *</label>
                                    <input type="date" class="form-control" id="booking_date" name="booking_date" 
                                           value="<?php echo htmlspecialchars($_POST['booking_date'] ?? date('Y-m-d')); ?>" required>
                                    <div class="invalid-feedback">Booking date is required</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="receiver_name" class="form-label">Receiver Name *</label>
                                    <input type="text" class="form-control" id="receiver_name" name="receiver_name" 
                                           value="<?php echo htmlspecialchars($_POST['receiver_name'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">Receiver name is required</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="receiver_phone" class="form-label">Receiver Phone *</label>
                                    <input type="text" class="form-control" id="receiver_phone" name="receiver_phone" 
                                           value="<?php echo htmlspecialchars($_POST['receiver_phone'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">Receiver phone is required</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="receiver_address" class="form-label">Receiver Address *</label>
                            <textarea class="form-control" id="receiver_address" name="receiver_address" rows="3" required><?php echo htmlspecialchars($_POST['receiver_address'] ?? ''); ?></textarea>
                            <div class="invalid-feedback">Receiver address is required</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="parcel_type" class="form-label">Parcel Type *</label>
                                    <select class="form-select" id="parcel_type" name="parcel_type" required>
                                        <option value="">Select Type</option>
                                        <option value="Document" <?php echo (isset($_POST['parcel_type']) && $_POST['parcel_type'] === 'Document') ? 'selected' : ''; ?>>Document</option>
                                        <option value="Box" <?php echo (isset($_POST['parcel_type']) && $_POST['parcel_type'] === 'Box') ? 'selected' : ''; ?>>Box</option>
                                        <option value="Fragile" <?php echo (isset($_POST['parcel_type']) && $_POST['parcel_type'] === 'Fragile') ? 'selected' : ''; ?>>Fragile</option>
                                        <option value="Other" <?php echo (isset($_POST['parcel_type']) && $_POST['parcel_type'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                    <div class="invalid-feedback">Please select parcel type</div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="weight" class="form-label">Weight (KG)</label>
                                    <input type="number" step="0.01" class="form-control" id="weight" name="weight" 
                                           value="<?php echo htmlspecialchars($_POST['weight'] ?? ''); ?>" min="0.01">
                                    <small class="text-muted">Optional</small>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="expected_delivery_date" class="form-label">Expected Delivery Date</label>
                                    <input type="date" class="form-control" id="expected_delivery_date" name="expected_delivery_date" 
                                           value="<?php echo htmlspecialchars($_POST['expected_delivery_date'] ?? ''); ?>">
                                    <small class="text-muted">Optional</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="parcel_description" class="form-label">Parcel Description</label>
                            <textarea class="form-control" id="parcel_description" name="parcel_description" rows="2"><?php echo htmlspecialchars($_POST['parcel_description'] ?? ''); ?></textarea>
                            <small class="text-muted">Optional description of parcel contents</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="delivery_charge" class="form-label">Delivery Charge *</label>
                                    <input type="number" step="0.01" class="form-control" id="delivery_charge" name="delivery_charge" 
                                           value="<?php echo htmlspecialchars($_POST['delivery_charge'] ?? '0.00'); ?>" required min="0">
                                    <div class="invalid-feedback">Delivery charge is required</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="cod_amount" class="form-label">COD Amount</label>
                                    <input type="number" step="0.01" class="form-control" id="cod_amount" name="cod_amount" 
                                           value="<?php echo htmlspecialchars($_POST['cod_amount'] ?? '0.00'); ?>" min="0">
                                    <small class="text-muted">Cash on Delivery amount (0 if no COD)</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> 
                                Tracking number will be auto-generated. Parcel status will be set to 'Pending' automatically.
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Book Parcel</button>
                        <a href="<?php echo BASE_URL; ?>modules/parcels/list.php" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
