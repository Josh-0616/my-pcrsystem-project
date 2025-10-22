<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Database connection
require_once '../db/connection.php';

// Debug function to log information
function debug_log($message) {
    file_put_contents('debug.log', date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

// Get admin data from database
 $username = $_SESSION['username'];

// Check if connection is successful
if (!$conn) {
    error_log("Database connection failed: " . mysqli_connect_error());
    die("Database connection error. Please try again later.");
}

// Check if the users table exists
 $tableCheck = "SHOW TABLES LIKE 'users'";
 $tableResult = mysqli_query($conn, $tableCheck);
if (!$tableResult || mysqli_num_rows($tableResult) == 0) {
    error_log("The 'users' table does not exist in the database.");
    die("System configuration error. Please contact administrator.");
}

// Get all columns in the users table
 $columnsQuery = "SHOW COLUMNS FROM users";
 $columnsResult = mysqli_query($conn, $columnsQuery);

if (!$columnsResult) {
    error_log("Error retrieving table columns: " . mysqli_error($conn));
    die("System error. Please try again later.");
}

 $existingColumns = [];
while ($column = mysqli_fetch_assoc($columnsResult)) {
    $existingColumns[] = $column['Field'];
}

// Log the columns we found for debugging
debug_log("Columns found in users table: " . implode(', ', $existingColumns));

// Determine the correct column name for username
 $usernameColumn = null;
 $alternatives = ['username', 'user_name', 'user', 'login', 'user_id', 'admin_name', 'name'];

foreach ($alternatives as $alt) {
    if (in_array($alt, $existingColumns)) {
        $usernameColumn = $alt;
        break;
    }
}

if ($usernameColumn === null) {
    error_log("No valid username column found. Available columns: " . implode(', ', $existingColumns));
    die("System configuration error. Please contact administrator.");
}

// Log the detected username column
debug_log("Using username column: " . $usernameColumn);

// Now build the query with the correct column name using prepared statement
 $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE $usernameColumn = ? AND role = 'admin' LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
 $result = mysqli_stmt_get_result($stmt);

if (!$result) {
    error_log("Query failed: " . mysqli_error($conn));
    die("System error. Please try again later.");
}

if (mysqli_num_rows($result) === 0) {
    // If admin not found, redirect to login
    header("Location: ../login.php");
    exit();
}

 $admin = mysqli_fetch_assoc($result);

// Get profile picture with proper default handling
 $profilePicture = '../uploads/1.png'; // Default profile picture
if (!empty($admin['profile_picture'])) {
    $profilePicture = '../uploads/' . htmlspecialchars($admin['profile_picture']);
}

// Process form submission
 $errors = [];
 $successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    $profilePicturePath = $admin['profile_picture'] ?? '1.png';
    $newProfilePicture = false; // Flag to track if profile picture was updated
    
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
                    $newProfilePicture = true;
                    
                    // Delete old profile picture if it's not the default and exists
                    if (isset($admin['profile_picture']) && 
                        $admin['profile_picture'] !== '1.png' && 
                        file_exists('../uploads/' . $admin['profile_picture'])) {
                        unlink('../uploads/' . $admin['profile_picture']);
                    }
                } else {
                    $errors[] = "Error uploading profile picture";
                }
            } else {
                $errors[] = "Only JPG, JPEG, PNG, and GIF images are allowed for profile picture";
            }
        }
    }
    
    // If no errors, update the database
    if (empty($errors)) {
        // Build update query based on available columns
        $updateFields = [];
        $params = [];
        $types = "";
        
        // Check if name columns exist before adding to update
        if (in_array('last_name', $existingColumns) && isset($_POST['lastName'])) {
            $updateFields[] = "last_name = ?";
            $params[] = $_POST['lastName'];
            $types .= "s";
        }
        
        if (in_array('first_name', $existingColumns) && isset($_POST['firstName'])) {
            $updateFields[] = "first_name = ?";
            $params[] = $_POST['firstName'];
            $types .= "s";
        }
        
        if (in_array('middle_name', $existingColumns) && isset($_POST['middleName'])) {
            $updateFields[] = "middle_name = ?";
            $params[] = $_POST['middleName'];
            $types .= "s";
        }
        
        // If we have separate name fields but also have full_name, update it too
        if (in_array('full_name', $existingColumns) && isset($_POST['lastName']) && isset($_POST['firstName'])) {
            $fullName = trim($_POST['lastName'] . ' ' . $_POST['firstName'] . ' ' . $_POST['middleName']);
            $fullName = preg_replace('/\s+/', ' ', $fullName); // Remove extra spaces
            $updateFields[] = "full_name = ?";
            $params[] = $fullName;
            $types .= "s";
        }
        
        // Check if email column exists before adding to update
        if (in_array('email', $existingColumns) && isset($_POST['email'])) {
            $updateFields[] = "email = ?";
            $params[] = $_POST['email'];
            $types .= "s";
        }
        
        // Check if contact_number column exists before adding to update
        if (in_array('contact_number', $existingColumns) && isset($_POST['contactNumber'])) {
            $updateFields[] = "contact_number = ?";
            $params[] = $_POST['contactNumber'];
            $types .= "s";
        }
        
        // Include profile picture in update if it was changed and column exists
        if ($newProfilePicture && in_array('profile_picture', $existingColumns)) {
            $updateFields[] = "profile_picture = ?";
            $params[] = $profilePicturePath;
            $types .= "s";
        }
        
        if (!empty($updateFields)) {
            // Add username parameter
            $params[] = $username;
            $types .= "s";
            
            // Use the correct username column in the UPDATE query with prepared statement
            $updateQuery = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE $usernameColumn = ?";
            debug_log("Update query: " . $updateQuery);
            
            $stmt = mysqli_prepare($conn, $updateQuery);
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            
            if (mysqli_stmt_execute($stmt)) {
                // Update session variables
                if (in_array('last_name', $existingColumns)) {
                    $_SESSION['last_name'] = $_POST['lastName'];
                }
                if (in_array('first_name', $existingColumns)) {
                    $_SESSION['first_name'] = $_POST['firstName'];
                }
                if (in_array('middle_name', $existingColumns)) {
                    $_SESSION['middle_name'] = $_POST['middleName'];
                }
                if (in_array('full_name', $existingColumns)) {
                    $fullName = trim($_POST['lastName'] . ' ' . $_POST['firstName'] . ' ' . $_POST['middleName']);
                    $fullName = preg_replace('/\s+/', ' ', $fullName);
                    $_SESSION['full_name'] = $fullName;
                }
                if (in_array('email', $existingColumns)) {
                    $_SESSION['email'] = $_POST['email'];
                }
                
                // Update profile picture in session if it was changed
                if ($newProfilePicture && in_array('profile_picture', $existingColumns)) {
                    $_SESSION['profile_picture'] = $profilePicturePath;
                    $profilePicture = '../uploads/' . $profilePicturePath;
                }
                
                // Refresh admin data for display
                if (in_array('last_name', $existingColumns)) {
                    $admin['last_name'] = $_POST['lastName'];
                }
                if (in_array('first_name', $existingColumns)) {
                    $admin['first_name'] = $_POST['firstName'];
                }
                if (in_array('middle_name', $existingColumns)) {
                    $admin['middle_name'] = $_POST['middleName'];
                }
                if (in_array('full_name', $existingColumns)) {
                    $fullName = trim($_POST['lastName'] . ' ' . $_POST['firstName'] . ' ' . $_POST['middleName']);
                    $fullName = preg_replace('/\s+/', ' ', $fullName);
                    $admin['full_name'] = $fullName;
                }
                if (in_array('email', $existingColumns)) {
                    $admin['email'] = $_POST['email'];
                }
                if (in_array('contact_number', $existingColumns)) {
                    $admin['contact_number'] = $_POST['contactNumber'];
                }
                if (in_array('profile_picture', $existingColumns)) {
                    $admin['profile_picture'] = $profilePicturePath;
                }
                
                $successMessage = "Profile updated successfully!";
            } else {
                error_log("Error updating profile: " . mysqli_error($conn));
                $errors[] = "Error updating profile. Please try again.";
            }
        } else {
            $errors[] = "No valid fields to update";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>My Account - PCR Admin</title>
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
            max-width: 800px;
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
        <div style="display: flex; align-items: center;">
            <div class="ms-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="admin_dashboard.php" class="text-white">Dashboard</a></li>
                        <li class="breadcrumb-item active text-white">My Account</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="card p-4">
            <h4 class="mb-4">My Account</h4>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($successMessage)): ?>
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
                            <input type="text" class="form-control" id="lastName" name="lastName" value="<?= htmlspecialchars($admin['last_name'] ?? '') ?>" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="firstName" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="firstName" name="firstName" value="<?= htmlspecialchars($admin['first_name'] ?? '') ?>" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="middleName" class="form-label">Middle Name</label>
                            <input type="text" class="form-control" id="middleName" name="middleName" value="<?= htmlspecialchars($admin['middle_name'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                
                <div class="info-section">
                    <div class="section-title">
                        <i class="fas fa-envelope"></i> Contact Information
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($admin['email'] ?? '') ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="contactNumber" class="form-label">Contact Number</label>
                        <input type="tel" class="form-control" id="contactNumber" name="contactNumber" value="<?= htmlspecialchars($admin['contact_number'] ?? '') ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" value="<?= htmlspecialchars($admin[$usernameColumn]) ?>" readonly>
                        <small class="text-muted">Username cannot be changed</small>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                    <a href="admin_dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Save Changes
                    </button>
                </div>
            </form>
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
        // Initialize theme from localStorage
        const htmlTag = document.documentElement;
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            htmlTag.setAttribute('data-theme', savedTheme);
        }

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
    </script>
</body>
</html>