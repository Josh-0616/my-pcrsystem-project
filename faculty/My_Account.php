<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}
include('../db/connection.php');

// Debug: Check session variables
error_log("Session user_id: " . print_r($_SESSION['user_id'], true));
error_log("Session username: " . print_r($_SESSION['username'], true));

// Get user ID from session with multiple fallbacks
 $userId = 0;
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $userId = (int)$_SESSION['user_id'];
} elseif (isset($_SESSION['id']) && !empty($_SESSION['id'])) {
    $userId = (int)$_SESSION['id'];
} else {
    // Try to get user ID from username
    if (isset($_SESSION['username']) && !empty($_SESSION['username'])) {
        $username = mysqli_real_escape_string($conn, $_SESSION['username']);
        $userQuery = "SELECT id FROM users WHERE username = '$username' AND role = 'faculty' LIMIT 1";
        $userResult = mysqli_query($conn, $userQuery);
        
        if ($userResult && mysqli_num_rows($userResult) > 0) {
            $userData = mysqli_fetch_assoc($userResult);
            $userId = (int)$userData['id'];
            // Update session with the correct user_id
            $_SESSION['user_id'] = $userId;
            error_log("Retrieved user_id from database: " . $userId);
        } else {
            error_log("Could not find user in database with username: " . $username);
        }
    }
}

// If we still don't have a valid user ID, redirect to login
if ($userId <= 0) {
    error_log("Invalid user ID after all attempts. Redirecting to login.");
    header("Location: ../login.php");
    exit();
}

// Get profile picture with proper default handling
 $profilePicture = '../uploads/1.png'; // Default profile picture
if (isset($_SESSION['profile_picture']) && !empty($_SESSION['profile_picture'])) {
    $profilePicture = '../uploads/' . $_SESSION['profile_picture'];
}

// Get other session variables with defaults
 $lastName = $_SESSION['last_name'] ?? '';
 $firstName = $_SESSION['first_name'] ?? '';
 $middleName = $_SESSION['middle_name'] ?? '';
 $fullName = $_SESSION['full_name'] ?? 'Faculty';
 $email = $_SESSION['email'] ?? '';
 $contactNumber = $_SESSION['contact_number'] ?? '';
 $department = $_SESSION['department'] ?? '';
 $contractStart = $_SESSION['contract_start'] ?? '';
 $contractEnd = $_SESSION['contract_end'] ?? '';
 $contractExtend = $_SESSION['contract_extend'] ?? '';
 $units = $_SESSION['units'] ?? '';

// Parse contract extension if it exists
 $extendAmount = '';
 $extendPeriod = '';
