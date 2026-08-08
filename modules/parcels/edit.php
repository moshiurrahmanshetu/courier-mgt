<?php
// Courier Management System - Edit Parcel
// Created: 2026-08-08

$pageTitle = 'Edit Parcel';
require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/db.php';
require_once 'helpers.php';

// Check if user has permission
if (!hasPermission($pdo, 'parcels', 'edit')) {
    setFlashMessage('error', 'Access denied. You do not have permission to access this module.');
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$parcelId = $_GET['id'] ?? 0;

if (!$parcelId) {
    setFlashMessage('error', 'Invalid parcel ID.');
    header('Location: ' . BASE_URL . 'modules/parcels/list.php');
    exit;
}

// Fetch parcel data
try {
    $stmt = $pdo->prepare("SELECT * FROM parcels WHERE id = :id");
    $stmt->execute(['id' => $parcelId]);
    $parcel = $stmt->fetch();
    
    if (!$parcel) {
        setFlashMessage('error', 'Parcel not found.');
        header('Location: ' . BASE_URL . 'modules/parcels/list.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Fetch Parcel Error: " . $e->getMessage());
    setFlashMessage('error', 'An error occurred while loading parcel data.');
    header('Location: ' . BASE_URL . 'modules/parcels/list.php');
    exit;
}

// Check if parcel can be edited (only pending or picked_up)
$editableStatuses = ['pending', 'picked_up'];
if (!in_array($parcel['current_status'], $editableStatuses)) {
    $canEdit = false;
} else {
    $canEdit = true;
}

// Get active customers for dropdown
try {
    $stmt = $pdo->query("SELECT id, customer_code, full_name FROM customers WHERE is_deleted = 0 AND status = 'active' ORDER BY full_name");
    $customers = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Fetch Customers Error: " . $e->getMessage());
    $customers = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canEdit) {
    // Collect form data
    $parcelData = [
        'receiver_name' => trim($_POST['receiver_name'] ?? ''),
        'receiver_phone' => trim($_POST['receiver_phone'] ?? ''),
        'receiver_address' => trim($_POST['receiver_address'] ?? ''),
        'parcel_type' => $_POST['parcel_type'] ?? '',
        'parcel_description' => trim($_POST['parcel_description'] ?? ''),
        'weight' => $_POST['weight'] ?? '',
        'delivery_charge' => $_POST['delivery_charge'] ?? 0,
        'cod_amount' => $_POST['cod_amount'] ?? 0,
        'expected_delivery_date' => $_POST['expected_delivery_date'] ?? ''
    ];
    
    // Validate data
    $validation = validateParcelData($parcelData);
    
    if (!$validation['valid']) {
        setFlashMessage('error', implode('<br>', $validation['errors']));
    } else {
        try {
            // Update parcel (customer_id and tracking_number cannot be changed)
            $sql = "UPDATE parcels SET receiver_name = :receiver_name, receiver_phone = :receiver_phone, 
                    receiver_address = :receiver_address, parcel_type = :parcel_type, parcel_description = :parcel_description, 
                    weight = :weight, delivery_charge = :delivery_charge, cod_amount = :cod_amount, 
                    expected_delivery_date = :expected_delivery_date 
                    WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'receiver_name' => $parcelData['receiver_name'],
                'receiver_phone' => $parcelData['receiver_phone'],
                'receiver_address' => $parcelData['receiver_address'],
                'parcel_type' => $parcelData['parcel_type'],
                'parcel_description' => $parcelData['parcel_description'] ?: null,
                'weight' => $parcelData['weight'] ?: null,
                'delivery_charge' => $parcelData['delivery_charge'],
                'cod_amount' => $parcelData['cod_amount'],
                'expected_delivery_date' => $parcelData['expected_delivery_date'] ?: null,
                'id' => $parcelId
            ]);
            
            setFlashMessage('success', 'Parcel updated successfully.');
            header('Location: ' . BASE_URL . 'modules/parcels/list.php');
            exit;
            
        } catch (PDOException $e) {
            error_log("Update Parcel Error: " . $e->getMessage());
            setFlashMessage('error', 'An error occurred while updating the parcel.');
        }
    }
}

// Use POST data if available, otherwise use parcel data
$receiverName = $_POST['receiver_name'] ?? $parcel['receiver_name'];
$receiverPhone = $_POST['receiver_phone'] ?? $parcel['receiver_phone'];
$receiverAddress = $_POST['receiver_address'] ?? $parcel['receiver_address'];
$parcelType = $_POST['parcel_type'] ?? $parcel['parcel_type'];
$parcelDescription = $_POST['parcel_description'] ?? $parcel['parcel_description'];
$weight = $_POST['weight'] ?? $parcel['weight'];
$deliveryCharge = $_POST['delivery_charge'] ?? $parcel['delivery_charge'];
$codAmount = $_POST['cod_amount'] ?? $parcel['cod_amount'];
$expectedDeliveryDate = $_POST['expected_delivery_date'] ?? $parcel['expected_delivery_date'];

require_once '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="page-title">Edit Parcel</h2>
            <a href="<?php echo BASE_URL; ?>modules/parcels/list.php" class="btn btn-secondary">Back to Parcels</a>
        </div>
    </div>
    
    <?php echo displayFlashMessages(); ?>
    
    <?php if (!$canEdit): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i> 
        <strong>Cannot Edit:</strong> This parcel cannot be edited because its current status is <strong><?php echo ucfirst(str_replace('_', ' ', $parcel['current_status'])); ?></strong>. 
        Only parcels with status 'Pending' or 'Picked Up' can be edited.
    </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Parcel Information</h5>
                    
                    <?php if ($canEdit): ?>
                    <form method="POST" id="editParcelForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tracking_number" class="form-label">Tracking Number</label>
                                    <input type="text" class="form-control" id="tracking_number" 
                                           value="<?php echo htmlspecialchars($parcel['tracking_number']); ?>" readonly>
                                    <small class="text-muted">Tracking number cannot be changed</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="customer_id" class="form-label">Sender (Customer)</label>
                                    <select class="form-select" id="customer_id" disabled>
                                        <?php foreach ($customers as $customer): ?>
                                            <option value="<?php echo $customer['id']; ?>" <?php echo $parcel['customer_id'] == $customer['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($customer['customer_code'] . ' - ' . $customer['full_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Sender cannot be changed</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="receiver_name" class="form-label">Receiver Name *</label>
                                    <input type="text" class="form-control" id="receiver_name" name="receiver_name" 
                                           value="<?php echo htmlspecialchars($receiverName); ?>" required>
                                    <div class="invalid-feedback">Receiver name is required</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="receiver_phone" class="form-label">Receiver Phone *</label>
                                    <input type="text" class="form-control" id="receiver_phone" name="receiver_phone" 
                                           value="<?php echo htmlspecialchars($receiverPhone); ?>" required>
                                    <div class="invalid-feedback">Receiver phone is required</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="receiver_address" class="form-label">Receiver Address *</label>
                            <textarea class="form-control" id="receiver_address" name="receiver_address" rows="3" required><?php echo htmlspecialchars($receiverAddress); ?></textarea>
                            <div class="invalid-feedback">Receiver address is required</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="parcel_type" class="form-label">Parcel Type *</label>
                                    <select class="form-select" id="parcel_type" name="parcel_type" required>
                                        <option value="">Select Type</option>
                                        <option value="Document" <?php echo $parcelType === 'Document' ? 'selected' : ''; ?>>Document</option>
                                        <option value="Box" <?php echo $parcelType === 'Box' ? 'selected' : ''; ?>>Box</option>
                                        <option value="Fragile" <?php echo $parcelType === 'Fragile' ? 'selected' : ''; ?>>Fragile</option>
                                        <option value="Other" <?php echo $parcelType === 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                    <div class="invalid-feedback">Please select parcel type</div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="weight" class="form-label">Weight (KG)</label>
                                    <input type="number" step="0.01" class="form-control" id="weight" name="weight" 
                                           value="<?php echo htmlspecialchars($weight); ?>" min="0.01">
                                    <small class="text-muted">Optional</small>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="expected_delivery_date" class="form-label">Expected Delivery Date</label>
                                    <input type="date" class="form-control" id="expected_delivery_date" name="expected_delivery_date" 
                                           value="<?php echo htmlspecialchars($expectedDeliveryDate); ?>">
                                    <small class="text-muted">Optional</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="parcel_description" class="form-label">Parcel Description</label>
                            <textarea class="form-control" id="parcel_description" name="parcel_description" rows="2"><?php echo htmlspecialchars($parcelDescription); ?></textarea>
                            <small class="text-muted">Optional description of parcel contents</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="delivery_charge" class="form-label">Delivery Charge *</label>
                                    <input type="number" step="0.01" class="form-control" id="delivery_charge" name="delivery_charge" 
                                           value="<?php echo htmlspecialchars($deliveryCharge); ?>" required min="0">
                                    <div class="invalid-feedback">Delivery charge is required</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="cod_amount" class="form-label">COD Amount</label>
                                    <input type="number" step="0.01" class="form-control" id="cod_amount" name="cod_amount" 
                                           value="<?php echo htmlspecialchars($codAmount); ?>" min="0">
                                    <small class="text-muted">Cash on Delivery amount (0 if no COD)</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> 
                                Current Status: <strong><?php echo ucfirst(str_replace('_', ' ', $parcel['current_status'])); ?></strong>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update Parcel</button>
                        <a href="<?php echo BASE_URL; ?>modules/parcels/view.php?id=<?php echo $parcel['id']; ?>" class="btn btn-info">View</a>
                        <a href="<?php echo BASE_URL; ?>modules/parcels/list.php" class="btn btn-secondary">Cancel</a>
                    </form>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <p class="text-muted">This parcel cannot be edited due to its current status.</p>
                        <a href="<?php echo BASE_URL; ?>modules/parcels/view.php?id=<?php echo $parcel['id']; ?>" class="btn btn-info">View Parcel Details</a>
                        <a href="<?php echo BASE_URL; ?>modules/parcels/list.php" class="btn btn-secondary">Back to List</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
