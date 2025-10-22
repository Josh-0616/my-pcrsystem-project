<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
include('../db/connection.php');

// Get faculty ID from URL parameter
 $facultyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($facultyId <= 0) {
    header("Location: manage_users.php");
    exit();
}

// Fetch faculty data from database using prepared statement
 $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ? AND role = 'faculty' LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $facultyId);
mysqli_stmt_execute($stmt);
 $result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    header("Location: manage_users.php");
    exit();
}

 $faculty = mysqli_fetch_assoc($result);

// Parse contract extension if it exists
 $extendAmount = '';
 $extendPeriod = '';
 $contractExtend = $faculty['contract_extend'] ?? '';
if (!empty($contractExtend)) {
    $parts = explode(' ', $contractExtend, 2);
    if (count($parts) === 2) {
        $extendAmount = $parts[0];
        $extendPeriod = $parts[1];
    }
}

// Get profile picture with proper default handling
 $profilePicture = '../uploads/1.png'; // Default profile picture
if (!empty($faculty['profile_picture'])) {
    $profilePicture = '../uploads/' . $faculty['profile_picture'];
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Validate inputs
    if (empty($_POST['lastName'])) {
        $errors[] = "Last name is required";
    }
    
    if (empty($_POST['firstName'])) {
        $errors[] = "First name is required";
    }
    
    if (empty($_POST['email'])) {
        $errors[] = "Email is required";
    } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }
    
    // Handle profile picture upload
    $profilePicturePath = $faculty['profile_picture'] ?? '1.png';
    if (isset($_FILES['profilePicture']) && $_FILES['profilePicture']['error'] === UPLOAD_ERR_OK) {
        // Check file size (2MB max)
        if ($_FILES['profilePicture']['size'] > 2097152) {
            $errors[] = "Profile picture must be less than 2MB";
        } else {
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $fileType = $_FILES['profilePicture']['type'];
            
            if (in_array($fileType, $allowedTypes)) {
                $uploadDir = '../uploads/';
                // Ensure upload directory exists
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileName = time() . '_' . basename($_FILES['profilePicture']['name']);
                $targetPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['profilePicture']['tmp_name'], $targetPath)) {
                    $profilePicturePath = $fileName;
                    
                    // Delete old profile picture if it's not the default and exists
                    if (isset($faculty['profile_picture']) && 
                        $faculty['profile_picture'] !== '1.png' && 
                        file_exists('../uploads/' . $faculty['profile_picture'])) {
                        unlink('../uploads/' . $faculty['profile_picture']);
                    }
                } else {
                    $errors[] = "Error uploading profile picture. Check directory permissions.";
                }
            } else {
                $errors[] = "Only JPG, JPEG, PNG, and GIF images are allowed for profile picture";
            }
        }
    }
    
    // Handle document upload
    $documentPath = $faculty['document_path'] ?? '';
    if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
        // Check file size (5MB max)
        if ($_FILES['document']['size'] > 5242880) {
            $errors[] = "Document must be less than 5MB";
        } else {
            $allowedDocTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/jpg', 'image/png'];
            $docFileType = $_FILES['document']['type'];
            
            if (in_array($docFileType, $allowedDocTypes)) {
                $uploadDir = '../uploads/documents/';
                // Create directory if it doesn't exist
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $docFileName = time() . '_' . basename($_FILES['document']['name']);
                $docTargetPath = $uploadDir . $docFileName;
                
                if (move_uploaded_file($_FILES['document']['tmp_name'], $docTargetPath)) {
                    $documentPath = 'documents/' . $docFileName;
                    
                    // Delete old document if it exists
                    if (isset($faculty['document_path']) && 
                        !empty($faculty['document_path']) && 
                        file_exists('../uploads/' . $faculty['document_path'])) {
                        unlink('../uploads/' . $faculty['document_path']);
                    }
                } else {
                    $errors[] = "Error uploading document. Check directory permissions.";
                }
            } else {
                $errors[] = "Only PDF, Word documents, and images are allowed for document upload";
            }
        }
    }
    
    // Process contract extension
    $extendAmount = isset($_POST['extendAmount']) ? (int)$_POST['extendAmount'] : 0;
    $extendPeriod = $_POST['extendPeriod'] ?? '';
    $contractExtend = '';
    $newContractEnd = '';
    
    if ($extendAmount > 0 && !empty($extendPeriod)) {
        $contractExtend = $extendAmount . ' ' . $extendPeriod;
        
        // Calculate new end date if contract end date is available
        if (!empty($_POST['contractEnd'])) {
            $endDate = new DateTime($_POST['contractEnd']);
            
            switch ($extendPeriod) {
                case 'days':
                    $endDate->add(new DateInterval('P' . $extendAmount . 'D'));
                    break;
                case 'months':
                    $endDate->add(new DateInterval('P' . $extendAmount . 'M'));
                    break;
                case 'years':
                    $endDate->add(new DateInterval('P' . $extendAmount . 'Y'));
                    break;
            }
            
            $newContractEnd = $endDate->format('Y-m-d');
        }
    }
    
    // If no errors, update the database
    if (empty($errors)) {
        // Build update query with prepared statement
        $updateFields = [];
        $params = [];
        $types = '';
        
        // Add fields to update
        $updateFields[] = "last_name = ?";
        $types .= 's';
        $params[] = $_POST['lastName'];
        
        $updateFields[] = "first_name = ?";
        $types .= 's';
        $params[] = $_POST['firstName'];
        
        $updateFields[] = "middle_name = ?";
        $types .= 's';
        $params[] = $_POST['middleName'];
        
        $updateFields[] = "full_name = ?";
        $types .= 's';
        $params[] = $_POST['lastName'] . ' ' . $_POST['firstName'] . ' ' . $_POST['middleName'];
        
        $updateFields[] = "email = ?";
        $types .= 's';
        $params[] = $_POST['email'];
        
        $updateFields[] = "contact_number = ?";
        $types .= 's';
        $params[] = $_POST['contactNumber'];
        
        $updateFields[] = "department = ?";
        $types .= 's';
        $params[] = $_POST['department'];
        
        $updateFields[] = "contract_start = ?";
        $types .= 's';
        $params[] = $_POST['contractStart'];
        
        // Use the new calculated end date if available, otherwise use the original
        $contractEnd = !empty($newContractEnd) ? $newContractEnd : $_POST['contractEnd'];
        $updateFields[] = "contract_end = ?";
        $types .= 's';
        $params[] = $contractEnd;
        
        $updateFields[] = "contract_extend = ?";
        $types .= 's';
        $params[] = $contractExtend;
        
        $updateFields[] = "units = ?";
        $types .= 's';
        $params[] = $_POST['units'];
        
        $updateFields[] = "document_path = ?";
        $types .= 's';
        $params[] = $documentPath;
        
        $updateFields[] = "profile_picture = ?";
        $types .= 's';
        $params[] = $profilePicturePath;
        
        // Add faculty ID as the last parameter
        $types .= 'i';
        $params[] = $facultyId;
        
        // Build and execute the update query
        $updateQuery = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = mysqli_prepare($conn, $updateQuery);
        
        // Bind parameters
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        
        if (mysqli_stmt_execute($stmt)) {
            // Update variables for display
            $faculty['last_name'] = $_POST['lastName'];
            $faculty['first_name'] = $_POST['firstName'];
            $faculty['middle_name'] = $_POST['middleName'];
            $faculty['full_name'] = $_POST['lastName'] . ' ' . $_POST['firstName'] . ' ' . $_POST['middleName'];
            $faculty['email'] = $_POST['email'];
            $faculty['contact_number'] = $_POST['contactNumber'];
            $faculty['department'] = $_POST['department'];
            $faculty['contract_start'] = $_POST['contractStart'];
            $faculty['contract_end'] = $contractEnd;
            $faculty['contract_extend'] = $contractExtend;
            $faculty['units'] = $_POST['units'];
            $faculty['document_path'] = $documentPath;
            $faculty['profile_picture'] = $profilePicturePath;
            
            // Parse the contract extension for display
            if (!empty($contractExtend)) {
                $parts = explode(' ', $contractExtend, 2);
                if (count($parts) === 2) {
                    $extendAmount = $parts[0];
                    $extendPeriod = $parts[1];
                }
            }
            
            $successMessage = "Faculty profile updated successfully!";
            // Refresh profile picture path
            $profilePicture = '../uploads/' . $profilePicturePath;
        } else {
            $errors[] = "Error updating profile: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>Edit Faculty Profile - IPCR Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../images/pcc1.png">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../js/bootstrap.bundle.min.js"></script>
    <style>
        :root {
            --bg-light: #f0f2f5;
            --text-light: #333;
            --card-light: #ffffff;
            --sidebar-bg-light: #3a6ea5;
            --bg-dark: #1e1e2f;
            --text-dark: #dcdcdc;
            --card-dark: #2c2c3e;
            --sidebar-bg-dark: #252536;
            --primary-color: #3a6ea5;
            --secondary-color: #6a9df7;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
        }
        
        html[data-theme='dark'] body {
            background-color: var(--bg-dark);
            color: var(--text-dark);
        }
        
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-light);
            color: var(--text-light);
        }
        
        .topbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 70px;
            background-color: var(--sidebar-bg-light);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 15px;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        [data-theme='dark'] .topbar {
            background-color: var(--sidebar-bg-dark);
        }
        
        .main-content {
            margin-left: 0;
            margin-top: 70px;
            padding: 20px;
            transition: margin-left 0.3s ease;
            position: relative;
            z-index: 1;
            max-width: 1200px;
            margin-right: auto;
            margin-left: auto;
        }
        
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            background-color: var(--card-light);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.1);
        }
        
        [data-theme='dark'] .card {
            background-color: var(--card-dark);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(58, 110, 165, 0.25);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }
        
        .breadcrumb {
            background-color: transparent;
            padding: 0;
            margin-bottom: 1rem;
        }
        
        .breadcrumb-item + .breadcrumb-item::before {
            content: ">";
            color: var(--primary-color);
        }
        
        .breadcrumb-item.active {
            color: var(--primary-color);
        }
        
        .info-section {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        [data-theme='dark'] .info-section {
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .info-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--primary-color);
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 5px;
            color: var(--text-light);
        }
        
        [data-theme='dark'] .form-label {
            color: var(--text-dark);
        }
        
        .form-control {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #ddd;
        }
        
        [data-theme='dark'] .form-control {
            background-color: var(--card-dark);
            border-color: #444;
            color: var(--text-dark);
        }
        
        .profile-upload {
            text-align: center;
            margin-bottom: 20px;
            padding: 20px;
            border-radius: 12px;
            background-color: rgba(58, 110, 165, 0.05);
            border: 2px dashed var(--primary-color);
            transition: all 0.3s ease;
        }
        
        .profile-upload:hover {
            background-color: rgba(58, 110, 165, 0.1);
        }
        
        .profile-img-container {
            position: relative;
            display: inline-block;
            margin-right: 15px;
        }
        
        .profile-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-color);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            cursor: pointer;
        }
        
        .profile-img:hover {
            transform: scale(1.05);
        }
        
        .camera-icon {
            position: absolute;
            bottom: 5px;
            right: 5px;
            width: 32px;
            height: 32px;
            background-color: #3a6ea5;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
            border: 2px solid white;
        }
        
        .camera-icon:hover {
            background-color: #6a9df7;
            transform: scale(1.1);
        }
        
        .camera-icon i {
            font-size: 16px;
        }
        
        .document-preview {
            margin-top: 10px;
            padding: 10px;
            border-radius: 8px;
            background-color: rgba(58, 110, 165, 0.1);
        }
        
        .extension-preview {
            margin-top: 10px;
            padding: 10px;
            border-radius: 8px;
            background-color: rgba(40, 167, 69, 0.1);
        }
        
        /* Modal styles for full-size image view */
        .image-modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            animation: fadeIn 0.3s;
        }
        
        .modal-content {
            position: relative;
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 12px;
            width: 400px;
            max-width: 90%;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            animation: slideIn 0.4s;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .modal-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }
        
        .modal-body {
            text-align: center;
        }
        
        .modal-body img {
            max-width: 100%;
            max-height: 300px;
            border-radius: 8px;
            object-fit: contain;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
        }
        
        @keyframes fadeIn {
            from {opacity: 0}
            to {opacity: 1}
        }
        
        @keyframes slideIn {
            from {transform: translateY(-50px); opacity: 0}
            to {transform: translateY(0); opacity: 1}
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }
            
            .topbar {
                padding: 0 10px;
            }
            
            .modal-content {
                width: 95%;
                margin: 15% auto;
            }
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="ms-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="admin_dashboard.php" style="color: white;">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="manage_users.php" style="color: white;">Manage Users</a></li>
                    <li class="breadcrumb-item active">Edit Faculty Profile</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="main-content">
        <div class="card p-4">
            <h4 class="mb-4">Edit Faculty Profile</h4>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (isset($successMessage)): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($successMessage) ?>
                </div>
            <?php endif; ?>
            
            <form method="post" enctype="multipart/form-data">
                <div class="info-section">
                    <div class="section-title">
                        <i class="fas fa-camera"></i> Profile Picture
                    </div>
                    
                    <div class="profile-upload">
                        <div class="d-flex align-items-center justify-content-center">
                            <div class="profile-img-container">
                                <img src="<?= $profilePicture ?>" id="previewProfile" alt="Profile" class="profile-img">
                                <div class="camera-icon" id="cameraIconButton" title="Change Profile Picture">
                                    <i class="fas fa-camera"></i>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <!-- Add the hidden file input for profile picture -->
                            <input type="file" id="profilePicture" name="profilePicture" accept="image/jpeg,image/jpg,image/png,image/gif" style="display: none;">
                            <small class="text-muted">Click on the image to view full size or camera icon to change</small>
                        </div>
                    </div>
                </div>
                
                <div class="info-section">
                    <div class="section-title">
                        <i class="fas fa-user"></i> Name Information
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="lastName" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="lastName" name="lastName" value="<?= htmlspecialchars($faculty['last_name'] ?? '') ?>" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="firstName" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="firstName" name="firstName" value="<?= htmlspecialchars($faculty['first_name'] ?? '') ?>" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="middleName" class="form-label">Middle Name</label>
                            <input type="text" class="form-control" id="middleName" name="middleName" value="<?= htmlspecialchars($faculty['middle_name'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                
                <div class="info-section">
                    <div class="section-title">
                        <i class="fas fa-phone"></i> Contact Information
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($faculty['email'] ?? '') ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="contactNumber" class="form-label">Contact Number</label>
                            <input type="tel" class="form-control" id="contactNumber" name="contactNumber" value="<?= htmlspecialchars($faculty['contact_number'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="department" class="form-label">Department</label>
                        <select class="form-control" id="department" name="department">
                            <option value="">Select Department</option>
                            <option value="BSIT" <?= ($faculty['department'] ?? '') == 'BSIT' ? 'selected' : '' ?>>BSIT</option>
                            <option value="BSBA" <?= ($faculty['department'] ?? '') == 'BSBA' ? 'selected' : '' ?>>BSBA</option>
                            <option value="BSCRIM" <?= ($faculty['department'] ?? '') == 'BSCRIM' ? 'selected' : '' ?>>BSCRIM</option>
                        </select>
                    </div>
                </div>
                
                <div class="info-section">
                    <div class="section-title">
                        <i class="fas fa-file-contract"></i> Contract Information
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="contractStart" class="form-label">Start of Contract</label>
                            <input type="date" class="form-control" id="contractStart" name="contractStart" value="<?= htmlspecialchars($faculty['contract_start'] ?? '') ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="contractEnd" class="form-label">End of Contract</label>
                            <input type="date" class="form-control" id="contractEnd" name="contractEnd" value="<?= htmlspecialchars($faculty['contract_end'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="extendAmount" class="form-label">Contract Extension</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="extendAmount" name="extendAmount" value="<?= htmlspecialchars($extendAmount) ?>" min="1" max="60" placeholder="Amount">
                                <select class="form-select" id="extendPeriod" name="extendPeriod">
                                    <option value="">Select period</option>
                                    <option value="days" <?= ($extendPeriod ?? '') == 'days' ? 'selected' : '' ?>>Days</option>
                                    <option value="months" <?= ($extendPeriod ?? '') == 'months' ? 'selected' : '' ?>>Months</option>
                                    <option value="years" <?= ($extendPeriod ?? '') == 'years' ? 'selected' : '' ?>>Years</option>
                                </select>
                            </div>
                            <small class="text-muted">Enter the amount and select the period for extension</small>
                            
                            <?php if (!empty($faculty['contract_end']) && !empty($extendAmount) && !empty($extendPeriod)): ?>
                                <div class="extension-preview mt-2">
                                    <small class="text-success">Extended contract end date: <span id="newEndDate"></span></small>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="units" class="form-label">Units</label>
                            <input type="number" class="form-control" id="units" name="units" value="<?= htmlspecialchars($faculty['units'] ?? '') ?>" min="0">
                        </div>
                    </div>
                </div>
                
                <div class="info-section">
                    <div class="section-title">
                        <i class="fas fa-file-upload"></i> Upload Documents
                    </div>
                    
                    <div class="mb-3">
                        <label for="document" class="form-label">Subject Documents or Pictures</label>
                        <input type="file" class="form-control" id="document" name="document" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        <small class="text-muted">PDF, Word documents, or images. Max size 5MB.</small>
                        
                        <?php if (!empty($faculty['document_path'])): ?>
                            <div class="document-preview mt-2">
                                <p class="mb-1">Current Document:</p>
                                <a href="../uploads/<?= htmlspecialchars($faculty['document_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-file-alt me-1"></i> View Document
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                    <a href="manage_users.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Users
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal for full-size image view -->
    <div id="imageModal" class="image-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Profile Picture</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <img id="modalProfileImage" src="" alt="Profile Picture">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="closeModalBtn">Close</button>
                <button type="button" class="btn btn-primary" id="changePictureBtn">Change Picture</button>
            </div>
        </div>
    </div>

    <script>
        // Profile picture preview and click to change
        const profilePictureInput = document.getElementById('profilePicture');
        const previewProfile = document.getElementById('previewProfile');
        const cameraIconButton = document.getElementById('cameraIconButton');
        const imageModal = document.getElementById('imageModal');
        const modalProfileImage = document.getElementById('modalProfileImage');
        const closeModal = document.querySelector('.close-modal');
        const closeModalBtn = document.getElementById('closeModalBtn');
        const changePictureBtn = document.getElementById('changePictureBtn');

        // Function to trigger file input click
        function triggerFileInput() {
            profilePictureInput.click();
        }

        // Add click event to profile image for viewing full size
        if (previewProfile) {
            previewProfile.addEventListener('click', function(e) {
                // Only view full size if not clicking on the camera icon
                if (!e.target.closest('.camera-icon')) {
                    modalProfileImage.src = this.src;
                    imageModal.style.display = 'block';
                }
            });
        }

        // Add click event to camera icon
        if (cameraIconButton) {
            cameraIconButton.addEventListener('click', function(e) {
                e.stopPropagation(); // Prevent triggering the profile image click
                triggerFileInput();
            });
        }

        // Add click event to change picture button in modal
        if (changePictureBtn) {
            changePictureBtn.addEventListener('click', function() {
                imageModal.style.display = 'none';
                triggerFileInput();
            });
        }

        // Close modal when clicking the close button
        if (closeModal) {
            closeModal.addEventListener('click', function() {
                imageModal.style.display = 'none';
            });
        }

        // Close modal when clicking the close button in footer
        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', function() {
                imageModal.style.display = 'none';
            });
        }

        // Close modal when clicking outside the modal content
        if (imageModal) {
            imageModal.addEventListener('click', function(e) {
                if (e.target === imageModal) {
                    imageModal.style.display = 'none';
                }
            });
        }

        // Preview image when selected
        if (profilePictureInput) {
            profilePictureInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    // Check file size (2MB max)
                    if (this.files[0].size > 2097152) {
                        alert('File size must be less than 2MB');
                        this.value = '';
                        return;
                    }
                    
                    // Check file type
                    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                    if (!allowedTypes.includes(this.files[0].type)) {
                        alert('Only JPG, JPEG, PNG, and GIF images are allowed');
                        this.value = '';
                        return;
                    }
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewProfile.src = e.target.result;
                    }
                    reader.readAsDataURL(this.files[0]);
                }
            });
        }

        // Calculate extended contract end date
        const extendAmount = document.getElementById('extendAmount');
        const extendPeriod = document.getElementById('extendPeriod');
        const contractEnd = document.getElementById('contractEnd');
        const newEndDateSpan = document.getElementById('newEndDate');

        function calculateExtendedDate() {
            if (!extendAmount.value || !extendPeriod.value || !contractEnd.value) {
                return;
            }
            
            const endDate = new Date(contractEnd.value);
            const amount = parseInt(extendAmount.value);
            
            switch (extendPeriod.value) {
                case 'days':
                    endDate.setDate(endDate.getDate() + amount);
                    break;
                case 'months':
                    endDate.setMonth(endDate.getMonth() + amount);
                    break;
                case 'years':
                    endDate.setFullYear(endDate.getFullYear() + amount);
                    break;
            }
            
            const formattedDate = endDate.toISOString().split('T')[0];
            if (newEndDateSpan) {
                newEndDateSpan.textContent = formattedDate;
            }
        }

        if (extendAmount && extendPeriod && contractEnd) {
            extendAmount.addEventListener('input', calculateExtendedDate);
            extendPeriod.addEventListener('change', calculateExtendedDate);
            contractEnd.addEventListener('change', calculateExtendedDate);
            
            // Calculate on page load if values are already set
            if (extendAmount.value && extendPeriod.value && contractEnd.value) {
                calculateExtendedDate();
            }
        }
    </script>
</body>
</html>