if (!empty($contractExtend)) {
    $parts = explode(' ', $contractExtend, 2);
    if (count($parts) === 2) {
        $extendAmount = $parts[0];
        $extendPeriod = $parts[1];
    }
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
    $profilePicturePath = $_SESSION['profile_picture'] ?? '1.png';
    if (isset($_FILES['profilePicture']) && $_FILES['profilePicture']['error'] === UPLOAD_ERR_OK) {
        // Check file size (2MB max)
        if ($_FILES['profilePicture']['size'] > 2097152) {
            $errors[] = "Profile picture must be less than 2MB";
        } else {
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $fileType = $_FILES['profilePicture']['type'];
            
            if (in_array($fileType, $allowedTypes)) {
                $uploadDir = '../uploads/';
                $fileName = time() . '_' . basename($_FILES['profilePicture']['name']);
                $targetPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['profilePicture']['tmp_name'], $targetPath)) {
                    $profilePicturePath = $fileName;
                    
                    // Delete old profile picture if it's not the default and exists
                    if (isset($_SESSION['profile_picture']) && 
                        $_SESSION['profile_picture'] !== '1.png' && 
                        file_exists('../uploads/' . $_SESSION['profile_picture'])) {
                        unlink('../uploads/' . $_SESSION['profile_picture']);
                    }
                } else {
                    $errors[] = "Error uploading profile picture";
                }
            } else {
                $errors[] = "Only JPG, JPEG, PNG, and GIF images are allowed for profile picture";
            }
        }
    }
    
    // Handle document upload
    $documentPath = $_SESSION['document_path'] ?? '';
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
                    mkdir($uploadDir, 0777, true);
                }
                
                $docFileName = time() . '_' . basename($_FILES['document']['name']);
                $docTargetPath = $uploadDir . $docFileName;
                
                if (move_uploaded_file($_FILES['document']['tmp_name'], $docTargetPath)) {
                    $documentPath = 'documents/' . $docFileName;
                    
                    // Delete old document if it exists
                    if (isset($_SESSION['document_path']) && 
                        !empty($_SESSION['document_path']) && 
                        file_exists('../uploads/' . $_SESSION['document_path'])) {
                        unlink('../uploads/' . $_SESSION['document_path']);
                    }
                } else {
                    $errors[] = "Error uploading document";
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
        // First, check the actual column names in the users table
        $checkColumns = "SHOW COLUMNS FROM users";
        $columnsResult = mysqli_query($conn, $checkColumns);
        $columns = [];
        
        if ($columnsResult) {
            while ($column = mysqli_fetch_assoc($columnsResult)) {
                $columns[] = $column['Field'];
            }
        } else {
            $errors[] = "Database error: " . mysqli_error($conn);
        }
        
        // Build update query based on available columns
        $updateFields = [];
        
        if (in_array('last_name', $columns)) {
            $lastName = mysqli_real_escape_string($conn, $_POST['lastName']);
            $updateFields[] = "last_name = '$lastName'";
        }
        
        if (in_array('first_name', $columns)) {
            $firstName = mysqli_real_escape_string($conn, $_POST['firstName']);
            $updateFields[] = "first_name = '$firstName'";
        }
        
        if (in_array('middle_name', $columns)) {
            $middleName = mysqli_real_escape_string($conn, $_POST['middleName']);
            $updateFields[] = "middle_name = '$middleName'";
        }
        
        if (in_array('full_name', $columns)) {
            $fullName = mysqli_real_escape_string($conn, $_POST['lastName'] . ' ' . $_POST['firstName'] . ' ' . $_POST['middleName']);
            $updateFields[] = "full_name = '$fullName'";
        } elseif (in_array('name', $columns)) {
            $fullName = mysqli_real_escape_string($conn, $_POST['lastName'] . ' ' . $_POST['firstName'] . ' ' . $_POST['middleName']);
            $updateFields[] = "name = '$fullName'";
        }
        
        if (in_array('email', $columns)) {
            $email = mysqli_real_escape_string($conn, $_POST['email']);
            $updateFields[] = "email = '$email'";
        }
        
        if (in_array('contact_number', $columns)) {
            $contactNumber = mysqli_real_escape_string($conn, $_POST['contactNumber']);
            $updateFields[] = "contact_number = '$contactNumber'";
        }
        
        if (in_array('department', $columns)) {
            $department = mysqli_real_escape_string($conn, $_POST['department']);
            $updateFields[] = "department = '$department'";
        }
        
        if (in_array('contract_start', $columns)) {
            $contractStart = mysqli_real_escape_string($conn, $_POST['contractStart']);
            $updateFields[] = "contract_start = '$contractStart'";
        }
        
        if (in_array('contract_end', $columns)) {
            // Use the new calculated end date if available, otherwise use the original
            $contractEnd = !empty($newContractEnd) ? $newContractEnd : mysqli_real_escape_string($conn, $_POST['contractEnd']);
            $updateFields[] = "contract_end = '$contractEnd'";
        }
        
        if (in_array('contract_extend', $columns)) {
            $updateFields[] = "contract_extend = '$contractExtend'";
        }
        
        if (in_array('units', $columns)) {
            $units = mysqli_real_escape_string($conn, $_POST['units']);
            $updateFields[] = "units = '$units'";
        }
        
        if (in_array('document_path', $columns)) {
            $updateFields[] = "document_path = '$documentPath'";
        }
        
        if (in_array('profile_picture', $columns)) {
            $updateFields[] = "profile_picture = '$profilePicturePath'";
        }
        
        if (!empty($updateFields)) {
            $updateQuery = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = $userId";
            error_log("Update query: " . $updateQuery);
            
            if (mysqli_query($conn, $updateQuery)) {
                // Update session variables
                $_SESSION['last_name'] = $_POST['lastName'];
                $_SESSION['first_name'] = $_POST['firstName'];
                $_SESSION['middle_name'] = $_POST['middleName'];
                $_SESSION['full_name'] = $_POST['lastName'] . ' ' . $_POST['firstName'] . ' ' . $_POST['middleName'];
                $_SESSION['email'] = $_POST['email'];
                $_SESSION['contact_number'] = $_POST['contactNumber'];
                $_SESSION['department'] = $_POST['department'];
                $_SESSION['contract_start'] = $_POST['contractStart'];
                $_SESSION['contract_end'] = !empty($newContractEnd) ? $newContractEnd : $_POST['contractEnd'];
                $_SESSION['contract_extend'] = $contractExtend;
                $_SESSION['units'] = $_POST['units'];
                $_SESSION['document_path'] = $documentPath;
                $_SESSION['profile_picture'] = $profilePicturePath;
                
                // Update variables for display
                $contractEnd = $_SESSION['contract_end'];
                $contractExtend = $_SESSION['contract_extend'];
                
                // Parse the contract extension for display
                if (!empty($contractExtend)) {
                    $parts = explode(' ', $contractExtend, 2);
                    if (count($parts) === 2) {
                        $extendAmount = $parts[0];
                        $extendPeriod = $parts[1];
                    }
                }
                
                // Update notifications with the new faculty information
                $newFullName = $_POST['lastName'] . ' ' . $_POST['firstName'] . ' ' . $_POST['middleName'];
                
                // Check if faculty_id column exists in notifications table
                $checkNotificationColumns = mysqli_query($conn, "SHOW COLUMNS FROM admindashboard_notification LIKE 'faculty_id'");
                if ($checkNotificationColumns && mysqli_num_rows($checkNotificationColumns) > 0) {
                    // Update notifications that have faculty_id
                    $updateNotificationsQuery = "UPDATE admindashboard_notification SET faculty_name = ? WHERE faculty_id = ?";
                    $updateNotificationsStmt = $conn->prepare($updateNotificationsQuery);
                    
                    if ($updateNotificationsStmt) {
                        $updateNotificationsStmt->bind_param("si", $newFullName, $userId);
                        $updateNotificationsStmt->execute();
                        $updateNotificationsStmt->close();
                    }
                } else {
                    // If faculty_id column doesn't exist, try to match by name
                    $oldFullName = $_SESSION['full_name'] ?? '';
                    if (!empty($oldFullName)) {
                        $updateNotificationsQuery = "UPDATE admindashboard_notification SET faculty_name = ? WHERE faculty_name = ?";
                        $updateNotificationsStmt = $conn->prepare($updateNotificationsQuery);
                        
                        if ($updateNotificationsStmt) {
                            $updateNotificationsStmt->bind_param("ss", $newFullName, $oldFullName);
                            $updateNotificationsStmt->execute();
                            $updateNotificationsStmt->close();
                        }
                    }
                }
                
                $successMessage = "Profile updated successfully!";
                // Refresh profile picture path
                $profilePicture = '../uploads/' . $profilePicturePath;
            } else {
                $errors[] = "Error updating profile: " . mysqli_error($conn);
                error_log("Database error: " . mysqli_error($conn));
            }
        } else {
            $errors[] = "No valid fields to update";
            error_log("No valid fields to update");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>My Account - IPCR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../images/pcc1.png">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
        
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
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
            max-width: 900px;
            margin-right: auto;
            margin-left: auto;
        }
        
        .dark-toggle {
            cursor: pointer;
            font-size: 1.2rem;
            color: white;
            transition: transform 0.2s ease;
        }
        
        .dark-toggle:hover {
            transform: rotate(30deg);
        }
        
        .profile-img-sm {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ccc;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        
        .profile-img-sm:hover {
            transform: scale(1.05);
        }
        
        .pcc-logo {
            width: 40px;
            height: auto;
            border-radius: 10px;
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
        
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            border-radius: 16px;
            background-color: var(--card-light);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
        }
        
        [data-theme='dark'] .profile-header {
            background-color: var(--card-dark);
        }
        
        .profile-info {
            margin-left: 20px;
        }
        
        .profile-info h2 {
            margin-bottom: 5px;
            color: var(--primary-color);
        }
        
        .profile-info p {
            margin-bottom: 0;
            color: #6c757d;
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
        
        .profile-container {
            position: relative;
            display: inline-block;
            margin-bottom: 15px;
        }
        
        .profile-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .profile-img:hover {
            transform: scale(1.05);
        }
        
        .camera-overlay {
            position: absolute;
            bottom: 5px;
            right: 5px;
            width: 36px;
            height: 36px;
            background-color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .camera-overlay:hover {
            transform: scale(1.1);
            background-color: var(--primary-color);
        }
        
        .camera-overlay:hover i {
            color: white;
        }
        
        .camera-overlay i {
            color: #333;
            font-size: 16px;
            transition: color 0.3s ease;
        }
        
        .btn-outline-primary {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
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
        
        .notification-sync {
            margin-top: 10px;
            padding: 10px;
            border-radius: 8px;
            background-color: rgba(40, 167, 69, 0.1);
            display: flex;
            align-items: center;
        }
        
        .notification-sync i {
            margin-right: 8px;
            color: var(--success-color);
        }
        
        /* Modal styles */
        .profile-modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from {opacity: 0;}
            to {opacity: 1;}
        }
        
        .profile-modal-content {
            position: relative;
            margin: 5% auto;
            width: 80%;
            max-width: 500px;
            background-color: var(--card-light);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            animation: slideIn 0.3s;
        }
        
        [data-theme='dark'] .profile-modal-content {
            background-color: var(--card-dark);
        }
        
        @keyframes slideIn {
            from {transform: translateY(-50px); opacity: 0;}
            to {transform: translateY(0); opacity: 1;}
        }
        
        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        [data-theme='dark'] .modal-header {
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .modal-body {
            padding: 20px;
            text-align: center;
        }
        
        .modal-profile-img {
            max-width: 100%;
            max-height: 400px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid rgba(0,0,0,0.1);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        [data-theme='dark'] .modal-footer {
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .close:hover {
            color: var(--danger-color);
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }
            
            .topbar {
                padding: 0 10px;
            }
            
            .profile-img-sm {
                width: 30px;
                height: 30px;
            }
            
            .pcc-logo {
                width: 35px;
            }
            
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-info {
                margin-left: 0;
                margin-top: 15px;
            }
            
            .profile-modal-content {
                width: 95%;
                margin: 10% auto;
            }
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div style="display: flex; align-items: center;">
            <img src="../images/pcc1.png" alt="PCC Logo" class="pcc-logo">
            <div class="ms-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="faculty_dashboard.php" class="text-white">Dashboard</a></li>
                        <li class="breadcrumb-item active text-white">My Account</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="topbar-right">
            <span class="dark-toggle" id="toggleTheme" title="Toggle Dark Mode">
                <i class="fas fa-moon"></i>
            </span>
            <img src="<?= $profilePicture ?>" class="profile-img-sm" alt="Profile">
        </div>
    </div>

    <div class="main-content">
        <div class="card p-4">
            <h4 class="mb-4">Personal Information</h4>
            
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
                    <div class="notification-sync">
                        <i class="fas fa-check-circle"></i>
                        <span>Your profile information has been updated in all notifications as well.</span>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="post" enctype="multipart/form-data">
                <div class="info-section">
                    <div class="section-title">
                        <i class="fas fa-camera"></i> Profile Picture
                    </div>
                    
                    <div class="profile-upload">
                        <div class="profile-container">
                            <img src="<?= $profilePicture ?>" id="previewProfile" alt="Profile" class="profile-img">
                            <div class="camera-overlay" id="cameraIcon">
                                <i class="fas fa-camera"></i>
                            </div>
                        </div>
                        <div>
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
                            <input type="text" class="form-control" id="lastName" name="lastName" value="<?= htmlspecialchars($lastName) ?>" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="firstName" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="firstName" name="firstName" value="<?= htmlspecialchars($firstName) ?>" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="middleName" class="form-label">Middle Name</label>
                            <input type="text" class="form-control" id="middleName" name="middleName" value="<?= htmlspecialchars($middleName) ?>">
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
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="contactNumber" class="form-label">Contact Number</label>
                            <input type="tel" class="form-control" id="contactNumber" name="contactNumber" value="<?= htmlspecialchars($contactNumber) ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="department" class="form-label">Department</label>
                        <select class="form-control" id="department" name="department">
                            <option value="">Select Department</option>
                            <option value="BSIT" <?= $department == 'BSIT' ? 'selected' : '' ?>>BSIT</option>
                            <option value="BSBA" <?= $department == 'BSBA' ? 'selected' : '' ?>>BSBA</option>
                            <option value="BSCRIM" <?= $department == 'BSCRIM' ? 'selected' : '' ?>>BSCRIM</option>
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
                            <input type="date" class="form-control" id="contractStart" name="contractStart" value="<?= htmlspecialchars($contractStart) ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="contractEnd" class="form-label">End of Contract</label>
                            <input type="date" class="form-control" id="contractEnd" name="contractEnd" value="<?= htmlspecialchars($contractEnd) ?>">
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
                            
                            <?php if (!empty($contractEnd) && !empty($extendAmount) && !empty($extendPeriod)): ?>
                                <div class="extension-preview mt-2">
                                    <small class="text-success">Extended contract end date: <span id="newEndDate"></span></small>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="units" class="form-label">Units</label>
                            <input type="number" class="form-control" id="units" name="units" value="<?= htmlspecialchars($units) ?>" min="0">
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
                        
                        <?php if (!empty($_SESSION['document_path'])): ?>
                            <div class="document-preview mt-2">
                                <p class="mb-1">Current Document:</p>
                                <a href="../uploads/<?= htmlspecialchars($_SESSION['document_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-file-alt me-1"></i> View Document
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Profile Picture Modal -->
    <div id="profileModal" class="profile-modal">
        <div class="profile-modal-content">
            <div class="modal-header">
                <h3>Profile Picture</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <img id="modalProfileImg" src="" alt="Profile Picture" class="modal-profile-img">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="closeModal">Close</button>
                <button type="button" class="btn btn-primary" id="changePictureBtn">Change Picture</button>
            </div>
        </div>
    </div>

    <script>
        // Theme toggle
        const toggleTheme = document.getElementById('toggleTheme');
        const htmlTag = document.documentElement;
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            htmlTag.setAttribute('data-theme', savedTheme);
            updateThemeIcon(savedTheme);
        }

        function updateThemeIcon(theme) {
            const icon = toggleTheme.querySelector('i');
            if (theme === 'dark') {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            } else {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            }
        }

        toggleTheme.addEventListener('click', () => {
            const next = htmlTag.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            htmlTag.setAttribute('data-theme', next);
            localStorage.setItem('theme', next);
            updateThemeIcon(next);
        });

        // Profile picture preview and click to change
        const profilePictureInput = document.getElementById('profilePicture');
        const previewProfile = document.getElementById('previewProfile');
        const cameraIcon = document.getElementById('cameraIcon');
        
        // Modal elements
        const profileModal = document.getElementById('profileModal');
        const modalProfileImg = document.getElementById('modalProfileImg');
        const closeModalBtn = document.querySelector('.close');
        const closeModalFooterBtn = document.getElementById('closeModal');
        const changePictureBtn = document.getElementById('changePictureBtn');

        // Function to trigger file input click
        function triggerFileInput() {
            profilePictureInput.click();
        }

        // Function to open modal with profile picture
        function openProfileModal() {
            modalProfileImg.src = previewProfile.src;
            profileModal.style.display = 'block';
        }

        // Function to close modal
        function closeProfileModal() {
            profileModal.style.display = 'none';
        }

        // Add click event to profile image
        if (previewProfile) {
            previewProfile.addEventListener('click', openProfileModal);
        }

        // Add click event to camera icon
        if (cameraIcon) {
            cameraIcon.addEventListener('click', triggerFileInput);
        }

        // Add click event to close modal buttons
        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', closeProfileModal);
        }

        if (closeModalFooterBtn) {
            closeModalFooterBtn.addEventListener('click', closeProfileModal);
        }

        // Add click event to change picture button in modal
        if (changePictureBtn) {
            changePictureBtn.addEventListener('click', function() {
                closeProfileModal();
                triggerFileInput();
            });
        }

        // Close modal when clicking outside of it
        window.addEventListener('click', function(event) {
            if (event.target === profileModal) {
                closeProfileModal();
            }
        });

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